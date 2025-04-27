<?php

namespace App\Core\Parser;

use App\Core\Parser\Exception\ParserException;

/**
 * PlantUML Parser
 * 
 * Main entry point for parsing PlantUML text and converting it to a structured format
 */
class PlantUmlParser implements ParserInterface
{
    /**
     * @var DiagramTypeDetector
     */
    private $typeDetector;

    /**
     * @var DiagramParserFactory
     */
    private $parserFactory;

    public function __construct(DiagramTypeDetector $typeDetector, DiagramParserFactory $parserFactory)
    {
        $this->typeDetector = $typeDetector;
        $this->parserFactory = $parserFactory;
    }

    /**
     * Parse PlantUML text into a structured diagram model
     *
     * @param string $plantUmlText The PlantUML text to parse
     * @return mixed Returns a diagram model (ClassDiagram, SequenceDiagram, etc.)
     * @throws ParserException If parsing fails
     */
    public function parse(string $plantUmlText)
    {
        // Normalize line endings
        $plantUmlText = str_replace(["\r\n", "\r"], "\n", $plantUmlText);

        // Detect diagram type
        $diagramType = $this->typeDetector->detectType($plantUmlText);

        // Fallback for diagrams with relationships but no class/interface/enum keywords
        if ($diagramType === DiagramTypeDetector::TYPE_UNKNOWN) {
            // Check for common relationship patterns
            if (preg_match('/[A-Za-z0-9_]+\s+(?:--|->|<-|<--|-->|o--|<\|--|<-\.\.|\.\.-|\.\.>|--\*|\*--)\s+[A-Za-z0-9_]+/', $plantUmlText)) {
                $diagramType = DiagramTypeDetector::TYPE_CLASS;
            }
        }

        // Get appropriate parser and parse the diagram
        $parser = $this->parserFactory->createParser($diagramType);
        return $parser->parse($plantUmlText);
    }
} 
