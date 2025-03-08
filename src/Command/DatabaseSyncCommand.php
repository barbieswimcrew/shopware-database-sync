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
            $this->io->title('Datenbank Synchronisation');
            $this->io->text([
                'Dieser Befehl wird Ihre lokale Datenbank mit einer Remote-Instanz synchronisieren.',
                'Bitte wählen Sie die gewünschte Verbindung:',
                '',
                '- production: Für die Synchronisation mit der Live-Umgebung',
                '- staging: Für die Synchronisation mit der Test-Umgebung',
            ]);

            $connectionName = $this->io->choice(
                'Verbindung',
                $allowedConnections,
                'production'
            );
        } elseif (!in_array($connectionName, $allowedConnections, true)) {
            $this->io->error(sprintf(
                'Ungültige Verbindung "%s". Erlaubte Werte sind: "%s"',
                $connectionName,
                implode('" oder "', $allowedConnections)
            ));
            return Command::FAILURE;
        }

        $this->io->text(sprintf('Gewählte Verbindung: <info>%s</info>', $connectionName));

        // Lade die Verbindungsparameter
        $options = $this->config->getConnection($connectionName);

        $this->io->section('Verbindungsdetails');
        $this->io->table(
            ['Parameter', 'Wert'],
            [
                ['Host', $options['host'] ?? 'Nicht konfiguriert'],
                ['User', $options['user'] ?? 'Nicht konfiguriert'],
                ['Port', $options['port'] ?? 'Nicht konfiguriert'],
                ['Remote Path', $options['remote_path'] ?? 'Nicht konfiguriert'],
                ['SSH Key', $options['key'] ?? 'Nicht konfiguriert'],
                ['Password', isset($options['password']) && $options['password'] ? 'Konfiguriert' : 'Nicht konfiguriert'],
            ]
        );

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