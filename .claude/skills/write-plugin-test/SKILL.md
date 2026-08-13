---
name: write-plugin-test
description: Creates PHPUnit 9.6 tests in `tests/PluginTest.php` under `Detain\MyAdminPayza\Tests\`. Use when user says 'add test', 'write unit test', 'test plugin method', 'test getHooks', 'test getSettings', or 'test getRequirements'. Covers bootstrap stub patterns, ReflectionClass-based structural checks, anonymous-class event subjects, and source-file assertion patterns. Do NOT use for integration tests requiring a live DB or full MyAdmin bootstrap. NOTE: for a plugin's contract/behavioral tests (tests/ContractTest.php, the shared harness, composer myadmin:scaffold-tests) use the plugin-contract-tests skill instead — this skill's reflection-only guidance predates that harness.
---
<!-- myadmin-contract-harness-notice -->
> ### ⚠️ Read this before the rest of the file
>
> This package is on the **shared plugin contract harness**. Parts of the guidance below
> predate it and are now wrong in one specific way:
>
> **Any instruction here that a plugin's `getHooks()` / `getSettings()` / `getActivate()` /
> `getDeactivate()` / `getQueue()` must not be *called* — that only its existence, visibility
> or parameter count may be checked through `ReflectionClass` — no longer applies.** That rule
> existed because those methods reference bare constants (`PRORATE_BILLING` and friends) that
> only a live MyAdmin request defines, so calling them from a test used to fatal. The harness
> defines them first. It then executes the handlers for real, in a process of its own.
>
> A reflection-only assertion passes whether or not the thing works: `getActivate()` can exist,
> be public, be static, take one argument, and still fatal the moment it runs. Three real
> production bugs in this fleet were sitting behind assertions of exactly that shape.
>
> **Use the `plugin-contract-tests` skill** for anything touching `tests/ContractTest.php`,
> the contract inspectors, or `composer myadmin:scaffold-tests`.
>
> **Everything else in this file is still accurate and still applies** — this package's own
> classes, its API wrappers, its fixtures, its bootstrap, and the reasons certain classes must
> not be constructed. Nothing below has been removed.

# write-plugin-test

## Critical

- **Never** require a live DB or full MyAdmin bootstrap — stubs replace every external dependency.
- All tests MUST be in `tests/PluginTest.php` under namespace `Detain\MyAdminPayza\Tests\`.
- PHPUnit version is **9.6** — use `TestCase`, not deprecated `PHPUnit_Framework_TestCase`.
- `declare(strict_types=1)` is required at the top of every test file.
- Do NOT mock vendor classes (`MyAdmin\Settings`, `MyAdmin\Plugins\Loader`) — use anonymous classes instead (see §Examples).
- Define constants (`PAYZA_ENABLE`, `PAYZA_EMAIL`) inside individual tests with `if (!defined(...))` guards, not globally.

## Instructions

1. **Verify bootstrap stubs** — `tests/bootstrap.php` must load the autoloader and stub `_()` if absent:
   ```php
   require dirname(__DIR__) . '/vendor/autoload.php';
   if (!function_exists('_')) {
       function _(string $message): string { return $message; }
   }
   ```
   Verify `phpunit.xml.dist` points `bootstrap="tests/bootstrap.php"`. Do not proceed until this is confirmed.

2. **Declare the test class** — open `tests/PluginTest.php` with exact header:
   ```php
   <?php
   declare(strict_types=1);
   namespace Detain\MyAdminPayza\Tests;
   use Detain\MyAdminPayza\Plugin;
   use PHPUnit\Framework\TestCase;
   use ReflectionClass;
   use Symfony\Component\EventDispatcher\GenericEvent;
   class PluginTest extends TestCase { ... }
   ```

3. **Add `setUp()` with ReflectionClass** — store reflection once, reuse across structural tests:
   ```php
   private ReflectionClass $reflection;
   protected function setUp(): void {
       $this->reflection = new ReflectionClass(Plugin::class);
   }
   ```

4. **Write class-structure tests** — test existence, instantiation, namespace, and zero-arg constructor:
   - `assertTrue(class_exists(Plugin::class))`
   - `assertInstanceOf(Plugin::class, new Plugin())`
   - `assertSame(0, $this->reflection->getConstructor()->getNumberOfRequiredParameters())`
   - `assertSame('Detain\\MyAdminPayza', $this->reflection->getNamespaceName())`

5. **Write static-property tests** — assert `$name`, `$description`, `$type`, `$help` are public, static, and have expected values:
   ```php
   $prop = $this->reflection->getProperty('name');
   $this->assertTrue($prop->isStatic() && $prop->isPublic());
   $this->assertSame('Payza Plugin', Plugin::$name);
   $this->assertSame('plugin', Plugin::$type);
   ```

6. **Write `getHooks()` tests** — assert return type, count (2), and each key maps to `[Plugin::class, 'methodName']`:
   ```php
   $hooks = Plugin::getHooks();
   $this->assertCount(2, $hooks);
   $this->assertSame([Plugin::class, 'getSettings'], $hooks['system.settings']);
   $this->assertSame([Plugin::class, 'getRequirements'], $hooks['function.requirements']);
   ```
   Also verify all referenced methods exist via `method_exists($class, $method)`.

7. **Write event-handler signature tests** — use ReflectionMethod for `getSettings`, `getRequirements`, `getMenu`:
   ```php
   $method = $this->reflection->getMethod('getSettings');
   $this->assertTrue($method->isStatic() && $method->isPublic());
   $params = $method->getParameters();
   $this->assertCount(1, $params);
   $this->assertSame('event', $params[0]->getName());
   $this->assertSame(GenericEvent::class, $params[0]->getType()->getName());
   ```

8. **Write `getRequirements` behavior test** — use an anonymous class as the loader subject:
   ```php
   $loader = new class {
       public array $requirements = [];
       public function add_page_requirement(string $name, string $path): void {
           $this->requirements[] = [$name, $path];
       }
   };
   Plugin::getRequirements(new GenericEvent($loader));
   $this->assertSame('pay_balance_payza', $loader->requirements[0][0]);
   $this->assertStringContainsString('myadmin-payza-payments', $loader->requirements[0][1]);
   ```

9. **Write `getSettings` behavior test** — define constants with guards, use an anonymous settings class:
   ```php
   if (!defined('PAYZA_ENABLE')) define('PAYZA_ENABLE', true);
   if (!defined('PAYZA_EMAIL')) define('PAYZA_EMAIL', 'test@example.com');
   $settings = new class {
       public array $radios = [], $texts = [];
       public function add_radio_setting(...$args): void { $this->radios[] = $args; }
       public function add_text_setting(...$args): void { $this->texts[] = $args; }
   };
   Plugin::getSettings(new GenericEvent($settings));
   $this->assertSame('payza_enable', $settings->radios[0][2]);
   $this->assertSame('payza_email', $settings->texts[0][2]);
   ```

10. **Write source-file assertion tests** — validate `src/pay_balance_payza.php` content without executing it:
    ```php
    $src = file_get_contents(dirname(__DIR__) . '/src/pay_balance_payza.php');
    $this->assertStringContainsString('function pay_balance_payza()', $src);
    $this->assertStringContainsString('bcadd(', $src);           // financial math
    $this->assertStringContainsString('real_escape', $src);      // SQL safety
    $this->assertStringContainsString("myadmin_log('billing'", $src); // audit log
    $this->assertStringContainsString('https://secure.payza.com/checkout', $src);
    $this->assertStringContainsString('invoices_paid=0', $src);  // unpaid filter
    ```

11. **Run tests** to confirm all pass:
    ```bash
    vendor/bin/phpunit tests/ -v
    ```
    Verify zero failures and zero errors before committing.

## Examples

**User says:** "Add a test that verifies getRequirements registers the pay_balance_payza page"

**Actions taken:**
1. Open `tests/PluginTest.php`.
2. Add `testGetRequirementsCallsAddPageRequirement()` under the `// ─── getRequirements Behavior` section.
3. Build an anonymous loader class, fire `Plugin::getRequirements(new GenericEvent($loader))`, assert name and path.

