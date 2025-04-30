<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MonacoEditorDemoController extends AbstractController
{
    #[Route('/monaco-editor-demo', name: 'monaco_editor_demo')]
    public function index(Request $request): Response
    {
        $languages = [
            ['value' => 'plaintext', 'label' => 'Plain Text'],
            ['value' => 'javascript', 'label' => 'JavaScript'],
            ['value' => 'html', 'label' => 'HTML'],
            ['value' => 'css', 'label' => 'CSS'],
            ['value' => 'json', 'label' => 'JSON'],
            ['value' => 'php', 'label' => 'PHP'],
            ['value' => 'java', 'label' => 'Java'],
            ['value' => 'python', 'label' => 'Python'],
            ['value' => 'csharp', 'label' => 'C#'],
            ['value' => 'sql', 'label' => 'SQL'],
        ];
        
        $initialContent = $request->query->get('content', "// Welcome to Monaco Editor\n// Type your code here\n\nfunction hello() {\n    console.log('Hello, world!');\n}");
        
        return $this->render('monaco_editor_demo/index.html.twig', [
            'languages' => $languages,
            'initialContent' => $initialContent
        ]);
    }
} 
