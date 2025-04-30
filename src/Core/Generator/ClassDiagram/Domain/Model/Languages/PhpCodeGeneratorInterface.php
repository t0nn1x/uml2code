<?php

namespace App\Core\Generator\ClassDiagram\Domain\Model\Languages;

use App\Core\Generator\ClassDiagram\Domain\Model\LanguageCodeGenerator;

/**
 * Interface for PHP code generators with PHP-specific configuration
 */
interface PhpCodeGeneratorInterface extends LanguageCodeGenerator
{
    /**
     * Set the namespace prefix for generated PHP code
     *
     * @param string $namespacePrefix
     * @return self
     */
    public function setNamespacePrefix(string $namespacePrefix): self;
    
    /**
     * Get the namespace prefix for generated PHP code
     *
     * @return string
     */
    public function getNamespacePrefix(): string;
} 
