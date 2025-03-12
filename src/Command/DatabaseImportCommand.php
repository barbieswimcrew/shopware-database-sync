<?php declare(strict_types=1);

namespace Barbieswimcrew\DatabaseSync\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'database:import',
    description: 'Import a database dump from var/dump directory',
)]
class DatabaseImportCommand extends Command
{
    private const string MYSQL_CONFIG_PATH = '/tmp/mysql-import.cnf';
    private SymfonyStyle $io;

    public function __construct(
        private readonly Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        try {
            $dumpFile = $this->selectDumpFile();
            if ($dumpFile === null) {
                $this->io->error('No database dumps found in var/dump directory.');
                return Command::FAILURE;
            }

            if ($this->shouldImportDump()) {
                $this->importDatabaseDump($dumpFile);
            } else {
                $this->io->note('Database import skipped by user.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function selectDumpFile(): ?string
    {
        $finder = new Finder();
        $dumpDir = dirname(__DIR__, 5) . '/var/dump';

        if (!is_dir($dumpDir)) {
            mkdir($dumpDir, 0777, true);
            return null;
        }

        $finder->files()
            ->in($dumpDir)
            ->name('*.sql.gz')
            ->sortByModifiedTime();

        if (!$finder->hasResults()) {
            return null;
        }

        $files = iterator_to_array($finder, false);
        $choices = [];

        foreach ($files as $i => $file) {
            $modified = date('Y-m-d H:i:s', $file->getMTime());
            $size = $this->getFormattedFileSize($file->getRealPath());
            $choices[] = sprintf(
                '[%d] %s (Modified: %s, Size: %s)',
                $i,
                $file->getFilename(),
                $modified,
                $size
            );
        }

        $selectedIndex = array_search(
            $this->io->choice(
                'Please select a dump file to import',
                $choices,
                '0'
            ),
            $choices
        );

        if ($selectedIndex === false || !isset($files[$selectedIndex])) {
            return null;
        }

        return $files[$selectedIndex]->getRealPath();
    }

    private function shouldImportDump(): bool
    {
        $this->io->note([
            'Importing the dump will REPLACE your current local database!',
            'Make sure you have a backup if needed.'
        ]);

        return $this->io->confirm('Do you want to import the database dump now?', false);
    }

    private function importDatabaseDump(string $dumpFile): void
    {
        $this->io->section('Importing Database Dump');
        $this->io->text('This may take a while depending on the database size...');

        try {
            $params = $this->connection->getParams();

            // Create temporary MySQL config file
            $config = sprintf(
                "[client]\nhost = %s\nuser = %s\npassword = %s\nport = %d\n",
                $params['host'] ?? 'localhost',
                $params['user'] ?? '',
                $params['password'] ?? '',
                $params['port'] ?? 3306
            );

            if (file_put_contents(self::MYSQL_CONFIG_PATH, $config) === false) {
                throw new \RuntimeException('Failed to create MySQL configuration file');
            }
            chmod(self::MYSQL_CONFIG_PATH, 0600);

            $importCommand = sprintf(
                'zcat %s | mysql --defaults-file=%s --binary-mode %s',
                $dumpFile,
                self::MYSQL_CONFIG_PATH,
                $params['dbname']
            );

            $process = Process::fromShellCommandline($importCommand);
            $process->setTimeout(3600);
            $process->run(function ($type, $buffer) {
                $this->io->write($buffer);
            });

            if (!$process->isSuccessful()) {
                throw new \RuntimeException(sprintf(
                    "Import failed:\nExit Code: %d\nError: %s",
                    $process->getExitCode(),
                    $process->getErrorOutput()
                ));
            }

            if (file_exists(self::MYSQL_CONFIG_PATH)) {
                unlink(self::MYSQL_CONFIG_PATH);
            }

            $this->io->success([
                'Database imported successfully!',
                sprintf('Database %s has been completely replaced with the new dump.', $params['dbname'])
            ]);
        } catch (\Exception $e) {
            if (file_exists(self::MYSQL_CONFIG_PATH)) {
                unlink(self::MYSQL_CONFIG_PATH);
            }
            throw $e;
        }
    }

    private function getFormattedFileSize(string $filePath): string
    {
        $bytes = filesize($filePath);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return sprintf('%.2f %s', $bytes, $units[$pow]);
    }
}