---
name: add-plugin-setting
description: Adds a new setting to the Payza plugin via getSettings() in src/Plugin.php. Use when user says 'add setting', 'new config option', 'register setting'. Covers add_radio_setting and add_text_setting patterns with _() i18n wrappers and defined() constant guards. Do NOT use for non-setting plugin changes.
---
# add-plugin-setting

## Critical

- All label/description strings MUST be wrapped in `_()`  — never pass raw strings to setting methods.
- Text settings MUST guard the constant with `defined('CONSTANT_NAME') ? CONSTANT_NAME : ''` — radio settings reference the constant directly (it is always defined by the time settings fire).
- Constant names follow `PAYZA_<UPPER_SNAKE>` convention matching the `payza_<snake>` field key.
- Never add PDO, raw SQL, or any DB calls inside `getSettings()`.

## Instructions

1. **Identify setting type.** Determine whether the new setting is a radio (boolean enable/disable toggle) or a free-form text value.
   - Verify the constant name you will use (e.g. `PAYZA_FOO`) is not already registered in `src/Plugin.php`.

2. **Open `src/Plugin.php`** and locate the `getSettings()` method (line ~66). All additions go inside this method, after the existing `add_radio_setting` / `add_text_setting` calls.

3. **For a radio (toggle) setting**, append:
   ```php
   $settings->add_radio_setting(_('Billing'), _('Payza'), 'payza_<key>', _('<Label>'), _('<Description>'), PAYZA_<KEY>, [true, false], ['Enabled', 'Disabled']);
   ```
   Signature: `add_radio_setting(group, subgroup, field_key, label, description, current_value, values[], labels[])`

4. **For a text setting**, append:
   ```php
   $settings->add_text_setting(_('Billing'), _('Payza'), 'payza_<key>', _('<Label>'), _('<Description>'), (defined('PAYZA_<KEY>') ? PAYZA_<KEY> : ''));
   ```
   Signature: `add_text_setting(group, subgroup, field_key, label, description, current_value)`

5. **Verify** the method now has one call per setting and no syntax errors:
   ```bash
   vendor/bin/phpunit tests/ -v
   ```

## Examples

**User says:** "Add a text setting for the Payza merchant ID"

**Actions taken:**
- Field key: `payza_merchant_id` → constant: `PAYZA_MERCHANT_ID`
- Append inside `getSettings()` in `src/Plugin.php`:
  ```php
  $settings->add_text_setting(_('Billing'), _('Payza'), 'payza_merchant_id', _('Merchant ID'), _('Merchant ID'), (defined('PAYZA_MERCHANT_ID') ? PAYZA_MERCHANT_ID : ''));
  ```

**Result:** `src/Plugin.php` `getSettings()` contains:
```php
$settings->add_radio_setting(_('Billing'), _('Payza'), 'payza_enable', _('Enable Payza'), _('Enable Payza'), PAYZA_ENABLE, [true, false], ['Enabled', 'Disabled']);
$settings->add_text_setting(_('Billing'), _('Payza'), 'payza_email', _('Email'), _('Email'), (defined('PAYZA_EMAIL') ? PAYZA_EMAIL : ''));
$settings->add_text_setting(_('Billing'), _('Payza'), 'payza_merchant_id', _('Merchant ID'), _('Merchant ID'), (defined('PAYZA_MERCHANT_ID') ? PAYZA_MERCHANT_ID : ''));
```
Tests pass with `vendor/bin/phpunit tests/ -v`.

## Common Issues

- **`PHP Notice: Use of undefined constant PAYZA_<KEY>`** — You used the constant directly in a text setting without a `defined()` guard. Wrap it: `(defined('PAYZA_<KEY>') ? PAYZA_<KEY> : '')`.
- **Strings not translatable / i18n audit failure** — A label or description was passed as a bare PHP string. Every string argument to `add_radio_setting` / `add_text_setting` must be wrapped in `_()`.
- **Tests fail with `Call to undefined method`** — `$settings` is a `\MyAdmin\Settings` object injected via `$event->getSubject()`; do not instantiate it manually. Confirm you are inside `getSettings(GenericEvent $event)` and using `$settings = $event->getSubject();`.
- **Setting not appearing in UI** — The field key must be unique and use the `payza_` prefix. Duplicate keys silently overwrite previous registrations.
