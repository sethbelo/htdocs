const crypto = require('crypto');
const fs = require('fs');
const https = require('https');
const os = require('os');
const path = require('path');
const { spawnSync } = require('child_process');
const {
  POSTGRES_ARCHIVE_SHA256,
  POSTGRES_DOWNLOAD_URL,
  POSTGRES_VERSION,
  isCompleteRuntime,
} = require('../postgres-runtime.cjs');

const projectRoot = path.resolve(__dirname, '..');
const destination = path.join(projectRoot, 'postgres-win');

function download(url, destinationPath, redirects = 0) {
  return new Promise((resolve, reject) => {
    if (redirects > 5) return reject(new Error('Trop de redirections HTTP.'));

    https.get(url, response => {
      if (response.statusCode >= 300 && response.statusCode < 400 && response.headers.location) {
        response.resume();
        download(new URL(response.headers.location, url).toString(), destinationPath, redirects + 1).then(resolve, reject);
        return;
      }
      if (response.statusCode !== 200) {
        response.resume();
        reject(new Error(`Téléchargement refusé (HTTP ${response.statusCode}).`));
        return;
      }

      const total = Number(response.headers['content-length']) || 0;
      let received = 0;
      const output = fs.createWriteStream(destinationPath);
      response.on('data', chunk => {
        received += chunk.length;
        if (total > 0) process.stdout.write(`\rTéléchargement PostgreSQL ${Math.floor((received / total) * 100)} %`);
      });
      response.pipe(output);
      output.on('finish', () => output.close(() => {
        process.stdout.write('\n');
        resolve();
      }));
      output.on('error', reject);
    }).on('error', reject);
  });
}

function extract(archivePath, extractPath) {
  const result = process.platform === 'win32'
    ? spawnSync('powershell.exe', ['-NoProfile', '-NonInteractive', '-Command', `Expand-Archive -LiteralPath '${archivePath.replaceAll("'", "''")}' -DestinationPath '${extractPath.replaceAll("'", "''")}' -Force`], { stdio: 'inherit' })
    : spawnSync('unzip', ['-q', archivePath, '-d', extractPath], { stdio: 'inherit' });

  if (result.error || result.status !== 0) throw new Error('Extraction de PostgreSQL impossible.');
}

async function prepare() {
  if (isCompleteRuntime(destination)) {
    console.log(`PostgreSQL ${POSTGRES_VERSION} x64 est prêt dans postgres-win.`);
    return;
  }

  const temporaryRoot = fs.mkdtempSync(path.join(os.tmpdir(), 'yeboox-postgres-'));
  const archivePath = path.join(temporaryRoot, 'postgresql.zip');
  const extractPath = path.join(temporaryRoot, 'extract');

  try {
    fs.mkdirSync(extractPath, { recursive: true });
    await download(POSTGRES_DOWNLOAD_URL, archivePath);
    const digest = crypto.createHash('sha256').update(fs.readFileSync(archivePath)).digest('hex');
    if (digest !== POSTGRES_ARCHIVE_SHA256) throw new Error('La somme SHA-256 PostgreSQL est invalide.');

    extract(archivePath, extractPath);
    const source = path.join(extractPath, 'pgsql');
    fs.rmSync(destination, { recursive: true, force: true });
    fs.mkdirSync(destination, { recursive: true });
    for (const entry of ['bin', 'lib', 'share', 'server_license.txt']) {
      fs.cpSync(path.join(source, entry), path.join(destination, entry), { recursive: true });
    }

    if (!isCompleteRuntime(destination)) throw new Error('Le runtime PostgreSQL préparé est incomplet.');
    console.log(`PostgreSQL ${POSTGRES_VERSION} x64 a été vérifié et préparé.`);
  } finally {
    fs.rmSync(temporaryRoot, { recursive: true, force: true });
  }
}

prepare().catch(error => {
  console.error(error.message);
  process.exitCode = 1;
});
