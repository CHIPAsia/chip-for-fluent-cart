# AGENTS.md

This file provides guidance to AI coding agents (AGENTS.md standard) when working with code in this repository.

## Project Overview

CHIP for FluentCart is a WordPress payment gateway plugin that integrates the CHIP payment platform with FluentCart. It supports FPX, credit/debit cards, e-wallets, and DuitNow QR payments.

## Development Commands

### Linting

The project uses PHPCS with WordPress Coding Standards (WPCS). There is no local phpcs config file; standards are passed via CLI arguments.

```bash
# Basic PHP syntax check
php -l chip-for-fluent-cart.php
php -l includes/class-chip.php
php -l includes/class-chipfluentcartapi.php
php -l includes/class-chiplogger.php
php -l includes/class-chipsettingsbase.php

# PHPCS (requires global install or composer install)
phpcs --standard=WordPress --extensions=php --ignore=vendor,node_modules .

# PHPCompatibility check
phpcs --standard=PHPCompatibilityWP --runtime-set testVersion 8.4 --extensions=php --ignore=vendor,node_modules .
```

To install the coding standards locally:
```bash
composer install
# Then use vendor/bin/phpcs with the same arguments above
```

### CI

GitHub Actions run on push/PR to `main`:
- **PHP Compatibility** (`plugin-check.yml`): Checks against PHP 8.4 using PHPCompatibilityWP.
- **PHPCS** (`plugin-check.yml`): Enforces WordPress coding standards.
- **Plugin Check** (`plugin-check.yml`): Runs WordPress.org plugin checks.

## Architecture

### Payment Gateway Lifecycle

The plugin registers itself as a FluentCart custom payment method. Understanding this flow is essential for any changes:

1. **Registration** (`chip-for-fluent-cart.php:32-50`): Hooks into `fluent_cart/register_payment_methods` to register the gateway. Requires `fluent_cart_api()` to exist (FluentCart active).
2. **Gateway Instance** (`includes/class-chip.php`): Extends `AbstractPaymentGateway`. The constructor receives a `ChipSettingsBase` instance for configuration.
3. **Boot** (`class-chip.php:87-95`): Hooks into `fluent_cart/payment_paid`, settings filters, and transaction URL filters.
4. **Payment Flow**:
   - Checkout calls `makePaymentFromPaymentInstance()` which builds payment data and calls `processPayment()`
   - `processPayment()` uses `ChipFluentCartApi` to create a CHIP purchase
   - On success, stores the CHIP purchase ID in `fct_order_meta` via `OrderMeta::updateOrCreate()`
   - Customer is redirected to CHIP checkout via `checkout_url`
5. **Post-Payment Handling**:
   - **Webhook (IPN)**: CHIP sends webhook to `handleIPN()` → verifies signature with public key → updates order via `processWebhook()`
   - **Redirect**: Customer returns to `handleInitRedirect()` (init hook, not admin-ajax) → checks order status → redirects to success or cancel URL
   - Both paths use database locking (`acquireLock`/`releaseLock`) to prevent race conditions between webhook and redirect processing

### Redirect vs Webhook Race Condition

A critical design detail: payment completion is handled by **two independent paths** that can fire in any order:
- The webhook (async IPN from CHIP)
- The customer's browser redirect (sync, via init hook)

Both acquire a database lock on the order ID before updating status. MySQL uses `GET_LOCK()`/`RELEASE_LOCK()`; PostgreSQL uses `pg_advisory_lock()`/`pg_advisory_unlock()`. The lock timeout is 15 seconds.

### API Client

`ChipFluentCartApi` wraps HTTP calls to `https://gate.chip-in.asia/api/v1`. Key methods:
- `create_payment()` → POST `/purchases/`
- `get_payment()` → GET `/purchases/{id}/`
- `refund_payment()` → POST `/purchases/{id}/refund/`
- `public_key()` → GET `/public_key/`

The API client uses `wp_remote_request()` with a 10-second timeout. SSL verification can be disabled by defining `CHIP_FOR_FLUENTCART_SSLVERIFY_FALSE`.

### Settings Architecture

`ChipSettingsBase` extends `BaseGatewaySettings` (FluentCart). Settings are stored in the FluentCart settings system with the handler key `fluent_cart_payment_settings_chip`.

Default settings include `is_active`, `payment_mode`, `brand_id`, `secret_key`, `public_key`, `payment_method_whitelist`, `email_fallback`, `show_gateway_description`, `debug`.

### Webhook Signature Verification

Public keys are fetched once from CHIP API and cached in a WordPress option (`chip-for-fluent-cart-public-key`), keyed by `brand_id`. The raw webhook payload is verified against the `X-Signature` header using `openssl_verify()` with `OPENSSL_ALGO_SHA256`.

### Key Constants

- `CHIP_FOR_FLUENTCART_VERSION` — Plugin version string
- `CHIP_FOR_FLUENTCART_ROOT_URL` — API base URL (`https://gate.chip-in.asia/api`)
- `CHIP_FOR_FLUENTCART_SSLVERIFY_FALSE` — Disables SSL verification in API requests

## File Structure

| File | Purpose |
|------|---------|
| `chip-for-fluent-cart.php` | Main plugin file: registration, hooks, asset enqueueing |
| `includes/class-chip.php` | Core gateway: payment processing, webhooks, redirects, refunds |
| `includes/class-chipfluentcartapi.php` | HTTP API wrapper for CHIP endpoints |
| `includes/class-chiplogger.php` | Thin wrapper around `fluent_cart_add_log()` |
| `includes/class-chipsettingsbase.php` | Settings defaults and accessors |
| `assets/admin.js` | Replaces "CHIP" text with logo in FluentCart admin UI |
| `readme.txt` | WordPress.org plugin page metadata |

## Release Process

The plugin is distributed on both GitHub and WordPress.org SVN.

- **GitHub release**: Creating a GitHub release triggers `release-zip.yml` which uploads a zip asset.
- **WordPress.org**: Push a tag matching `fluentcart-upload-v{VERSION}` (e.g., `fluentcart-upload-v1.0.0`) to trigger `fluentcart.upload.yml`, which commits to SVN `/tags/`.
- **Stable tag**: `fluentcart.yml` copies the latest tag's `readme.txt` to SVN trunk to mark it stable.

## Important Constraints

- Requires FluentCart plugin (`Requires Plugins: fluent-cart`). The main file bails early if `fluent_cart_api()` is unavailable.
- Amounts are in **cents** (FluentCart handles this before passing to the gateway).
- The plugin supports both MySQL/MariaDB and PostgreSQL for the database lock mechanism.
- PHP minimum is 7.4; CI tests against PHP 8.4.
