<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap file for myadmin-payza-payments tests.
 *
 * Loads the Composer autoloader and defines stubs for global functions
 * that are unavailable outside the full MyAdmin application context.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

if (!function_exists('_')) {
    /**
     * Stub for the gettext _() translation function.
     *
     * @param string $message
     * @return string
     */
    function _(string $message): string
    {
        return $message;
    }
}
