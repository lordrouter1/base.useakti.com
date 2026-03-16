import app from './src/app.js';
import { env } from './src/config/env.js';

const PORT = env.PORT;

app.listen(PORT, () => {
  console.log(`[Akti API] Running in ${env.NODE_ENV} mode on port ${PORT}`);
});
