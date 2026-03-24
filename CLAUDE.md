# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Bili is a PHP utility library ("swiss army knife of PHP modules") by Neverwoods. It provides standalone utility classes for common tasks: collections, cryptography, date handling, sanitization, language/i18n, file I/O, HTTP requests, image editing, CSS/JS asset inclusion, and more.

## Backward Compatibility

This is a published package with downstream dependents. **Public API and behavior must not change.** Method signatures, return types, class names, and observable behavior must remain stable. Internal refactors and dependency updates are fine as long as the external contract is preserved. When updating dependencies, ensure the library continues to behave identically from a consumer's perspective.

## Commands

```bash
# Install dependencies
composer install

# Run all tests
vendor/bin/phpunit tests

# Run a single test file
vendor/bin/phpunit tests/CryptTest.php

# Run a specific test method
vendor/bin/phpunit --filter testGenerateToken tests/CryptTest.php
```

## Architecture

- **Namespace:** `Bili` — all classes live under `classes/Bili/` using PSR-0 autoloading
- **Tests:** `tests/` directory, namespace `Bili\Tests\`, PSR-4 autoloaded, PHPUnit 9.5
- **No phpunit.xml** at root — tests are run by pointing phpunit directly at the `tests/` directory
- All classes are in a flat structure (no subdirectories) under `classes/Bili/`
- Most utility classes use static methods (e.g., `Crypt`, `Date`, `Sanitize`)
- `Collection` implements `Iterator` and `JsonSerializable` for iterable object collections
- `Language` is a singleton managing i18n with translation files in `tests/languages/`
- `Date` wraps `nesbot/carbon` for date operations
- CSS/JS includers use `mrclay/minify` for asset minification

## CI

Tests run on PHP 8.1–8.4 with both `prefer-lowest` and `prefer-stable` dependency versions. The CI also installs `nl_NL` and `en_US` locales (required by language/date tests).
