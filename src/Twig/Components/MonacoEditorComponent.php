<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('monaco_editor')]
class MonacoEditorComponent
{
    public string $id = 'editor';
    public string $language = 'plaintext';
    public string $content = '';
    public string $size = 'medium';
    public bool $readOnly = false;
    public array $options = [];
    public ?string $formField = null;
    public bool $showToolbar = false;
    public array $languages = [];
} 
