<?php declare(strict_types=1);

namespace AtticConcepts\DatabaseSync\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class DatabaseSyncCommand extends Command
{
    protected static $defaultName = 'database:sync';
    protected static $defaultDescription = 'Sync database from remote Shopware instance via SSH';

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

        $host = $input->getOption('host');
        $user = $input->getOption('user');
        $port = $input->getOption('port');
        $remotePath = $input->getOption('remote-path');
        $key = $input->getOption('key');
        $password = $input->getOption('password');

        $this->io->title('Starting database sync...');

        // Create SSH command
        $sshCommand = sprintf(
            'ssh -p %s %s@%s',
            $port,
            $user,
            $host
        );

        if ($key) {
            $sshCommand .= sprintf(' -i %s', $key);
        }

        // Create remote dump command
        $remoteCommand = sprintf(
            'cd %s && bin/console system:dump',
            $remotePath
        );

        // Create local import command
        $localCommand = 'bin/console system:restore';

        // Combine commands
        $command = sprintf('%s "%s" | %s', $sshCommand, $remoteCommand, $localCommand);

        $this->io->text('Executing sync command...');

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(3600); // 1 hour timeout

        if ($password) {
            $process->setInput($password);
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
}