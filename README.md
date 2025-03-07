# Database Sync Plugin for Shopware 6

This plugin provides a CLI command to sync databases between Shopware 6 instances via SSH.

## Features

-   Sync database from remote Shopware instance to local instance
-   Support for SSH key and password authentication
-   Configurable SSH port and remote path
-   Progress feedback during sync process

## Installation

1. Download the plugin
2. Upload the plugin files to `custom/plugins/DatabaseSync` in your Shopware installation
3. Install the plugin via the Plugin Manager or using the following command:
    ```bash
    bin/console plugin:install --activate DatabaseSync
    ```

## Usage

The plugin provides a new CLI command `database:sync` with the following options:

```bash
bin/console database:sync \
    --host=example.com \
    --user=shopware \
    --remote-path=/var/www/html \
    --key=/path/to/private/key
```

### Available Options

-   `--host`: The SSH host of the remote server (required)
-   `--user`: The SSH username (required)
-   `--port`: The SSH port (optional, default: 22)
-   `--remote-path`: The path to the Shopware root directory on the remote server (required)
-   `--key`: The path to the SSH private key (optional)
-   `--password`: The SSH password (optional)

## Requirements

-   PHP 8.1 or higher
-   Shopware 6.5.x
-   SSH access to the remote server
-   The remote server must have Shopware 6 installed with the `system:dump` command available
-   The local Shopware instance must support the `system:restore` command

## License

MIT License
