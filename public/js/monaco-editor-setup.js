/**
 * Monaco Editor initialization and configuration
 */

class MonacoEditorManager {
    constructor() {
        this.editors = {};
        this.setupLanguages();
    }

    /**
     * Set up custom language configurations
     */
    setupLanguages() {
        // Register PlantUML language if Monaco is available
        if (typeof monaco !== 'undefined') {
            // Register PlantUML as a language
            monaco.languages.register({ id: 'plantuml' });
            
            // Define PlantUML syntax highlighting
            monaco.languages.setMonarchTokensProvider('plantuml', {
                tokenizer: {
                    root: [
                        // Keywords
                        [/\b(class|interface|enum|abstract|annotation|component|package|namespace|@startuml|@enduml)\b/, 'keyword'],
                        
                        // Visibility modifiers
                        [/[+-#~]/, 'keyword'],
                        
                        // Relationship symbols
                        [/<\|--|<\|\.\.|-\|>|\.\.>|\*--|\*\.\.|-\->|\.\.>|<-\.>|--|\.\.|o--|\.\.|--o|--\*|\*--|\*\.\.|-\.->|<-\.-/, 'operator'],
                        
                        // Strings
                        [/"([^"\\]|\\.)*$/, 'string.invalid'],  // non-teminated string
                        [/"/, { token: 'string.quote', bracket: '@open', next: '@string' }],
                        
                        // Type definitions
                        [/\b(int|string|boolean|void|float|double|long|short|byte|char|Object)\b/, 'type'],
                        
                        // Comments
                        [/'.*$/, 'comment'],
                        [/\/\/.*$/, 'comment'],
                        [/\/\*/, 'comment', '@comment'],
                        
                        // Numbers
                        [/\d*\.\d+([eE][\-+]?\d+)?/, 'number.float'],
                        [/0[xX][0-9a-fA-F]+/, 'number.hex'],
                        [/\d+/, 'number'],
                        
                        // Identifiers
                        [/[a-zA-Z_$][\w$]*/, 'identifier'],
                    ],
                    
                    comment: [
                        [/[^\/*]+/, 'comment'],
                        [/\/\*/, 'comment', '@push'],
                        ["\\*/", 'comment', '@pop'],
                        [/[\/*]/, 'comment']
                    ],
                    
                    string: [
                        [/[^\\"]+/, 'string'],
                        [/\\./, 'string.escape'],
                        [/"/, { token: 'string.quote', bracket: '@close', next: '@pop' }]
                    ]
                }
            });
            
            // Define PlantUML language configuration
            monaco.languages.setLanguageConfiguration('plantuml', {
                comments: {
                    lineComment: "'",
                    blockComment: ['/*', '*/']
                },
                brackets: [
                    ['{', '}'],
                    ['(', ')']
                ],
                autoClosingPairs: [
                    { open: '{', close: '}' },
                    { open: '(', close: ')' },
                    { open: '"', close: '"' }
                ],
                surroundingPairs: [
                    { open: '{', close: '}' },
                    { open: '(', close: ')' },
                    { open: '"', close: '"' }
                ]
            });
        }
    }

    /**
     * Initialize Monaco editor on a DOM element
     * @param {string} elementId - The ID of the DOM element
     * @param {string} language - The language for syntax highlighting (e.g., 'plaintext', 'javascript', 'java', etc.)
     * @param {string} initialValue - The initial text content
     * @param {Object} options - Additional editor options
     * @returns {monaco.editor.IStandaloneCodeEditor} The editor instance
     */
    createEditor(elementId, language = 'plaintext', initialValue = '', options = {}) {
        const element = document.getElementById(elementId);
        if (!element) {
            console.error(`Element with ID '${elementId}' not found`);
            return null;
        }

        // Default options with overrides
        const editorOptions = {
            value: initialValue,
            language: language,
            theme: 'vs-dark',
            automaticLayout: true,
            minimap: { enabled: true },
            lineNumbers: 'on',
            scrollBeyondLastLine: false,
            roundedSelection: true,
            readOnly: false,
            cursorStyle: 'line',
            fontSize: 14,
            scrollbar: {
                useShadows: false,
                verticalHasArrows: true,
                horizontalHasArrows: true,
                vertical: 'visible',
                horizontal: 'visible',
                verticalScrollbarSize: 12,
                horizontalScrollbarSize: 12,
            },
            ...options
        };

        // Create editor instance
        const editor = monaco.editor.create(element, editorOptions);
        
        // Store the editor instance for later reference
        this.editors[elementId] = editor;
        
        return editor;
    }

    /**
     * Get an existing editor instance by its container element ID
     * @param {string} elementId - The ID of the container element
     * @returns {monaco.editor.IStandaloneCodeEditor|null} The editor instance or null if not found
     */
    getEditor(elementId) {
        return this.editors[elementId] || null;
    }

    /**
     * Set the content of an editor
     * @param {string} elementId - The ID of the editor container
     * @param {string} content - The content to set
     */
    setContent(elementId, content) {
        const editor = this.getEditor(elementId);
        if (editor) {
            editor.setValue(content);
        }
    }

    /**
     * Get the content from an editor
     * @param {string} elementId - The ID of the editor container
     * @returns {string} The editor content
     */
    getContent(elementId) {
        const editor = this.getEditor(elementId);
        return editor ? editor.getValue() : '';
    }

    /**
     * Change the language of an existing editor
     * @param {string} elementId - The ID of the editor container
     * @param {string} language - The language to set
     */
    setLanguage(elementId, language) {
        const editor = this.getEditor(elementId);
        if (editor) {
            monaco.editor.setModelLanguage(editor.getModel(), language);
        }
    }

    /**
     * Dispose of an editor instance
     * @param {string} elementId - The ID of the editor container
     */
    disposeEditor(elementId) {
        const editor = this.getEditor(elementId);
        if (editor) {
            editor.dispose();
            delete this.editors[elementId];
        }
    }

    /**
     * Dispose all editor instances
     */
    disposeAll() {
        Object.values(this.editors).forEach(editor => editor.dispose());
        this.editors = {};
    }
}

// Create a global instance of the editor manager
window.monacoEditorManager = new MonacoEditorManager(); 
