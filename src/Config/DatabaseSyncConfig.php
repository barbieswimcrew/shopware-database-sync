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
        if ($this->hasProductionConfig()) {
            $this->config['production'] = [
                'host' => $this->parameterBag->get('env(DATABASE_SYNC_PROD_HOST)'),
                'user' => $this->parameterBag->get('env(DATABASE_SYNC_PROD_USER)'),
                'port' => (int) $this->parameterBag->get('env(DATABASE_SYNC_PROD_PORT)'),
                'remote_path' => $this->parameterBag->get('env(DATABASE_SYNC_PROD_PATH)'),
                'key' => $this->parameterBag->get('env(DATABASE_SYNC_PROD_KEY)'),
            ];
        }

        // Staging configuration
        if ($this->hasStagingConfig()) {
            $this->config['staging'] = [
                'host' => $this->parameterBag->get('env(DATABASE_SYNC_STAGING_HOST)'),
                'user' => $this->parameterBag->get('env(DATABASE_SYNC_STAGING_USER)'),
                'port' => (int) $this->parameterBag->get('env(DATABASE_SYNC_STAGING_PORT)'),
                'remote_path' => $this->parameterBag->get('env(DATABASE_SYNC_STAGING_PATH)'),
                'password' => $this->parameterBag->get('env(DATABASE_SYNC_STAGING_PASSWORD)'),
            ];
        }
    }

    private function hasProductionConfig(): bool
    {
        return $this->parameterBag->has('env(DATABASE_SYNC_PROD_HOST)')
            && $this->parameterBag->has('env(DATABASE_SYNC_PROD_USER)')
            && $this->parameterBag->has('env(DATABASE_SYNC_PROD_PORT)')
            && $this->parameterBag->has('env(DATABASE_SYNC_PROD_PATH)')
            && $this->parameterBag->has('env(DATABASE_SYNC_PROD_KEY)');
    }

    private function hasStagingConfig(): bool
    {
        return $this->parameterBag->has('env(DATABASE_SYNC_STAGING_HOST)')
            && $this->parameterBag->has('env(DATABASE_SYNC_STAGING_USER)')
            && $this->parameterBag->has('env(DATABASE_SYNC_STAGING_PORT)')
            && $this->parameterBag->has('env(DATABASE_SYNC_STAGING_PATH)')
            && $this->parameterBag->has('env(DATABASE_SYNC_STAGING_PASSWORD)');
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