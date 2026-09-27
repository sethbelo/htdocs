const fs = require('fs');
const os = require('os');
const path = require('path');
const crypto = require('crypto');
const { spawnSync } = require('child_process');

const root = path.resolve(__dirname, '..');
const failures = [];
const requiredFiles = [
  'php-win/php.exe',
  'php-win/php.ini',
  'php-win/vcruntime140.dll',
  'php-win/vcruntime140_1.dll',
  'php-win/msvcp140.dll',
  'php-win/vc-runtime-manifest.json',
  'php-win/libsqlite3.dll',
  'php-win/ext/php_pdo_sqlite.dll',
  'php-win/ext/php_sqlite3.dll',
  'php-win/ext/php_pdo_pgsql.dll',
  'php-win/ext/php_pgsql.dll',
  'php-win/ext/php_gd.dll',
  'php-win/ext/php_fileinfo.dll',
  'php-win/ext/php_zip.dll',
  'vendor/dompdf/dompdf/src/Dompdf.php',
  'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php',
  'vendor/livewire/livewire/dist/livewire.min.js',
  'vendor/livewire/flux/dist/flux.min.js',
  'vendor/livewire/flux/dist/flux.css',
  'public/build/manifest.json',
  'database/installer.sqlite',
  'main.cjs',
  'electron-server.php',
];

function readPeMachine(filePath) {
  const file = fs.readFileSync(filePath);
  if (file.length < 64 || file.toString('ascii', 0, 2) !== 'MZ') return null;
  const peOffset = file.readUInt32LE(0x3c);
  if (peOffset + 6 > file.length || file.toString('ascii', peOffset, peOffset + 4) !== 'PE\0\0') return null;

  return file.readUInt16LE(peOffset + 4);
}

function sha256File(filePath) {
  return crypto.createHash('sha256').update(fs.readFileSync(filePath)).digest('hex');
}

function walkFiles(directory) {
  return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    const entryPath = path.join(directory, entry.name);
    return entry.isDirectory() ? walkFiles(entryPath) : [entryPath];
  });
}

for (const relativePath of requiredFiles) {
  if (!fs.existsSync(path.join(root, relativePath))) {
    failures.push(`Fichier Electron manquant : ${relativePath}`);
  }
}

// Empêche définitivement qu'un binaire x86 ou ARM soit mélangé au runtime
// PHP x64. Ce mélange provoque l'erreur Windows VCRUNTIME140 incompatible.
for (const peFile of walkFiles(path.join(root, 'php-win')).filter(file => /\.(?:exe|dll)$/i.test(file))) {
  if (readPeMachine(peFile) !== 0x8664) {
    failures.push(`Binaire PHP Windows non x64 : ${path.relative(root, peFile)}`);
  }
}

const vcRuntimeManifestPath = path.join(root, 'php-win/vc-runtime-manifest.json');
if (fs.existsSync(vcRuntimeManifestPath)) {
  try {
    const manifest = JSON.parse(fs.readFileSync(vcRuntimeManifestPath, 'utf8'));
    if (manifest.architecture !== 'x64' || manifest.deployment !== 'application-local') {
      failures.push('Le manifeste Visual C++ ne décrit pas un déploiement local x64.');
    }
    for (const [filename, expectedHash] of Object.entries(manifest.files || {})) {
      const runtimeFile = path.join(root, 'php-win', filename);
      if (!fs.existsSync(runtimeFile)) {
        failures.push(`DLL Visual C++ absente : php-win/${filename}`);
      } else if (sha256File(runtimeFile) !== expectedHash) {
        failures.push(`DLL Visual C++ altérée ou remplacée : php-win/${filename}`);
      }
    }
  } catch (error) {
    failures.push(`Manifeste Visual C++ invalide : ${error.message}`);
  }
}

