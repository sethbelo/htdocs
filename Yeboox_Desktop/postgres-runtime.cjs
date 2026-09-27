const crypto = require('crypto');
const fs = require('fs');
const https = require('https');
const path = require('path');
const { spawnSync } = require('child_process');

const POSTGRES_VERSION = '16.15-3';
const POSTGRES_DOWNLOAD_URL = 'https://get.enterprisedb.com/postgresql/postgresql-16.15-3-windows-x64-binaries.zip';
const POSTGRES_ARCHIVE_SHA256 = '5e8afffe67daf949aeeb03b74951f1ec2324e1888f73fbd036ab0e567ab004d9';

function executable(runtimeRoot, name) {
  return path.join(runtimeRoot, 'bin', `${name}.exe`);
}

function isCompleteRuntime(runtimeRoot) {
  return ['postgres', 'pg_ctl', 'initdb', 'createdb', 'pg_isready', 'pg_dump']
    .every(name => fs.existsSync(executable(runtimeRoot, name)))
    && fs.existsSync(path.join(runtimeRoot, 'share', 'postgresql.conf.sample'));
}

function sha256File(filePath) {
  return crypto.createHash('sha256').update(fs.readFileSync(filePath)).digest('hex');
}

function downloadFile(url, destination, onProgress, redirects = 0) {
  return new Promise((resolve, reject) => {
    if (redirects > 5) {
      reject(new Error('Trop de redirections pendant le téléchargement PostgreSQL.'));
      return;
    }

    const request = https.get(url, response => {
      if (response.statusCode >= 300 && response.statusCode < 400 && response.headers.location) {
        response.resume();
        downloadFile(new URL(response.headers.location, url).toString(), destination, onProgress, redirects + 1)
          .then(resolve, reject);
        return;
      }

      if (response.statusCode !== 200) {
        response.resume();
        reject(new Error(`Téléchargement PostgreSQL refusé (HTTP ${response.statusCode}).`));
        return;
      }

      const total = Number(response.headers['content-length']) || 0;
      let received = 0;
      const output = fs.createWriteStream(destination, { mode: 0o600 });
      response.on('data', chunk => {
        received += chunk.length;
        if (total > 0) onProgress?.(Math.floor((received / total) * 100));
      });
      response.pipe(output);
      output.on('finish', () => output.close(resolve));
      output.on('error', reject);
    });

    request.setTimeout(120000, () => request.destroy(new Error('Le téléchargement PostgreSQL a expiré.')));
    request.on('error', reject);
  });
}

function quotePowerShell(value) {
  return `'${String(value).replaceAll("'", "''")}'`;
}

function extractArchive(archivePath, destination) {
  fs.rmSync(destination, { recursive: true, force: true });
  fs.mkdirSync(destination, { recursive: true });

  const command = `Expand-Archive -LiteralPath ${quotePowerShell(archivePath)} -DestinationPath ${quotePowerShell(destination)} -Force`;
  const result = spawnSync('powershell.exe', [
    '-NoLogo',
    '-NoProfile',
    '-NonInteractive',
    '-ExecutionPolicy',
    'Bypass',
    '-Command',
    command,
  ], { encoding: 'utf8', windowsHide: true, timeout: 600000 });

  if (result.error || result.status !== 0) {
    throw new Error(`Extraction PostgreSQL impossible : ${result.error?.message || result.stderr || 'erreur inconnue'}`);
  }
}

async function resolvePostgresRuntime({ bundledRoot, userDataRoot, allowDownload, onProgress }) {
  if (isCompleteRuntime(bundledRoot)) return bundledRoot;

  const downloadedRoot = path.join(userDataRoot, 'postgres-runtime', POSTGRES_VERSION);
  if (isCompleteRuntime(downloadedRoot)) return downloadedRoot;
  if (!allowDownload) return null;

  const downloadDirectory = path.join(userDataRoot, 'downloads');
  const archivePath = path.join(downloadDirectory, `postgresql-${POSTGRES_VERSION}-windows-x64.zip`);
  const extractDirectory = path.join(userDataRoot, 'postgres-runtime', `${POSTGRES_VERSION}.extracting`);
  fs.mkdirSync(downloadDirectory, { recursive: true });

  onProgress?.(0);
  await downloadFile(POSTGRES_DOWNLOAD_URL, `${archivePath}.part`, onProgress);
  fs.renameSync(`${archivePath}.part`, archivePath);

  if (sha256File(archivePath) !== POSTGRES_ARCHIVE_SHA256) {
    fs.rmSync(archivePath, { force: true });
    throw new Error('La signature SHA-256 de PostgreSQL ne correspond pas à la version officielle attendue.');
  }

  extractArchive(archivePath, extractDirectory);
  const extractedRoot = path.join(extractDirectory, 'pgsql');
  if (!isCompleteRuntime(extractedRoot)) {
    throw new Error('L’archive PostgreSQL téléchargée est incomplète.');
  }

  for (const entry of fs.readdirSync(extractedRoot)) {
    if (!['bin', 'lib', 'share', 'server_license.txt'].includes(entry)) {
      fs.rmSync(path.join(extractedRoot, entry), { recursive: true, force: true });
    }
  }

  fs.mkdirSync(path.dirname(downloadedRoot), { recursive: true });
  fs.rmSync(downloadedRoot, { recursive: true, force: true });
  fs.renameSync(extractedRoot, downloadedRoot);
  fs.rmSync(extractDirectory, { recursive: true, force: true });
  fs.rmSync(archivePath, { force: true });

  return downloadedRoot;
}

