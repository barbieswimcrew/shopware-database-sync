<?php declare(strict_types=1);

namespace AtticConcepts\DatabaseSync\Command;

use AtticConcepts\DatabaseSync\Config\DatabaseSyncConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'database:sync',
    description: 'Sync database from remote Shopware instance via SSH',
)]
class DatabaseSyncCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly DatabaseSyncConfig $config
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('connection', InputArgument::OPTIONAL, 'Name of the connection to use (production or staging)')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command synchronizes a database from a remote Shopware instance:

  <info>%command.full_name%</info>

You can also specify the connection directly:

  <info>%command.full_name% production</info>
  <info>%command.full_name% staging</info>

The command will:
  * Connect to the remote server via SSH
  * Create a database dump on the remote server
  * Import the dump into your local database

Available connections are configured in your <comment>.env.local</comment> file.
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $connections = $this->config->getConnections();
        if (empty($connections)) {
            $this->io->error([
                'No database connections configured.',
                '',
                'You can configure either a production or staging connection (or both) in your .env.local file:',
                '',
                '# Option 1: Production Connection',
                'DATABASE_SYNC_PROD_HOST=production.example.com',
                'DATABASE_SYNC_PROD_USER=shopware',
                'DATABASE_SYNC_PROD_PORT=22',
                'DATABASE_SYNC_PROD_PATH=/var/www/html',
                'DATABASE_SYNC_PROD_KEY=%kernel.project_dir%/.ssh/id_rsa',
                '',
                '# Option 2: Staging Connection',
                'DATABASE_SYNC_STAGING_HOST=staging.example.com',
                'DATABASE_SYNC_STAGING_USER=shopware',
                'DATABASE_SYNC_STAGING_PORT=22',
                'DATABASE_SYNC_STAGING_PATH=/var/www/staging',
                'DATABASE_SYNC_STAGING_PASSWORD=your-ssh-password',
                '',
                'After configuration, you can use:',
                '  bin/console database:sync production',
                '  bin/console database:sync staging',
                '',
                'See the README.md file for more configuration examples and documentation.'
            ]);
            return Command::FAILURE;
        }

        // Get connection name
        $connectionName = $input->getArgument('connection');
        if (!$connectionName) {
            $this->io->title('Database Sync');
            $this->io->text([
                'This command will synchronize your local database with a remote Shopware instance.',
                'Please select which connection you want to use:',
                ''
            ]);

            $connectionName = $this->io->choice(
                'Connection',
                array_keys($connections),
                array_key_first($connections)
            );
        }

        $options = $this->config->getConnection($connectionName);
        if (!$options) {
            $this->io->error([
                sprintf('Connection "%s" not found.', $connectionName),
                '',
                'Available connections must be configured in your .env.local file.',
                sprintf('For a %s connection, use:', $connectionName),
                '',
                sprintf('DATABASE_SYNC_%s_HOST=your-server.com', strtoupper($connectionName)),
                sprintf('DATABASE_SYNC_%s_USER=shopware', strtoupper($connectionName)),
                sprintf('DATABASE_SYNC_%s_PORT=22', strtoupper($connectionName)),
                sprintf('DATABASE_SYNC_%s_PATH=/var/www/html', strtoupper($connectionName)),
                sprintf('DATABASE_SYNC_%s_KEY=%%kernel.project_dir%%/.ssh/id_rsa', strtoupper($connectionName)),
                '# or',
                sprintf('DATABASE_SYNC_%s_PASSWORD=your-password', strtoupper($connectionName))
            ]);
            return Command::FAILURE;
        }

        // Validate required options
        $missingOptions = $this->validateOptions($options);
        if (!empty($missingOptions)) {
            $this->io->error([
                sprintf('Missing required configuration for connection "%s":', $connectionName),
                'Please check the following environment variables:',
                ...$missingOptions,
                '',
                'Make sure all required variables are set in your .env.local file.'
            ]);
            return Command::FAILURE;
        }

        $this->io->title('Database Sync Configuration');
        $this->io->warning([
            'This command will:',
            sprintf('1. Connect to %s via SSH', $options['host']),
            sprintf('2. Create a database dump on %s', $options['host']),
            '3. Import the dump into your local database',
            '',
            'Your local database will be overwritten!'
        ]);

        // Show configuration summary
        $this->showConfigurationSummary($connectionName, $options);

        // Ask for confirmation
        if (!$this->io->confirm('Do you want to proceed with the database sync?', false)) {
            $this->io->warning('Operation cancelled by user.');
            return Command::SUCCESS;
        }

        $this->io->section('Starting Database Sync');

        // Create SSH command
        $sshCommand = sprintf(
            'ssh -p %s %s@%s',
            $options['port'],
            $options['user'],
            $options['host']
        );

        if (isset($options['key']) && $options['key']) {
            $sshCommand .= sprintf(' -i %s', $options['key']);
        }

        // Create remote dump command
        $remoteCommand = sprintf(
            'cd %s && bin/console system:dump',
            $options['remote_path']
        );

        // Create local import command
        $localCommand = 'bin/console system:restore';

        // Combine commands
        $command = sprintf('%s "%s" | %s', $sshCommand, $remoteCommand, $localCommand);

        $this->io->text([
            'Executing sync command...',
            sprintf('• Remote server: %s', $options['host']),
            sprintf('• Remote path: %s', $options['remote_path']),
            sprintf('• Authentication: %s', isset($options['password']) ? 'Password' : 'SSH Key'),
            ''
        ]);

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(3600); // 1 hour timeout

        if (isset($options['password']) && $options['password']) {
            $process->setInput($options['password']);
        }

        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->io->error($buffer);
            } else {
                $this->io->text($buffer);
            }
        });

        if (!$process->isSuccessful()) {
            $this->io->error([
                'Database sync failed!',
                '',
                'Common issues:',
                '• SSH connection failed (check host, port, and authentication)',
                '• Remote path is incorrect',
                '• Missing permissions on remote server',
                '• Insufficient disk space',
                '',
                'Check the error message above for more details.'
            ]);
            return Command::FAILURE;
        }

        $this->io->success([
            'Database sync completed successfully!',
            '',
            'Your local database has been updated with the data from:',
            sprintf('• %s (%s)', $options['host'], $connectionName)
        ]);
        return Command::SUCCESS;
    }

    private function validateOptions(array $options): array
    {
        $missingOptions = [];
        $requiredOptions = ['host', 'user', 'port', 'remote_path'];

        foreach ($requiredOptions as $option) {
            if (!isset($options[$option]) || empty($options[$option])) {
                $missingOptions[] = sprintf('DATABASE_SYNC_*_%s', strtoupper($option));
            }
        }

        if (!isset($options['key']) && !isset($options['password'])) {
            $missingOptions[] = 'Either DATABASE_SYNC_*_KEY or DATABASE_SYNC_*_PASSWORD must be set';
        }

        return $missingOptions;
    }

    private function showConfigurationSummary(string $connectionName, array $options): void
    {
        $this->io->section(sprintf('Configuration Summary for "%s"', $connectionName));
        $this->io->table(
            ['Option', 'Value'],
            [
                ['Host', $options['host']],
                ['User', $options['user']],
                ['Port', $options['port']],
                ['Remote Path', $options['remote_path']],
                ['SSH Key', isset($options['key']) ? $options['key'] : 'Not provided'],
                ['Authentication', isset($options['password']) && $options['password'] ? 'Password' : (isset($options['key']) && $options['key'] ? 'SSH Key' : 'None')],
            ]
        );
    }
}