const manifestPath = path.join(root, 'public/build/manifest.json');
if (fs.existsSync(manifestPath)) {
  try {
    const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
    for (const entry of Object.values(manifest)) {
      for (const asset of [entry.file, ...(entry.css || [])].filter(Boolean)) {
        if (!fs.existsSync(path.join(root, 'public/build', asset))) {
          failures.push(`Asset Vite local manquant : public/build/${asset}`);
        }
      }
    }
  } catch (error) {
    failures.push(`Manifest Vite invalide : ${error.message}`);
  }
}

for (const relativePath of [
  'resources/views/layouts/app.blade.php',
  'resources/views/layouts/guest.blade.php',
  'resources/views/layouts/pos.blade.php',
  'resources/css/app.css',
  'resources/js/app.js',
]) {
  const source = fs.readFileSync(path.join(root, relativePath), 'utf8');
  if (/https?:\/\//i.test(source)) {
    failures.push(`Dépendance Internet automatique interdite : ${relativePath}`);
  }
}

const snapshotPath = path.join(root, 'php-win/snapshot.txt');
if (fs.existsSync(snapshotPath)) {
  const snapshot = fs.readFileSync(snapshotPath, 'utf8');
  const version = snapshot.match(/Version:\s*(\d+)\.(\d+)\.(\d+)/);
  if (!version || Number(version[1]) < 8 || (Number(version[1]) === 8 && Number(version[2]) < 3)) {
    failures.push(`Runtime PHP Windows incompatible : ${version?.[0] || 'version inconnue'}`);
  }
} else {
  failures.push('Le fichier de version PHP Windows est absent.');
}

const iniPath = path.join(root, 'php-win/php.ini');
if (fs.existsSync(iniPath)) {
  const ini = fs.readFileSync(iniPath, 'utf8');
  for (const extension of ['curl', 'fileinfo', 'gd', 'intl', 'mbstring', 'openssl', 'pdo_pgsql', 'pgsql', 'pdo_sqlite', 'sqlite3', 'zip']) {
    if (!new RegExp(`^extension=${extension}$`, 'm').test(ini)) {
      failures.push(`Extension PHP non activée : ${extension}`);
    }
  }
  if (!/^memory_limit\s*=\s*512M$/m.test(ini)) {
    failures.push('La mémoire PHP embarquée doit être fixée à 512M pour les rapports PDF.');
  }
}

const databasePath = path.join(root, 'database/installer.sqlite');
const emptyTemplateCheck = spawnSync('php', ['-r',
  "$db = new PDO('sqlite:'.$argv[1]); foreach (['users','products','customers','sales','sale_items','stock_movements','appointments','accounting_records','invoices','invoice_items','expenses','credit_notes','purchases','subscriptions','register_sessions'] as $table) { if ((int) $db->query('SELECT COUNT(*) FROM '.$table)->fetchColumn() !== 0) { fwrite(STDERR, 'Le modèle doit être vide : '.$table); exit(1); } }",
  databasePath,
], { encoding: 'utf8', timeout: 30000 });
if (emptyTemplateCheck.error || emptyTemplateCheck.status !== 0) {
  failures.push(`Base de première installation non vide : ${emptyTemplateCheck.stderr || emptyTemplateCheck.error?.message}`);
}
if (fs.existsSync(databasePath)) {
  const header = fs.readFileSync(databasePath).subarray(0, 16).toString('binary');
  if (header !== 'SQLite format 3\u0000') {
    failures.push('La base embarquée n’est pas un fichier SQLite valide.');
  }
}

