<?php

	function pay_balance_payza() {
		myadmin_log('billing', 'info', 'Pay with Payza Called', __LINE__, __FILE__);
		page_title('Pay Balance With Payza');
		$table = new TFTable;
		if ($GLOBALS['tf']->ima == 'admin')
			$custid = $GLOBALS['tf']->db->real_escape($GLOBALS['tf']->variables->request['custid']);
		else
			$custid = $GLOBALS['tf']->session->account_id;
		$table = new TFTable;
		$module = 'default';
		if (isset($GLOBALS['tf']->variables->request['module']))
			$module = $GLOBALS['tf']->variables->request['module'];
		$module = get_module_name($module);
		$settings = \get_module_settings($module);
		$custid = get_custid($custid, $module);
		$table->add_hidden('module', $module);
		$db = get_module_db($module);
		$GLOBALS['tf']->accounts->set_db_module($module);
		$GLOBALS['tf']->history->set_db_module($module);
		$data = $GLOBALS['tf']->accounts->read($custid);
		$table->set_title('Make ' . $settings['TBLNAME'] . ' Payza Payment');
		$table->add_hidden('custid', $custid);
		$table->add_field('Invoice Description');
		$table->add_field('Invoice Amount');
		$table->add_row();
		$invoices = [];
		if (isset($GLOBALS['tf']->variables->request['invoices'])) {
			$GLOBALS['tf']->variables->request['invoices'] = $db->real_escape(str_replace('INV' . $module, '', $GLOBALS['tf']->variables->request['invoices']));
			$table->add_hidden('invoices', $GLOBALS['tf']->variables->request['invoices']);
			$query = "select * from invoices where invoices_module='{$module}' and invoices_paid=0 and invoices_type=1 and invoices_custid='{$custid}' and invoices_id in ('" . implode("','", explode(',', $GLOBALS['tf']->variables->request['invoices'])) . "') order by invoices_id desc";
			myadmin_log('billing', 'info', $query, __LINE__, __FILE__);
			$db->query($query, __LINE__, __FILE__);
		} else {
			$query = "select * from invoices where invoices_module='{$module}' and invoices_paid=0 and invoices_type=1 and invoices_custid='{$custid}' order by invoices_id desc";
			$db->query($query, __LINE__, __FILE__);
		}
		$randstring = _randomstring(30);
		$output = '<form method="post" action="https://secure.payza.com/checkout" >
<input type="hidden" name="apc_1" value="' . $randstring . '"/>
<input type="hidden" name="ap_purchasetype" value="service"/>
<input type="hidden" name="ap_merchant" value="mike@interserver.net"/>
<input type="hidden" name="ap_currency" value="USD"/>';
		$gidx = 0;
		$iids = [];
		$returnURL = 'https://' . DOMAIN . URLDIR . $GLOBALS['tf']->link('/index.php', 'choice=none.view_balance');
		$amount = 0;
		while ($db->next_record(MYSQL_ASSOC)) {
			$amount = bcadd($amount, $db->Record['invoices_amount'], 2);
			$invoices[] = $db->Record['invoices_id'];
			$iids[] = 'INV' . $module . $db->Record['invoices_id'];
			$table->add_field($db->Record['invoices_description']);
			$table->add_field('$' . $db->Record['invoices_amount'], 'r');
			$table->add_row();

			++$gidx;
			$output .= '
<input type="hidden" name="ap_itemname_' . $gidx . '" value="' . $db->Record['invoices_description'] . '"/>
<input type="hidden" name="ap_quantity_' . $gidx . '" value="' . 1 . '"/>
<input type="hidden" name="ap_amount_' . $gidx . '" value="' . $db->Record['invoices_amount'] . '"/>';
		}
		$table->add_hidden('balance', $amount);
		$table->add_field('<b>Total Amount To Pay</b>', 'r');
		$table->add_field('<b>$' . $amount . '</b>', 'r');
		$table->add_row();
		$output .= '
<input type="hidden" name="ap_returnurl" value="' . 'https://my.interserver.net/index.php?choice=none.return_url&payza_token=' . $randstring . '&url=' . urlencode(base64_encode($returnURL)) . '"/>
<input type="hidden" name="ap_cancelurl" value="' . 'https://my.interserver.net/index.php?choice=none.return_url&url=' . urlencode(base64_encode($returnURL)) . '"/>
<input type="image" name="ap_image" src="https://www.payza.com/images/payza-buy-now.png" style="border: none;"/>
</form>';

		add_output($table->get_table());
		add_output($output);
	}
