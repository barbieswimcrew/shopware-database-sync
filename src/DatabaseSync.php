<?php declare(strict_types=1);

namespace AtticConcepts\DatabaseSync;

use AtticConcepts\DatabaseSync\DependencyInjection\DatabaseSyncExtension;
use Shopware\Core\Framework\Plugin;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class DatabaseSync extends Plugin
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DatabaseSyncExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/Resources/config'));
        $loader->load('services.yaml');
    }
}