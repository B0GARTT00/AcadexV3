/**
 * Smooth Page Transitions
 * Provides smooth loading experience during navigation
 */

(function() {
    'use strict';
    
    // Minimum loading time for smooth UX (in ms)
    const MIN_LOADING_TIME = 200;
    let pageLoadStart = Date.now();
    
    // Fade in page when loaded
    window.addEventListener('load', function() {
        const elapsed = Date.now() - pageLoadStart;
        const remainingTime = Math.max(0, MIN_LOADING_TIME - elapsed);
        
        setTimeout(function() {
            document.body.classList.add('loaded');
        }, remainingTime);
    });
    
    // Handle page visibility changes
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && document.readyState === 'complete') {
            setTimeout(function() {
                document.body.classList.add('loaded');
            }, 100);
        }
    });
    
    // Track navigation
    let isNavigating = false;
    
    // Handle internal link clicks
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        
        if (link && 
            link.href && 
            link.href.indexOf(window.location.origin) === 0 &&
            !link.hasAttribute('target') &&
            !link.hasAttribute('download') &&
            !link.getAttribute('href').startsWith('#') &&
            !link.classList.contains('dropdown-toggle') &&
            !link.closest('.dropdown-menu') &&
            !link.closest('[data-bs-toggle]')) {
            
            isNavigating = true;
            pageLoadStart = Date.now();
        }
    });
    
    // Handle browser back/forward navigation
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            pageLoadStart = Date.now();
            setTimeout(function() {
                document.body.classList.add('loaded');
            }, MIN_LOADING_TIME);
        }
    });
    
    // Handle form submissions
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form && !form.hasAttribute('target')) {
            isNavigating = true;
            pageLoadStart = Date.now();
            document.body.classList.remove('loaded');
        }
    });
    
})();
