---
name: add-event-hook
description: Registers a new Symfony EventDispatcher hook in src/Plugin.php getHooks(). Use when user says 'add hook', 'listen to event', 'handle system event', 'register callback'. Covers GenericEvent $event, $event->getSubject(), and returning hook arrays. Do NOT use for adding settings fields or page requirements (those are separate existing hooks).
---
# add-event-hook

## Critical

- `getHooks()` MUST return a flat associative array: `'event.name' => [__CLASS__, 'methodName']`.
- Every handler MUST have signature `public static function methodName(GenericEvent $event)` — never instance methods.
- `use Symfony\Component\EventDispatcher\GenericEvent;` MUST be present at the top of `src/Plugin.php`.
- Never put business logic directly in `getHooks()` — only the hook map.

## Instructions

1. **Identify the event name and subject type.**
   Determine the event string (e.g. `'billing.invoice'`) and what `$event->getSubject()` returns for that event (loader, settings object, array, etc.).
   Verify the event string is fired somewhere in the host app before proceeding.

2. **Add the entry to `getHooks()` in `src/Plugin.php`.**
   Open `src/Plugin.php` and add one line inside the returned array:
   ```php
   'event.name' => [__CLASS__, 'handleEventName'],
   ```
   The array already contains `'system.settings'` and `'function.requirements'` — add your entry alongside them.

3. **Add the static handler method to the `Plugin` class.**
   Place the new method after the last existing handler. Follow this exact pattern:
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    */
   public static function handleEventName(GenericEvent $event)
   {
       $subject = $event->getSubject();
       // your logic here
       myadmin_log('billing', 'info', _('Event handled'), __LINE__, __FILE__);
   }
   ```
   Use `$event->getSubject()` to retrieve the payload — never access `$_GET`/`$_POST` directly inside a hook handler.

4. **Use `$event->getSubject()` correctly.**
   - If the subject is the loader: `/** @var \MyAdmin\Plugins\Loader $loader */ $loader = $event->getSubject();`
   - If the subject is settings: `/** @var \MyAdmin\Settings $settings */ $settings = $event->getSubject();`
   - Add the matching `@var` docblock comment exactly as shown in existing handlers.

5. **Wrap all user-visible strings in `_()`.**
   Every string literal passed to log calls or UI methods must use gettext: `_('My message')`, not `'My message'`.

6. **Run tests to verify no regressions.**
   ```bash
   vendor/bin/phpunit tests/ -v
   ```
   All existing tests in `tests/PluginTest.php` must pass before committing.

## Examples

**User says:** "Add a hook for `billing.invoice` that logs the invoice ID."

**Actions taken:**

`getHooks()` updated:
```php
public static function getHooks()
{
    return [
        'system.settings'      => [__CLASS__, 'getSettings'],
        'function.requirements' => [__CLASS__, 'getRequirements'],
        'billing.invoice'      => [__CLASS__, 'handleInvoice'],
    ];
}
```

New handler added:
```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function handleInvoice(GenericEvent $event)
{
    $invoice = $event->getSubject();
    myadmin_log('billing', 'info', _('Payza invoice hook fired: ') . intval($invoice['invoices_id']), __LINE__, __FILE__);
}
```

**Result:** Hook fires on `billing.invoice`, logs the ID, and `vendor/bin/phpunit tests/ -v` passes.

## Common Issues

- **Error: `Call to undefined method ... getSubject()`** — The event passed is not a `GenericEvent`. Verify the host dispatcher fires `GenericEvent`, not a bare `Event`. Check `use Symfony\Component\EventDispatcher\GenericEvent;` is imported.
- **Hook never fires** — The event string in `getHooks()` does not match what the host calls. Search the host codebase for `run_event('event.name'` to confirm the exact string.
- **PHPUnit: `Cannot redeclare _()`** — `tests/bootstrap.php` already stubs `_()`. Do not redefine it in new test files; the bootstrap is loaded once via `phpunit.xml.dist`.
- **Scrutinizer camelCase warning** — Method and parameter names must be camelCase (e.g. `handleInvoice`, not `handle_invoice`). `.scrutinizer.yml` enforces this.
