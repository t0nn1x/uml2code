<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle for the Code Generator component
 */
class GeneratorBundle extends Bundle
{
    /**
     * @inheritDoc
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }
} 
