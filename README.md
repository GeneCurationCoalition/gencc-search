# GenCC Search

The public-facing search portal for the Gene Curation Coalition (GenCC), providing a searchable interface for gene-disease relationship curations submitted by member organizations worldwide.

**Live Site:** [https://search.thegencc.org](https://search.thegencc.org)

## About GenCC

The Gene Curation Coalition (GenCC) is an international collaboration of genetic and genomic database resources working together to harmonize gene-level resources and provide consistent information about the clinical validity of gene-disease relationships.

## Features

- **Gene Search** - Search by gene symbol, HGNC ID, OMIM ID, Ensembl ID, or other identifiers
- **Disease Search** - Search by disease name or ontology ID (MONDO, OMIM, Orphanet)
- **Genomic Region Search** - Search by genomic coordinates (GRCh37/GRCh38)
- **Submission Browser** - View and filter gene-disease curations from all member organizations
- **Classification Summary** - Aggregate view of curation classifications (Definitive, Strong, Moderate, Limited, etc.)
- **Data Export** - Download curations in CSV, TSV, or Excel format
- **Statistics Dashboard** - Overview of submissions, genes, and diseases in the database

## Architecture

GenCC Search is a **read-only** application that connects to the GenCC submission database (gencc-sub). All data management, curation submissions, and administrative functions are handled by the [gencc-sub](https://github.com/GeneCurationCoalition/gencc-sub) application.

```text
┌─────────────────┐     read-only      ┌─────────────────┐
│  gencc-search   │ ──────────────────▶│   gencc-sub     │
│  (public site)  │                    │   database      │
└─────────────────┘                    └─────────────────┘
                                              ▲
                                              │ write
                                       ┌──────┴────────┐
                                       │   gencc-sub   │
                                       │  (admin site) │
                                       └───────────────┘
```

## Technology Stack

- **Backend:** PHP 7.4+ with Laravel 8
- **Frontend:** Livewire, Alpine.js, Tailwind CSS
- **Database:** MySQL 8 (read-only connection to gencc-sub database)
- **Asset Compilation:** Laravel Mix (Webpack)

## Requirements

- PHP 7.4 or higher
- Composer
- Node.js and npm
- MySQL 8.0+ (access to gencc-sub database)

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/GeneCurationCoalition/gencc-search.git
   cd gencc-search
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install Node dependencies:
   ```bash
   npm install
   ```

4. Copy the environment file and configure:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. Configure database connection in `.env`:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=gencc_sub
   DB_USERNAME=gencc_search_reader
   DB_PASSWORD=your_password
   ```

   Note: Use a read-only database user for security.

6. Build frontend assets:
   ```bash
   npm run dev
   ```

7. Start the development server:
   ```bash
   php artisan serve
   ```

## Project Structure

```
app/
├── Console/Commands/     # Utility commands
├── Exports/              # Data export classes (CSV, TSV, XLSX)
├── Http/
│   ├── Controllers/      # Request handlers
│   └── Livewire/         # Livewire components for dynamic UI
├── Query/Filters/        # Search filter implementations
├── Traits/               # Shared model functionality
├── Classification.php    # Curation classification levels
├── Disease.php           # Disease model with cross-ontology resolution
├── Gene.php              # Gene model with multi-ID search
├── Inheritance.php       # Mode of inheritance
├── Submission.php        # Gene-disease curation submissions
└── Submitter.php         # Member organizations

resources/
├── views/                # Blade templates
│   ├── livewire/         # Livewire component views
│   └── partials/         # Reusable view components
├── css/                  # Stylesheets
└── js/                   # JavaScript

database/
├── factories/            # Model factories for testing
└── migrations/           # Test database schema
```

## Testing

Run the test suite:
```bash
php artisan test
# or
vendor/bin/phpunit
```

Note: Tests use SQLite in-memory database by default. Some tests requiring MySQL-specific features (JSON queries, REGEXP) are skipped when running with SQLite.

## Environment Variables

Key environment variables (see `.env.example` for full list):

| Variable       | Description                              |
| -------------- | ---------------------------------------- |
| `DB_DATABASE`  | Database name (typically `gencc_sub`)    |
| `DB_USERNAME`  | Database user (use read-only user)       |
| `APP_ENV`      | Environment (`local`, `production`)      |
| `APP_DEBUG`    | Enable debug mode (false in production)  |

## Related Projects

- **[gencc-sub](https://github.com/GeneCurationCoalition/gencc-sub)** - Submission portal for member organizations to submit and manage their curations (also serves as the master database)

## License

This project is licensed under the MIT License.

## Contact

For questions about the GenCC database, visit [https://thegencc.org](https://thegencc.org).
