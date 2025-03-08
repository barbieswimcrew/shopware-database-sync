# Database Sync Plugin for Shopware 6

This plugin allows you to synchronize databases between different Shopware 6 instances via SSH.

## Features

-   Sync database from remote Shopware instance to local instance
-   Support for SSH key and password authentication
-   Multiple connection support (production/staging)
-   Interactive connection selection
-   Configurable SSH port and remote path
-   Progress feedback during sync process
-   Environment-based configuration

## Requirements

-   PHP 8.1 or higher
-   Shopware 6.5.x
-   SSH access to the remote server
-   Remote server must have Shopware 6 installed with `system:dump` command available
-   Local Shopware instance must support `system:restore` command

## Installation

1. Clone the repository into your Shopware 6 `custom/plugins` directory:

```bash
cd custom/plugins
git clone https://github.com/attic-concepts/database-sync.git AtticConceptsDatabaseSync
```

2. Install the plugin via command line:

```bash
bin/console plugin:refresh
bin/console plugin:install --activate AtticConceptsDatabaseSync
bin/console cache:clear
```

## Configuration

The configuration is managed through environment variables in your `.env.local` file. You can configure multiple connections (e.g., Production, Staging).

### Production Environment

```bash
# SSH Host
DATABASE_SYNC_PROD_HOST=production.example.com

# SSH User
DATABASE_SYNC_PROD_USER=shopware

# SSH Port (default: 22)
DATABASE_SYNC_PROD_PORT=22

# Path to Shopware installation on remote server
DATABASE_SYNC_PROD_PATH=/var/www/html

# Path to SSH key (optional if using password authentication)
DATABASE_SYNC_PROD_KEY=%kernel.project_dir%/.ssh/id_rsa
```

### Staging Environment

```bash
# SSH Host
DATABASE_SYNC_STAGING_HOST=staging.example.com

# SSH User
DATABASE_SYNC_STAGING_USER=shopware

# SSH Port
DATABASE_SYNC_STAGING_PORT=22

# Path to Shopware installation on remote server
DATABASE_SYNC_STAGING_PATH=/var/www/staging

# SSH Password (optional if using key authentication)
DATABASE_SYNC_STAGING_PASSWORD=your-ssh-password
```

## Usage

The command can be executed in two ways:

1. Interactive mode (recommended):

```bash
bin/console database:sync
```

This will:

-   Show available connections
-   Display configuration summary
-   Ask for confirmation before proceeding
-   Show detailed progress during sync

2. Direct connection specification:

```bash
bin/console database:sync production
# or
bin/console database:sync staging
```

The sync process will:

1. Connect to the remote server via SSH
2. Create a database dump on the remote server
3. Import the dump into your local database

⚠️ **Warning**: Your local database will be overwritten during the sync process!

## Security Considerations

-   Never store sensitive data (passwords, SSH keys) in code
-   Prefer SSH key authentication over password authentication
-   Ensure `.env.local` file is not tracked in Git repository
-   Restrict SSH access to necessary directories only
-   Use a dedicated SSH user with limited permissions
-   Set appropriate permissions for SSH keys (600)

## Troubleshooting

### No Connections Configured

Verify that the required environment variables are correctly set in your `.env.local` file.

### SSH Connection Failed

-   Check if SSH key/password is correct
-   Ensure user has access to the remote system
-   Verify configured port is correct
-   Check SSH key permissions (should be 600)
-   Test SSH connection manually first

### Database Dump Failed

-   Verify remote path is correct
-   Ensure user has necessary permissions
-   Check available disk space
-   Verify Shopware console commands are accessible
-   Check remote server's PHP memory limit

## Command Help

For detailed information about the command and its options, use:

```bash
bin/console database:sync --help
```

## Support

For questions or issues, please open an issue on GitHub or contact us at support@attic-concepts.com

## License

MIT License