const electronMain = fs.readFileSync(path.join(root, 'main.cjs'), 'utf8');
if (!electronMain.includes("'Yeboox Desktop'")) {
  failures.push('Le dossier de données isolé de Yeboox Desktop n’est pas configuré.');
}
if (!electronMain.includes('PORTABLE_EXECUTABLE_DIR') || !electronMain.includes("'Yeboox POS Data'")) {
  failures.push('Le stockage autonome de la version portable n’est pas configuré.');
}
if (!electronMain.includes('VIEW_COMPILED_PATH')) {
  failures.push('Le cache Blade externe de la version Electron n’est pas configuré.');
}
if (!electronMain.includes('enableOfflineRuntime') || !electronMain.includes("'127.0.0.1', 'localhost', '::1'")) {
  failures.push('Le blocage des dépendances réseau externes n’est pas configuré.');
}
if (!electronMain.includes("DB_CONNECTION: 'sqlite'") || !electronMain.includes('attempt <= 4')) {
  failures.push('Le démarrage SQLite et les nouvelles tentatives de migration ne sont pas configurés.');
}
if (!electronMain.includes("ipcMain.handle('display:apply'") || !electronMain.includes('screen.getDisplayMatching')) {
  failures.push('Le redimensionnement sécurisé de la fenêtre Electron n’est pas configuré.');
}
if (!electronMain.includes('validateBundledPhpRuntime') || !electronMain.includes('frenchPhpStartupReason')) {
  failures.push('Le contrôle préventif du runtime Visual C++ est absent du démarrage Electron.');
}
if (!electronMain.includes('createStartupWindow') || !fs.existsSync(path.join(root, 'build/startup.html'))) {
  failures.push('L’écran local de démarrage fluide est absent.');
}

const electronPreload = fs.readFileSync(path.join(root, 'preload.cjs'), 'utf8');
if (!electronPreload.includes('applyDisplaySettings')) {
  failures.push('Le pont local des profils d’affichage Electron est absent.');
}

const productionCacheFiles = [
  'bootstrap/cache/packages.php',
  'bootstrap/cache/services.php',
];
const forbiddenProviders = [
  'Laravel\\\\Pail',
  'Laravel\\\\Pao',
  'Laravel\\\\Tinker',
  'NunoMaduro\\\\Collision',
];

for (const relativePath of productionCacheFiles) {
  const cachePath = path.join(root, relativePath);

  if (!fs.existsSync(cachePath)) {
    failures.push(`Cache Laravel de production manquant : ${relativePath}`);
    continue;
  }

  const cache = fs.readFileSync(cachePath, 'utf8');
  for (const provider of forbiddenProviders) {
    if (cache.includes(provider)) {
      failures.push(`Service de développement interdit dans ${relativePath} : ${provider}`);
    }
  }
}

const migrationStatus = spawnSync('php', ['artisan', 'migrate:status', '--no-interaction'], {
  cwd: root,
  env: {
    ...process.env,
    APP_ENV: 'production',
    APP_DEBUG: 'false',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: databasePath,
  },
  encoding: 'utf8',
  timeout: 60000,
});

if (migrationStatus.error || migrationStatus.status !== 0 || /Pending/i.test(migrationStatus.stdout || '')) {
  failures.push(`État des migrations invalide : ${(migrationStatus.stderr || migrationStatus.stdout || migrationStatus.error?.message || '').trim()}`);
}

// Rejouer le cycle complet sur une base temporaire détecte notamment les
// index SQLite qui empêcheraient une réparation ou un rollback sous Windows.
const migrationCheckRoot = fs.mkdtempSync(path.join(os.tmpdir(), 'yeboox-electron-migrations-'));
const migrationCheckDatabase = path.join(migrationCheckRoot, 'database.sqlite');
fs.writeFileSync(migrationCheckDatabase, '');