function runPostgres(runtimeRoot, command, args, options = {}) {
  const result = spawnSync(executable(runtimeRoot, command), args, {
    encoding: 'utf8',
    windowsHide: true,
    timeout: options.timeout || 120000,
    env: { ...process.env, ...options.env },
  });

  if (result.error || result.status !== 0) {
    throw new Error(`${command} a échoué : ${result.error?.message || result.stderr || result.stdout || 'erreur inconnue'}`);
  }

  return result;
}

function readOrCreateCredentials(postgresRoot) {
  const credentialsPath = path.join(postgresRoot, 'credentials.json');
  if (fs.existsSync(credentialsPath)) {
    return JSON.parse(fs.readFileSync(credentialsPath, 'utf8'));
  }

  const credentials = {
    database: 'yeboox_desktop',
    username: 'yeboox_app',
    password: crypto.randomBytes(32).toString('base64url'),
  };
  fs.mkdirSync(postgresRoot, { recursive: true });
  fs.writeFileSync(credentialsPath, JSON.stringify(credentials, null, 2), { encoding: 'utf8', mode: 0o600 });

  return credentials;
}

function startPostgresCluster({ runtimeRoot, postgresRoot, port, logPath }) {
  const dataPath = path.join(postgresRoot, 'data');
  const credentials = readOrCreateCredentials(postgresRoot);
  const environment = { PGPASSWORD: credentials.password };

  if (!fs.existsSync(path.join(dataPath, 'PG_VERSION'))) {
    const passwordFile = path.join(postgresRoot, '.init-password');
    fs.mkdirSync(dataPath, { recursive: true });
    fs.writeFileSync(passwordFile, `${credentials.password}\n`, { encoding: 'utf8', mode: 0o600 });
    try {
      runPostgres(runtimeRoot, 'initdb', [
        '-D', dataPath,
        '-U', credentials.username,
        '--pwfile', passwordFile,
        '--auth-host=scram-sha-256',
        '--auth-local=scram-sha-256',
        '--encoding=UTF8',
        '--locale=C',
      ], { timeout: 300000 });
    } finally {
      fs.rmSync(passwordFile, { force: true });
    }
  }

  const previousServer = spawnSync(executable(runtimeRoot, 'pg_ctl'), ['status', '-D', dataPath], {
    encoding: 'utf8',
    windowsHide: true,
    timeout: 15000,
  });
  if (previousServer.status === 0) {
    runPostgres(runtimeRoot, 'pg_ctl', [
      'stop', '-D', dataPath, '-m', 'fast', '-w', '-t', '30',
    ], { timeout: 45000 });
  }

  runPostgres(runtimeRoot, 'pg_ctl', [
    'start',
    '-D', dataPath,
    '-l', logPath,
    '-o', `-p ${port} -h 127.0.0.1 -c listen_addresses=127.0.0.1`,
    '-w',
    '-t', '90',
  ], { timeout: 120000 });

  const readyArguments = ['-h', '127.0.0.1', '-p', String(port), '-U', credentials.username, '-d', 'postgres'];
  runPostgres(runtimeRoot, 'pg_isready', readyArguments, { env: environment, timeout: 30000 });

  const databaseCheck = spawnSync(executable(runtimeRoot, 'psql'), [
    ...readyArguments,
    '-tAc', `SELECT 1 FROM pg_database WHERE datname='${credentials.database}'`,
  ], { encoding: 'utf8', windowsHide: true, timeout: 30000, env: { ...process.env, ...environment } });

  if (databaseCheck.error || databaseCheck.status !== 0) {
    throw new Error(`Vérification de la base Yeboox impossible : ${databaseCheck.error?.message || databaseCheck.stderr}`);
  }
  if (databaseCheck.stdout.trim() !== '1') {
    runPostgres(runtimeRoot, 'createdb', [
      '-h', '127.0.0.1', '-p', String(port), '-U', credentials.username,
      '--encoding=UTF8', credentials.database,
    ], { env: environment, timeout: 60000 });
  }

  return { dataPath, credentials };
}

function stopPostgresCluster(runtimeRoot, dataPath) {
  if (!runtimeRoot || !dataPath || !fs.existsSync(path.join(dataPath, 'PG_VERSION'))) return;

  spawnSync(executable(runtimeRoot, 'pg_ctl'), [
    'stop', '-D', dataPath, '-m', 'fast', '-w', '-t', '30',
  ], { encoding: 'utf8', windowsHide: true, timeout: 45000 });
}

function backupPostgresDatabase({ runtimeRoot, postgresRoot, port, credentials, version }) {
  const backupDirectory = path.join(postgresRoot, 'backups');
  const backupPath = path.join(backupDirectory, `yeboox-before-${version}.dump`);
  fs.mkdirSync(backupDirectory, { recursive: true });
  if (fs.existsSync(backupPath)) return backupPath;

  runPostgres(runtimeRoot, 'pg_dump', [
    '-h', '127.0.0.1', '-p', String(port), '-U', credentials.username,
    '--format=custom', '--file', `${backupPath}.partial`, credentials.database,
  ], { env: { PGPASSWORD: credentials.password }, timeout: 300000 });
  fs.renameSync(`${backupPath}.partial`, backupPath);

  return backupPath;
}

module.exports = {
  POSTGRES_ARCHIVE_SHA256,
  POSTGRES_DOWNLOAD_URL,
  POSTGRES_VERSION,
  backupPostgresDatabase,
  isCompleteRuntime,
  resolvePostgresRuntime,
  startPostgresCluster,
  stopPostgresCluster,
};
