# Monaco Editor Integration

This document explains how to use the Monaco Editor integration in the project.

## Overview

[Monaco Editor](https://microsoft.github.io/monaco-editor/) is the code editor that powers VS Code. It provides features like syntax highlighting, code completion, error checking, and more for many programming languages.

In this project, Monaco Editor is available for all text-based interactions, such as editing code, working with configuration files, or other text-based content.

## Directory Structure

- `public/js/lib/monaco-editor/` - Contains the Monaco Editor library files
- `public/js/monaco-editor-setup.js` - Contains the JavaScript code to initialize and manage Monaco Editor instances
- `public/css/monaco-editor.css` - Contains the CSS styles for Monaco Editor containers
- `src/Twig/Components/MonacoEditorComponent.php` - Twig component for easy integration
- `templates/components/monaco_editor.html.twig` - Twig template for the component

## Using the Monaco Editor Component

To add Monaco Editor to a page, use the Twig component:

```twig
{{ component('monaco_editor', {
    id: 'uniqueEditorId',
    language: 'javascript',
    content: 'console.log("Hello, world!");',
    size: 'medium',
    showToolbar: true,
    languages: [
        { value: 'javascript', label: 'JavaScript' },
        { value: 'html', label: 'HTML' }
    ]
}) }}
```

### Component Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `id` | string | 'editor' | Unique ID for the editor instance |
| `language` | string | 'plaintext' | Initial language for syntax highlighting |
| `content` | string | '' | Initial content of the editor |
| `size` | string | 'medium' | Size of the editor ('small', 'medium', 'large', 'full-height') |
| `readOnly` | boolean | false | Whether the editor should be read-only |
| `options` | array | [] | Additional Monaco Editor options |
| `formField` | string | null | If set, binds editor content to a form field with this name |
| `showToolbar` | boolean | false | Whether to show the editor toolbar |
| `languages` | array | [] | List of languages for the language selector in the toolbar |

## Form Integration

To use Monaco Editor in a form, set the `formField` option:

```twig
<form method="post">
    {{ component('monaco_editor', {
        id: 'codeEditor',
        language: 'php',
        content: initialCode,
        formField: 'code_content'
    }) }}
    
    <button type="submit">Submit</button>
</form>
```

When the form is submitted, the editor content will be included in the request.

## JavaScript API

You can interact with the editor instances programmatically using the global `monacoEditorManager` object:

```javascript
// Get an editor instance by its container ID
const editor = window.monacoEditorManager.getEditor('editorId');

// Get the content of an editor
const content = window.monacoEditorManager.getContent('editorId');

// Set the content of an editor
window.monacoEditorManager.setContent('editorId', 'New content');

// Change the language of an editor
window.monacoEditorManager.setLanguage('editorId', 'php');

// Dispose of an editor instance
window.monacoEditorManager.disposeEditor('editorId');
```

## Demo Page

Visit `/monaco-editor-demo` to see examples of Monaco Editor integration.

## Common Issues

- If the editor doesn't appear, check the browser console for errors.
- Make sure the Monaco Editor library files are properly loaded.
- Ensure the editor container has a fixed height or a height specified by CSS.

## Additional Resources

- [Monaco Editor Documentation](https://microsoft.github.io/monaco-editor/docs.html)
- [Monaco Editor API Reference](https://microsoft.github.io/monaco-editor/api/index.html)
- [Monaco Editor Playground](https://microsoft.github.io/monaco-editor/playground.html) 
