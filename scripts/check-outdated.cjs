const { spawnSync } = require('node:child_process');

const result = spawnSync('npm outdated', {
    stdio: 'inherit',
    shell: true,
});

if (result.error) {
    console.error(result.error.message);
}

process.exit(0);
