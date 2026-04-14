import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    root: '.',
    base: '/assets/dist/',
    build: {
        outDir: 'assets/dist',
        emptyOutDir: true,
        sourcemap: true,
        rollupOptions: {
            input: {
                // CSS principal
                theme: resolve(__dirname, 'assets/css/theme.css'),
                'design-system': resolve(__dirname, 'assets/css/design-system.css'),
                // JS principal
                app: resolve(__dirname, 'assets/js/script.js'),
                // Componentes
                toast: resolve(__dirname, 'assets/js/components/toast.js'),
                skeleton: resolve(__dirname, 'assets/js/components/skeleton.js'),
                shortcuts: resolve(__dirname, 'assets/js/components/shortcuts.js'),
                'command-palette': resolve(__dirname, 'assets/js/components/command-palette.js'),
                'notification-bell': resolve(__dirname, 'assets/js/components/notification-bell.js'),
                'session-timeout': resolve(__dirname, 'assets/js/components/session-timeout.js'),
                // Módulos de página
                stock: resolve(__dirname, 'assets/js/modules/stock.js'),
                pipeline: resolve(__dirname, 'assets/js/modules/pipeline.js'),
                'workflows-index': resolve(__dirname, 'assets/js/modules/workflows-index.js'),
                'workflows-form': resolve(__dirname, 'assets/js/modules/workflows-form.js'),
                'supply-movements': resolve(__dirname, 'assets/js/modules/supply-movements.js'),
                // Select2 integrations
                'customer-select2': resolve(__dirname, 'assets/js/customer-select2.js'),
                'product-select2': resolve(__dirname, 'assets/js/product-select2.js'),
                // Walkthrough
                walkthrough: resolve(__dirname, 'assets/js/walkthrough.js'),
            },
            output: {
                entryFileNames: 'js/[name].[hash].js',
                chunkFileNames: 'js/[name].[hash].js',
                assetFileNames: 'css/[name].[hash][extname]',
            },
        },
        minify: 'terser',
        terserOptions: {
            compress: { drop_console: true },
        },
    },
    css: {
        devSourcemap: true,
    },
});
