<?php

namespace App\Tests\Core\Parser;

use App\Core\Parser\DiagramTypeDetector;
use App\Core\Parser\Models\ClassEntity;
use App\Core\Parser\PlantUmlParser;
use App\Core\Parser\DiagramParserFactory;
use App\Core\Parser\ClassEntityParser;
use App\Core\Parser\RelationshipParser;
use PHPUnit\Framework\TestCase;

class JavaStyleAttributeTest extends TestCase
{
    private PlantUmlParser $parser;

    protected function setUp(): void
    {
        $detector = new DiagramTypeDetector();
        $classEntityParser = new ClassEntityParser();
        $relationshipParser = new RelationshipParser();
        $factory = new DiagramParserFactory($detector, $classEntityParser, $relationshipParser);
        $this->parser = new PlantUmlParser($detector, $factory);
    }

    public function testJavaStyleAttributeParsing(): void
    {
        $uml = <<<'UML'
@startuml
class User {
    +String username
    -Integer id
    #Date createdAt
    ~Boolean active
}
@enduml
UML;

        $diagram = $this->parser->parse($uml);
        $user = $diagram->getClass('User');
        $attributes = $user->getAttributes();

        // We should have 4 attributes
        $this->assertCount(4, $attributes);

        // Check each attribute
        $usernameAttr = $idAttr = $createdAtAttr = $activeAttr = null;

        foreach ($attributes as $attr) {
            if ($attr['name'] === 'username') $usernameAttr = $attr;
            if ($attr['name'] === 'id') $idAttr = $attr;
            if ($attr['name'] === 'createdAt') $createdAtAttr = $attr;
            if ($attr['name'] === 'active') $activeAttr = $attr;
        }

        $this->assertNotNull($usernameAttr, "Username attribute not found");
        $this->assertEquals('String', $usernameAttr['type']);
        $this->assertEquals(ClassEntity::VISIBILITY_PUBLIC, $usernameAttr['visibility']);

        $this->assertNotNull($idAttr, "ID attribute not found");
        $this->assertEquals('Integer', $idAttr['type']);
        $this->assertEquals(ClassEntity::VISIBILITY_PRIVATE, $idAttr['visibility']);

        $this->assertNotNull($createdAtAttr, "createdAt attribute not found");
        $this->assertEquals('Date', $createdAtAttr['type']);
        $this->assertEquals(ClassEntity::VISIBILITY_PROTECTED, $createdAtAttr['visibility']);

        $this->assertNotNull($activeAttr, "active attribute not found");
        $this->assertEquals('Boolean', $activeAttr['type']);
        $this->assertEquals(ClassEntity::VISIBILITY_PACKAGE, $activeAttr['visibility']);
    }

    public function testMixedAttributeStyles(): void
    {
        $uml = <<<'UML'
@startuml
class Product {
    +id: Integer
    +String name
    -double price
    -inventory: int
}
@enduml
UML;

        $diagram = $this->parser->parse($uml);
        $product = $diagram->getClass('Product');
        $attributes = $product->getAttributes();

        // We should have 4 attributes
        $this->assertCount(4, $attributes);

        // Check each attribute
        $idAttr = $nameAttr = $priceAttr = $inventoryAttr = null;

        foreach ($attributes as $attr) {
            if ($attr['name'] === 'id') $idAttr = $attr;
            if ($attr['name'] === 'name') $nameAttr = $attr;
            if ($attr['name'] === 'price') $priceAttr = $attr;
            if ($attr['name'] === 'inventory') $inventoryAttr = $attr;
        }

        $this->assertNotNull($idAttr, "ID attribute not found");
        $this->assertEquals('Integer', $idAttr['type']);
        $this->assertEquals(ClassEntity::VISIBILITY_PUBLIC, $idAttr['visibility']);

        $this->assertNotNull($nameAttr, "Name attribute not found");
        $this->assertEquals('String', $nameAttr['type']);
        $this->assertEquals(ClassEntity::VISIBILITY_PUBLIC, $nameAttr['visibility']);

        $this->assertNotNull($priceAttr, "Price attribute not found");
        $this->assertEquals('double', $priceAttr['type']);
        $this->assertEquals(ClassEntity::VISIBILITY_PRIVATE, $priceAttr['visibility']);

        $this->assertNotNull($inventoryAttr, "Inventory attribute not found");
        $this->assertEquals('int', $inventoryAttr['type']);
        $this->assertEquals(ClassEntity::VISIBILITY_PRIVATE, $inventoryAttr['visibility']);
    }

    public function testGenericTypes(): void
    {
        $uml = <<<'UML'
@startuml
class Repository {
    +List<User> users
    -Map<String, Product> productMap
    #Set<Integer> validIds
}
@enduml
UML;

        $diagram = $this->parser->parse($uml);
        $repository = $diagram->getClass('Repository');
        $attributes = $repository->getAttributes();

        // We should have 3 attributes
        $this->assertCount(3, $attributes);

        // Check each attribute
        $usersAttr = $productMapAttr = $validIdsAttr = null;

        foreach ($attributes as $attr) {
            if ($attr['name'] === 'users') $usersAttr = $attr;
            if ($attr['name'] === 'productMap') $productMapAttr = $attr;
            if ($attr['name'] === 'validIds') $validIdsAttr = $attr;
        }

        $this->assertNotNull($usersAttr, "Users attribute not found");
        $this->assertEquals('List<User>', $usersAttr['type']);

        $this->assertNotNull($productMapAttr, "ProductMap attribute not found");
        $this->assertEquals('Map<String, Product>', $productMapAttr['type']);

        $this->assertNotNull($validIdsAttr, "ValidIds attribute not found");
        $this->assertEquals('Set<Integer>', $validIdsAttr['type']);
    }

    public function testArrayTypes(): void
    {
        $uml = <<<'UML'
@startuml
class DataProcessor {
    +String[] names
    -int[][] matrix
    #Object[] items
}
@enduml
UML;

        $diagram = $this->parser->parse($uml);
        $processor = $diagram->getClass('DataProcessor');
        $attributes = $processor->getAttributes();

        // We should have 3 attributes
        $this->assertCount(3, $attributes);

        // Check each attribute
        $namesAttr = $matrixAttr = $itemsAttr = null;

        foreach ($attributes as $attr) {
            if ($attr['name'] === 'names') $namesAttr = $attr;
            if ($attr['name'] === 'matrix') $matrixAttr = $attr;
            if ($attr['name'] === 'items') $itemsAttr = $attr;
        }

        $this->assertNotNull($namesAttr, "Names attribute not found");
        $this->assertEquals('String[]', $namesAttr['type']);

        $this->assertNotNull($matrixAttr, "Matrix attribute not found");
        $this->assertEquals('int[][]', $matrixAttr['type']);

        $this->assertNotNull($itemsAttr, "Items attribute not found");
        $this->assertEquals('Object[]', $itemsAttr['type']);
    }
}
