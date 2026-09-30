/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,ts}',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
            },
            colors: {
                // Petrol-station palette: dark navy shell, amber/green accents.
                navy: {
                    50: '#f2f6fa', 100: '#e2eaf3', 200: '#c0d4e6', 300: '#8fb4d3',
                    400: '#578ebc', 500: '#356fa4', 600: '#255a8c', 700: '#1d4773',
                    800: '#0f2540', 900: '#091a2e', 950: '#06111f',
                },
                amberx: {
                    400: '#fbbf24', 500: '#f59f00', 600: '#d97706',
                },
            },
            boxShadow: {
                card: '0 1px 2px 0 rgb(9 26 46 / 0.06), 0 1px 3px 0 rgb(9 26 46 / 0.10)',
            },
        },
    },
    plugins: [],
};
