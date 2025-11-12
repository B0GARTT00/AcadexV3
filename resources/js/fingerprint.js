import FingerprintJS from '@fingerprintjs/fingerprintjs';

/**
 * Initialize and get device fingerprint
 * This creates a unique identifier for the device that persists across sessions
 */
export async function getDeviceFingerprint() {
    try {
        // Initialize the agent
        const fp = await FingerprintJS.load();
        
        // Get the visitor identifier
        const result = await fp.get();
        
        // Return the unique fingerprint
        return result.visitorId;
    } catch (error) {
        console.error('Error generating fingerprint:', error);
        // Fallback to a simple hash if fingerprinting fails
        return generateFallbackFingerprint();
    }
}

/**
 * Fallback fingerprint generation using basic browser properties
 */
function generateFallbackFingerprint() {
    const data = [
        navigator.userAgent,
        navigator.language,
        screen.colorDepth,
        screen.width + 'x' + screen.height,
        new Date().getTimezoneOffset(),
        navigator.hardwareConcurrency || 'unknown',
        navigator.deviceMemory || 'unknown'
    ].join('|||');
    
    // Simple hash function
    let hash = 0;
    for (let i = 0; i < data.length; i++) {
        const char = data.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Convert to 32bit integer
    }
    return Math.abs(hash).toString(16);
}

/**
 * Store fingerprint in a hidden input or send with login form
 * @deprecated Use getDeviceFingerprint() directly instead
 */
export function injectFingerprint(formSelector) {
    getDeviceFingerprint().then(fingerprint => {
        const form = document.querySelector(formSelector);
        if (form) {
            // Check if input already exists
            let input = form.querySelector('input[name="device_fingerprint"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'device_fingerprint';
                form.appendChild(input);
            }
            input.value = fingerprint;
        }
    });
}
