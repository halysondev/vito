import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import { defineConfig, loadEnv } from 'vite';

const resolveHmrHost = (appUrl?: string): string => {
  try {
    return new URL(appUrl ?? 'http://localhost').hostname;
  } catch {
    return 'localhost';
  }
};

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const vitePort = Number(env.VITE_PORT ?? 5173);

  return {
    plugins: [
      laravel({
        input: ['resources/css/app.css', 'resources/js/app.tsx'],
        ssr: 'resources/js/ssr.tsx',
        refresh: true,
      }),
      react(),
      tailwindcss(),
    ],
    esbuild: {
      jsx: 'automatic',
    },
    server: {
      host: '0.0.0.0',
      port: vitePort,
      strictPort: true,
      hmr: {
        host: resolveHmrHost(env.APP_URL),
      },
    },
    resolve: {
      alias: {
        'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
      },
    },
  };
});
