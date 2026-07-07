/** @type {import('tailwindcss').Config} */
module.exports = {
  // ─── Purge: scan all PHP/HTML/JS files ─────────────────────────────────────
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/Http/**/*.php",
    "./app/Services/**/*.php",
    "./public/index.php",
    "./public/assets/js/**/*.js",
  ],

  // ─── Dark Mode via [data-theme="dark"] ──────────────────────────────────────
  darkMode: ['selector', '[data-theme="dark"]'],

  theme: {
    extend: {
      // ─── Color Palette ───────────────────────────────────────────────────────
      colors: {
        primary: {
          50:  '#f0f9ff',
          100: '#e0f2fe',
          200: '#bae6fd',
          300: '#7dd3fc',
          400: '#38bdf8',
          500: '#0ea5e9',   // Sky Blue main
          600: '#0284c7',   // Sky Blue hover / accessible contrast
          700: '#0369a1',
          800: '#075985',
          900: '#0c4a6e',
        },
        emerald: {
          50:  '#e6f5ec',
          100: '#cdebd9',
          200: '#9cd7b3',
          300: '#62bd88',
          400: '#2ea35e',
          500: '#046a38',   // Deep Emerald
          600: '#03542c',
          700: '#023f21',
          800: '#012a16',
          900: '#01150b',
        },
        surface: {
          1: 'hsl(220, 20%, 98%)',
          2: 'hsl(220, 15%, 94%)',
          3: 'hsl(220, 12%, 90%)',
        },
        gov: {
          green:  '#046a38', // Deep Emerald Success
          amber:  '#f0a500',
          red:    '#e53e3e',
          blue:   '#0066cc', // Azure Blue Primary
          navy:   '#0a2540',
          silver: '#8b98a8',
        },
      },

      // ─── Font Family (Cairo Arabic + Inter Latin) ────────────────────────
      fontFamily: {
        sans: ['Cairo', 'Inter', 'system-ui', 'sans-serif'],
        arabic: ['Cairo', 'sans-serif'],
        latin: ['Inter', 'system-ui', 'sans-serif'],
      },

      // ─── Font Size Scale ────────────────────────────────────────────────────
      fontSize: {
        '2xs':  ['0.65rem', { lineHeight: '1rem' }],
        'xs':   ['0.72rem', { lineHeight: '1.125rem' }],
        'sm':   ['0.82rem', { lineHeight: '1.25rem' }],
        'base': ['0.92rem', { lineHeight: '1.5rem' }],
        'lg':   ['1.05rem', { lineHeight: '1.6rem' }],
        'xl':   ['1.2rem',  { lineHeight: '1.75rem' }],
        '2xl':  ['1.4rem',  { lineHeight: '2rem' }],
        '3xl':  ['1.75rem', { lineHeight: '2.25rem' }],
        '4xl':  ['2.25rem', { lineHeight: '2.75rem' }],
        
        // Exact tokens from specification
        'micro':        ['0.75rem', { lineHeight: '1rem' }],      // 12px
        'body-normal':  ['1rem', { lineHeight: '1.5rem' }],        // 16px
        'card-header':  ['1.5rem', { lineHeight: '2rem' }],       // 24px
        'card-header-lg': ['2rem', { lineHeight: '2.5rem' }],     // 32px
        'page-title':   ['3rem', { lineHeight: '3.5rem' }],       // 48px
      },

      // ─── Spacing ─────────────────────────────────────────────────────────────
      spacing: {
        'dock': '64px',
        '4.5': '1.125rem',
        '5.5': '1.375rem',
        '13': '3.25rem',
        '15': '3.75rem',
      },

      // ─── Border Radius ────────────────────────────────────────────────────────
      borderRadius: {
        '4xl': '2rem',
        '5xl': '2.5rem',
      },

      // ─── Box Shadow (Glassmorphism) ────────────────────────────────────────
      boxShadow: {
        'glass': '0 4px 24px rgba(10, 37, 64, 0.06), 0 1px 4px rgba(10, 37, 64, 0.04)',
        'glass-lg': '0 8px 48px rgba(10, 37, 64, 0.10), 0 2px 8px rgba(10, 37, 64, 0.06)',
        'card': '0 2px 12px rgba(10, 37, 64, 0.08)',
        'card-hover': '0 8px 32px rgba(26, 107, 204, 0.15)',
        'dock': '-4px 0 40px rgba(10, 37, 64, 0.12)',
        'primary': '0 4px 16px rgba(26, 107, 204, 0.35)',
      },

      // ─── Backdrop Blur ────────────────────────────────────────────────────────
      backdropBlur: {
        'xs': '4px',
        'glass': '20px',
        'heavy': '40px',
      },

      // ─── Animation ────────────────────────────────────────────────────────────
      keyframes: {
        shimmer: {
          '0%':   { backgroundPosition: '200% 0' },
          '100%': { backgroundPosition: '-200% 0' },
        },
        fadeUp: {
          '0%':   { opacity: '0', transform: 'translateY(12px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        fadeIn: {
          '0%':   { opacity: '0' },
          '100%': { opacity: '1' },
        },
        scaleIn: {
          '0%':   { opacity: '0', transform: 'scale(0.95)' },
          '100%': { opacity: '1', transform: 'scale(1)' },
        },
        slideRight: {
          '0%':   { transform: 'translateX(-100%)' },
          '100%': { transform: 'translateX(0)' },
        },
        pulse: {
          '0%, 100%': { opacity: '1' },
          '50%':      { opacity: '0.5' },
        },
        countUp: {
          '0%':   { opacity: '0', transform: 'translateY(8px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
      },
      animation: {
        'shimmer':    'shimmer 1.8s linear infinite',
        'fade-up':    'fadeUp 0.3s ease-out forwards',
        'fade-in':    'fadeIn 0.2s ease-out forwards',
        'scale-in':   'scaleIn 0.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards',
        'slide-right':'slideRight 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards',
        'count-up':   'countUp 0.5s ease-out forwards',
      },

      // ─── Transition Timing ─────────────────────────────────────────────────
      transitionTimingFunction: {
        'spring': 'cubic-bezier(0.34, 1.56, 0.64, 1)',
        'smooth': 'cubic-bezier(0.4, 0, 0.2, 1)',
      },

      // ─── Grid (Bento Layout) ─────────────────────────────────────────────
      gridTemplateColumns: {
        'bento': 'repeat(12, 1fr)',
        'bento-sm': 'repeat(6, 1fr)',
      },
    },
  },

  corePlugins: {
    preflight: false,
  },

  // ─── Plugins ────────────────────────────────────────────────────────────────
  plugins: [],
};
