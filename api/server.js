import { readFileSync, existsSync } from 'node:fs';
import { createServer as createHttpsServer } from 'node:https';
import app from './src/app.js';
import { env } from './src/config/env.js';
import { testMasterConnection, tenantPool } from './src/config/database.js';

const PORT = env.PORT;
const HTTPS_PORT = env.HTTPS_PORT;

(async () => {
  try {
    // Testar conexão com o banco master antes de aceitar requests
    await testMasterConnection();

    // HTTP server
    app.listen(PORT, () => {
      console.log(`[Akti API] HTTP running in ${env.NODE_ENV} mode on port ${PORT}`);
    });

    // HTTPS server (se certificados disponíveis)
    if (env.SSL_CERT && env.SSL_KEY && existsSync(env.SSL_CERT) && existsSync(env.SSL_KEY)) {
      const sslOptions = {
        cert: readFileSync(env.SSL_CERT),
        key: readFileSync(env.SSL_KEY),
      };
      createHttpsServer(sslOptions, app).listen(HTTPS_PORT, () => {
        console.log(`[Akti API] HTTPS running on port ${HTTPS_PORT}`);
      });
    } else {
      console.log('[Akti API] SSL certificates not found — HTTPS disabled.');
      console.log(`  Configure SSL_CERT and SSL_KEY in .env to enable HTTPS.`);
    }
  } catch (err) {
    console.error('[Akti API] Failed to start:', err.message);
    process.exit(1);
  }
})();

// ── Graceful shutdown ──
async function shutdown(signal) {
  console.log(`\n[Akti API] ${signal} received. Shutting down gracefully...`);
  await tenantPool.closeAll();
  process.exit(0);
}

process.on('SIGINT', () => shutdown('SIGINT'));
process.on('SIGTERM', () => shutdown('SIGTERM'));
