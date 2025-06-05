// JSZip Global Loader - Forces JSZip to be available globally
(function() {
    'use strict';
    
    // If JSZip is already available, don't load again
    if (window.JSZip) {
        window.dispatchEvent(new CustomEvent('jszipReady', { detail: window.JSZip }));
        return;
    }
    
    // Store original AMD define to restore later
    var originalDefine = window.define;
    var originalExports = window.exports;
    var originalModule = window.module;
    
    // Temporarily disable AMD/CommonJS detection
    if (typeof define !== 'undefined') {
        window.define = undefined;
    }
    if (typeof exports !== 'undefined') {
        window.exports = undefined;
    }
    if (typeof module !== 'undefined') {
        window.module = undefined;
    }
    
    // Create a script element to load JSZip
    var script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js';
    
    script.onload = function() {
        // Restore AMD/CommonJS if they existed
        if (originalDefine) {
            window.define = originalDefine;
        }
        if (originalExports) {
            window.exports = originalExports;
        }
        if (originalModule) {
            window.module = originalModule;
        }
        
        // Dispatch a custom event to notify that JSZip is ready
        if (window.JSZip) {
            window.dispatchEvent(new CustomEvent('jszipReady', { detail: window.JSZip }));
        } else {
            tryLocalFallback();
        }
    };
    
    script.onerror = function() {
        // Restore AMD/CommonJS if they existed
        if (originalDefine) {
            window.define = originalDefine;
        }
        if (originalExports) {
            window.exports = originalExports;
        }
        if (originalModule) {
            window.module = originalModule;
        }
        
        tryLocalFallback();
    };
    
    function tryLocalFallback() {
        // Try fallback to local file
        var fallbackScript = document.createElement('script');
        fallbackScript.src = '/js/lib/jszip.min.js';
        fallbackScript.onload = function() {
            if (window.JSZip) {
                window.dispatchEvent(new CustomEvent('jszipReady', { detail: window.JSZip }));
            } else {
                window.dispatchEvent(new CustomEvent('jszipError', { detail: 'JSZip local fallback loaded but not available' }));
            }
        };
        fallbackScript.onerror = function() {
            window.dispatchEvent(new CustomEvent('jszipError', { detail: 'Both CDN and local JSZip failed to load' }));
        };
        document.head.appendChild(fallbackScript);
    }
    
    // Add the script to start loading
    document.head.appendChild(script);
})(); 
