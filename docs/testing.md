# Testing and Dependency Installation Notes

The current container image does not include the `vendor/` directory, so PHP dependencies must be installed locally before the Laravel test suite can run. Running `composer install` inside this environment currently fails because Composer cannot download packages from GitHub without authentication; the download step falls back to source control and then asks for GitHub credentials, which we cannot provide in CI.

## Why the install fails
Composer attempts to fetch packages such as `symfony/polyfill-mbstring` from GitHub. The request goes through a proxy that returns HTTP 403/timeout responses for anonymous API usage. Composer then tries to clone the repositories via SSH and prompts for a GitHub token, halting the install and leaving the `vendor/` folder missing. Without `vendor/autoload.php`, commands like `php artisan test` abort immediately.

## How to fix the issue locally
1. Generate a GitHub fine-grained personal access token with **read-only** access to public repositories (no scopes required) from https://github.com/settings/tokens/new.
2. Configure Composer to use that token:
   ```bash
   composer config -g github-oauth.github.com <token>
   ```
3. Re-run the dependency install:
   ```bash
   composer install
   ```
   Composer will now authenticate to GitHub and download all required packages, creating the missing `vendor/` directory.
4. Once dependencies are installed, execute the application test suite:
   ```bash
   php artisan test
   ```

If you are running within a CI service that blocks outbound GitHub access entirely, mirror the packages in an internal Composer repository or provide the necessary proxy credentials so Composer can reach `https://api.github.com`.

## Additional checks
* JavaScript dependencies are already committed under `node_modules/`, so Vite builds can run without additional steps in this environment. Regenerate them via `npm ci` if you need a clean install.
* After `composer install` succeeds, cache the `vendor/` directory or set up Composer's cache in CI to avoid re-triggering GitHub rate limits on every run.
