<?php

namespace App\Core\Parser\ClassDiagram\Infrastructure;

use App\Core\Parser\ClassDiagram\Infrastructure\Provider\ControllerProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle class for the Class Diagram Parser module
 */
class ParserBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Register controllers
        $controllerProvider = new ControllerProvider();
        $controllerProvider->register($container);
    }
}
