<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AdminExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('getFileLanguage', [$this, 'getFileLanguage']),
        ];
    }

    public function getFileLanguage(?string $filename = null): string
    {
        if (!$filename) {
            return 'text';
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        return match ($extension) {
            'php' => 'php',
            'java' => 'java',
            'py' => 'python',
            'cs' => 'csharp',
            'js' => 'javascript',
            'ts' => 'typescript',
            'html' => 'html',
            'css' => 'css',
            'json' => 'json',
            'xml' => 'xml',
            'yml', 'yaml' => 'yaml',
            'sql' => 'sql',
            default => 'text'
        };
    }
} 
