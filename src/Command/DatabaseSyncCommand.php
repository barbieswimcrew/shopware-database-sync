<?php

declare(strict_types=1);

namespace Barbieswimcrew\DatabaseSync\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'database:sync',
    description: 'Synchronizes a database from a remote server to localhost',
)]
class DatabaseSyncCommand extends Command
{

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);


        $helper = $this->getHelper('question');

        // Ask for SSH credentials
        $host = $helper->ask($input, $output, new Question('Please enter the remote host: '));
        $user = $helper->ask($input, $output, new Question('Please enter the SSH username: '));
        $port = $helper->ask($input, $output, new Question('Please enter the SSH port [22]: ', '22'));
        $remotePath = $helper->ask($input, $output, new Question('Please enter the remote path [/var/www/html]: ', '/var/www/html'));

        if (!$host || !$user || !$remotePath) {
            $io->error('All credentials and the remote path are required');
            return Command::FAILURE;
        }

        try {

            // Get remote dump path
            $io->info('Connecting to remote server and trying to dump the database...');

            $dumpFileName = 'remote_' . date('Y-m-d_H-i-s') . '.sql.gz';
            $localDumpPath = sprintf('%s/var/dump/%s', getcwd(), $dumpFileName);

            // Ensure dump directory exists
            if (!is_dir(dirname($localDumpPath))) {
                mkdir(dirname($localDumpPath), 0777, true);
            }

            // First, create the dump on the remote server and get its path
            $dumpCommand = sprintf(
                'ssh -p %s %s@%s "source ~/.profile && cd %s && bin/console database:dump --path-only"',
                $port,
                $user,
                $host,
                $remotePath
            );

            $process = Process::fromShellCommandline($dumpCommand);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }

            $io->info('Downloading the database dump from the remote server...');
            $remoteDumpPath = trim($process->getOutput());

            // Now download the actual dump file using SCP
            $downloadCommand = sprintf(
                'scp -P %s %s@%s:%s %s',
                $port,
                $user,
                $host,
                $remoteDumpPath,
                $localDumpPath
            );

            $process = Process::fromShellCommandline($downloadCommand);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }

            $io->success(sprintf('Database dump successfully downloaded to %s', $localDumpPath));
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}