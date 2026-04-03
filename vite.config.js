import { defineConfig } from 'vite';
import basicSsl from '@vitejs/plugin-basic-ssl';
import { viteStaticCopy } from 'vite-plugin-static-copy';

// VITE_DEV_HTTPS=1 when PHP is https:// and the browser blocks http:// Vite (mixed content).
const useHttps =
  process.env.VITE_DEV_HTTPS === '1' || process.env.VITE_DEV_HTTPS === 'true';

export default defineConfig({
  publicDir: false,
  plugins: [
    ...(useHttps ? [basicSsl()] : []),
    viteStaticCopy({
      targets: [
        {
          src: 'node_modules/flag-icons/flags/*',
          dest: 'flags'
        },
        {
          src: 'node_modules/flag-icons/css/flag-icons.min.css',
          dest: './'
        },
        {
          src: 'app/resources/js/crud-table.js',
          dest: './'
        }
      ]
    })
  ],
  build: {
    outDir: 'public_html/dist',
    emptyOutDir: true,
    rollupOptions: {
      input: 'app/resources/js/app.js',
      output: {
        entryFileNames: `app.js`,
        assetFileNames: `app.[ext]`
      }
    }
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: false,
    cors: true,
    https: useHttps,
    hmr: {
      host: 'localhost',
      protocol: useHttps ? 'wss' : 'ws'
    }
  }
});
