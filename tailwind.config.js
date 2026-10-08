import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
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
            colors: {
                // ── GMTM Brand Scale (driven by tokens.css) ──
                brand: {
                    50:  '#f0faf3',
                    100: '#d9f2e1',
                    200: '#b3e4c4',
                    300: '#7dcfa0',
                    400: '#46b576',
                    500: '#289858',
                    600: '#1a7a44',
                    700: '#1a5f2a',
                    800: '#174d24',
                    900: '#133d1d',
                    950: '#0a2010',
                },
                // ── Semantic Aliases ──────────────────────────
                primary:  'var(--color-primary)',
                danger:   'var(--color-danger)',
                warning:  'var(--color-warning)',
                success:  'var(--color-success)',
                info:     'var(--color-info)',
            },
        },
    },

    plugins: [forms],

    safelist: [
        'grid-cols-4',
        'translate-x-0',
        'translate-x-7',
        // brand shades used dynamically
        { pattern: /bg-brand-(50|100|200|500|600|700|800|950)/ },
        { pattern: /text-brand-(300|400|500|600|700)/ },
        { pattern: /border-brand-(200|600|800)/ },
        { pattern: /ring-brand-(500|600)/ },
    ],
};

