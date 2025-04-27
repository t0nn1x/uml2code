<?php

namespace App\Service;

use App\Core\Parser\Exception\ParserException;
use App\Core\Parser\Models\ClassDiagram;
use App\Core\Parser\PlantUmlParser;

/**
 * Service for parsing UML diagrams
 */
class UmlParserService
{
    /**
     * @var PlantUmlParser
     */
    private $parser;

    /**
     * @param PlantUmlParser $parser
     */
    public function __construct(PlantUmlParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Parse PlantUML text into a diagram model
     *
     * @param string $plantUmlText
     * @return mixed The parsed diagram model
     * @throws ParserException
     */
    public function parseUml(string $plantUmlText)
    {
        return $this->parser->parse($plantUmlText);
    }

    /**
     * Parse a class diagram
     *
     * @param string $plantUmlText
     * @return ClassDiagram
     * @throws ParserException
     */
    public function parseClassDiagram(string $plantUmlText): ClassDiagram
    {
        $diagram = $this->parser->parse($plantUmlText);

        if (!$diagram instanceof ClassDiagram) {
            throw new ParserException('Expected a class diagram but got a different type');
        }

        return $diagram;
    }

    /**
     * Validate PlantUML syntax without fully parsing it
     *
     * @param string $plantUmlText
     * @return bool
     */
    public function validateSyntax(string $plantUmlText): bool
    {
        try {
            // Check for basic syntax structures
            if (!preg_match('/@startuml.*@enduml/s', $plantUmlText)) {
                return false;
            }

            // More validation could be added here

            // Attempt to parse (simplified approach)
            $this->parser->parse($plantUmlText);

            return true;
        } catch (ParserException $e) {
            return false;
        }
    }

    /**
     * Extract metadata from a UML diagram
     *
     * @param string $plantUmlText
     * @return array
     */
    public function extractMetadata(string $plantUmlText): array
    {
        $metadata = [
            'title' => null,
            'author' => null,
            'date' => null,
            'version' => null,
            'description' => null,
        ];

        // Extract title
        if (preg_match('/title\s+(.+)/', $plantUmlText, $matches)) {
            $metadata['title'] = trim($matches[1]);
        }

        // Extract note and footer information
        if (preg_match('/header\s+(.+)/s', $plantUmlText, $matches)) {
            $header = trim($matches[1]);

            // Try to extract author, date, etc. from header
            if (preg_match('/author:\s*(.+)/i', $header, $authorMatches)) {
                $metadata['author'] = trim($authorMatches[1]);
            }

            if (preg_match('/date:\s*(.+)/i', $header, $dateMatches)) {
                $metadata['date'] = trim($dateMatches[1]);
            }

            if (preg_match('/version:\s*(.+)/i', $header, $versionMatches)) {
                $metadata['version'] = trim($versionMatches[1]);
            }
        }

        // Extract description from notes
        if (preg_match('/note\s+(?:left|right)(?:\s+of\s+[A-Za-z0-9_]+)?\s*:\s*(.+?)\s*(?:end\s+note|\n\n)/s', $plantUmlText, $matches)) {
            $metadata['description'] = trim($matches[1]);
        }

        return $metadata;
    }
}
