# Testing Guide for GenCC Submission Portal

> **Last Updated:** December 2025

## ⚠️ CRITICAL: Database Safety

**Tests use SQLite in-memory database and NEVER touch the production MySQL database.**

### How Tests Are Protected

1. **phpunit.xml Configuration**
   - All tests automatically use `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`
   - This is enforced in `phpunit.xml` lines 24-25
   - **DO NOT comment out these lines**

2. **Alternative: Command-Line Override**
   ```bash
   # Explicit SQLite override (redundant but safe)
   DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test
   ```

3. **GitHub Actions CI/CD**
   - Automatically uses SQLite :memory: (configured in `.github/workflows/tests.yml`)
   - No MySQL connection possible in CI environment

## Running Tests

### Recommended: Use Laravel Artisan (uses phpunit.xml automatically)

```bash
# Run all tests
php artisan test

# Run only Feature tests
php artisan test --testsuite=Feature

# Run only Unit tests
php artisan test --testsuite=Unit

# Run specific test file
php artisan test tests/Feature/SubmissionFileValidationTest.php

# Run specific test method
php artisan test --filter=test_passes_with_valid_new_submission
```

### Alternative: Use PHPUnit directly (also uses phpunit.xml)

```bash
# Run all tests
./vendor/bin/phpunit

# Run with explicit configuration
./vendor/bin/phpunit --configuration phpunit.xml
```

## What Caused the MySQL Database Wipe?

**Root Cause:** Someone likely ran tests WITHOUT the phpunit.xml configuration OR with the SQLite lines commented out.

When this happens:
1. Laravel uses the default `.env` configuration (MySQL)
2. The `RefreshDatabase` trait in Feature tests runs migrations
3. This **wipes and rebuilds the MySQL database** (all data lost!)

## How to Prevent Database Wipes

### ✅ Safe Practices

1. **Always run tests through `php artisan test`**
   - This automatically uses `phpunit.xml` configuration
   - Safest and recommended method

2. **Keep phpunit.xml SQLite configuration uncommented**
   ```xml
   <env name="DB_CONNECTION" value="sqlite"/>
   <env name="DB_DATABASE" value=":memory:"/>
   ```

3. **Never run migrations manually in local environment**
   - Use `php artisan migrate` ONLY on empty databases
   - Use `php artisan migrate:fresh` ONLY on development databases

4. **Check database before running unknown commands**
   ```bash
   # Quick check
   php artisan tinker --execute="echo 'Users: ' . \App\Models\User::count();"
   ```

### ❌ Dangerous Practices

1. **DO NOT comment out SQLite configuration in phpunit.xml**
2. **DO NOT run `php artisan migrate:fresh` on production data**
3. **DO NOT run tests with `APP_ENV=local`**
4. **DO NOT create tests without understanding `RefreshDatabase` trait**

## Understanding RefreshDatabase Trait

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;  // ⚠️ WIPES DATABASE!
}
```

**What it does:**
- Runs all migrations from scratch
- Resets database to clean state
- **DELETES ALL DATA** in the database

**Why it's safe in tests:**
- phpunit.xml forces SQLite :memory: connection
- In-memory database is temporary (destroyed after tests)
- Production MySQL database is never touched

## Test Database Seeding

Feature tests seed their own test data:

```php
protected function seedTestData(): void
{
    // Create minimal test data
    Gene::create(['hgnc_id' => 'HGNC:5', ...]);
    Disease::create(['curie' => 'MONDO:0000001', ...]);
    // etc.
}
```

This seeding happens in the in-memory SQLite database, not MySQL.

## Troubleshooting

### "No such table" errors

This is GOOD! It means tests are correctly using SQLite :memory:.

If you see this during tests, the test setup needs to seed data or run migrations.

### Tests are slow

Feature tests should run in ~1 second with SQLite :memory:.

If tests are slow (>5 seconds), they might be hitting MySQL. Check:
```bash
grep -E "DB_CONNECTION|DB_DATABASE" phpunit.xml
```

Should show:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### How to restore MySQL database

If the MySQL database was wiped:

1. **Check for backups**
   ```bash
   ls -la database/backups/
   ls -la storage/backups/
   ```

2. **Use production backup command**
   ```bash
   # If available
   php artisan make-prod-db
   ```

3. **Restore from SQL dump** (if available)
   ```bash
   mysql -u root -p gencc_sub < backup.sql
   ```

4. **Re-import data** (if needed)
   ```bash
   php artisan import:genes
   php artisan import:diseases
   php artisan import:gencc
   # etc.
   ```

## Test Coverage

### Unit Tests (`tests/Unit/`)

| Test File | Description |
|-----------|-------------|
| `ExampleTest.php` | Basic Laravel test example |
| `DiseaseUpdateRefactoringTest.php` | Disease ontology update and equivalence logic |
| `JobStateMachineTest.php` | Job status transitions and validation |
| `SubmissionStateMachineTest.php` | Submission status transitions |
| `SubmissionFileValidationTest.php` | File upload validation logic |

### Feature Tests (`tests/Feature/`)

| Test File | Description |
|-----------|-------------|
| `AuthenticationTest.php` | User login/logout flows |
| `SubmissionApiTest.php` | API endpoint validation |
| `SubmissionFileValidationTest.php` | End-to-end file validation |
| `DocumentRowCountTest.php` | Excel file row counting (excludes empty rows) |
| `*TeamTest.php` | Jetstream team management tests |
| `*PasswordTest.php` | Password reset/update flows |

### Running Specific Test Groups

```bash
# All application-specific tests (excluding Jetstream boilerplate)
php artisan test --filter="Submission|Job|Disease|Document"

# State machine tests only
php artisan test --filter="StateMachine"

# API tests only
php artisan test tests/Feature/SubmissionApiTest.php
```

## CI/CD Testing

GitHub Actions automatically runs tests on:
- Push to `main`, `master`, `develop`, `feature/*` branches
- Pull requests

Configuration: `.github/workflows/tests.yml`

Tests run on:
- PHP 8.1
- PHP 8.2
- SQLite :memory: (no MySQL connection possible)

## Summary

✅ **Safe:** `php artisan test` (uses phpunit.xml)
✅ **Safe:** `./vendor/bin/phpunit` (uses phpunit.xml)
✅ **Safe:** GitHub Actions CI/CD
❌ **Dangerous:** Commenting out SQLite config
❌ **Dangerous:** Running migrations on production database

**Golden Rule:** Never modify phpunit.xml SQLite configuration!
