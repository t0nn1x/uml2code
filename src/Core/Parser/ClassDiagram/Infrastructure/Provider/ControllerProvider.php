<?php

namespace App\Core\Parser\ClassDiagram\Infrastructure\Provider;

use App\Controller\UmlApiController;
use App\Controller\UmlParserPageController;
use App\Core\Parser\ClassDiagram\Application\Service\UmlParserService;
use App\Core\Parser\ClassDiagram\Presentation\Controller\ApiController;
use App\Core\Parser\ClassDiagram\Presentation\Controller\WebController;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Provider for registering controllers with Symfony's container
 */
class ControllerProvider
{
    /**
     * Register controllers with the container
     *
     * @param ContainerBuilder $container The service container
     */
    public function register(ContainerBuilder $container): void
    {
        // Register API controller
        $apiControllerDef = new Definition(ApiController::class);
        $apiControllerDef->setAutowired(true);
        $apiControllerDef->setAutoconfigured(true);
        $apiControllerDef->addTag('controller.service_arguments');
        $apiControllerDef->setArgument('$parserService', new Reference(UmlParserService::class));
        $container->setDefinition(ApiController::class, $apiControllerDef);

        // Create alias for the traditional controller location (App\Controller namespace)
        $container->setAlias(UmlApiController::class, ApiController::class);

        // Register web controller
        $webControllerDef = new Definition(WebController::class);
        $webControllerDef->setAutowired(true);
        $webControllerDef->setAutoconfigured(true);
        $webControllerDef->addTag('controller.service_arguments');
        $container->setDefinition(WebController::class, $webControllerDef);

        // Create alias for the traditional controller location (App\Controller namespace)
        $container->setAlias(UmlParserPageController::class, WebController::class);
    }
}
