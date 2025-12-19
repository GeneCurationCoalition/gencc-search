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

## Technology Stack

- **Backend:** PHP 7.3+ with Laravel 8
- **Frontend:** Livewire, Alpine.js, Tailwind CSS
- **Database:** MySQL 8
- **Asset Compilation:** Laravel Mix (Webpack)

## Requirements

- PHP 7.3 or higher
- Composer
- Node.js and npm
- MySQL 8.0+

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

5. Configure your database connection in `.env`

6. Run migrations:
   ```bash
   php artisan migrate
   ```

7. Build frontend assets:
   ```bash
   npm run dev
   ```

8. Start the development server:
   ```bash
   php artisan serve
   ```

## Data Management Commands

The application includes several artisan commands for managing external data sources:

| Command | Description |
|---------|-------------|
| `php artisan update:diseases` | Update disease data from MONDO, OMIM, and Orphanet |
| `php artisan update:mondo` | Update MONDO disease ontology |
| `php artisan update:mim` | Update OMIM gene map data |
| `php artisan update:sources` | Master command to update all data sources |
| `php artisan run:report` | Generate reports |

## Project Structure

```
app/
├── Console/Commands/     # Artisan commands for data management
├── Http/
│   ├── Controllers/      # Request handlers
│   └── Livewire/         # Livewire components
├── Traits/               # Shared model functionality
├── Disease.php           # Disease model with cross-ontology resolution
├── Gene.php              # Gene model with multi-ID search
├── Submission.php        # Gene-disease curation submissions
├── Submitter.php         # Member organizations
└── Classification.php    # Curation classification levels

resources/
├── views/                # Blade templates
├── css/                  # Stylesheets
└── js/                   # JavaScript

database/
├── migrations/           # Database schema
└── seeders/              # Initial data
```

## Testing

Run the test suite:
```bash
php artisan test
# or
vendor/bin/phpunit
```

## Related Projects

- **[gencc-sub](https://github.com/GeneCurationCoalition/gencc-sub)** - Submission portal for member organizations to submit and manage their curations

## License

This project is licensed under the MIT License.

## Contact

For questions about the GenCC database, visit [https://thegencc.org](https://thegencc.org).
