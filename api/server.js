import app from './src/app.js';
import { env } from './src/config/env.js';
import { testMasterConnection, tenantPool } from './src/config/database.js';

const PORT = env.PORT;

(async () => {
  try {
    // Testar conexão com o banco master antes de aceitar requests
    await testMasterConnection();

    app.listen(PORT, () => {
      console.log(`[Akti API] Running in ${env.NODE_ENV} mode on port ${PORT}`);
    });
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
