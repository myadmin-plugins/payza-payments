<?php

declare(strict_types=1);

namespace Detain\MyAdminPayza\Tests;

use Detain\MyAdminPayza\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Tests for the Detain\MyAdminPayza\Plugin class.
 *
 * Validates class structure, static properties, hook registration,
 * and event handler signatures without relying on external dependencies.
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    /**
     * Set up the reflection instance used across tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    // ─── Class Structure ────────────────────────────────────────────────

    /**
     * Test that the Plugin class exists and is instantiable.
     *
     * @return void
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    /**
     * Test that the Plugin class can be instantiated without arguments.
     *
     * @return void
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Test that the constructor accepts zero parameters.
     *
     * @return void
     */
    public function testConstructorHasNoRequiredParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertSame(0, $constructor->getNumberOfRequiredParameters());
    }

    /**
     * Test that the class resides in the expected namespace.
     *
     * @return void
     */
    public function testNamespace(): void
    {
        $this->assertSame('Detain\\MyAdminPayza', $this->reflection->getNamespaceName());
    }

    // ─── Static Properties ──────────────────────────────────────────────

    /**
     * Test that the $name static property is set correctly.
     *
     * @return void
     */
    public function testNameProperty(): void
    {
        $this->assertSame('Payza Plugin', Plugin::$name);
    }

    /**
     * Test that the $description static property is a non-empty string.
     *
     * @return void
     */
    public function testDescriptionProperty(): void
    {
        $this->assertIsString(Plugin::$description);
        $this->assertNotEmpty(Plugin::$description);
        $this->assertStringContainsString('Payza', Plugin::$description);
    }

    /**
     * Test that the $help static property exists and is a string.
     *
     * @return void
     */
    public function testHelpProperty(): void
    {
        $this->assertTrue($this->reflection->hasProperty('help'));
        $this->assertIsString(Plugin::$help);
    }

    /**
     * Test that the $type static property is set to 'plugin'.
     *
     * @return void
     */
    public function testTypeProperty(): void
    {
        $this->assertSame('plugin', Plugin::$type);
    }

    /**
     * Test that all expected static properties exist on the class.
     *
     * @return void
     */
    public function testAllStaticPropertiesExist(): void
    {
        $expectedProperties = ['name', 'description', 'help', 'type'];
        foreach ($expectedProperties as $property) {
            $this->assertTrue(
                $this->reflection->hasProperty($property),
                "Missing static property: \${$property}"
            );
            $prop = $this->reflection->getProperty($property);
            $this->assertTrue($prop->isStatic(), "\${$property} should be static");
            $this->assertTrue($prop->isPublic(), "\${$property} should be public");
        }
    }

    // ─── getHooks() ─────────────────────────────────────────────────────

    /**
     * Test that getHooks returns an array.
     *
     * @return void
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Test that getHooks is a static method.
     *
     * @return void
     */
    public function testGetHooksIsStatic(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test that getHooks registers the system.settings hook.
     *
     * @return void
     */
    public function testGetHooksContainsSystemSettings(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('system.settings', $hooks);
        $this->assertSame([Plugin::class, 'getSettings'], $hooks['system.settings']);
    }

    /**
     * Test that getHooks registers the function.requirements hook.
     *
     * @return void
     */
    public function testGetHooksContainsFunctionRequirements(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('function.requirements', $hooks);
        $this->assertSame([Plugin::class, 'getRequirements'], $hooks['function.requirements']);
    }

    /**
     * Test that all hook callbacks reference callable static methods.
     *
     * @return void
     */
    public function testHookCallbacksReferenceExistingMethods(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $callback) {
            $this->assertIsArray($callback, "Callback for '{$eventName}' should be an array");
            $this->assertCount(2, $callback, "Callback for '{$eventName}' should have class and method");
            [$class, $method] = $callback;
            $this->assertTrue(
                method_exists($class, $method),
                "Method {$class}::{$method} referenced in '{$eventName}' hook does not exist"
            );
        }
    }

    /**
     * Test that hook count matches expected number.
     *
     * @return void
     */
    public function testGetHooksCount(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(2, $hooks);
    }

    // ─── Event Handler Signatures ───────────────────────────────────────

    /**
     * Test that getSettings accepts a GenericEvent parameter.
     *
     * @return void
     */
    public function testGetSettingsSignature(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that getRequirements accepts a GenericEvent parameter.
     *
     * @return void
     */
    public function testGetRequirementsSignature(): void
    {
        $method = $this->reflection->getMethod('getRequirements');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that getMenu accepts a GenericEvent parameter.
     *
     * @return void
     */
    public function testGetMenuSignature(): void
    {
        $method = $this->reflection->getMethod('getMenu');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    // ─── getRequirements Behavior ───────────────────────────────────────

    /**
     * Test that getRequirements calls add_page_requirement on the loader subject.
     *
     * Uses an anonymous class to avoid mocking vendor classes.
     *
     * @return void
     */
    public function testGetRequirementsCallsAddPageRequirement(): void
    {
        $loader = new class {
            /** @var array<int, array{0: string, 1: string}> */
            public array $requirements = [];

            /**
             * @param string $name
             * @param string $path
             * @return void
             */
            public function add_page_requirement(string $name, string $path): void
            {
                $this->requirements[] = [$name, $path];
            }
        };

        $event = new GenericEvent($loader);
        Plugin::getRequirements($event);

        $this->assertCount(1, $loader->requirements);
        $this->assertSame('pay_balance_payza', $loader->requirements[0][0]);
        $this->assertStringContainsString('pay_balance_payza.php', $loader->requirements[0][1]);
        $this->assertStringContainsString('myadmin-payza-payments', $loader->requirements[0][1]);
    }

    // ─── getSettings Behavior ───────────────────────────────────────────

    /**
     * Test that getSettings registers the expected settings fields.
     *
     * Uses an anonymous class to capture settings registrations
     * without depending on MyAdmin\Settings.
     *
     * @return void
     */
    public function testGetSettingsRegistersExpectedFields(): void
    {
        if (!defined('PAYZA_ENABLE')) {
            define('PAYZA_ENABLE', true);
        }
        if (!defined('PAYZA_EMAIL')) {
            define('PAYZA_EMAIL', 'test@example.com');
        }

        $settings = new class {
            /** @var array<int, array<string, mixed>> */
            public array $radios = [];
            /** @var array<int, array<string, mixed>> */
            public array $texts = [];

            /**
             * @param mixed ...$args
             * @return void
             */
            public function add_radio_setting(...$args): void
            {
                $this->radios[] = $args;
            }

            /**
             * @param mixed ...$args
             * @return void
             */
            public function add_text_setting(...$args): void
            {
                $this->texts[] = $args;
            }
        };

        // Stub the _() translation function if not available
        if (!function_exists('_')) {
            // _ is a built-in PHP alias for gettext; it always exists
        }

        $event = new GenericEvent($settings);
        Plugin::getSettings($event);

        $this->assertCount(1, $settings->radios, 'Expected one radio setting for payza_enable');
        $this->assertCount(1, $settings->texts, 'Expected one text setting for payza_email');

        // Verify radio setting has correct key
        $this->assertSame('payza_enable', $settings->radios[0][2]);

        // Verify text setting has correct key
        $this->assertSame('payza_email', $settings->texts[0][2]);
    }

    // ─── Static Analysis: pay_balance_payza.php ─────────────────────────

    /**
     * Test that the pay_balance_payza function file exists and is readable.
     *
     * @return void
     */
    public function testPayBalancePayzaFileExists(): void
    {
        $filePath = dirname(__DIR__) . '/src/pay_balance_payza.php';
        $this->assertFileExists($filePath);
        $this->assertFileIsReadable($filePath);
    }

    /**
     * Test that pay_balance_payza.php defines the expected function.
     *
     * @return void
     */
    public function testPayBalancePayzaFunctionDefinedInSource(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/src/pay_balance_payza.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('function pay_balance_payza()', $source);
    }

    /**
     * Test that pay_balance_payza.php references the Payza checkout URL.
     *
     * @return void
     */
    public function testPayBalancePayzaContainsPayzaCheckoutUrl(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/src/pay_balance_payza.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('https://secure.payza.com/checkout', $source);
    }

    /**
     * Test that pay_balance_payza.php uses USD currency.
     *
     * @return void
     */
    public function testPayBalancePayzaUsesUsdCurrency(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/src/pay_balance_payza.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('ap_currency', $source);
        $this->assertStringContainsString('USD', $source);
    }

    /**
     * Test that pay_balance_payza.php uses bcadd for financial calculations.
     *
     * @return void
     */
    public function testPayBalancePayzaUsesBcaddForAmounts(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/src/pay_balance_payza.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('bcadd(', $source);
    }

    /**
     * Test that pay_balance_payza.php includes required hidden form fields.
     *
     * @return void
     */
    public function testPayBalancePayzaContainsRequiredFormFields(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/src/pay_balance_payza.php');
        $this->assertIsString($source);

        $requiredFields = [
            'ap_purchasetype',
            'ap_merchant',
            'ap_currency',
            'ap_returnurl',
            'ap_cancelurl',
            'ap_itemname_',
            'ap_quantity_',
            'ap_amount_',
            'apc_1',
        ];

        foreach ($requiredFields as $field) {
            $this->assertStringContainsString(
                $field,
                $source,
                "Missing required Payza form field: {$field}"
            );
        }
    }

    /**
     * Test that pay_balance_payza.php uses SQL parameterization via real_escape.
     *
     * @return void
     */
    public function testPayBalancePayzaUsesRealEscape(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/src/pay_balance_payza.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('real_escape', $source);
    }

    /**
     * Test that pay_balance_payza.php calls myadmin_log for audit logging.
     *
     * @return void
     */
    public function testPayBalancePayzaCallsMyadminLog(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/src/pay_balance_payza.php');
        $this->assertIsString($source);
        $this->assertStringContainsString("myadmin_log('billing'", $source);
    }

    /**
     * Test that pay_balance_payza.php uses service purchase type.
     *
     * @return void
     */
    public function testPayBalancePayzaPurchaseTypeIsService(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/src/pay_balance_payza.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('value="service"', $source);
    }

    /**
     * Test that pay_balance_payza.php filters unpaid invoices only.
     *
     * @return void
     */
    public function testPayBalancePayzaFiltersUnpaidInvoices(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/src/pay_balance_payza.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('invoices_paid=0', $source);
    }

    /**
     * Test that pay_balance_payza.php generates a random token for IPN verification.
     *
     * @return void
     */
    public function testPayBalancePayzaGeneratesRandomToken(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/src/pay_balance_payza.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('_randomstring(', $source);
    }
}
