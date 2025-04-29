<?php

namespace App\Core\Parser\ClassDiagram\Application\Service;

use App\Core\Parser\ClassDiagram\Domain\Model\ClassDiagram;
use App\Core\Parser\ClassDiagram\Domain\Exception\ParserException;

/**
 * Interface for class diagram parsers
 */
interface ClassDiagramParserInterface
{
    /**
     * Parse UML content into a class diagram model
     *
     * @param string $content The UML content to parse
     * @return ClassDiagram The parsed class diagram
     * @throws ParserException If parsing fails
     */
    public function parse(string $content): ClassDiagram;

    /**
     * Validate UML syntax without fully parsing it
     *
     * @param string $content The UML content to validate
     * @return bool True if the syntax is valid
     */
    public function validate(string $content): bool;

    /**
     * Extract basic metadata from UML content
     *
     * @param string $content The UML content to analyze
     * @return array Metadata about the content
     */
    public function extractMetadata(string $content): array;
}
