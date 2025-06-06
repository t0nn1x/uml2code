<?php

namespace Tests\Core\Generator\ClassDiagram\Infrastructure\Languages\Python;

use App\Core\Generator\ClassDiagram\Application\Service\GeneratorFactory;
use App\Core\Generator\ClassDiagram\Domain\Model\LanguageCodeGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for all Python code generators
 */
class PythonGeneratorIntegrationTest extends TestCase
{
    private GeneratorFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new GeneratorFactory();
    }

    /**
     * Helper method to create and configure a generator
     */
    private function createGenerator(array $diagram, string $version): LanguageCodeGenerator
    {
        $generator = $this->factory->createGenerator($diagram, 'PYTHON', $version);
        assert($generator instanceof LanguageCodeGenerator);
        $generator->setOutputDirectory('test/output');
        return $generator;
    }

    /**
     * Test Python 3.11 generator with Self type
     */
    public function testPython311SelfTypeGeneration(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'Builder',
                    'type' => 'class',
                    'methods' => [
                        [
                            'name' => 'withName',
                            'visibility' => 'public',
                            'parameters' => [
                                ['name' => 'name', 'type' => 'str']
                            ],
                            'returnType' => 'self'
                        ]
                    ]
                ]
            ]
        ];

        $generator = $this->createGenerator($diagram, '3.11');
        $files = $generator->generate();

        $this->assertCount(1, $files);
        $this->assertEquals('Builder.py', $files[0]->getFilename());

        $content = $files[0]->getContent();
        $this->assertStringContainsString('from typing import Self', $content);
        $this->assertStringContainsString('def withName(self, name: str) -> Self:', $content);
        $this->assertStringContainsString('return self', $content);
        $this->assertStringContainsString('Python 3.11+', $content);
    }

    /**
     * Test Python 3.11 generator with exception groups
     */
    public function testPython311ExceptionGroupsGeneration(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'DataProcessor',
                    'type' => 'class',
                    'methods' => [
                        [
                            'name' => 'processData',
                            'visibility' => 'public',
                            'returnType' => 'bool'
                        ]
                    ]
                ]
            ]
        ];

        $generator = $this->createGenerator($diagram, '3.11');
        $files = $generator->generate();

        $content = $files[0]->getContent();
        $this->assertStringContainsString('except* (ValueError, TypeError) as eg:', $content);
        $this->assertStringContainsString('Exception groups handling', $content);
        $this->assertStringContainsString('handle_multiple_operations', $content);
    }

    /**
     * Test Python 3.12 generator with type aliases
     */
    public function testPython312TypeAliasesGeneration(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'Container',
                    'type' => 'class',
                    'typeParameters' => ['T']
                ]
            ]
        ];

        $generator = $this->createGenerator($diagram, '3.12');
        $files = $generator->generate();

        $content = $files[0]->getContent();
        $this->assertStringContainsString('# Python 3.12: Generic type aliases', $content);
        $this->assertStringContainsString('type ContainerList[T] = list[Container[T]]', $content);
        $this->assertStringContainsString('type ContainerDict[K, V] = dict[K, Container[V]]', $content);
        $this->assertStringContainsString('type OptionalContainer[T] = Container[T] | None', $content);
    }

    /**
     * Test Python 3.12 generator with @override decorator
     */
    public function testPython312OverrideDecoratorGeneration(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'Child',
                    'type' => 'class',
                    'extends' => 'Parent',
                    'methods' => [
                        [
                            'name' => '__str__',
                            'visibility' => 'public',
                            'returnType' => 'str'
                        ]
                    ]
                ]
            ]
        ];

        $generator = $this->createGenerator($diagram, '3.12');
        $files = $generator->generate();

        $content = $files[0]->getContent();
        $this->assertStringContainsString('from typing import override', $content);
        $this->assertStringContainsString('@override', $content);
        $this->assertStringContainsString('def __str__(self) -> str:', $content);
    }

    /**
     * Test Python 3.12 generator with enhanced f-strings
     */
    public function testPython312EnhancedFStringsGeneration(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'Logger',
                    'type' => 'class',
                    'attributes' => [
                        ['name' => 'level', 'type' => 'str']
                    ]
                ]
            ]
        ];

        $generator = $this->createGenerator($diagram, '3.12');
        $files = $generator->generate();

        $content = $files[0]->getContent();
        $this->assertStringContainsString("f\"{self.__class__.__name__}(id={id(self)}, created={'{datetime.now().isoformat()}'})", $content);
        $this->assertStringContainsString("f\"{self.__class__.__name__}({', '.join(f'{k}={v}' for k, v in self.__dict__.items() if not k.startswith('_'))})\"", $content);
        $this->assertStringContainsString('Enhanced f-string debugging', $content);
    }

    /**
     * Test Python 3.12 generator with performance monitoring
     */
    public function testPython312PerformanceMonitoringGeneration(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'Calculator',
                    'type' => 'class',
                    'methods' => [
                        [
                            'name' => 'calculate',
                            'visibility' => 'public',
                            'returnType' => 'float'
                        ]
                    ]
                ]
            ]
        ];

        $generator = $this->createGenerator($diagram, '3.12');
        $files = $generator->generate();

        $content = $files[0]->getContent();
        $this->assertStringContainsString('monitor_performance', $content);
        $this->assertStringContainsString('from sys import monitoring', $content);
        $this->assertStringContainsString('comprehension inlining optimization', $content);
        $this->assertStringContainsString('monitoring.use_tool_id', $content);
    }

    /**
     * Test Python 3.12 enhanced enum generation
     */
    public function testPython312EnhancedEnumGeneration(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'Priority',
                    'type' => 'enum',
                    'enumValues' => [
                        ['name' => 'LOW', 'value' => 1, 'description' => 'Low priority'],
                        ['name' => 'MEDIUM', 'value' => 2, 'description' => 'Medium priority'],
                        ['name' => 'HIGH', 'value' => 3, 'description' => 'High priority']
                    ]
                ]
            ]
        ];

        $generator = $this->createGenerator($diagram, '3.12');
        $files = $generator->generate();

        $content = $files[0]->getContent();
        $this->assertStringContainsString('Enhanced enum Priority for Python 3.12+', $content);
        $this->assertStringContainsString('LOW = 1  # Low priority', $content);
        $this->assertStringContainsString('MEDIUM = 2  # Medium priority', $content);
        $this->assertStringContainsString('HIGH = 3  # High priority', $content);
        $this->assertStringContainsString('def __str__(self) -> str:', $content);
        $this->assertStringContainsString('f"{self.__class__.__name__}.{self.name}(value={self.value})"', $content);
    }

    /**
     * Test union types across Python versions
     */
    public function testUnionTypesAcrossVersions(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'DataStore',
                    'type' => 'class',
                    'methods' => [
                        [
                            'name' => 'getValue',
                            'visibility' => 'public',
                            'returnType' => 'Union[str, int]'
                        ]
                    ]
                ]
            ]
        ];

        // Test Python 3.10 - should use | operator
        $generator310 = $this->createGenerator($diagram, '3.10');
        $files310 = $generator310->generate();
        $content310 = $files310[0]->getContent();
        $this->assertStringContainsString('def getValue(self) -> str | int:', $content310);

        // Test Python 3.11 - should use | operator
        $generator311 = $this->createGenerator($diagram, '3.11');
        $files311 = $generator311->generate();
        $content311 = $files311[0]->getContent();
        $this->assertStringContainsString('def getValue(self) -> str | int:', $content311);

        // Test Python 3.12 - should use | operator
        $generator312 = $this->createGenerator($diagram, '3.12');
        $files312 = $generator312->generate();
        $content312 = $files312[0]->getContent();
        $this->assertStringContainsString('def getValue(self) -> str | int:', $content312);
    }

    /**
     * Test builtin generics across modern Python versions
     */
    public function testBuiltinGenericsAcrossVersions(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'Collection',
                    'type' => 'class',
                    'attributes' => [
                        ['name' => 'items', 'type' => 'List[str]'],
                        ['name' => 'mapping', 'type' => 'Dict[str, int]']
                    ]
                ]
            ]
        ];

        $modernVersions = ['3.10', '3.11', '3.12'];

        foreach ($modernVersions as $version) {
            $generator = $this->createGenerator($diagram, $version);
            $files = $generator->generate();
            $content = $files[0]->getContent();

            // Should use builtin generics
            $this->assertStringContainsString('items: list[str] = None', $content);
            $this->assertStringContainsString('mapping: dict[str, int] = None', $content);
        }
    }

    /**
     * Test that each Python version generates progressively enhanced code
     */
    public function testProgressiveEnhancement(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'TestClass',
                    'type' => 'class',
                    'attributes' => [
                        ['name' => 'value', 'type' => 'str']
                    ],
                    'methods' => [
                        [
                            'name' => 'getValue',
                            'visibility' => 'public',
                            'returnType' => 'str'
                        ]
                    ]
                ]
            ]
        ];

        $versions = ['3.8', '3.9', '3.10', '3.11', '3.12'];
        
        foreach ($versions as $version) {
            $generator = $this->createGenerator($diagram, $version);
            $files = $generator->generate();
            
            $this->assertCount(1, $files, "Failed to generate for Python $version");
            $this->assertEquals('TestClass.py', $files[0]->getFilename());
            
            $content = $files[0]->getContent();
            $this->assertStringContainsString('class TestClass', $content);
            $this->assertStringContainsString('def getValue(self) -> str:', $content);
            $this->assertStringContainsString("Generated for Python $version+", $content);
        }
    }

    /**
     * Test all supported Python versions are available
     */
    public function testSupportedVersions(): void
    {
        $supportedLanguages = $this->factory->getSupportedLanguages();
        
        $this->assertArrayHasKey('PYTHON', $supportedLanguages);
        $pythonVersions = $supportedLanguages['PYTHON'];
        
        $expectedVersions = ['3.8', '3.9', '3.10', '3.11', '3.12'];
        $this->assertEquals($expectedVersions, $pythonVersions);
    }

    /**
     * Test Python 3.11 LiteralString support
     */
    public function testPython311LiteralStringSupport(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'StringProcessor',
                    'type' => 'class',
                    'methods' => [
                        [
                            'name' => 'processLiteral',
                            'visibility' => 'public',
                            'parameters' => [
                                ['name' => 'text', 'type' => 'LiteralString']
                            ],
                            'returnType' => 'str'
                        ]
                    ]
                ]
            ]
        ];

        $generator = $this->createGenerator($diagram, '3.11');
        $files = $generator->generate();
        $content = $files[0]->getContent();

        $this->assertStringContainsString('from typing import LiteralString', $content);
        $this->assertStringContainsString('def processLiteral(self, text: LiteralString) -> str:', $content);
    }

    /**
     * Test Python 3.11 NotRequired/Required support
     */
    public function testPython311TypedDictSupport(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'ConfigProcessor',
                    'type' => 'class',
                    'methods' => [
                        [
                            'name' => 'processConfig',
                            'visibility' => 'public',
                            'parameters' => [
                                ['name' => 'optional_field', 'type' => 'NotRequired[str]'],
                                ['name' => 'required_field', 'type' => 'Required[int]']
                            ],
                            'returnType' => 'bool'
                        ]
                    ]
                ]
            ]
        ];

        $generator = $this->createGenerator($diagram, '3.11');
        $files = $generator->generate();
        $content = $files[0]->getContent();

        $this->assertStringContainsString('from typing import NotRequired, Required', $content);
        $this->assertStringContainsString('optional_field: NotRequired[str]', $content);
        $this->assertStringContainsString('required_field: Required[int]', $content);
    }

    /**
     * Test abstract class generation across versions
     */
    public function testAbstractClassGeneration(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'BaseProcessor',
                    'type' => 'abstract',
                    'methods' => [
                        [
                            'name' => 'process',
                            'visibility' => 'public',
                            'isAbstract' => true,
                            'returnType' => 'None'
                        ]
                    ]
                ]
            ]
        ];

        $versions = ['3.10', '3.11', '3.12'];

        foreach ($versions as $version) {
            $generator = $this->createGenerator($diagram, $version);
            $files = $generator->generate();
            $content = $files[0]->getContent();

            $this->assertStringContainsString('from abc import ABC, abstractmethod', $content);
            $this->assertStringContainsString('class BaseProcessor(ABC):', $content);
            $this->assertStringContainsString('@abstractmethod', $content);
            $this->assertStringContainsString('def process(self) -> None:', $content);
        }
    }

    /**
     * Test interface generation (using ABC)
     */
    public function testInterfaceGeneration(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'Drawable',
                    'type' => 'interface',
                    'methods' => [
                        [
                            'name' => 'draw',
                            'visibility' => 'public',
                            'returnType' => 'None'
                        ]
                    ]
                ]
            ]
        ];

        $versions = ['3.10', '3.11', '3.12'];

        foreach ($versions as $version) {
            $generator = $this->createGenerator($diagram, $version);
            $files = $generator->generate();
            $content = $files[0]->getContent();

            $this->assertStringContainsString('class Drawable(ABC):', $content);
            $this->assertStringContainsString('Interface Drawable', $content);
            $this->assertStringContainsString('@abstractmethod', $content);
        }
    }

    /**
     * Test complex class with inheritance and generics
     */
    public function testComplexClassGeneration(): void
    {
        $diagram = [
            'title' => 'Complex Test',
            'classes' => [
                [
                    'name' => 'Repository',
                    'type' => 'class',
                    'extends' => 'BaseRepository',
                    'implements' => ['Serializable', 'Cacheable'],
                    'typeParameters' => ['T'],
                    'attributes' => [
                        ['name' => 'entities', 'type' => 'List[T]', 'visibility' => 'private']
                    ],
                    'methods' => [
                        [
                            'name' => 'findAll',
                            'visibility' => 'public',
                            'returnType' => 'List[T]'
                        ],
                        [
                            'name' => 'save',
                            'visibility' => 'public',
                            'parameters' => [
                                ['name' => 'entity', 'type' => 'T']
                            ],
                            'returnType' => 'T'
                        ]
                    ]
                ]
            ]
        ];

        $generator = $this->createGenerator($diagram, '3.12');
        $files = $generator->generate();
        $content = $files[0]->getContent();

        // Should contain type aliases
        $this->assertStringContainsString('type RepositoryList[T] = list[Repository[T]]', $content);
        
        // Should contain class definition with inheritance
        $this->assertStringContainsString('class Repository(BaseRepository, Serializable, Cacheable):', $content);
        
        // Should contain builtin generics
        $this->assertStringContainsString('_entities: list[T] = None', $content);
        $this->assertStringContainsString('def findAll(self) -> list[T]:', $content);
        $this->assertStringContainsString('def save(self, entity: T) -> T:', $content);
    }
} 
