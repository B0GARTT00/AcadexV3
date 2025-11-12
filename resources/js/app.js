import 'bootstrap/dist/css/bootstrap.min.css';
// Bootstrap Icons loaded via CDN for better caching
import '@fortawesome/fontawesome-free/css/all.min.css';

import Alpine from 'alpinejs';
import { getDeviceFingerprint } from './fingerprint';

window.Alpine = Alpine;

Alpine.start();

// Initialize device fingerprinting on login page
document.addEventListener('DOMContentLoaded', async () => {
    const loginForms = document.querySelectorAll('form[action*="login"]');
    
    if (loginForms.length > 0) {
        // Get fingerprint immediately
        const fingerprint = await getDeviceFingerprint();
        console.log('Device fingerprint generated:', fingerprint);
        
        loginForms.forEach(form => {
            // Check if input already exists
            let input = form.querySelector('input[name="device_fingerprint"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'device_fingerprint';
                form.appendChild(input);
            }
            input.value = fingerprint;
            
            // Also add to form submit handler to ensure it's always set
            form.addEventListener('submit', function(e) {
                input.value = fingerprint;
                console.log('Submitting with fingerprint:', fingerprint);
            });
        });
    }
});
