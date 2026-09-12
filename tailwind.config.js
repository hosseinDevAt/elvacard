import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        'bg-primary-50', 'bg-primary-600', 'bg-primary-700',
        'bg-accent-400', 'bg-accent-500', 'bg-accent-600',
        'text-primary-50', 'text-primary-100', 'text-primary-200',
        'text-primary-300', 'text-primary-600', 'text-primary-700', 'text-primary-900',
        'text-accent-500',
        'hover:bg-primary-700', 'hover:border-primary-200', 'hover:text-primary-600',
        'hover:bg-accent-500', 'hover:bg-accent-600',
        'from-primary-100', 'border-primary-200',
        'border-accent-500', 'focus:ring-accent-200', 'focus:border-accent-500',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Vazirmatn', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50:  '#f2f4f8',
                    100: '#e3e7f0',
                    200: '#c3cbdc',
                    300: '#97a3c2',
                    400: '#5f7099',
                    500: '#2b3d68',
                    600: '#00071a',
                    700: '#000512',
                    800: '#00030c',
                    900: '#000206',
                },
                accent: {
                    50:  '#fffdf2',
                    100: '#fff8d6',
                    200: '#fff0a8',
                    300: '#ffe77c',
                    400: '#ffe05e',
                    500: '#ffde5b',
                    600: '#ecc63a',
                    700: '#cfa71c',
                },
            },
        },
    },

    plugins: [forms],
};
