<?php

namespace Tests\Core\Generator\ClassDiagram\Infrastructure\Languages\Php;

use App\Core\Generator\ClassDiagram\Application\Service\GeneratorFactory;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for all PHP code generators
 */
class PhpGeneratorIntegrationTest extends TestCase
{
    private GeneratorFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new GeneratorFactory();
    }

    /**
     * Test PHP 8.1 generator with enum support
     */
    public function testPhp81EnumGeneration(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'Status',
                    'type' => 'enum',
                    'enumType' => 'string',
                    'enumValues' => [
                        ['name' => 'ACTIVE', 'value' => 'active'],
                        ['name' => 'INACTIVE', 'value' => 'inactive'],
                        ['name' => 'PENDING', 'value' => 'pending']
                    ]
                ]
            ]
        ];

        $generator = $this->factory->createGenerator($diagram, 'PHP', '8.1');
        $generator->setOutputDirectory('test/output');
        $files = $generator->generate();

        $this->assertCount(1, $files);
        $this->assertEquals('Status.php', $files[0]->getFilename());

        $content = $files[0]->getContent();
        $this->assertStringContainsString('enum Status: string', $content);
        $this->assertStringContainsString("case ACTIVE = 'active';", $content);
        $this->assertStringContainsString("case INACTIVE = 'inactive';", $content);
        $this->assertStringContainsString("case PENDING = 'pending';", $content);
    }

    /**
     * Test PHP 8.1 generator with readonly properties
     */
    public function testPhp81ReadonlyProperties(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'User',
                    'type' => 'class',
                    'attributes' => [
                        [
                            'name' => 'id',
                            'type' => 'int',
                            'visibility' => 'public',
                            'readonly' => true
                        ],
                        [
                            'name' => 'email',
                            'type' => 'string',
                            'visibility' => 'public',
                            'readonly' => true
                        ]
                    ]
                ]
            ]
        ];

        $generator = $this->factory->createGenerator($diagram, 'PHP', '8.1');
        $generator->setOutputDirectory('test/output');
        $files = $generator->generate();

        $content = $files[0]->getContent();
        $this->assertStringContainsString('public readonly int $id;', $content);
        $this->assertStringContainsString('public readonly string $email;', $content);
    }

    /**
     * Test PHP 8.2 generator with readonly class
     */
    public function testPhp82ReadonlyClass(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'ImmutableUser',
                    'type' => 'class',
                    'readonly' => true,
                    'attributes' => [
                        [
                            'name' => 'id',
                            'type' => 'int',
                            'visibility' => 'public'
                        ],
                        [
                            'name' => 'name',
                            'type' => 'string',
                            'visibility' => 'public'
                        ]
                    ]
                ]
            ]
        ];

        $generator = $this->factory->createGenerator($diagram, 'PHP', '8.2');
        $generator->setOutputDirectory('test/output');
        $files = $generator->generate();

        $content = $files[0]->getContent();
        $this->assertStringContainsString('readonly class ImmutableUser', $content);
        $this->assertStringContainsString('public readonly int $id;', $content);
        $this->assertStringContainsString('public readonly string $name;', $content);
    }

    /**
     * Test PHP 8.3 generator with typed constants
     */
    public function testPhp83TypedConstants(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'Config',
                    'type' => 'class',
                    'constants' => [
                        [
                            'name' => 'MAX_RETRY_COUNT',
                            'type' => 'int',
                            'value' => '3',
                            'visibility' => 'public'
                        ],
                        [
                            'name' => 'DEFAULT_TIMEOUT',
                            'type' => 'float',
                            'value' => '30.0',
                            'visibility' => 'public'
                        ]
                    ]
                ]
            ]
        ];

        $generator = $this->factory->createGenerator($diagram, 'PHP', '8.3');
        $generator->setOutputDirectory('test/output');
        $files = $generator->generate();

        $content = $files[0]->getContent();
        $this->assertStringContainsString('public const int MAX_RETRY_COUNT = 3;', $content);
        $this->assertStringContainsString('public const float DEFAULT_TIMEOUT = 30.0;', $content);
    }

    /**
     * Test PHP 8.3 generator with Override attribute
     */
    public function testPhp83OverrideAttribute(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'ChildClass',
                    'type' => 'class',
                    'extends' => 'ParentClass',
                    'methods' => [
                        [
                            'name' => 'process',
                            'visibility' => 'public',
                            'returnType' => 'void',
                            'override' => true
                        ]
                    ]
                ]
            ]
        ];

        $generator = $this->factory->createGenerator($diagram, 'PHP', '8.3');
        $generator->setOutputDirectory('test/output');
        $files = $generator->generate();

        $content = $files[0]->getContent();
        $this->assertStringContainsString('#[\\Override]', $content);
        $this->assertStringContainsString('public function process(): void', $content);
    }

    /**
     * Test PHP 8.4 generator with asymmetric visibility
     */
    public function testPhp84AsymmetricVisibility(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'Product',
                    'type' => 'class',
                    'attributes' => [
                        [
                            'name' => 'price',
                            'type' => 'float',
                            'getVisibility' => 'public',
                            'setVisibility' => 'private'
                        ]
                    ]
                ]
            ]
        ];

        $generator = $this->factory->createGenerator($diagram, 'PHP', '8.4');
        $generator->setOutputDirectory('test/output');
        $files = $generator->generate();

        $content = $files[0]->getContent();
        $this->assertStringContainsString('public(private) float $price;', $content);
    }

    /**
     * Test PHP 8.4 generator with property hooks
     */
    public function testPhp84PropertyHooks(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'Temperature',
                    'type' => 'class',
                    'attributes' => [
                        [
                            'name' => 'celsius',
                            'type' => 'float',
                            'visibility' => 'public',
                            'getHook' => 'return $this->celsius;',
                            'setHook' => '$this->celsius = $value;'
                        ]
                    ]
                ]
            ]
        ];

        $generator = $this->factory->createGenerator($diagram, 'PHP', '8.4');
        $generator->setOutputDirectory('test/output');
        $files = $generator->generate();

        $content = $files[0]->getContent();
        $this->assertStringContainsString('public float $celsius', $content);
        $this->assertStringContainsString('get {', $content);
        $this->assertStringContainsString('return $this->celsius;', $content);
        $this->assertStringContainsString('set {', $content);
        $this->assertStringContainsString('$this->celsius = $value;', $content);
    }

    /**
     * Test that each PHP version generates progressively enhanced code
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
                        [
                            'name' => 'value',
                            'type' => 'string',
                            'visibility' => 'public'
                        ]
                    ],
                    'methods' => [
                        [
                            'name' => 'getValue',
                            'visibility' => 'public',
                            'returnType' => 'string'
                        ]
                    ]
                ]
            ]
        ];

        $versions = ['7.4', '8.0', '8.1', '8.2', '8.3', '8.4'];
        
        foreach ($versions as $version) {
            $generator = $this->factory->createGenerator($diagram, 'PHP', $version);
            $generator->setOutputDirectory('test/output');
            $files = $generator->generate();
            
            $this->assertCount(1, $files, "Failed to generate for PHP $version");
            $this->assertEquals('TestClass.php', $files[0]->getFilename());
            
            $content = $files[0]->getContent();
            $this->assertStringContainsString('class TestClass', $content);
            $this->assertStringContainsString('public string $value;', $content);
            $this->assertStringContainsString('public function getValue(): string', $content);
        }
    }

    /**
     * Test all supported PHP versions are available
     */
    public function testSupportedVersions(): void
    {
        $supportedLanguages = $this->factory->getSupportedLanguages();
        
        $this->assertArrayHasKey('PHP', $supportedLanguages);
        $phpVersions = $supportedLanguages['PHP'];
        
        $expectedVersions = ['7.4', '8.0', '8.1', '8.2', '8.3', '8.4'];
        $this->assertEquals($expectedVersions, $phpVersions);
    }
} 
