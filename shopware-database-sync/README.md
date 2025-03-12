# Database Sync Plugin for Shopware 6

This plugin enables synchronization of database dumps from a remote server to your local development environment and provides commands for managing database dumps.

## Installation

```bash
composer require barbieswimcrew/database-sync
bin/console plugin:refresh
bin/console plugin:install --activate BarbieswimcrewDatabaseSync
```

## Usage

### Sync from Remote Server

The following command starts the synchronization process from a remote server:

```bash
bin/console database:sync
```

The command will interactively ask for the following information:

-   Remote Host (e.g. dev.example.com)
-   SSH Username
-   SSH Port (default: 22)
-   Remote Path (default: /var/www/html)

The database dump will be saved in the local directory `var/dump` with the prefix "remote\_" and a timestamp.

### Create Local Database Dump

To create a dump of your local database:

```bash
# Create dump and show status messages
bin/console database:dump

# Create dump and only output the file path
bin/console database:dump --path-only
```

The dump will be saved in the `var/dump` directory with a timestamp and automatically compressed with gzip.

### Import Database Dump

To import an existing database dump:

```bash
bin/console database:import
```

This command will:

1. Show a list of available dumps in the `var/dump` directory
2. Let you select which dump to import
3. Import the selected dump into your database

## Requirements

-   SSH access to the remote server (for sync functionality)
-   Installed Shopware 6 instance on the remote server
-   The plugin must be installed on both the local and the remote server
-   Sufficient permissions for database dumps
-   Sufficient disk space for database dumps

## Support

For questions or issues, please open an issue on GitHub or contact us at info@attic-concepts.com

## License

MIT License
