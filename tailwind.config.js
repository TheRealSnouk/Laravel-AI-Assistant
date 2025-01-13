/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        'cyber-black': '#0D0D0D',
        'cyber-dark': '#1A1A1A',
        'cyber-blue': '#00F0FF',
        'cyber-pink': '#FF00F5',
        'cyber-green': '#00FF9F',
        'cyber-yellow': '#FFE600',
        'hedera': '#222222',
        'paypal': '#003087',
        'stripe': '#635BFF',
        'sunset-light': '#FDEECA',
        'sunset-primary': '#F9C80E',
        'sunset-secondary': '#F86624',
        'sunset-dark': '#EA3546',
        'forest-light': '#C8E6C9',
        'forest-primary': '#4CAF50',
        'forest-secondary': '#8BC34A',
        'forest-dark': '#388E3C',
        'ocean-light': '#E0F7FA',
        'ocean-primary': '#00BCD4',
        'ocean-secondary': '#4DD0E1',
        'ocean-dark': '#0097A7',
        'neon-primary': '#00FF00',
        'neon-secondary': '#FFFF00',
        'neon-accent': '#FF00FF',
        'pastel-light': '#F0F8FF',
        'pastel-primary': '#B0E0E6',
        'pastel-secondary': '#ADD8E6',
        'monochrome-light': '#F0F0F0',
        'monochrome-medium': '#808080',
        'monochrome-dark': '#333333'
      },
      fontFamily: {
        'jetbrains': ['JetBrains Mono', 'monospace'],
        'space': ['Space Grotesk', 'sans-serif']
      },
      animation: {
        'cyber-pulse': 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'cyber-glow': 'glow 2s ease-in-out infinite alternate',
        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite'
      },
      keyframes: {
        glow: {
          '0%': { textShadow: '0 0 4px #00F0FF' },
          '100%': { textShadow: '0 0 8px #00F0FF, 0 0 12px #00F0FF' }
        }
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms')
  ],
}
