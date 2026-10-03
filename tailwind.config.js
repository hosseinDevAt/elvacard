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
        'bg-[#ffde5b]', 'bg-[#010619]', 'text-[#010619]', 'text-[#ffde5b]',
        'border-[#ffde5b]', 'border-[#010619]',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Yekan Bakh', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    yellow: '#ffde5b',
                    'yellow-hover': '#f5d347',
                    'yellow-active': '#e8c535',
                    'yellow-light': '#fffbe6',
                    'yellow-subtle': '#fff9db',
                    navy: '#010619',
                    'navy-hover': '#08112e',
                    'navy-card': '#070e24',
                    'navy-border': '#152244',
                    'navy-muted': '#1f2e54',
                    'navy-light': '#2a3b66',
                },
                navy: {
                    50:  '#f1f5fa',
                    100: '#dde6f2',
                    200: '#b9cbe3',
                    300: '#8da6cc',
                    400: '#5f7cad',
                    500: '#3f5b8c',
                    600: '#2c4270',
                    700: '#22325a',
                    800: '#182444',
                    900: '#010619', // Brand Dark Navy
                    950: '#010410',
                },
                primary: {
                    50:  '#f2f5fb',
                    100: '#e1e8f5',
                    200: '#c2d1ec',
                    300: '#94b1de',
                    400: '#5f8bcc',
                    500: '#3866ab',
                    600: '#010619', // Brand Dark Navy
                    700: '#010514',
                    800: '#00030c',
                    900: '#000206',
                },
                accent: {
                    50:  '#fffdf2',
                    100: '#fff8d6',
                    200: '#fff0a8',
                    300: '#ffe77c',
                    400: '#ffe05e',
                    500: '#ffde5b', // Brand Primary Yellow
                    600: '#ecc63a',
                    700: '#cfa71c',
                },
            },
        },
    },

    plugins: [forms],
};