**Result:**
```php
public function testGetRequirementsCallsAddPageRequirement(): void
{
    $loader = new class {
        public array $requirements = [];
        public function add_page_requirement(string $name, string $path): void {
            $this->requirements[] = [$name, $path];
        }
    };
    Plugin::getRequirements(new GenericEvent($loader));
    $this->assertCount(1, $loader->requirements);
    $this->assertSame('pay_balance_payza', $loader->requirements[0][0]);
    $this->assertStringContainsString('pay_balance_payza.php', $loader->requirements[0][1]);
}
```

## Common Issues

- **"Constant PAYZA_ENABLE already defined"** — you defined it outside an `if (!defined(...))` guard. Wrap every `define()` call: `if (!defined('PAYZA_ENABLE')) define('PAYZA_ENABLE', true);`
- **"Call to undefined function `_()`"** — `tests/bootstrap.php` is not being loaded. Verify `phpunit.xml.dist` has `bootstrap="tests/bootstrap.php"` and the stub is present.
- **"Class 'Symfony\\Component\\EventDispatcher\\GenericEvent' not found"** — run `composer install` to populate `vendor/`. The `symfony/event-dispatcher` package is required.
- **`assertSame` fails on hook count** — `getHooks()` has a commented-out entry (`ui.menu`). The expected count is `2`, not `3`; do not uncomment unless you also update `testGetHooksCount()`.
- **ReflectionClass `getType()` returns null** — the method parameter lacks a type hint in source. Check `src/Plugin.php` still has `GenericEvent $event` on the method signature before writing the type assertion.
