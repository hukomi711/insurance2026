const { spawnSync } = require('node:child_process');
const path = require('node:path');

const ROOT = path.resolve(__dirname, '..', '..');

const result = spawnSync('npm outdated', {
    cwd: ROOT,
    stdio: 'inherit',
    shell: true,
});

if (result.error) {
    console.error(result.error.message);
}

process.exit(0);
