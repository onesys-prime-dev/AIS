/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './app/Views/**/*.php',
    './app/Controllers/**/*.php',
    './app/Cells/**/*.php',
    './app/Libraries/**/*.php',
    './public/js/**/*.js',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
  safelist: [
    'grid-cols-[1fr_1fr_1fr_1fr_1fr_1fr_0.8fr]',
  ],
}