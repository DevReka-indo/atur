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

            keyframes: {
                dropIn: {
                    from: { opacity: '0', transform: 'scale(0.92) translateX(6px)' },
                    to:   { opacity: '1', transform: 'scale(1) translateX(0)' },
                },
                modalIn: {
                    from: { opacity: '0', transform: 'scale(.9) translateY(10px)' },
                    to:   { opacity: '1', transform: 'scale(1) translateY(0)' },
                },
            },
            animation: {
                'drop-in':  'dropIn 0.12s ease',
                'modal-in': 'modalIn 0.18s ease',
            },
        },
    },

    plugins: [forms],
};
