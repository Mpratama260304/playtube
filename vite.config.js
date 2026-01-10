import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Detect GitHub Codespaces environment
const codespaceName = process.env.CODESPACE_NAME;
const isCodespaces = !!codespaceName;
const codespacesHost = isCodespaces ? `${codespaceName}-5173.app.github.dev` : null;
const appHost = isCodespaces ? `${codespaceName}-8000.app.github.dev` : null;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // Set origin for proper asset URLs in Codespaces
        origin: isCodespaces ? `https://${codespacesHost}` : undefined,
        hmr: isCodespaces
            ? {
                  // HMR over HTTPS WebSocket in Codespaces
                  protocol: 'wss',
                  host: codespacesHost,
                  clientPort: 443,
              }
            : undefined,
        // Explicit CORS headers for Codespaces cross-origin requests
        headers: isCodespaces
            ? {
                  'Access-Control-Allow-Origin': '*',
                  'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
                  'Access-Control-Allow-Headers': 'X-Requested-With, content-type, Authorization',
              }
            : undefined,
    },
});
