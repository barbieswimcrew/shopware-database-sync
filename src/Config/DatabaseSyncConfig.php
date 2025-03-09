<?php declare(strict_types=1);

namespace AtticConcepts\DatabaseSync\Config;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DatabaseSyncConfig
{
    private array $config = [];

    public function __construct(private readonly ParameterBagInterface $parameterBag)
    {
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        // Production configuration
        $this->config['production'] = [
            'host' => $this->getEnvOrNull('DATABASE_SYNC_PROD_HOST'),
            'user' => $this->getEnvOrNull('DATABASE_SYNC_PROD_USER'),
            'port' => (int) ($this->getEnvOrNull('DATABASE_SYNC_PROD_PORT') ?? 22),
            'remote_path' => $this->getEnvOrNull('DATABASE_SYNC_PROD_PATH'),
        ];

        // Staging configuration
        $this->config['staging'] = [
            'host' => $this->getEnvOrNull('DATABASE_SYNC_STAGING_HOST'),
            'user' => $this->getEnvOrNull('DATABASE_SYNC_STAGING_USER'),
            'port' => (int) ($this->getEnvOrNull('DATABASE_SYNC_STAGING_PORT') ?? 22),
            'remote_path' => $this->getEnvOrNull('DATABASE_SYNC_STAGING_PATH'),
        ];
    }

    private function getEnvOrNull(string $name): ?string
    {
        return $_ENV[$name] ?? $_SERVER[$name] ?? null;
    }

    public function getConnections(): array
    {
        return $this->config;
    }

    public function getConnection(string $name): ?array
    {
        return $this->config[$name] ?? null;
    }
}