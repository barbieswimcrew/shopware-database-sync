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
        $allowedConnections = ['production', 'staging'];

        $connectionName = $input->getArgument('connection');
        if (!$connectionName) {
            $this->io->title('Database Synchronization');
            $this->io->text([
                'This command will synchronize your local database with a remote instance.',
                'Please choose the desired connection:',
                '',
                '- production: For synchronization with the live environment',
                '- staging: For synchronization with the test environment',
            ]);

            $connectionName = $this->io->choice(
                'Connection',
                $allowedConnections,
                'production'
            );
        } elseif (!in_array($connectionName, $allowedConnections, true)) {
            $this->io->error(sprintf(
                'Invalid connection "%s". Allowed values are: "%s"',
                $connectionName,
                implode('" or "', $allowedConnections)
            ));
            return Command::FAILURE;
        }

        $this->io->text(sprintf('Selected connection: <info>%s</info>', $connectionName));

        // Load connection parameters
        $options = $this->config->getConnection($connectionName);

        $this->io->section('Connection Details');
        $this->io->table(
            ['Parameter', 'Value'],
            [
                ['Host', $options['host'] ?? 'Not configured'],
                ['User', $options['user'] ?? 'Not configured'],
                ['Port', $options['port'] ?? 'Not configured'],
                ['Remote Path', $options['remote_path'] ?? 'Not configured'],
            ]
        );

        // Validate connection parameters
        $missingOptions = $this->validateOptions($options);
        if (!empty($missingOptions)) {
            $this->io->error([
                'Missing configuration parameters:',
                ...$missingOptions
            ]);
            return Command::FAILURE;
        }

        try {
            // Create SSH connection and execute pwd
            $this->io->section('Testing SSH Connection');

            $sshCommand = sprintf(
                'ssh -p %d %s@%s',
                $options['port'],
                $options['user'],
                $options['host']
            );

            // Execute pwd command
            $command = sprintf('%s "pwd"', $sshCommand);

            $process = Process::fromShellCommandline($command);
            $process->setTimeout(30);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException(sprintf(
                    'Error connecting to remote server: %s',
                    $process->getErrorOutput()
                ));
            }

            $this->io->success(sprintf('Current remote directory: %s', trim($process->getOutput())));
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
            return Command::FAILURE;
        }
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
            ]
        );
    }
}