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
                // Body: Inter. Heading/UI: Plus Jakarta Sans (docs/spec.md bagian 10).
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                heading: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Warna identitas modul (chip kode A10-A70), docs/spec.md bagian 10.
                // Primary (indigo/violet) tidak di-alias terpisah karena sudah ada
                // langsung di palet default Tailwind (indigo-600, violet-600, dst).
                module: {
                    a10: '#6366F1',
                    a20: '#0EA5E9',
                    a30: '#10B981',
                    a40: '#F59E0B',
                    a50: '#F43F5E',
                    a60: '#F97316',
                    a70: '#65A30D',
                },
            },
        },
    },

    plugins: [forms],
};
