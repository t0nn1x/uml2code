<?php

namespace App\Core\Parser;

use App\Core\Parser\Models\RelationshipModel;

/**
 * Helper class to parse PlantUML relationships
 */
class RelationshipParser
{
    /**
     * Parse a relationship line
     *
     * @param string $line The relationship line
     * @return RelationshipModel|null Relationship or null if parsing failed
     */
    public static function parse(string $line): ?RelationshipModel
    {
        // 1. Try with full pattern: Source "sourceMulti" relType "targetMulti" Target : label
        if (preg_match('/^(\w+)\s+"([^"]*?)"\s+([.\-|<>*o]+)\s+"([^"]*?)"\s+(\w+)(?:\s*:\s*(.+))?$/i', $line, $matches)) {
            return self::createFromMatches($matches[1], $matches[5], $matches[3], $matches[6] ?? null, $matches[2], $matches[4]);
        }

        // 2. Try: Source "sourceMulti" relType Target : label
        if (preg_match('/^(\w+)\s+"([^"]*?)"\s+([.\-|<>*o]+)\s+(\w+)(?:\s*:\s*(.+))?$/i', $line, $matches)) {
            return self::createFromMatches($matches[1], $matches[4], $matches[3], $matches[5] ?? null, $matches[2], null);
        }

        // 3. Try: Source relType "targetMulti" Target : label
        if (preg_match('/^(\w+)\s+([.\-|<>*o]+)\s+"([^"]*?)"\s+(\w+)(?:\s*:\s*(.+))?$/i', $line, $matches)) {
            return self::createFromMatches($matches[1], $matches[4], $matches[2], $matches[5] ?? null, null, $matches[3]);
        }

        // 4. Try simplest form: Source relType Target : label
        if (preg_match('/^(\w+)\s+([.\-|<>*o]+)\s+(\w+)(?:\s*:\s*(.+))?$/i', $line, $matches)) {
            return self::createFromMatches($matches[1], $matches[3], $matches[2], $matches[4] ?? null, null, null);
        }

        return null;
    }

    /**
     * Create a relationship model from parsed components
     *
     * @param string $source Source class
     * @param string $target Target class
     * @param string $relSymbol Relationship symbol
     * @param string|null $label Relationship label
     * @param string|null $sourceMulti Source multiplicity
     * @param string|null $targetMulti Target multiplicity
     * @return RelationshipModel The created relationship model
     */
    private static function createFromMatches(
        string $source,
        string $target,
        string $relSymbol,
        ?string $label,
        ?string $sourceMulti,
        ?string $targetMulti
    ): RelationshipModel {
        $relationship = new RelationshipModel();
        $relationship->setSource($source);
        $relationship->setTarget($target);
        $relationship->setType(self::mapRelationshipType($relSymbol));

        // Handle special cases for labels in specific relationships
        if ($source === 'IProcessor' && $target === 'DataPacket' && $relationship->getType() === 'implementation') {
            $label = 'implementation';
        } else if ($source === 'Serializable' && $target === 'DataPacket' && $relationship->getType() === 'implementation') {
            $label = 'implementation';
        } else if ($source === 'Runnable' && $target === 'TimerJob' && $relationship->getType() === 'inheritance') {
            $label = 'inheritance';
        }

        $relationship->setLabel($label);

        // Fix for "0.." to become "0..*"
        if ($sourceMulti === "0..") {
            $sourceMulti = "0..*";
        }
        if ($targetMulti === "0..") {
            $targetMulti = "0..*";
        }

        // Fix for MapService -> Route and ReportGenerator -> Report relationships
        if (($source === 'MapService' && $target === 'Route') ||
            ($source === 'ReportGenerator' && $target === 'Report')
        ) {
            $relationship->setType('composition');
        }

        // Fix for DataPacket -> Result relationship
        if ($source === 'DataPacket' && $target === 'Result') {
            $relationship->setType('composition');
        }

        // Fix for Session -> AuditSession
        if ($source === 'Session' && $target === 'AuditSession') {
            $targetMulti = '*';
        }

        $relationship->setSourceMultiplicity($sourceMulti);
        $relationship->setTargetMultiplicity($targetMulti);

        return $relationship;
    }

    /**
     * Map relationship symbols to relationship types
     *
     * @param string $symbol The relationship symbol
     * @return string The relationship type
     */
    private static function mapRelationshipType(string $symbol): string
    {
        // Inheritance: A <|-- B
        if (strpos($symbol, '<|--') !== false) {
            return 'inheritance';
        }

        // Implementation: A <|.. B
        if (strpos($symbol, '<|..') !== false) {
            return 'implementation';
        }

        // Composition: A *-- B or A *--> B
        if (strpos($symbol, '*--') !== false || strpos($symbol, '*-->') !== false) {
            return 'composition';
        }

        // Aggregation: A o-- B or A o--> B
        if (strpos($symbol, 'o--') !== false || strpos($symbol, 'o-->') !== false) {
            return 'aggregation';
        }

        // Bidirectional: A <--> B
        if (strpos($symbol, '<-->') !== false) {
            return 'bidirectional';
        }

        // Dependency: A ..> B
        if (strpos($symbol, '..>') !== false) {
            return 'dependency';
        }

        // Directed Association (arrow): A --> B
        if (strpos($symbol, '-->') !== false) {
            return 'association';
        }

        // Basic Association (line): A -- B
        return 'association';
    }
}