try {
  const migrationCheckEnvironment = {
    ...process.env,
    APP_ENV: 'testing',
    APP_DEBUG: 'false',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: migrationCheckDatabase,
    SESSION_DRIVER: 'array',
    CACHE_STORE: 'array',
    QUEUE_CONNECTION: 'sync',
  };
  const migrationChecks = [
    ['création', ['artisan', 'migrate:fresh', '--force', '--no-interaction']],
    ['retour arrière', ['artisan', 'migrate:rollback', '--force', '--no-interaction']],
    ['réapplication', ['artisan', 'migrate', '--force', '--no-interaction']],
  ];

  for (const [label, args] of migrationChecks) {
    const result = spawnSync('php', args, {
      cwd: root,
      env: migrationCheckEnvironment,
      encoding: 'utf8',
      timeout: 120000,
    });

    if (result.error || result.status !== 0 || /\bFAIL\b/i.test(result.stdout || '')) {
      failures.push(`Cycle des migrations (${label}) invalide : ${(result.stderr || result.stdout || result.error?.message || '').trim()}`);
      break;
    }
  }
} finally {
  fs.rmSync(migrationCheckRoot, { recursive: true, force: true });
}

const applicationHealth = spawnSync('php', ['artisan', 'yeboox:health', '--no-interaction'], {
  cwd: root,
  env: {
    ...process.env,
    APP_ENV: 'production',
    APP_DEBUG: 'false',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: databasePath,
    SESSION_DRIVER: 'file',
    CACHE_STORE: 'file',
    QUEUE_CONNECTION: 'sync',
  },
  encoding: 'utf8',
  timeout: 60000,
});

if (applicationHealth.error || applicationHealth.status !== 0 || !/"status":"ok"/.test(applicationHealth.stdout || '')) {
  failures.push(`Diagnostic Laravel invalide : ${(applicationHealth.stderr || applicationHealth.stdout || applicationHealth.error?.message || '').trim()}`);
}

const httpCheckRoot = fs.mkdtempSync(path.join(os.tmpdir(), 'yeboox-electron-http-'));
const httpCheckStorage = path.join(httpCheckRoot, 'storage');
const httpCheckDatabase = path.join(httpCheckRoot, 'database.sqlite');

try {
  for (const relativePath of [
    'framework/cache/data',
    'framework/sessions',
    'framework/views',
    'logs',
  ]) {
    fs.mkdirSync(path.join(httpCheckStorage, relativePath), { recursive: true });
  }
  fs.copyFileSync(databasePath, httpCheckDatabase);

  const httpBoot = spawnSync('php', ['-r', [
    'require "vendor/autoload.php";',
    '$app = require "bootstrap/app.php";',
    '$kernel = $app->make(Illuminate\\Contracts\\Http\\Kernel::class);',
    '$request = Illuminate\\Http\\Request::create("/ready", "GET", [], [], [], ["HTTP_ACCEPT" => "application/json"]);',
    '$response = $kernel->handle($request);',
    'echo $response->getStatusCode()."|".$response->getContent();',
    '$kernel->terminate($request, $response);',
  ].join(' ')], {
    cwd: root,
    env: {
      ...process.env,
      APP_ENV: 'production',
      APP_DEBUG: 'false',
      DB_CONNECTION: 'sqlite',
      DB_DATABASE: httpCheckDatabase,
      SESSION_DRIVER: 'file',
      CACHE_STORE: 'file',
      QUEUE_CONNECTION: 'sync',
      LARAVEL_STORAGE_PATH: httpCheckStorage,
      VIEW_COMPILED_PATH: path.join(httpCheckStorage, 'framework', 'views'),
    },
    encoding: 'utf8',
    timeout: 60000,
  });

  if (httpBoot.error || httpBoot.status !== 0 || !/^200\|\{"status":"ok"\}/.test(httpBoot.stdout || '')) {
    failures.push(`Démarrage HTTP Electron invalide : ${(httpBoot.stderr || httpBoot.stdout || httpBoot.error?.message || '').trim()}`);
  }
} finally {
  fs.rmSync(httpCheckRoot, { recursive: true, force: true });
}

if (failures.length) {
  console.error('\nVérification Electron Windows refusée :');
  failures.forEach((failure) => console.error(`- ${failure}`));
  process.exit(1);
}

console.log('Vérification Electron Windows réussie : PHP 8.3+, SQLite, migrations et services de production conformes.');
