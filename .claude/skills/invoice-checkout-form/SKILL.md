---
name: invoice-checkout-form
description: Generates or modifies the Payza checkout HTML form in `src/pay_balance_payza.php`. Use when user says 'update checkout', 'add form field', 'modify payment form', 'change IPN token', or 'add invoice field'. Covers `bcadd()` totals, `_randomstring()` IPN token, invoice DB query via `get_module_db()`, and `ap_itemname_N`/`ap_amount_N` hidden field generation. Do NOT use for Plugin.php hook registration or PHPUnit test changes.
---
# Invoice Checkout Form

## Critical

- **Never interpolate raw `$_GET`/`$_POST`** into queries. Sanitize invoice IDs with `array_map('intval', ...)` + `array_filter($ids, function($v) { return $v > 0; })` before use in SQL.
- **Always use `bcadd()` with scale=2** for accumulating money: `$amount = bcadd($amount, $db->Record['invoices_amount'], 2);` — never use `+=` or `array_sum()` on currency.
- **IPN token must be 30 chars**: `$randstring = _randomstring(30);` — stored in `apc_1` hidden field and appended to `ap_returnurl`.
- **Always pass `__LINE__, __FILE__`** to every `$db->query()` and every `myadmin_log()` call.
- Wrap all user-visible strings in `_('...')` for gettext i18n.

## Instructions

1. **Resolve customer ID** — distinguish admin vs. customer context before any DB access:
   ```php
   if ($GLOBALS['tf']->ima == 'admin') {
       $custid = $GLOBALS['tf']->db->real_escape($GLOBALS['tf']->variables->request['custid']);
   } else {
       $custid = $GLOBALS['tf']->session->account_id;
   }
   ```
   Verify `$custid` is set before proceeding to Step 2.

2. **Bootstrap module + DB handle**:
   ```php
   $module = isset($GLOBALS['tf']->variables->request['module'])
       ? $GLOBALS['tf']->variables->request['module'] : 'default';
   $module   = get_module_name($module);
   $settings = \get_module_settings($module);
   $custid   = get_custid($custid, $module);
   $db       = get_module_db($module);
   ```
   Confirm `$db` is a valid DB handle (non-null) before running queries.

3. **Sanitize invoice IDs and build query** — if `invoices` param is present, strip the `INV{module}` prefix and filter:
   ```php
   $raw_invoices = str_replace('INV'.$module, '', $GLOBALS['tf']->variables->request['invoices']);
   $invoice_ids  = array_map('intval', explode(',', $raw_invoices));
   $invoice_ids  = array_filter($invoice_ids, function($v) { return $v > 0; });
   $query = "select * from invoices where invoices_module='{$module}' and invoices_paid=0
       and invoices_type=1 and invoices_custid='{$custid}'
       and invoices_id in ('" . implode("','", $invoice_ids) . "') order by invoices_id desc";
   ```
   If no `invoices` param, omit the `in (...)` clause. Always log the query:
   ```php
   myadmin_log('billing', 'info', $query, __LINE__, __FILE__);
   $db->query($query, __LINE__, __FILE__);
   ```

4. **Generate IPN token + open form**:
   ```php
   $randstring = _randomstring(30);
   $output = '<form method="post" action="https://secure.payza.com/checkout" >
   <input type="hidden" name="apc_1" value="' . $randstring . '"/>
   <input type="hidden" name="ap_purchasetype" value="service"/>
   <input type="hidden" name="ap_merchant" value="mike@interserver.net"/>
   <input type="hidden" name="ap_currency" value="USD"/>';
   $gidx = 0;
   $amount = 0;
   ```

5. **Iterate invoices — accumulate total + emit per-item hidden fields**:
   ```php
   while ($db->next_record(MYSQL_ASSOC)) {
       $amount = bcadd($amount, $db->Record['invoices_amount'], 2);
       ++$gidx;
       $output .= '
   <input type="hidden" name="ap_itemname_' . $gidx . '" value="' . $db->Record['invoices_description'] . '"/>
   <input type="hidden" name="ap_quantity_'  . $gidx . '" value="1"/>
   <input type="hidden" name="ap_amount_'    . $gidx . '" value="' . $db->Record['invoices_amount'] . '"/>';
   }
   ```
   To add a new per-item field (e.g. `ap_taxamount_N`), add another `$output .=` line inside this loop using the same `$gidx` counter.

6. **Close form with return/cancel URLs and submit button**:
   ```php
   $returnURL = 'https://'.DOMAIN.URLDIR.$GLOBALS['tf']->link('/index.php', 'choice=none.view_balance');
   $output .= '
   <input type="hidden" name="ap_returnurl" value="https://my.interserver.net/index.php?choice=none.return_url&payza_token=' . $randstring . '&url=' . urlencode(base64_encode($returnURL)) . '"/>
   <input type="hidden" name="ap_cancelurl" value="https://my.interserver.net/index.php?choice=none.return_url&url=' . urlencode(base64_encode($returnURL)) . '"/>
   <input type="image" name="ap_image" src="https://www.payza.com/images/payza-buy-now.png" style="border: none;"/>
   </form>';
   add_output($table->get_table());
   add_output($output);
   ```

## Examples

**User says:** "Add a tax amount field to each invoice line in the checkout form."

**Actions taken:**
1. Open `src/pay_balance_payza.php`.
2. Inside the `while ($db->next_record(MYSQL_ASSOC))` loop, after the existing `ap_amount_N` line, add:
   ```php
   <input type="hidden" name="ap_taxamount_' . $gidx . '" value="' . $db->Record['invoices_tax'] . '"/>
   ```
3. No changes needed outside the loop — `$gidx` is already incremented per invoice.

**Result:** Each invoice item now emits `ap_itemname_N`, `ap_quantity_N`, `ap_amount_N`, and `ap_taxamount_N`.

## Common Issues

- **`bcadd(): Argument must be of type string`** — `$amount` initial value must be `0` (int is accepted) but intermediate values from `$db->Record` must be strings. Ensure the column is `VARCHAR`/`DECIMAL` in MySQL, not cast to float in PHP before passing to `bcadd()`.
- **Invoice IDs not filtering correctly** — If you see unexpected invoices, confirm the `str_replace('INV'.$module, '', ...)` strip runs before `array_map('intval', ...)`. Module name changes break the prefix strip.
- **`_randomstring` undefined** — This is a MyAdmin core helper. If missing in test context, stub it in `tests/bootstrap.php` as `function _randomstring($n) { return str_repeat('x', $n); }`.
- **`DOMAIN` or `URLDIR` undefined** — These constants are set by MyAdmin core bootstrap. In isolated tests they must be `define()`d in `tests/bootstrap.php` before the file under test is included.
- **Form posts to wrong endpoint** — The Payza sandbox URL differs from production. Verify `action` is `https://secure.payza.com/checkout` for production; use `https://sandbox.payza.com/checkout` for testing.