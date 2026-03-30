# MyAdmin Payza Payments Plugin

Payza (formerly AlertPay) payment gateway plugin for the [MyAdmin](https://github.com/detain/myadmin) billing platform. Handles checkout form generation, invoice processing, and IPN callbacks.

## Commands

```bash
composer install                        # install deps
vendor/bin/phpunit                      # run tests
vendor/bin/phpunit tests/ -v            # run tests verbose
```

```bash
# Coverage report (mirrors CI step in .github/ workflows)
vendor/bin/phpunit --coverage-clover coverage.xml --whitelist src/ tests/ -v
```

```bash
# Static analysis — inspection rules defined in .idea/inspectionProfiles/
php -l src/Plugin.php src/pay_balance_payza.php
```

## Architecture

- **Namespace**: `Detain\MyAdminPayza\` → `src/`
- **Test namespace**: `Detain\MyAdminPayza\Tests\` → `tests/`
- **Entry**: `src/Plugin.php` · **Checkout form**: `src/pay_balance_payza.php`
- **Tests**: `tests/PluginTest.php` · bootstrap: `tests/bootstrap.php`
- **CI/CD**: `.github/` workflows run PHPUnit and coverage checks on push · `.idea/` stores IDE inspection profiles (`inspectionProfiles/`), deployment configuration (`deployment.xml`), and encoding settings (`encodings.xml`)

**Plugin registration** (`src/Plugin.php`):
- `getHooks()` returns hook map: `system.settings` → `getSettings`, `function.requirements` → `getRequirements`
- `getRequirements()` calls `$loader->add_page_requirement('pay_balance_payza', '/../vendor/detain/myadmin-payza-payments/src/pay_balance_payza.php')`
- `getSettings()` registers `payza_enable` (radio) and `payza_email` (text) under `_('Billing')` / `_('Payza')`

**Checkout logic** (`src/pay_balance_payza.php`):
- Queries `invoices` table via `get_module_db($module)` using `$db->query()` / `$db->next_record(MYSQL_ASSOC)`
- Accumulates totals with `bcadd($amount, $db->Record['invoices_amount'], 2)`
- Generates IPN token via `_randomstring(30)` stored as `apc_1`
- Posts to `https://secure.payza.com/checkout` with `ap_itemname_N`, `ap_amount_N`, `ap_quantity_N` fields

## Conventions

- All i18n strings wrapped in `_('string')` (gettext)
- Logging: `myadmin_log('billing', 'info', $message, __LINE__, __FILE__)`
- DB queries use `$db->real_escape()` on user input; never raw `$_GET`/`$_POST` interpolation
- Invoice IDs sanitized: `array_map('intval', ...)` + `array_filter(... > 0)`
- Constants referenced: `PAYZA_ENABLE`, `PAYZA_EMAIL`, `DOMAIN`, `URLDIR`
- Coding style: tabs for indentation, camelCase properties/parameters (enforced by `.scrutinizer.yml`)

## Testing

- PHPUnit 9.6 — config in `phpunit.xml.dist`
- Bootstrap stubs `_()` translation function in `tests/bootstrap.php`
- Tests live in `tests/PluginTest.php` under namespace `Detain\MyAdminPayza\Tests\`
- CI: `.scrutinizer.yml` runs `vendor/bin/phpunit tests/ -v --coverage-clover coverage.xml --whitelist src/`

## Dependencies

- PHP `>=7.4` · `ext-soap` · `ext-bcmath`
- `symfony/event-dispatcher` `^5.0||^6.0||^7.0`
- `detain/myadmin-plugin-installer` (type: `myadmin-plugin`)

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
