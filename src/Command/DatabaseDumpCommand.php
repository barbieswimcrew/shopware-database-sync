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
            $this->io->title('Erstelle Datenbank-Dump');
        }

        try {
            // Erstelle Dump-Verzeichnis, falls es nicht existiert
            $dumpDir = getcwd() . '/var/dump';
            if (!is_dir($dumpDir)) {
                mkdir($dumpDir, 0777, true);
            }

            // Erstelle Dump-Datei
            $dumpFile = sprintf('%s/dump_%s.sql', $dumpDir, date('Y-m-d_H-i-s'));

            // Hole Datenbank-Konfiguration aus .env
            $dbUrl = $_SERVER['DATABASE_URL'] ?? null;
            if (!$dbUrl) {
                throw new \RuntimeException('DATABASE_URL is not configured');
            }

            // Parse DATABASE_URL
            $dbConfig = parse_url($dbUrl);
            if ($dbConfig === false) {
                throw new \RuntimeException('DATABASE_URL konnte nicht geparst werden');
            }

            // Erstelle mysqldump Befehl
            $command = sprintf(
                'mysqldump -h%s -u%s -p%s %s > %s',
                $dbConfig['host'],
                $dbConfig['user'],
                urldecode($dbConfig['pass']),
                trim($dbConfig['path'], '/'),
                $dumpFile
            );

            // Führe mysqldump aus
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(3600);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException(sprintf(
                    'Error creating dump: %s',
                    $process->getErrorOutput()
                ));
            }

            if ($pathOnly) {
                $output->write($dumpFile);
            } else {
                $this->io->success(sprintf('Datenbank-Dump wurde erstellt: %s', $dumpFile));
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