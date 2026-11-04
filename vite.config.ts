import { defineConfig } from 'vite';
import Symfony from '@symfony/reprise/vite';

export default defineConfig({
    input: {
        app: './assets/app.js',
    },
    plugins: [
        Symfony({
            // Defaults match config/packages/reprise.yaml: assets are
            // emitted to public/build, entrypoints.json and manifest.json
            // are written next to them.
            //
            // When the browser reaches the dev server (npm run dev) through
            // another address than the server itself - e.g. a Docker port
            // mapping - set the public origin here:
            // devServerOrigin: 'http://localhost:5173',
        }),
    ],
    server: {
        // Expose the dev server so it can be reached from outside the
        // container:
        // podman compose run --rm --service-ports frontend npm run dev
        host: true,
        port: 5173,
        strictPort: true,
    },
});
