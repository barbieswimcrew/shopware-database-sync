<?php declare(strict_types=1);

namespace AtticConcepts\DatabaseSync\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('database_sync');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->arrayNode('connections')
            ->arrayPrototype()
            ->children()
            ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('user')->isRequired()->cannotBeEmpty()->end()
            ->integerNode('port')->defaultValue(22)->min(1)->max(65535)->end()
            ->scalarNode('remote_path')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('key')->defaultNull()->end()
            ->scalarNode('password')->defaultNull()->end()
            ->end()
            ->validate()
            ->ifTrue(function ($v) {
                return empty($v['key']) && empty($v['password']);
            })
            ->thenInvalid('Either key or password must be set')
            ->end()
            ->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}