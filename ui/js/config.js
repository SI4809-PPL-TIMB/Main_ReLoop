// Tailwind Play CDN config. Saat pindah ke Laravel, salin `theme.extend` ke tailwind.config.js.
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['Figtree', 'system-ui', 'sans-serif'] },
      colors: {
        ink: '#15181c',
        // Warna logo Reloop
        loop: { DEFAULT: '#9be600', dark: '#5f9200', soft: '#f3fbe3' },
        brand: {
          50: '#ecfbf1', 100: '#d3f5df', 200: '#a9eac2', 300: '#72d99c', 400: '#3fc476',
          500: '#22b45a', 600: '#179447', 700: '#14753b', 800: '#135d32', 900: '#0f3d22',
        },
        kraft: { 50: '#fbf5ec', 100: '#f6ead9', 300: '#e2bf8f', 500: '#c8955c', 700: '#8a5f33' },
      },
      boxShadow: { card: '0 8px 24px -12px rgba(16, 40, 24, .22)' },
    },
  },
};
