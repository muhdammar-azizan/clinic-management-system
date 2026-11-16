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
            // To try another font, change the first name here and the font link in the layouts.
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                // Header, headings
                deep: '#0B3C6D',

                // Links, buttons, focus
                primary: {
                    DEFAULT: '#0A6FD1',
                    hover: '#0859A8',
                    soft: '#E8F2FC',
                },

                // Accent only. Never use for errors, delete, cancel or critical states.
                accent: {
                    DEFAULT: '#C2540A',
                    hover: '#A14508',
                    soft: '#FFF1E6',
                },

                // Page background, text and borders
                canvas: '#FDFDFE',
                ink: '#0B2540',
                muted: '#5B6B7F',
                line: '#E3E8EF',

                // Status colors. "dark" is the text color on top of the "soft" background.
                success: { DEFAULT: '#1E9E6A', soft: '#E6F6EF', dark: '#14724B' },
                warning: { DEFAULT: '#E8A317', soft: '#FEF5DD', dark: '#8A5B00' },
                danger: { DEFAULT: '#C81E3C', soft: '#FDE8EC', dark: '#A3172F' },
                neutral: { DEFAULT: '#8A97A8', soft: '#EEF1F5', dark: '#4A5668' },
            },

            borderRadius: {
                card: '8px',
            },

            boxShadow: {
                card: '0 1px 2px rgba(11, 37, 64, 0.06)',
                pop: '0 8px 24px rgba(11, 37, 64, 0.12)',
            },
        },
    },

    plugins: [forms],
};
