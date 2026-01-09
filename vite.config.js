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
    server: {
        host: true, // Listen on all addresses (0.0.0.0)
        port: 5173,
        strictPort: true,
        cors: true,
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
    },
});
