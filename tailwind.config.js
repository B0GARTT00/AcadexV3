import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    // Use class-based dark mode so the site only switches to dark styles
    // when an explicit `.dark` class is present on <html> or <body>.
    // This prevents following the OS `prefers-color-scheme` setting.
    darkMode: 'class',

    plugins: [forms],
};
