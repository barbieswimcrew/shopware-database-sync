# Shopware Database Sync Plugin

This plugin enables database synchronization from a remote Shopware instance via SSH.

## Features

-   Support for SSH key and password authentication
-   Configurable SSH port and remote path
-   Interactive connection selection with validation
-   Automatic cleanup of temporary files
-   Support for production and staging environments

## Installation

1. Clone the repository into your `custom/plugins` directory:

```bash
cd custom/plugins
git clone https://github.com/barbieswimcrew/shopware-database-sync.git AtticConceptsDatabaseSync
```

2. Install the plugin via Shopware CLI:

```bash
bin/console plugin:refresh
bin/console plugin:install --activate AtticConceptsDatabaseSync
```

## Configuration

Configuration is managed through the `.env.local` file. Two connections are supported:

### Production

```bash
# Production connection
DATABASE_SYNC_PROD_HOST=example.com
DATABASE_SYNC_PROD_USER=ssh-user
DATABASE_SYNC_PROD_PORT=22
DATABASE_SYNC_PROD_PATH=/var/www/shopware
DATABASE_SYNC_PROD_KEY=/path/to/ssh/key
```

### Staging

```bash
# Staging connection
DATABASE_SYNC_STAGING_HOST=staging.example.com
DATABASE_SYNC_STAGING_USER=ssh-user
DATABASE_SYNC_STAGING_PORT=22
DATABASE_SYNC_STAGING_PATH=/var/www/shopware
DATABASE_SYNC_STAGING_PASSWORD=ssh-password
```

## Usage

### Synchronize Database

```bash
# Interactive connection selection
bin/console database:sync

# Direct connection specification
bin/console database:sync production
bin/console database:sync staging
```

The command performs the following steps:

1. Validates connection parameters
2. Creates a dump on the remote server
3. Downloads the dump
4. Imports into local database
5. Automatically cleans up temporary files

### Create Database Dump

```bash
# Create dump and output path
bin/console database:dump --path-only

# Create dump with status messages
bin/console database:dump
```

## Troubleshooting

### Invalid Connection

When an invalid connection is specified (e.g., "test"), an error message displays the allowed values:

```
Invalid connection "test". Allowed values are: "production" or "staging"
```

### Missing Configuration

Missing configuration parameters are clearly displayed:

```
Missing configuration parameters:
DATABASE_SYNC_*_HOST
DATABASE_SYNC_*_USER
...
```

### SSH Connection Issues

For SSH connection problems:

1. Check connection parameters (host, user, port)
2. Ensure SSH key or password is correct
3. Test SSH connection manually: `ssh -p PORT USER@HOST`

## Security

-   SSH keys are preferred over passwords
-   Temporary files are automatically deleted
-   Sensitive information is not logged
-   Connection parameters are validated

## Requirements

-   PHP 8.1 or higher
-   Shopware 6.5.x
-   SSH access to the remote server
-   Remote server must have Shopware 6 installed with `system:dump` command available
-   Local Shopware instance must support `system:restore` command

## Support

For questions or issues, please open an issue on GitHub or contact us at support@attic-concepts.com

## License

MIT License
