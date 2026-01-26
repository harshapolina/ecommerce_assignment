import { build } from 'vite';
import config from './vite.config.js';

build(config).catch((err) => {
  console.error(err);
  process.exit(1);
});

