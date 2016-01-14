<?php

namespace Majora\Bundle\GeneratorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass to guess all content modifiers for generator.
 */
class ContentModifiersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('majora.generator')) {
            return;
        }

        $generator   = $container->getDefinition('majora.generator');
        $modifierDef = $container->findTaggedServiceIds('majora.generator.content_modifier');

        foreach ($modifierDef as $id => $attributes) {
            if (!empty($attributes[0]['alias'])) {
                $generator->addMethodCall('registerContentModifier', array(
                    $attributes[0]['alias'],
                    new Reference($id),
                ));
            }
        }
    }
}
