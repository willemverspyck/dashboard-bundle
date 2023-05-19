<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition) {
    $definition->rootNode()
        ->children()
            ->arrayNode('chart')
                ->children()
                    ->scalarNode('command')->isRequired()->end()
                    ->scalarNode('directory')->isRequired()->end()
                ->end()
            ->end()
        ->end()
        ->children()
            ->arrayNode('mailer')
                ->children()
                    ->arrayNode('from')
                        ->children()
                            ->scalarNode('email')->isRequired()->end()
                            ->scalarNode('name')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
};