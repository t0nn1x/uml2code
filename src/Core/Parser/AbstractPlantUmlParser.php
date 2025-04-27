<?php

namespace App\Core\Parser;

use App\Core\Parser\Exception\ParserException;

/**
 * Abstract base class for PlantUML parsers
 */
abstract class AbstractPlantUmlParser implements DiagramParserInterface
{
    /**
     * @var DiagramTypeDetector
     */
    protected $typeDetector;

    public function __construct(DiagramTypeDetector $typeDetector)
    {
        $this->typeDetector = $typeDetector;
    }

    /**
     * Clean the input by removing comments and normalizing whitespace
     *
     * @param string $input
     * @return string
     */
    protected function cleanInput(string $input): string
    {
        // Remove single-line comments that don't start with @
        $input = preg_replace('/(?<!@)\'[^\n]*\n/', "\n", $input);

        // Convert tabs to spaces and normalize line endings
        $input = str_replace(["\t", "\r\n", "\r"], [' ', "\n", "\n"], $input);

        // Normalize spaces around braces and colons
        $input = preg_replace('/\s*([{}:])\s*/', ' $1 ', $input);

        return $input;
    }

    /**
     * Extract content between @startuml and @enduml tags
     *
     * @param string $plantUmlText
     * @return string
     * @throws ParserException
     */
    protected function extractDiagramContent(string $plantUmlText): string
    {
        preg_match('/\s*@startuml\s*(.+?)\s*@enduml\s*/s', $plantUmlText, $matches);
        if (empty($matches[1])) {
            throw new ParserException("Invalid PlantUML: missing @startuml/@enduml tags");
        }
        return $matches[1];
    }

    /**
     * Extract diagram title if present
     *
     * @param string $content
     * @return array [title, content without title]
     */
    protected function extractTitle(string $content): array
    {
        $title = null;
        if (preg_match('/^title\s+(.*?)$/m', $content, $titleMatches)) {
            $title = trim($titleMatches[1]);
            // Remove the title line completely from content
            $content = preg_replace('/^title\s+.*?$/m', '', $content);
        }
        return [$title, $content];
    }
} 
