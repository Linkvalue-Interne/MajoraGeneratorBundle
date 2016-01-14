<?php

namespace Majora\GeneratorBundle;

use Majora\GeneratorBundle\DependencyInjection\Compiler\ContentModifiersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MajoraGeneratorBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ContentModifiersPass());
    }
}
