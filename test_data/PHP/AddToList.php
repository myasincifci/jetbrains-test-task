<?php

namespace Symfony\Component\Config\Tests\Builder\Fixtures;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class AddToList implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tb = new TreeBuilder('add_to_list');
        $rootNode = $tb->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('translator')
                    ->fixXmlConfig('fallback')
                    ->fixXmlConfig('source')
                    ->children()
                        ->arrayNode('fallbacks')
                            ->prototype('scalar')->end()
                            ->defaultValue([])
                        ->end()
                        ->arrayNode('sources')
                            ->useAttributeAsKey('source_class')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('messenger')
                    ->children()
                        ->arrayNode('routing')
                            ->normalizeKeys(false)
                            ->useAttributeAsKey('message_class')
                            ->prototype('array')
                                ->performNoDeepMerging()
                                ->fixXmlConfig('sender')
                                ->children()
                                    ->arrayNode('senders')
                                        ->requiresAtLeastOneElement()
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('receiving')
                            ->prototype('array')
                                ->children()
                                    ->integerNode('priority')->end()
                                    ->scalarNode('color')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ;

        return $tb;
    }
}
