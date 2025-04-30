<?php

namespace App\Core\Generator\ClassDiagram\Domain\Model;

/**
 * Interface defining the contract for all code generators
 */
interface CodeGenerator
{
    /**
     * Generate code from the diagram
     *
     * @return CodeFile[] Generated code files
     */
    public function generate(): array;
    
    /**
     * Get all generated files
     *
     * @return CodeFile[]
     */
    public function getFiles(): array;
    
    /**
     * Get files as array representation
     *
     * @return array
     */
    public function getFilesAsArray(): array;
} 
