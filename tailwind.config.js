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
      },
      fontFamily: {
        'jetbrains': ['JetBrains Mono', 'monospace'],
        'space': ['Space Grotesk', 'sans-serif'],
      },
      animation: {
        'cyber-pulse': 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'cyber-glow': 'glow 2s ease-in-out infinite alternate',
        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
      },
      keyframes: {
        glow: {
          '0%': { textShadow: '0 0 4px #00F0FF' },
          '100%': { textShadow: '0 0 8px #00F0FF, 0 0 12px #00F0FF' },
        }
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}
