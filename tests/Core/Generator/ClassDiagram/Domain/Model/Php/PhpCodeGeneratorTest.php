<?php

namespace App\Tests\Core\Generator\ClassDiagram\Domain\Model\Php;

use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;
use App\Core\Generator\ClassDiagram\Domain\Model\Php\PhpCodeGenerator;
use PHPUnit\Framework\TestCase;

class PhpCodeGeneratorTest extends TestCase
{
    /**
     * Test generating a simple class
     */
    public function testGenerateSimpleClass(): void
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
                            'visibility' => 'private',
                            'type' => 'int'
                        ],
                        [
                            'name' => 'name',
                            'visibility' => 'private',
                            'type' => 'string'
                        ]
                    ],
                    'methods' => [
                        [
                            'name' => 'getId',
                            'visibility' => 'public',
                            'returnType' => 'int'
                        ],
                        [
                            'name' => 'getName',
                            'visibility' => 'public',
                            'returnType' => 'string'
                        ]
                    ]
                ]
            ]
        ];

        $generator = new PhpCodeGenerator($diagram, 'PHP', '7.4');
        $generator->setNamespacePrefix('App\\Test');
        $generator->setOutputDirectory('test/output');

        $files = $generator->generate();

        $this->assertCount(1, $files);
        $this->assertInstanceOf(CodeFile::class, $files[0]);
        $this->assertEquals('User.php', $files[0]->getFilename());

        $content = $files[0]->getContent();
        $this->assertStringContainsString('namespace App\\Test;', $content);
        $this->assertStringContainsString('class User', $content);
        $this->assertStringContainsString('private int $id;', $content);
        $this->assertStringContainsString('private string $name;', $content);
        $this->assertStringContainsString('public function getId(): int', $content);
        $this->assertStringContainsString('public function getName(): string', $content);
    }

    /**
     * Test generating an interface
     */
    public function testGenerateInterface(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'UserRepository',
                    'type' => 'interface',
                    'methods' => [
                        [
                            'name' => 'findById',
                            'visibility' => 'public',
                            'parameters' => [
                                [
                                    'name' => 'id',
                                    'type' => 'int'
                                ]
                            ],
                            'returnType' => 'User'
                        ]
                    ]
                ]
            ]
        ];

        $generator = new PhpCodeGenerator($diagram, 'PHP', '7.4');
        $generator->setNamespacePrefix('App\\Test');
        $generator->setOutputDirectory('test/output');

        $files = $generator->generate();

        $this->assertCount(1, $files);
        $this->assertEquals('UserRepository.php', $files[0]->getFilename());

        $content = $files[0]->getContent();
        $this->assertStringContainsString('interface UserRepository', $content);
        $this->assertStringContainsString('public function findById(int $id): User;', $content);
    }

    /**
     * Test generating an enum (as a class with constants in PHP 7.4)
     */
    public function testGenerateEnum(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'UserRole',
                    'type' => 'enum',
                    'attributes' => [
                        [
                            'name' => 'ADMIN',
                            'visibility' => 'public'
                        ],
                        [
                            'name' => 'USER',
                            'visibility' => 'public'
                        ]
                    ]
                ]
            ]
        ];

        $generator = new PhpCodeGenerator($diagram, 'PHP', '7.4');
        $generator->setNamespacePrefix('App\\Test');
        $generator->setOutputDirectory('test/output');

        $files = $generator->generate();

        $this->assertCount(1, $files);
        $this->assertEquals('UserRole.php', $files[0]->getFilename());

        $content = $files[0]->getContent();
        $this->assertStringContainsString('class UserRole', $content);
        $this->assertStringContainsString('public const ADMIN = \'ADMIN\';', $content);
        $this->assertStringContainsString('public const USER = \'USER\';', $content);
    }

    /**
     * Test generating a class with generic types
     */
    public function testGenerateClassWithGenerics(): void
    {
        $diagram = [
            'title' => 'Test Diagram',
            'classes' => [
                [
                    'name' => 'Collection',
                    'type' => 'class',
                    'typeParameters' => ['T'],
                    'attributes' => [
                        [
                            'name' => 'items',
                            'visibility' => 'private',
                            'type' => 'array'
                        ]
                    ],
                    'methods' => [
                        [
                            'name' => 'add',
                            'visibility' => 'public',
                            'parameters' => [
                                [
                                    'name' => 'item',
                                    'type' => 'T'
                                ]
                            ],
                            'returnType' => 'void'
                        ]
                    ]
                ]
            ]
        ];

        $generator = new PhpCodeGenerator($diagram, 'PHP', '7.4');
        $generator->setNamespacePrefix('App\\Test');
        $generator->setOutputDirectory('test/output');

        $files = $generator->generate();

        $this->assertCount(1, $files);
        $this->assertEquals('Collection.php', $files[0]->getFilename());

        $content = $files[0]->getContent();
        $this->assertStringContainsString('@template T', $content);
        $this->assertStringContainsString('private array $items;', $content);
        $this->assertStringContainsString('public function add($item): void', $content);
    }
} 
