# Testing Guide for GenCC Search

## Overview

GenCC Search uses PHPUnit with Laravel's testing framework. Tests run against an in-memory SQLite database to ensure isolation and speed.

## Running Tests

### Quick Start

```bash
# Run all tests
php artisan test

# Run with verbose output
php artisan test --verbose
```

### Using PHPUnit Directly

```bash
# Run all tests
./vendor/bin/phpunit

# Run with specific configuration
./vendor/bin/phpunit --configuration phpunit.xml
```

### Running Specific Tests

```bash
# Run only Feature tests
php artisan test --testsuite=Feature

# Run only Unit tests
php artisan test --testsuite=Unit

# Run a specific test file
php artisan test tests/Feature/SubmissionFeatureTest.php

# Run a specific test method
php artisan test --filter=test_submission_show_page_returns_200

# Run tests matching a pattern
php artisan test --filter="Gene"
```

## Test Database Configuration

Tests use SQLite in-memory database, configured in `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

This ensures:

- Tests never touch the production gencc-sub database
- Fast test execution (no disk I/O)
- Complete isolation between test runs

## MySQL-Specific Tests

Some tests require MySQL features not available in SQLite:

- `JSON_EXTRACT` queries
- `REGEXP_SUBSTR` for gene symbol ordering

These tests are marked with `@group mysql` and skip automatically when running with SQLite:

```php
/**
 * @test
 * @group mysql
 */
public function genes_listing_shows_genes_with_submissions()
{
    if (config('database.default') === 'sqlite') {
        $this->markTestSkipped('This test requires MySQL for JSON_EXTRACT support');
    }
    // ...
}
```

To run MySQL-specific tests:

```bash
# Set environment to use MySQL
DB_CONNECTION=mysql DB_DATABASE=gencc_test php artisan test --group=mysql
```

## Test Structure

### Test Suites

| Suite   | Location            | Description                        |
| ------- | ------------------- | ---------------------------------- |
| Unit    | `tests/Unit/`       | Model and component tests          |
| Feature | `tests/Feature/`    | HTTP request and integration tests |

### Feature Tests

| Test File                      | Description                              |
| ------------------------------ | ---------------------------------------- |
| `DiseaseFeatureTest.php`       | Disease listing and detail pages         |
| `DownloadFeatureTest.php`      | Data export/download functionality       |
| `GeneFeatureTest.php`          | Gene search and detail pages             |
| `Genes200Test.php`             | Gene page HTTP 200 response tests        |
| `MemberFeatureTest.php`        | Member/submitter organization pages      |
| `StaticPagesFeatureTest.php`   | Static content pages (about, FAQ, etc.)  |
| `StatisticsFeatureTest.php`    | Statistics dashboard                     |
| `Stats200Test.php`             | Statistics page HTTP 200 response tests  |
| `SubmissionFeatureTest.php`    | Submission detail pages                  |
| `Submitters200Test.php`        | Submitter pages HTTP 200 response tests  |

### Livewire Tests

| Test File                                        | Description                             |
| ------------------------------------------------ | --------------------------------------- |
| `Livewire/GenesListingTest.php`                  | Main gene listing component             |
| `Livewire/GeneListingByClassificationTest.php`   | Gene listing filtered by classification |
| `Livewire/GeneListingByDiseaseTest.php`          | Gene listing filtered by disease        |
| `Livewire/GeneListingBySubmitterTest.php`        | Gene listing filtered by submitter      |
| `Livewire/SubmitterListingOfSubmissionsTest.php` | Submitter's submissions listing         |

### Unit Tests

| Test File                      | Description                              |
| ------------------------------ | ---------------------------------------- |
| `ClassificationModelTest.php`  | Classification model accessors           |
| `DiseaseModelTest.php`         | Disease model and relationships          |
| `GeneModelTest.php`            | Gene model with multi-ID search          |
| `SubmissionModelTest.php`      | Submission scopes and relationships      |
| `SubmitterModelTest.php`       | Submitter model tests                    |

## Writing Tests

### Basic Feature Test

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Gene;
use App\Disease;
use App\Submission;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function example_page_returns_200()
    {
        // Create test data
        $gene = Gene::factory()->create();

        // Make request
        $response = $this->get('/genes/' . $gene->curie);

        // Assert response
        $response->assertStatus(200);
        $response->assertViewHas('gene');
    }
}
```

### Using Factories

Model factories are in `database/factories/`:

```php
// Create a gene
$gene = Gene::factory()->create(['symbol' => 'BRCA1']);

// Create a submission with relationships
$submission = Submission::factory()->create([
    'gene_id' => $gene->id,
    'disease_id' => Disease::factory(),
    'classification_id' => Classification::factory()->definitive(),
]);
```

### Testing Livewire Components

```php
use Livewire\Livewire;
use App\Http\Livewire\Genes\Listing;

/** @test */
public function genes_listing_component_renders()
{
    $this->createTestSubmission();

    Livewire::test(Listing::class)
        ->assertStatus(200)
        ->assertSee('BRCA1');
}
```

## CI/CD Pipeline

GitHub Actions runs tests automatically on:

- Push to `main`, `master`, `develop` branches
- Pull requests

Configuration: `.github/workflows/tests.yaml`

The CI pipeline:

1. Sets up PHP 7.4
2. Installs Composer dependencies
3. Runs PHPUnit with SQLite in-memory database
4. Reports test results

## Troubleshooting

### "No such table" Errors

This is expected with SQLite. The `RefreshDatabase` trait runs migrations before each test. If you see this error, ensure:

1. The migration exists in `database/migrations/`
2. The test uses `RefreshDatabase` trait

### Tests Are Slow

If tests take more than a few seconds, check you're using SQLite:

```bash
grep "DB_CONNECTION" phpunit.xml
# Should show: <env name="DB_CONNECTION" value="sqlite"/>
```

### MySQL Features Not Available

Some queries don't work with SQLite. Skip these tests:

```php
if (config('database.default') === 'sqlite') {
    $this->markTestSkipped('Requires MySQL');
}
```

## Summary

| Command                             | Description              |
| ----------------------------------- | ------------------------ |
| `php artisan test`                  | Run all tests            |
| `php artisan test --testsuite=Unit` | Run unit tests only      |
| `php artisan test --filter="Gene"`  | Run tests matching "Gene"|
| `./vendor/bin/phpunit`              | Run via PHPUnit directly |
