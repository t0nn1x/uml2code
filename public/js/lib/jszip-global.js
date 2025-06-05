// JSZip Global Loader - Forces JSZip to be available globally
(function() {
    'use strict';
    
    // If JSZip is already available, don't load again
    if (window.JSZip) {
        return;
    }
    
    // Store original AMD define to restore later
    var originalDefine = window.define;
    var originalRequire = window.require;
    
    // Temporarily disable AMD
    if (typeof define !== 'undefined') {
        window.define = undefined;
    }
    if (typeof require !== 'undefined') {
        window.require = undefined;
    }
    
    // Create a script element to load JSZip
    var script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js';
    
    script.onload = function() {
        // Restore AMD if it existed
        if (originalDefine) {
            window.define = originalDefine;
        }
        if (originalRequire) {
            window.require = originalRequire;
        }
        
        // Dispatch a custom event to notify that JSZip is ready
        if (window.JSZip) {
            window.dispatchEvent(new CustomEvent('jszipReady', { detail: window.JSZip }));
        }
    };
    
    script.onerror = function() {
        // Restore AMD if it existed
        if (originalDefine) {
            window.define = originalDefine;
        }
        if (originalRequire) {
            window.require = originalRequire;
        }
        
        // Try fallback to local file
        var fallbackScript = document.createElement('script');
        fallbackScript.src = '/js/lib/jszip.min.js';
        fallbackScript.onload = function() {
            if (window.JSZip) {
                window.dispatchEvent(new CustomEvent('jszipReady', { detail: window.JSZip }));
            }
        };
        fallbackScript.onerror = function() {
            window.dispatchEvent(new CustomEvent('jszipError', { detail: 'Failed to load JSZip' }));
        };
        document.head.appendChild(fallbackScript);
    };
    
    // Add the script to start loading
    document.head.appendChild(script);
})(); 
