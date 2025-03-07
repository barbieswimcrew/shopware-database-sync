<?php declare(strict_types=1);

namespace AtticConcepts\DatabaseSync\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

    protected function configure(): void
    {
        $this
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'SSH host')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'SSH username')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'SSH port', '22')
            ->addOption('remote-path', null, InputOption::VALUE_REQUIRED, 'Remote Shopware root path')
            ->addOption('key', null, InputOption::VALUE_OPTIONAL, 'SSH private key path')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'SSH password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        // Validate required options
        $this->validateRequiredOptions($input);

        // Get and validate all options
        $options = $this->getValidatedOptions($input);

        // Show configuration summary
        $this->showConfigurationSummary($options);

        // Ask for confirmation
        if (!$this->io->confirm('Do you want to proceed with the database sync?', false)) {
            $this->io->warning('Operation cancelled by user.');
            return Command::SUCCESS;
        }

        $this->io->title('Starting database sync...');

        // Create SSH command
        $sshCommand = sprintf(
            'ssh -p %s %s@%s',
            $options['port'],
            $options['user'],
            $options['host']
        );

        if ($options['key']) {
            $sshCommand .= sprintf(' -i %s', $options['key']);
        }

        // Create remote dump command
        $remoteCommand = sprintf(
            'cd %s && bin/console system:dump',
            $options['remote-path']
        );

        // Create local import command
        $localCommand = 'bin/console system:restore';

        // Combine commands
        $command = sprintf('%s "%s" | %s', $sshCommand, $remoteCommand, $localCommand);

        $this->io->text('Executing sync command...');

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(3600); // 1 hour timeout

        if ($options['password']) {
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
            $this->io->error('Database sync failed!');
            return Command::FAILURE;
        }

        $this->io->success('Database sync completed successfully!');
        return Command::SUCCESS;
    }

    private function validateRequiredOptions(InputInterface $input): void
    {
        $requiredOptions = ['host', 'user', 'remote-path'];
        $missingOptions = [];

        foreach ($requiredOptions as $option) {
            if (!$input->getOption($option)) {
                $missingOptions[] = $option;
            }
        }

        if (!empty($missingOptions)) {
            throw new \RuntimeException(sprintf(
                'Missing required options: %s',
                implode(', ', $missingOptions)
            ));
        }
    }

    private function getValidatedOptions(InputInterface $input): array
    {
        $options = [
            'host' => $input->getOption('host'),
            'user' => $input->getOption('user'),
            'port' => $input->getOption('port'),
            'remote-path' => $input->getOption('remote-path'),
            'key' => $input->getOption('key'),
            'password' => $input->getOption('password')
        ];

        // Validate port number
        if (!is_numeric($options['port']) || $options['port'] < 1 || $options['port'] > 65535) {
            throw new \RuntimeException('Invalid port number. Port must be between 1 and 65535.');
        }

        // Validate remote path
        if (!preg_match('/^[a-zA-Z0-9\/\-_\.]+$/', $options['remote-path'])) {
            throw new \RuntimeException('Invalid remote path. Path contains invalid characters.');
        }

        // Validate SSH key path if provided
        if ($options['key'] && !file_exists($options['key'])) {
            throw new \RuntimeException(sprintf('SSH key file not found: %s', $options['key']));
        }

        return $options;
    }

    private function showConfigurationSummary(array $options): void
    {
        $this->io->section('Configuration Summary');
        $this->io->table(
            ['Option', 'Value'],
            [
                ['Host', $options['host']],
                ['User', $options['user']],
                ['Port', $options['port']],
                ['Remote Path', $options['remote-path']],
                ['SSH Key', $options['key'] ?: 'Not provided'],
                ['Authentication', $options['password'] ? 'Password' : ($options['key'] ? 'SSH Key' : 'None')],
            ]
        );
    }
}