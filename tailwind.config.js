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
                brand: {
                    DEFAULT: '#ff0033',
                    50: '#fff0f3',
                    100: '#ffe0e6',
                    200: '#ffc7d2',
                    300: '#ff9dae',
                    400: '#ff6382',
                    500: '#ff0033',
                    600: '#e60030',
                    700: '#c0002a',
                    800: '#a00025',
                    900: '#850024',
                },
                accent: {
                    DEFAULT: '#3ea6ff',
                    dark: '#065fd4',
                },
                surface: {
                    light: '#ffffff',
                    'light-alt': '#f8fafc',
                    dark: '#0f0f0f',
                    'dark-alt': '#1a1a1a',
                    'dark-elevated': '#212121',
                },
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.25rem',
            },
            spacing: {
                '18': '4.5rem',
                'header': '64px',
                'sidebar': '240px',
                'sidebar-collapsed': '72px',
            },
            zIndex: {
                'header': '40',
                'sidebar': '30',
                'modal': '50',
                'dropdown': '45',
                'overlay': '35',
            },
            animation: {
                'fade-in': 'fadeIn 0.2s ease-out',
                'slide-in-left': 'slideInLeft 0.2s ease-out',
                'slide-in-right': 'slideInRight 0.2s ease-out',
                'scale-in': 'scaleIn 0.15s ease-out',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideInLeft: {
                    '0%': { transform: 'translateX(-100%)' },
                    '100%': { transform: 'translateX(0)' },
                },
                slideInRight: {
                    '0%': { transform: 'translateX(100%)' },
                    '100%': { transform: 'translateX(0)' },
                },
                scaleIn: {
                    '0%': { transform: 'scale(0.95)', opacity: '0' },
                    '100%': { transform: 'scale(1)', opacity: '1' },
                },
            },
        },
    },

    plugins: [forms],
};
