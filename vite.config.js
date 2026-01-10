import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Detect GitHub Codespaces environment
const codespaceName = process.env.CODESPACE_NAME;
const isCodespaces = !!codespaceName;
const codespacesHost = isCodespaces ? `${codespaceName}-5173.app.github.dev` : null;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: isCodespaces
        ? {
              // Codespaces configuration
              host: true,
              port: 5173,
              strictPort: true,
              // Enable CORS for cross-origin requests from 8000 to 5173
              cors: {
                  origin: true,
              },
              // Explicit CORS headers as backup
              headers: {
                  'Access-Control-Allow-Origin': '*',
                  'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
                  'Access-Control-Allow-Headers': 'X-Requested-With, content-type, Authorization',
              },
              // Set origin for proper asset URLs in Codespaces HTTPS
              origin: `https://${codespacesHost}`,
              // HMR over secure WebSocket in Codespaces
              hmr: {
                  protocol: 'wss',
                  host: codespacesHost,
                  clientPort: 443,
              },
              // Allow Codespaces proxy hosts
              allowedHosts: ['.app.github.dev'],
          }
        : {
              // Local development configuration
              host: true,
              port: 5173,
              strictPort: true,
          },
});
