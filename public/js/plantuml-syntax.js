// PlantUML Language Definition for Monaco Editor
// Provides syntax highlighting for PlantUML diagrams including:
// - Keywords (class, interface, enum, etc.)
// - Directives (@startuml, @enduml, etc.)
// - Relationships (-->, <--, etc.)
// - Visibility modifiers (+, -, #, ~)
// - Comments (')
// - Strings and numbers
// - Stereotypes (<<stereotype>>)
// - Light and dark theme support
(function() {
    'use strict';

    // Define PlantUML language configuration
    const plantumlLanguageConfiguration = {
        comments: {
            lineComment: "'"
        },
        brackets: [
            ['{', '}'],
            ['[', ']'],
            ['(', ')']
        ],
        autoClosingPairs: [
            { open: '{', close: '}' },
            { open: '[', close: ']' },
            { open: '(', close: ')' },
            { open: '"', close: '"' },
            { open: "'", close: "'" }
        ],
        surroundingPairs: [
            { open: '{', close: '}' },
            { open: '[', close: ']' },
            { open: '(', close: ')' },
            { open: '"', close: '"' },
            { open: "'", close: "'" }
        ],
        folding: {
            markers: {
                start: new RegExp("^\\s*@start"),
                end: new RegExp("^\\s*@end")
            }
        }
    };

    // Define PlantUML language tokens
    const plantumlTokensProvider = {
        // Keywords and directives
        keywords: [
            'abstract', 'class', 'interface', 'enum', 'annotation',
            'package', 'namespace', 'entity', 'note', 'state',
            'activity', 'participant', 'actor', 'component',
            'database', 'cloud', 'node', 'folder', 'frame',
            'rectangle', 'circle', 'usecase', 'boundary',
            'control', 'collections', 'queue', 'card',
            'file', 'storage', 'artifact', 'portin', 'portout',
            'left', 'right', 'up', 'down', 'top', 'bottom',
            'title', 'header', 'footer', 'legend', 'end',
            'as', 'is', 'hide', 'show', 'skinparam', 'scale',
            'rotate', 'newpage', 'break', 'group', 'box'
        ],

        // Modifiers and visibility
        modifiers: [
            'public', 'private', 'protected', 'internal', 'static',
            'final', 'abstract', 'override', 'virtual', 'sealed'
        ],

        // Relationship operators
        relationships: [
            '-->', '<--', '--', '..>', '<..', '..', '||--||',
            '}--{', '}..{', '||..||', '}-{', '}-o{', '}o-{',
            '}-||{', '||o--o||', '<-', '->', '<->', 'o--o',
            'o-o', '<|-', '-|>', '<|--', '--|>', '*--', '--*',
            '0--', '--0', '1--', '--1'
        ],

        // Directives
        directives: [
            '@startuml', '@enduml', '@startmindmap', '@endmindmap',
            '@startsalt', '@endsalt', '@startgantt', '@endgantt',
            '@startwbs', '@endwbs', '@startjson', '@endjson',
            '@startyaml', '@endyaml', '@startditaa', '@endditaa',
            '@startjcckit', '@endjcckit', '@startmath', '@endmath',
            '@startlatex', '@endlatex', '@startdot', '@enddot',
            '@startcreole', '@endcreole'
        ],

        // Symbols
        symbols: /[=><!~?:&|+\-*\/\^%]+/,

        // Token rules
        tokenizer: {
            root: [
                // Directives
                [/@(start|end)[a-zA-Z]+/, 'keyword.directive'],
                
                // Comments
                [/'.*$/, 'comment'],
                
                // Preprocessor
                [/!.*$/, 'keyword.preprocessor'],
                
                // Keywords
                [/\b(abstract|class|interface|enum|annotation|package|namespace|entity|note|state|activity|participant|actor|component|database|cloud|node|folder|frame|rectangle|circle|usecase|boundary|control|collections|queue|card|file|storage|artifact|portin|portout|left|right|up|down|top|bottom|title|header|footer|legend|end|as|is|hide|show|skinparam|scale|rotate|newpage|break|group|box)\b/, 'keyword'],
                
                // Modifiers
                [/\b(public|private|protected|internal|static|final|abstract|override|virtual|sealed)\b/, 'keyword.modifier'],
                
                // Visibility symbols
                [/[+\-#~]/, 'keyword.visibility'],
                
                // Relationships
                [/(\-\-\>|\<\-\-|\-\-|\.\.\>|\<\.\.|\.\.|o\-\-o|o\-o|\<\|\-|\-\|\>|\<\|\-\-|\-\-\|\>|\*\-\-|\-\-\*|0\-\-|\-\-0|1\-\-|\-\-1)/, 'keyword.relationship'],
                
                // Strings
                [/"([^"\\]|\\.)*$/, 'string.invalid'],
                [/"/, 'string', '@string'],
                
                // Numbers
                [/\d+/, 'number'],
                
                // Stereotypes
                [/<<[^>]*>>/, 'type.stereotype'],
                
                // Identifiers
                [/[a-zA-Z_][a-zA-Z0-9_]*/, 'identifier'],
                
                // Symbols
                [/@symbols/, 'delimiter'],
                
                // Whitespace
                [/\s+/, 'white'],
                
                // Brackets
                [/[{}()\[\]]/, 'bracket'],
                
                // Delimiters
                [/[;,.]/, 'delimiter']
            ],

            string: [
                [/[^\\"]+/, 'string'],
                [/\\./, 'string.escape'],
                [/"/, 'string', '@pop']
            ]
        }
    };

    // Define PlantUML theme colors
    const plantumlThemeRules = {
        light: [
            { token: 'keyword.directive', foreground: '8b0000', fontStyle: 'bold' },
            { token: 'keyword', foreground: '0000ff', fontStyle: 'bold' },
            { token: 'keyword.modifier', foreground: '800080' },
            { token: 'keyword.visibility', foreground: 'ff6600', fontStyle: 'bold' },
            { token: 'keyword.relationship', foreground: '228b22', fontStyle: 'bold' },
            { token: 'keyword.preprocessor', foreground: '006400' },
            { token: 'comment', foreground: '008000', fontStyle: 'italic' },
            { token: 'string', foreground: 'a31515' },
            { token: 'string.invalid', foreground: 'ff0000' },
            { token: 'string.escape', foreground: 'ff6600' },
            { token: 'number', foreground: '098658' },
            { token: 'type.stereotype', foreground: '9932cc', fontStyle: 'italic' },
            { token: 'identifier', foreground: '000000' },
            { token: 'delimiter', foreground: '666666' },
            { token: 'bracket', foreground: '0000ff' }
        ],
        dark: [
            { token: 'keyword.directive', foreground: 'ff6b6b', fontStyle: 'bold' },
            { token: 'keyword', foreground: '569cd6', fontStyle: 'bold' },
            { token: 'keyword.modifier', foreground: 'c586c0' },
            { token: 'keyword.visibility', foreground: 'ffa500', fontStyle: 'bold' },
            { token: 'keyword.relationship', foreground: '4ec9b0', fontStyle: 'bold' },
            { token: 'keyword.preprocessor', foreground: '9cdcfe' },
            { token: 'comment', foreground: '6a9955', fontStyle: 'italic' },
            { token: 'string', foreground: 'ce9178' },
            { token: 'string.invalid', foreground: 'ff0000' },
            { token: 'string.escape', foreground: 'd7ba7d' },
            { token: 'number', foreground: 'b5cea8' },
            { token: 'type.stereotype', foreground: 'dda0dd', fontStyle: 'italic' },
            { token: 'identifier', foreground: 'd4d4d4' },
            { token: 'delimiter', foreground: 'cccccc' },
            { token: 'bracket', foreground: 'ffd700' }
        ]
    };

    // Register PlantUML language when Monaco is ready
    function registerPlantUMLLanguage() {
        if (typeof monaco !== 'undefined' && monaco.languages) {
            try {
                // Check if already registered
                const languages = monaco.languages.getLanguages();
                const isRegistered = languages.some(lang => lang.id === 'plantuml');
                
                if (!isRegistered) {
                    // Register the language
                    monaco.languages.register({ id: 'plantuml' });
                    
                    // Set language configuration
                    monaco.languages.setLanguageConfiguration('plantuml', plantumlLanguageConfiguration);
                    
                    // Set tokenization provider
                    monaco.languages.setMonarchTokensProvider('plantuml', plantumlTokensProvider);
                    
                    console.log('PlantUML language registered successfully');
                }
                
                // Define themes
                monaco.editor.defineTheme('plantuml-light', {
                    base: 'vs',
                    inherit: true,
                    rules: plantumlThemeRules.light,
                    colors: {}
                });
                
                monaco.editor.defineTheme('plantuml-dark', {
                    base: 'vs-dark',
                    inherit: true,
                    rules: plantumlThemeRules.dark,
                    colors: {}
                });
                
                // Store registration status
                window.plantumlLanguageRegistered = true;
                
            } catch (error) {
                console.error('Error registering PlantUML language:', error);
                // Fallback to retry
                setTimeout(registerPlantUMLLanguage, 500);
            }
        } else {
            // Monaco not ready yet, retry
            setTimeout(registerPlantUMLLanguage, 100);
        }
    }

    // Initialize PlantUML when Monaco is ready
    function initializePlantUML() {
        require(['vs/editor/editor.main'], function() {
            registerPlantUMLLanguage();
        });
    }

    // Auto-register when script loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializePlantUML);
    } else {
        initializePlantUML();
    }

    // Helper function to get the appropriate PlantUML theme
    window.getPlantUMLTheme = function(isDark) {
        return isDark ? 'plantuml-dark' : 'plantuml-light';
    };
    
    // Helper function to check if PlantUML language is registered
    window.isPlantUMLRegistered = function() {
        return window.plantumlLanguageRegistered || false;
    };
    
    // Export for global access
    window.registerPlantUMLLanguage = registerPlantUMLLanguage;
})(); 
