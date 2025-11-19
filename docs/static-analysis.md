# Static code analysis findings

## 1. Lab webhooks are exposed because the verification middleware is empty
- **Where:** `bootstrap/app.php` registers the `verify.webhook.signature` alias, the webhook routes in `routes/web.php` apply that alias, but `app/Http/Middleware/VerifyWebhookSignature.php` is a zero-byte file with no logic.
- **Impact:** Anyone on the internet can post arbitrary payloads to `/webhooks/lab-results` or `/webhooks/lab-hl7` and have them processed as authentic lab data because no shared secret, HMAC, or IP allowlist check is executed. That allows spoofed results, unauthorized status changes, and potential PHI disclosure through error logs.
- **Recommendation:** Implement the middleware so it actually validates an HMAC signature (or at least compares a shared secret header) before letting the request through; reject and log unsigned/invalid requests, and make sure the secret is stored in `config/services.php` or `.env` instead of the code.

## 2. Admin result routes without a controller crash route registration
- **Where:** Lines 120–127 of `routes/web.php` register `/{result}` routes inside a middleware group without wrapping them in a `Route::controller(...)` declaration, so Laravel tries to resolve an action named `download` with no controller class.
- **Impact:** `php artisan route:list` and the HTTP kernel will throw `Invalid route action: [download].` as soon as the route file is parsed, meaning the entire app cannot boot with the current route definition.
- **Recommendation:** Either delete the stray block or move the `download/regenerate/notify` routes into the existing `Route::controller(Admin\ResultController::class)` section (the one that already defines identical URIs) so every route resolves to a concrete controller method.

## 3. `Order::markAsCollected()` dispatches an event with the wrong signature
- **Where:** `app/Models/Order.php` dispatches `new \App\Events\OrderStatusChanged($this)` without providing the required `$previousStatus` argument, while `App\Events\OrderStatusChanged` declares `__construct(Order $order, string $previousStatus)`.
- **Impact:** Any code path that calls `Order::markAsCollected()` will immediately crash with an `ArgumentCountError`, so collection flows inside `Admin\OrderManagementController::markCollected()` cannot complete and the queue job that submits orders to the lab never runs.
- **Recommendation:** Capture the old status before the update and pass it to the event (e.g., `$previous = $this->status; ... event(new OrderStatusChanged($this->refresh(), $previous));`). Add a feature test that covers the admin “Mark as collected” button to prevent regressions.

## 4. The thermal label printer service cannot boot because its configuration file is missing
- **Where:** `App\Services\PrintService` hardcodes a `NetworkPrintConnector` that reads `config('printing.printer_ip')`/`config('printing.printer_port')`, but there is no `config/printing.php` file anywhere in the repository.
- **Impact:** Resolving `PrintService` (which happens in `Admin\OrderManagementController::printLabel`) throws a `RuntimeException` because the connector receives a `null` host. As a result, admins cannot print specimen labels at all.
- **Recommendation:** Ship a `config/printing.php` with sane defaults (or read from `.env`) and guard the service constructor so it raises a descriptive error when the IP is unset. That keeps `printLabel` usable in staging environments and lets users override the settings via environment variables.

## 5. New lab partners created via the admin UI can never submit orders
- **Where:** `LabPartnerManager` only registers adapters for the hard-coded codes `quest`, `labcorp`, and `hl7`, and `getAdapter()` throws when a partner’s `code` key is not one of those. The admin create/edit form (`Admin\LabPartnerController`) lets staff enter any unique code without selecting an adapter class.
- **Impact:** Creating a new lab partner with a code that doesn’t exactly match a pre-registered adapter succeeds, but every submission attempt later fails with “No adapter found for lab partner” because there is no mapping. That makes the entire lab-partner management UI misleading and non-functional for new partners.
- **Recommendation:** Either restrict the `code` validation rule to the set of registered adapters or add a separate “Adapter” select field tied to `LabPartnerManager::registerAdapter()`. Persist the adapter choice on the model so `getAdapter()` can instantiate the right class regardless of the arbitrary partner code.
