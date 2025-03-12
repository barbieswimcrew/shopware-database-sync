<?php declare(strict_types=1);

namespace Barbieswimcrew\DatabaseSync\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'database:dump',
    description: 'Create a database dump',
)]
class DatabaseDumpCommand extends Command
{
    private SymfonyStyle $io;

    protected function configure(): void
    {
        $this
            ->addOption('path-only', null, InputOption::VALUE_NONE, 'Only output the path to the dump file')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command creates a database dump:

  <info>%command.full_name%</info>

You can also only get the path to the dump file:

  <info>%command.full_name% --path-only</info>

HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $pathOnly = $input->getOption('path-only');

        if (!$pathOnly) {
            $this->io->title('Creating Database Dump');
        }

        try {
            // Create dump directory if it doesn't exist
            $dumpDir = getcwd() . '/var/dump';
            if (!is_dir($dumpDir)) {
                mkdir($dumpDir, 0777, true);
            }

            // Create dump file with .gz extension
            $dumpFile = sprintf('%s/dump_%s.sql.gz', $dumpDir, date('Y-m-d_H-i-s'));

            // Get database configuration from .env
            $dbUrl = $_SERVER['DATABASE_URL'] ?? null;
            if (!$dbUrl) {
                throw new \RuntimeException('DATABASE_URL is not configured');
            }

            // Parse DATABASE_URL
            $dbConfig = parse_url($dbUrl);
            if ($dbConfig === false) {
                throw new \RuntimeException('Could not parse DATABASE_URL');
            }

            // Create mysqldump command
            $command = sprintf(
                'mysqldump ' .
                '--default-character-set=utf8mb4 ' .
                '--host=%s ' .
                '--port=%s ' .
                '--user=%s ' .
                '--password=%s ' .
                '%s ' .  // Database name without --databases
                '--lock-tables=false ' .
                '--no-create-db ' .
                '--verbose ' .
                '--log-error=/tmp/mysqldump_error.log ' .
                '| gzip > %s',
                $dbConfig['host'],
                $dbConfig['port'] ?? '3306',
                $dbConfig['user'],
                urldecode($dbConfig['pass']),
                trim($dbConfig['path'], '/'),
                $dumpFile
            );

            // Execute mysqldump with shell
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(3600);
            $process->setEnv(['LANG' => 'en_US.UTF-8']);
            $process->run(function ($type, $buffer) use ($output, $pathOnly) {
                if (!$pathOnly && Process::ERR === $type) {
                    $output->writeln(sprintf('<error>%s</error>', $buffer));
                }
            });

            if (!$process->isSuccessful()) {
                throw new \RuntimeException(sprintf(
                    'Error creating dump: %s (Command: %s)',
                    $process->getErrorOutput(),
                    $command
                ));
            }

            // Überprüfe die Dateigröße
            if (filesize($dumpFile) === 0) {
                throw new \RuntimeException('Dump file was created but is empty. Check /tmp/mysqldump_error.log for details.');
            }

            if ($pathOnly) {
                $output->write($dumpFile);
            } else {
                $this->io->success(sprintf('Database dump created: %s', $dumpFile));
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            if (!$pathOnly) {
                $this->io->error($e->getMessage());
            }
            return Command::FAILURE;
        }
    }
}