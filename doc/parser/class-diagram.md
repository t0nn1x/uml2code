# Class Diagram Parser Documentation

## Overview

The Class Diagram Parser is a component that converts PlantUML class diagram notation into a structured object model. It supports parsing class diagrams with various UML elements including classes, interfaces, enums, relationships, attributes, and methods.

## Features

- Parses PlantUML class diagram syntax
- Supports multiple class element types:
  - Regular classes
  - Abstract classes
  - Interfaces
  - Enumerations
- Handles class members:
  - Attributes with visibility and types
  - Methods with parameters and return types
  - Static and abstract modifiers
- Supports relationships:
  - Inheritance
  - Implementation
  - Association
  - Composition
  - Aggregation
  - Dependency
- Handles generic types and type parameters
- Supports class stereotypes
- Validates UML syntax
- Extracts metadata from diagrams

## Usage

### Basic Usage

```php
use App\Core\Parser\ClassDiagram\Infrastructure\Parser\PlantUmlParser;

$parser = new PlantUmlParser();

// Parse UML content
$content = '@startuml
class User {
    -id: int
    +name: string
    +getFullName(): string
}
@enduml';

try {
    $diagram = $parser->parse($content);
} catch (ParserException $e) {
    // Handle parsing errors
    echo $e->getMessage();
    print_r($e->getContext());
}
```

### Validation

You can validate UML syntax without fully parsing it:

```php
$isValid = $parser->validate($content);
```

### Metadata Extraction

Extract basic information about the diagram:

```php
$metadata = $parser->extractMetadata($content);
// Returns array with:
// - title
// - number of classes
// - number of interfaces
// - number of enums
// - number of relationships
```

## Supported Syntax

### Class Definitions

```plantuml
class ClassName {
    +publicAttribute: string
    #protectedAttribute: int
    -privateAttribute: float
    ~packageAttribute: bool
    
    +publicMethod(param: string): void
    #protectedMethod(): int
    -privateMethod(): void
}

abstract class AbstractClass
interface Interface
enum Enumeration
```

### Relationships

```plantuml
// Inheritance
ChildClass --|> ParentClass

// Implementation
Class ..|> Interface

// Association
ClassA -- ClassB

// Composition
Whole *-- Part

// Aggregation
Container o-- Element

// Dependency
Client ..> Service

// Bidirectional
ClassA <--> ClassB
```

### Stereotypes and Generic Types

```plantuml
class GenericList<T> {
    -items: T[]
    +add(item: T): void
}

class Repository<T, ID> <<Service>> {
    +findById(id: ID): T
}
```

## Domain Model

The parser creates a structured object model with the following main classes:

### ClassDiagram

Main container class representing the entire diagram:
- Title
- List of class elements
- List of relationships

### ClassElement

Represents a class, interface, or enum:
- Name
- Type (class, abstract, interface, enum)
- Attributes
- Methods
- Extends relationship
- Implements relationships
- Type parameters
- Stereotypes

### Attribute

Represents a class attribute/property:
- Name
- Type
- Visibility
- Static modifier

### Method

Represents a class method:
- Name
- Return type
- Parameters
- Visibility
- Static/Abstract modifiers

### Relationship

Represents relationships between classes:
- Source class
- Target class
- Relationship type
- Label (optional)
- Multiplicities (optional)

## Error Handling

The parser uses the `ParserException` class for error reporting. Exceptions include:
- Invalid syntax
- Missing start/end tags
- Invalid relationship definitions
- Unknown class references

Each exception includes:
- Error message
- Context information (line numbers, relevant tokens)
- Original exception (if applicable)

## Best Practices

1. Always wrap UML content with `@startuml` and `@enduml` tags
2. Use consistent indentation for class members
3. Specify types for attributes and method parameters when possible
4. Use standard UML notation for relationships
5. Include visibility modifiers for class members
6. Document generic type parameters
7. Use meaningful names for classes and relationships

## Limitations

1. Nested classes are not supported
2. Multiple inheritance is not supported
3. Complex generic type constraints are not supported
4. Custom stereotypes are parsed but not validated
5. Some PlantUML-specific directives are ignored

## Integration

The parser is integrated into the Symfony framework through the `ParserBundle` class, which provides:
- Dependency injection configuration
- Controller registration
- Service definitions

## Future Enhancements

Planned improvements include:
1. Support for nested classes
2. Enhanced generic type constraints
3. Custom stereotype validation
4. Additional UML diagram types
5. Extended metadata extraction
6. Performance optimizations for large diagrams 
