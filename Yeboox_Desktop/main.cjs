const { app, BrowserWindow, ipcMain, dialog, session, screen } = require('electron');
const { spawn, spawnSync } = require('child_process');
const path = require('path');
const http = require('http');
const net = require('net');
const fs = require('fs');
const crypto = require('crypto');

let mainWindow;
let startupWindow;
let phpServer;
let queueWorker;
let syncWorker;
let isQuitting = false;
let startupFailureShown = false;
let activePhpPort = 8000;

const RECEIPT_PAPER_WIDTHS = new Set([58, 76, 80]);
const DISPLAY_PROFILES = new Map([
  ['800x600', [800, 600]],
  ['1024x768', [1024, 768]],
  ['1280x720', [1280, 720]],
  ['1366x768', [1366, 768]],
  ['1440x900', [1440, 900]],
  ['1600x900', [1600, 900]],
  ['1920x1080', [1920, 1080]],
  ['2560x1440', [2560, 1440]],
]);
const DISPLAY_SCALES = new Set([80, 90, 100, 110, 125]);
const CURRENCY_CODES = new Set(['USD', 'CDF', 'XAF', 'XOF', 'EUR']);

// The simplified Windows edition intentionally uses a clean, isolated data
// directory. Older installations remain untouched and cannot reintroduce a
// damaged cache or database into this build.
if (process.platform === 'win32') {
  // electron-builder expose ce dossier pour la cible portable. Les données
  // restent alors à côté de l'exécutable et suivent la clé USB ou le dossier.
  const portableRoot = process.env.PORTABLE_EXECUTABLE_DIR;
  app.setPath(
    'userData',
    portableRoot
      ? path.join(portableRoot, 'Yeboox POS Data')
      : path.join(app.getPath('appData'), 'Yeboox Desktop')
  );
}

const hasSingleInstanceLock = app.requestSingleInstanceLock();
if (!hasSingleInstanceLock) {
  app.quit();
} else {
  app.on('second-instance', () => {
    if (!mainWindow) return;
    if (mainWindow.isMinimized()) mainWindow.restore();
    mainWindow.show();
    mainWindow.focus();
  });
}

function posStatePath() {
  return path.join(app.getPath('userData'), 'pos-state.json');
}

function isTrustedRenderer(event) {
  try {
    const url = new URL(event.sender.getURL());
    return url.protocol === 'http:' && url.hostname === '127.0.0.1';
  } catch {
    return false;
  }
}

function translatePrinterFailure(reason) {
  const value = String(reason || '').trim();
  if (!value) return null;
  if (/print job cancel(?:ed|led)/i.test(value)) return 'Impression annulée.';
  if (/invalid printer settings/i.test(value)) return 'Les paramètres de l’imprimante ne sont pas valides.';
  if (/no printer|printer.*not found|device.*not found/i.test(value)) return 'Aucune imprimante compatible n’a été trouvée.';
  if (/offline|not available|unavailable/i.test(value)) return 'L’imprimante est hors ligne ou indisponible.';

  return 'L’impression n’a pas pu être terminée. Vérifiez l’imprimante puis réessayez.';
}

function enableOfflineRuntime() {
  const localHosts = new Set(['127.0.0.1', 'localhost', '::1']);

  session.defaultSession.webRequest.onBeforeRequest(
    { urls: ['http://*/*', 'https://*/*'] },
    (details, callback) => {
      try {
        const target = new URL(details.url);
        callback({ cancel: !localHosts.has(target.hostname) });
      } catch {
        callback({ cancel: true });
      }
    }
  );
}

ipcMain.handle('receipt:print', async (event, payload = {}) => {
  if (!isTrustedRenderer(event)) {
    throw new Error('Origine d’impression non autorisée.');
  }

  const requestedWidth = Number(payload.paperWidth);
  const paperWidth = RECEIPT_PAPER_WIDTHS.has(requestedWidth) ? requestedWidth : 80;
  const receiptHtml = String(payload.html || '').slice(0, 3_000_000);
  const receiptStyles = String(payload.styles || '').slice(0, 3_000_000);
  const silent = payload.silent === true;

  if (!receiptHtml.trim()) {
    return { success: false, canceled: false, reason: 'Ticket vide ou introuvable.', paperWidth };
  }

  // Print an isolated document instead of the complete POS window. Modal,
  // overflow and fixed-position styles in the POS used to push the receipt to
  // a second sheet, leaving the first thermal page blank on Windows drivers.
  const printWindow = new BrowserWindow({
    show: false,
    width: Math.max(320, Math.ceil((paperWidth / 25.4) * 96) + 24),
    height: 900,
    backgroundColor: '#ffffff',
    webPreferences: {
      contextIsolation: true,
      nodeIntegration: false,
      sandbox: true,
      javascript: true
    }
  });

  const standaloneDocument = `<!doctype html><html><head><meta charset="utf-8"><meta name="color-scheme" content="light">${receiptStyles}<style>html,body{margin:0!important;padding:0!important;width:${paperWidth}mm!important;min-height:0!important;height:auto!important;overflow:visible!important;background:#fff!important;color:#000!important}body{print-color-adjust:exact!important;-webkit-print-color-adjust:exact!important}.yeboox-receipt-sheet{display:block!important;position:static!important;visibility:visible!important;width:${paperWidth}mm!important;max-width:${paperWidth}mm!important;margin:0!important;padding:0!important;overflow:visible!important;background:#fff!important}.yeboox-receipt-sheet *{visibility:visible!important}@media print{html,body{width:${paperWidth}mm!important}.yeboox-receipt-sheet{break-before:avoid!important;break-after:avoid!important;page-break-before:avoid!important;page-break-after:avoid!important}}</style></head><body><main class="yeboox-receipt-sheet">${receiptHtml}</main></body></html>`;

  try {
    await printWindow.loadURL(`data:text/html;base64,${Buffer.from(standaloneDocument, 'utf8').toString('base64')}`);
    await printWindow.webContents.executeJavaScript(`Promise.all([
      document.fonts ? document.fonts.ready : Promise.resolve(),
      ...Array.from(document.images).map(image => image.complete ? Promise.resolve() : new Promise(resolve => { image.addEventListener('load', resolve, { once: true }); image.addEventListener('error', resolve, { once: true }); }))
    ])`);

    const renderedHeightPx = await printWindow.webContents.executeJavaScript(`Math.max(document.body.scrollHeight, document.documentElement.scrollHeight, document.querySelector('.yeboox-receipt-sheet')?.scrollHeight || 0)`);
    const fallbackHeight = Number(payload.contentHeightMm);
    const measuredHeight = Number.isFinite(Number(renderedHeightPx)) && Number(renderedHeightPx) > 0
      ? (Number(renderedHeightPx) / 96) * 25.4
      : fallbackHeight;
    const contentHeightMm = Math.min(2000, Math.max(50, Math.ceil(Number.isFinite(measuredHeight) ? measuredHeight : 297) + 2));

    await printWindow.webContents.executeJavaScript(`(() => { const style = document.createElement('style'); style.textContent = '@page { size: ${paperWidth}mm ${contentHeightMm}mm; margin: 0; }'; document.head.appendChild(style); })()`);

    const options = {
      silent,
      printBackground: true,
      color: false,
      margins: { marginType: 'none' },
      landscape: false,
      scaleFactor: 100,
      pagesPerSheet: 1,
      collate: false,
      copies: 1,
      duplexMode: 'simplex',
      header: '',
      footer: '',
      dpi: { horizontal: 300, vertical: 300 },
      pageSize: {
        width: paperWidth * 1000,
        height: contentHeightMm * 1000
      }
    };

    const executePrint = (printOptions) => new Promise((resolve) => {
      try {
        printWindow.webContents.print(printOptions, (success, failureReason) => resolve({ success, failureReason }));
      } catch (error) {
        resolve({ success: false, failureReason: error.message || 'Invalid printer settings' });
      }
    });

    let printResult = await executePrint(options);
    let usedPrinterDefaultSize = false;

    if (!printResult.success && /invalid printer settings/i.test(printResult.failureReason || '')) {
      const fallbackOptions = { ...options, usePrinterDefaultPageSize: true };
      delete fallbackOptions.pageSize;
      printResult = await executePrint(fallbackOptions);
      usedPrinterDefaultSize = true;
    }

    return {
      success: printResult.success,
      canceled: printResult.failureReason === 'Print job canceled',
      reason: translatePrinterFailure(printResult.failureReason),
      paperWidth,
      contentHeightMm,
      usedPrinterDefaultSize,
      isolatedDocument: true
    };
  } catch (error) {
    console.error('Receipt preparation failed:', error);
    return { success: false, canceled: false, reason: 'Échec de préparation du ticket.', paperWidth, isolatedDocument: true };
  } finally {
    if (!printWindow.isDestroyed()) printWindow.destroy();
  }
});

ipcMain.handle('document:print-a4', async (event, payload = {}) => {
  if (!isTrustedRenderer(event)) {
    throw new Error('Origine d’impression non autorisée.');
  }

  const options = {
    silent: payload.silent === true,
    printBackground: true,
    color: true,
    // The A4 layout owns its printable margins through @page. Avoid stacking
    // the printer defaults on top of CSS margins, which can shift content to
    // an extra leading page on some Windows drivers.
    margins: { marginType: 'none' },
    landscape: payload.landscape === true,
    scaleFactor: 100,
    pagesPerSheet: 1,
    collate: true,
    copies: 1,
    duplexMode: 'simplex',
    header: '',
    footer: '',
    pageSize: 'A4',
    dpi: { horizontal: 300, vertical: 300 }
  };

  return new Promise((resolve) => {
    try {
      event.sender.print(options, (success, failureReason) => resolve({
        success,
        canceled: failureReason === 'Print job canceled',
        reason: translatePrinterFailure(failureReason),
        pageSize: 'A4'
      }));
    } catch (error) {
      console.error('A4 print failed:', error);
      resolve({ success: false, canceled: false, reason: 'L’impression du document A4 a échoué.', pageSize: 'A4' });
    }
  });
});

ipcMain.handle('pos:state:save', async (event, payload = {}) => {
  if (!isTrustedRenderer(event)) {
    throw new Error('Origine de synchronisation non autorisée.');
  }

  const allowed = {
    version: 1,
    reason: String(payload.reason || '').slice(0, 40),
    register: {
      id: Number.isInteger(Number(payload.register?.id)) ? Number(payload.register.id) : null,
      status: payload.register?.status === 'open' ? 'open' : 'closed',
      openedAt: payload.register?.openedAt ? String(payload.register.openedAt).slice(0, 40) : null,
      openingBalance: Math.max(0, Number(payload.register?.openingBalance) || 0),
      expectedBalance: Math.max(0, Number(payload.register?.expectedBalance) || 0),
      baseCurrency: CURRENCY_CODES.has(payload.register?.baseCurrency) ? payload.register.baseCurrency : 'CDF',
      secondaryCurrency: CURRENCY_CODES.has(payload.register?.secondaryCurrency) ? payload.register.secondaryCurrency : 'USD'
    },
    exchangeRate: Math.max(0.00000001, Number(payload.exchangeRate) || 1),
    userId: Number.isInteger(Number(payload.userId)) ? Number(payload.userId) : null,
    currency: CURRENCY_CODES.has(payload.currency) ? payload.currency : 'CDF',
    category: String(payload.category || 'all').slice(0, 100),
    fulfillmentType: ['dine_in', 'takeaway', 'delivery'].includes(payload.fulfillmentType) ? payload.fulfillmentType : 'dine_in',
    tableName: payload.tableName ? String(payload.tableName).slice(0, 50) : null,
    pendingOrders: Math.max(0, Number(payload.pendingOrders) || 0),
    updatedAt: String(payload.updatedAt || new Date().toISOString()).slice(0, 40)
  };

  const target = posStatePath();
  const temporary = `${target}.tmp`;
  await fs.promises.mkdir(path.dirname(target), { recursive: true });
  await fs.promises.writeFile(temporary, JSON.stringify(allowed, null, 2), { encoding: 'utf8', mode: 0o600 });
  await fs.promises.rename(temporary, target);

  return { success: true, path: target };
});

ipcMain.handle('pos:state:load', async (event) => {
  if (!isTrustedRenderer(event)) {
    throw new Error('Origine de synchronisation non autorisée.');
  }

  try {
    return JSON.parse(await fs.promises.readFile(posStatePath(), 'utf8'));
  } catch (error) {
    if (error.code === 'ENOENT') return null;
    throw error;
  }
});

ipcMain.handle('display:apply', async (event, payload = {}) => {
  if (!isTrustedRenderer(event)) {
    throw new Error('Origine de configuration d’affichage non autorisée.');
  }

  const targetWindow = BrowserWindow.fromWebContents(event.sender);
  if (!targetWindow || targetWindow.isDestroyed()) {
    return { success: false, reason: 'Fenêtre Electron introuvable.' };
  }

  const profile = String(payload.resolution || 'auto');
  const scale = DISPLAY_SCALES.has(Number(payload.scale)) ? Number(payload.scale) : 100;
  targetWindow.webContents.setZoomFactor(scale / 100);

  // Le mode automatique conserve la taille choisie par l'utilisateur.
  if (profile === 'auto') {
    return { success: true, profile, scale, resized: false };
  }

  const requested = profile === 'custom'
    ? [Number(payload.width), Number(payload.height)]
    : DISPLAY_PROFILES.get(profile);
  if (!requested || !requested.every(Number.isFinite)) {
    return { success: false, reason: 'Profil de résolution invalide.' };
  }

  const activeDisplay = screen.getDisplayMatching(targetWindow.getBounds());
  const workArea = activeDisplay.workArea;
  const width = Math.min(workArea.width, Math.max(760, Math.round(requested[0])));
  const height = Math.min(workArea.height, Math.max(540, Math.round(requested[1])));

  targetWindow.setFullScreen(false);
  if (targetWindow.isMaximized()) targetWindow.unmaximize();
  targetWindow.setMinimumSize(Math.min(760, workArea.width), Math.min(540, workArea.height));
  targetWindow.setBounds({
    x: workArea.x + Math.max(0, Math.floor((workArea.width - width) / 2)),
    y: workArea.y + Math.max(0, Math.floor((workArea.height - height) / 2)),
    width,
    height,
  }, true);

  return {
    success: true,
    profile,
    scale,
    width,
    height,
    resized: true,
    clamped: width !== Math.round(requested[0]) || height !== Math.round(requested[1]),
  };
});

function getFreePort() {
  return new Promise((resolve, reject) => {
    const server = net.createServer();
    server.unref();
    server.on('error', reject);
    server.listen(0, '127.0.0.1', () => {
      const port = server.address().port;
      server.close(() => {
        resolve(port);
      });
    });
  });
}

function createStartupWindow() {
  startupWindow = new BrowserWindow({
    width: 520,
    height: 330,
    resizable: false,
    maximizable: false,
    minimizable: false,
    frame: false,
    show: false,
    center: true,
    skipTaskbar: true,
    backgroundColor: '#070b24',
    icon: path.join(__dirname, 'build', process.platform === 'win32' ? 'icon.ico' : 'icon.png'),
    webPreferences: {
      contextIsolation: true,
      nodeIntegration: false,
      sandbox: true,
    },
  });

  startupWindow.once('ready-to-show', () => startupWindow?.show());
  startupWindow.loadFile(path.join(__dirname, 'build', 'startup.html'));
}

function closeStartupWindow() {
  if (startupWindow && !startupWindow.isDestroyed()) startupWindow.close();
  startupWindow = null;
}

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

function validateBundledPhpRuntime(phpPath) {
  if (process.platform !== 'win32') return { ok: true };

  const runtimeDirectory = path.dirname(phpPath);
  const manifestPath = path.join(runtimeDirectory, 'vc-runtime-manifest.json');

  try {
    const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
    const checkedFiles = {
      'php.exe': null,
      ...manifest.files,
    };

    if (manifest.architecture !== 'x64') {
      return { ok: false, detail: `Architecture déclarée invalide : ${manifest.architecture || 'inconnue'}.` };
    }

    for (const [filename, expectedHash] of Object.entries(checkedFiles)) {
      const filePath = path.join(runtimeDirectory, filename);
      if (!fs.existsSync(filePath)) return { ok: false, detail: `Composant absent : ${filename}.` };
      if (readPeMachine(filePath) !== 0x8664) return { ok: false, detail: `Composant non x64 : ${filename}.` };
      if (expectedHash && sha256File(filePath) !== expectedHash) {
        return { ok: false, detail: `Composant altéré ou incompatible : ${filename}.` };
      }
    }

    return { ok: true, version: manifest.redistributable_version };
  } catch (error) {
    return { ok: false, detail: `Manifest Visual C++ illisible : ${error.message}` };
  }
}

function frenchPhpStartupReason(result) {
  const raw = [result?.stderr, result?.stdout, result?.error?.message].filter(Boolean).join(' ').trim();

  if (/VCRUNTIME140|MSVCP140|0xc000007b|not compatible|not a valid Win32 application|invalid image/i.test(raw)) {
    return 'Les composants Microsoft Visual C++ du moteur local sont endommagés ou incompatibles. Réinstallez cette version complète de Yeboox POS.';
  }
  if (result?.error?.code === 'ENOENT') {
    return 'Le moteur PHP local est incomplet. Réinstallez Yeboox POS avec l’installateur complet.';
  }

  return 'Le moteur local n’a pas réussi son contrôle de démarrage. Réinstallez l’application si le problème persiste.';
}

function stopAfterStartupFailure(title, message, diagnosticPath) {
  if (isQuitting || startupFailureShown) return;

  startupFailureShown = true;
  closeStartupWindow();
  dialog.showErrorBox(title, `${message}\n\nDiagnostic : ${diagnosticPath}`);
  app.quit();
}

async function createWindow(port, startupLogPath) {
  activePhpPort = port;
  mainWindow = new BrowserWindow({
    width: 1280,
    height: 850,
    minWidth: 760,
    minHeight: 540,
    backgroundColor: '#09090b',
    show: false,
    icon: path.join(__dirname, 'build', process.platform === 'win32' ? 'icon.ico' : 'icon.png'),
    autoHideMenuBar: true,
    title: "Yeboox Desktop 9.0",
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
      sandbox: true,
      preload: path.join(__dirname, 'preload.cjs')
    }
  });

  mainWindow.once('ready-to-show', () => {
    closeStartupWindow();
    mainWindow.show();
  });

  const appUrl = `http://127.0.0.1:${port}`;
  const healthUrl = `${appUrl}/ready`;

  // Wait for Laravel's health endpoint instead of opening the first partial
  // or failed response returned while PHP is still booting.
  function checkServerAndLoad(attempts = 0) {
    http.get(healthUrl, (res) => {
      res.resume();
      if (res.statusCode >= 200 && res.statusCode < 400) {
        mainWindow.loadURL(appUrl);
      } else if (attempts < 80) {
        setTimeout(() => checkServerAndLoad(attempts + 1), 250);
      } else {
        stopAfterStartupFailure(
          'Démarrage local impossible',
          `Le serveur local répond avec le statut HTTP ${res.statusCode}. L’application a été arrêtée avant l’affichage d’une page d’erreur.`,
          startupLogPath
        );
      }
    }).on('error', () => {
      if (attempts < 80) {
        setTimeout(() => checkServerAndLoad(attempts + 1), 250);
      } else {
        stopAfterStartupFailure(
          'Démarrage local impossible',
          'Le serveur PHP local ne répond pas. Fermez toute autre instance puis relancez Yeboox POS.',
          startupLogPath
        );
      }
    });
  }

  checkServerAndLoad();

  mainWindow.on('closed', function () {
    mainWindow = null;
  });
}

app.on('ready', async () => {
  if (!hasSingleInstanceLock) return;
  enableOfflineRuntime();
  createStartupWindow();
  const usePersistentData = app.isPackaged || process.platform === 'win32';
  const persistentRoot = usePersistentData ? app.getPath('userData') : __dirname;
  const storagePath = path.join(persistentRoot, 'storage');
  const databaseDirectory = path.join(persistentRoot, 'database');
  const databasePath = path.join(databaseDirectory, 'database.sqlite');
  const startupLogPath = path.join(storagePath, 'logs', 'electron-startup.log');
  // New installations start from a migration-only database. Existing user
  // databases remain in Electron's userData directory and are never replaced.
  const bundledDatabasePath = path.join(__dirname, 'database', 'installer.sqlite');
  const dirs = [
    path.join(storagePath, 'framework', 'views'),
    path.join(storagePath, 'framework', 'cache', 'data'),
    path.join(storagePath, 'framework', 'sessions'),
    path.join(storagePath, 'logs'),
    databaseDirectory
  ];
  dirs.forEach(dir => {
    if (!fs.existsSync(dir)) {
      try {
        fs.mkdirSync(dir, { recursive: true });
      } catch (e) {
        console.error(`Failed to create directory: ${dir}`, e);
      }
    }
  });

  if (usePersistentData && !fs.existsSync(databasePath)) {
    try {
      fs.copyFileSync(bundledDatabasePath, databasePath, fs.constants.COPYFILE_EXCL);
    } catch (error) {
      console.error('Failed to initialize persistent SQLite database', error);
      app.quit();
      return;
    }
  }

  // Determine PHP path based on platform and packaging
  let phpPath;
  let phpIniPath;
  if (process.platform === 'win32') {
    if (app.isPackaged) {
      phpPath = path.join(process.resourcesPath, 'php', 'php.exe');
      phpIniPath = path.join(process.resourcesPath, 'php', 'php.ini');
    } else {
      phpPath = path.join(__dirname, 'php-win', 'php.exe');
      phpIniPath = path.join(__dirname, 'php-win', 'php.ini');
    }
  } else {
    phpPath = 'php';
    phpIniPath = null;
  }

  // Refuse d'exécuter un PHP incomplet avant que Windows ne puisse afficher
  // sa boîte système « VCRUNTIME140.dll non compatible ».
  // Toutes les DLL du moteur PHP sont livrées à côté de php.exe.
  if (process.platform === 'win32') {
    const runtimePathKey = Object.keys(process.env).find(key => key.toLowerCase() === 'path') || 'Path';
    process.env[runtimePathKey] = `${path.dirname(phpPath)};${process.env[runtimePathKey] || ''}`;
  }
  const runtimeValidation = validateBundledPhpRuntime(phpPath);
  if (!runtimeValidation.ok) {
    try {
      fs.appendFileSync(startupLogPath, `[${new Date().toISOString()}] Visual C++ runtime validation failed: ${runtimeValidation.detail}\n`, 'utf8');
    } catch {}
    stopAfterStartupFailure(
      'Moteur local à réparer',
      'Les composants locaux nécessaires au démarrage sont incomplets ou incompatibles. Réinstallez Yeboox POS avec cette version complète.',
      startupLogPath
    );
    return;
  }

  let port = 8000;
  try {
    port = await getFreePort();
  } catch (e) {
    console.error("Could not find free port, using 8000");
  }

  const keyPath = path.join(persistentRoot, 'application-key.txt');
  if (!fs.existsSync(keyPath)) {
    fs.writeFileSync(keyPath, `base64:${crypto.randomBytes(32).toString('base64')}`, { mode: 0o600, flag: 'wx' });
  }
  const applicationKey = fs.readFileSync(keyPath, 'utf8').trim();
  if (!/^base64:[A-Za-z0-9+/]{43}=$/.test(applicationKey)) {
    throw new Error('La clé de cette installation est invalide. Restaurez application-key.txt depuis votre sauvegarde.');
  }
  const phpEnvironment = {
    ...process.env,
    APP_URL: `http://127.0.0.1:${port}`,
    APP_RUNTIME_MODE: 'desktop',
    APP_KEY: applicationKey,
    DB_URL: '',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: databasePath,
    SQLITE_DATABASE: usePersistentData ? databasePath : path.join(__dirname, 'database', 'database.sqlite'),
    OFFLINE_DB_DATABASE: usePersistentData ? databasePath : path.join(__dirname, 'database', 'database.sqlite'),
    OFFLINE_SYNC_ENABLED: 'true',
    DB_BUSY_TIMEOUT: '10000',
    DB_JOURNAL_MODE: 'DELETE',
    DB_SYNCHRONOUS: 'NORMAL',
    APP_ENV: 'production',
    APP_DEBUG: 'false',
    LOG_CHANNEL: 'single',
    SESSION_DRIVER: 'file',
    CACHE_STORE: 'file',
    // Les tâches non critiques sont conservées dans SQLite et traitées par
    // le worker PHP embarqué, sans dépendance réseau ni service Windows.
    QUEUE_CONNECTION: 'database',
    LARAVEL_STORAGE_PATH: storagePath,
    VIEW_COMPILED_PATH: path.join(storagePath, 'framework', 'views'),
    ...(phpIniPath ? { PHPRC: phpIniPath, PHP_INI_SCAN_DIR: '' } : {})
  };
  if (process.platform === 'win32') {
    const environmentPathKey = Object.keys(process.env).find(key => key.toLowerCase() === 'path') || 'Path';
    phpEnvironment[environmentPathKey] = `${path.dirname(phpPath)};${process.env[environmentPathKey] || ''}`;
  }

  const phpArguments = phpIniPath ? ['-c', phpIniPath, '-d', `extension_dir=${path.join(path.dirname(phpPath), 'ext')}`] : [];
  const artisanPath = path.join(__dirname, 'artisan');
  const runPhp = (args, timeout = 120000) => spawnSync(phpPath, [...phpArguments, ...args], {
    cwd: __dirname,
    env: phpEnvironment,
    encoding: 'utf8',
    windowsHide: true,
    timeout
  });
  const logResult = (label, result) => {
    const details = [
      `[${new Date().toISOString()}] ${label}`,
      `status=${result?.status ?? 'none'}`,
      result?.error ? `error=${result.error.message}` : '',
      result?.stdout ? `stdout=${result.stdout.trim()}` : '',
      result?.stderr ? `stderr=${result.stderr.trim()}` : '',
      ''
    ].filter(Boolean).join('\n');

    try {
      fs.appendFileSync(startupLogPath, `${details}\n`, 'utf8');
    } catch (error) {
      console.error('Unable to write the startup log:', error);
    }
  };

  // Fail early with a useful diagnostic when the embedded runtime is missing
  // or incompatible. SQLite est embarqué sur chaque plateforme.
  const databaseExtension = 'pdo_sqlite';
  const preflight = runPhp([
    '-r',
    `if (PHP_VERSION_ID < 80300) { fwrite(STDERR, 'PHP 8.3 minimum requis; version actuelle: '.PHP_VERSION); exit(10); } $required = ['${databaseExtension}', 'dom', 'mbstring', 'gd', 'fileinfo', 'zip', 'simplexml', 'xmlreader', 'xmlwriter']; foreach ($required as $extension) { if (!extension_loaded($extension)) { fwrite(STDERR, 'Extension PHP absente: '.$extension); exit(11); } } echo PHP_VERSION.'|'.implode(',', $required);`
  ], 30000);
  logResult('PHP preflight', preflight);

  if (preflight.error || preflight.status !== 0) {
    const reason = frenchPhpStartupReason(preflight);
    closeStartupWindow();
    dialog.showErrorBox(
      'PHP local indisponible',
      `Yeboox Desktop ne peut pas démarrer son moteur local.\n\n${reason}\n\nDiagnostic : ${startupLogPath}`
    );
    app.quit();
    return;
  }

  let recoveredDatabasePath = null;
  {
    // Vérifier la base locale sans remplacer silencieusement les données.
    const sqliteCheck = runPhp([
      '-r',
      "$pdo = new PDO('sqlite:'.$argv[1]); $result = $pdo->query('PRAGMA quick_check')->fetchColumn(); if ($result !== 'ok') { fwrite(STDERR, (string) $result); exit(12); } echo 'sqlite-ok';",
      databasePath
    ], 30000);
    logResult('SQLite integrity check', sqliteCheck);

    const sqliteCorrupt = sqliteCheck.status !== 0
      && /malformed|not a database|file is encrypted|disk image/i.test(sqliteCheck.stderr || '');

    if (sqliteCorrupt) {
      try {
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        recoveredDatabasePath = `${databasePath}.corrupt-${timestamp}.bak`;
        fs.copyFileSync(databasePath, recoveredDatabasePath, fs.constants.COPYFILE_EXCL);
        dialog.showErrorBox('Base SQLite à restaurer', `La base est endommagée. Une copie a été conservée : ${recoveredDatabasePath}. Restaurez une sauvegarde avant de relancer Yeboox.`);
        app.quit();
        return;
        fs.appendFileSync(startupLogPath, `[${new Date().toISOString()}] SQLite recovery backup=${recoveredDatabasePath}\n`, 'utf8');
      } catch (error) {
        logResult('SQLite recovery failure', { status: 1, error, stdout: '', stderr: '' });
        dialog.showErrorBox(
          'Réparation SQLite impossible',
          `La base locale est endommagée et sa copie de sécurité n’a pas pu être créée.\n\nDiagnostic : ${startupLogPath}`
        );
        app.quit();
        return;
      }
    }
  }

  // Sauvegarder les données utilisateur avant toute évolution de schéma.
  // La sauvegarde n'est jamais incluse dans le paquet et reste récupérable
  // depuis le dossier de données Windows de l'utilisateur.
  let preMigrationBackupPath = null;
  if (usePersistentData && fs.existsSync(databasePath)) {
    try {
      preMigrationBackupPath = `${databasePath}.before-${app.getVersion()}.bak`;
      if (!fs.existsSync(preMigrationBackupPath)) {
        const backup = runPhp(['-r', "$pdo = new PDO('sqlite:'.$argv[1]); $pdo->exec('VACUUM INTO '.$pdo->quote($argv[2]));", databasePath, `${preMigrationBackupPath}.partial`], 60000);
        if (backup.error || backup.status !== 0) throw new Error('La sauvegarde SQLite a échoué.');
        fs.renameSync(`${preMigrationBackupPath}.partial`, preMigrationBackupPath);
      }
      fs.appendFileSync(startupLogPath, `[${new Date().toISOString()}] Pre-migration backup=${preMigrationBackupPath}\n`, 'utf8');
    } catch (error) {
      logResult('Pre-migration backup failure', { status: 1, error, stdout: '', stderr: '' });
      dialog.showErrorBox('Sauvegarde locale impossible', `Yeboox POS n'a pas pu sécuriser la base avant la mise à jour.\n\nDiagnostic : ${startupLogPath}`);
      app.quit();
      return;
    }
  }

  // Réessayer les migrations lorsque SQLite est momentanément verrouillé.
  // L'application n'est jamais chargée avec un schéma incomplet.
  let migration;
  for (let attempt = 1; attempt <= 4; attempt += 1) {
    migration = runPhp([artisanPath, 'migrate', '--force', '--no-interaction']);
    logResult(`Laravel migration attempt ${attempt}`, migration);

    if (!migration.error && migration.status === 0) break;

    if (attempt === 1) {
      const clearCache = runPhp([artisanPath, 'optimize:clear', '--no-interaction'], 60000);
      logResult('Laravel cache repair', clearCache);
    }

    if (attempt < 4) {
      await new Promise(resolve => setTimeout(resolve, attempt * 500));
    }
  }

  const migrationFailed = migration?.error || migration?.status !== 0;
  if (migrationFailed) {
    console.error('Laravel migration failed after all retries:', migration?.error || migration?.stderr);
    dialog.showErrorBox(
      'Mise à jour de la base impossible',
      `Yeboox POS a conservé vos données mais ne peut pas démarrer avec une base partiellement mise à jour.\n\nFermez toute autre instance puis réessayez.\nDiagnostic : ${startupLogPath}`
    );
    app.quit();
    return;
  }

  const applicationHealth = runPhp([artisanPath, 'yeboox:health', '--no-interaction'], 60000);
  logResult('Laravel application health', applicationHealth);
  const applicationHealthFailed = applicationHealth.error || applicationHealth.status !== 0;

  if (applicationHealthFailed) {
    console.error('Laravel health check failed:', applicationHealth.error || applicationHealth.stderr);
    dialog.showErrorBox(
      'Vérification locale impossible',
      `Le moteur PHP ou la base SQLite n'est pas conforme. L'application a été arrêtée avant tout écran 500.\n\nDiagnostic : ${startupLogPath}`
    );
    app.quit();
    return;
  }

  // Start PHP directly on the public directory. This removes the additional
  // Artisan/Symfony process layer and is more reliable on Windows 10/11.
  const publicPath = path.join(__dirname, 'public');
  const routerPath = path.join(__dirname, 'electron-server.php');
  phpServer = spawn(phpPath, [...phpArguments, '-S', `127.0.0.1:${port}`, '-t', publicPath, routerPath], {
    cwd: __dirname,
    env: phpEnvironment,
    windowsHide: true
  });

  phpServer.stdout.on('data', (data) => {
    console.log(`PHP: ${data}`);
  });

  phpServer.stderr.on('data', (data) => {
    console.error(`PHP Error: ${data}`);
    try {
      fs.appendFileSync(startupLogPath, `[${new Date().toISOString()}] PHP server: ${data}`, 'utf8');
    } catch {}
  });
  phpServer.on('error', (error) => {
    logResult('PHP server process error', { status: 1, error, stdout: '', stderr: '' });
    stopAfterStartupFailure(
      'Serveur PHP indisponible',
      'Le moteur local n’a pas pu être lancé. Vérifiez les fichiers installés ou réinstallez l’application.',
      startupLogPath
    );
  });
  phpServer.on('exit', (code, signal) => {
    phpServer = null;
    if (!isQuitting) {
      logResult('PHP server stopped unexpectedly', { status: code, stdout: '', stderr: signal || '' });
      stopAfterStartupFailure(
        'Serveur PHP arrêté',
        'Le moteur local s’est arrêté de façon inattendue. Vos données SQLite restent conservées.',
        startupLogPath
      );
    }
  });

  const startQueueWorker = () => {
    queueWorker = spawn(phpPath, [
      ...phpArguments,
      artisanPath,
      'queue:work',
      'database',
      '--queue=audit,default',
      '--sleep=2',
      '--tries=3',
      '--timeout=60',
      '--memory=128'
    ], {
      cwd: __dirname,
      env: phpEnvironment,
      windowsHide: true
    });

    queueWorker.stderr.on('data', (data) => {
      console.error(`Queue Error: ${data}`);
      try {
        fs.appendFileSync(startupLogPath, `[${new Date().toISOString()}] Queue worker: ${data}`, 'utf8');
      } catch {}
    });
    queueWorker.stdout.on('data', (data) => console.log(`Queue: ${data}`));
    queueWorker.on('error', (error) => {
      logResult('Queue worker process error', { status: 1, error, stdout: '', stderr: '' });
    });

    // Une erreur ponctuelle ne doit pas désactiver la file jusqu'au prochain lancement.
    queueWorker.on('close', (code) => {
      queueWorker = null;
      if (!isQuitting) {
        try {
          fs.appendFileSync(startupLogPath, `[${new Date().toISOString()}] Queue worker exited code=${code}; restart scheduled\n`, 'utf8');
        } catch {}
        setTimeout(startQueueWorker, 1500);
      }
    });
  };

  startQueueWorker();

  // La synchronisation tourne dans un processus PHP séparé. Une panne réseau
  // n'affecte ni le serveur local, ni l'encaissement, ni l'impression.
  const startSyncWorker = () => {
    syncWorker = spawn(phpPath, [
      ...phpArguments,
      artisanPath,
      'sync:sales',
      '--watch',
      '--no-interaction'
    ], {
      cwd: __dirname,
      env: phpEnvironment,
      windowsHide: true
    });

    syncWorker.stdout.on('data', (data) => console.log(`Sync: ${data}`));
    syncWorker.stderr.on('data', (data) => {
      console.error(`Sync Error: ${data}`);
      try {
        fs.appendFileSync(startupLogPath, `[${new Date().toISOString()}] Sync worker: ${data}`, 'utf8');
      } catch {}
    });
    syncWorker.on('error', (error) => {
      logResult('Sync worker process error', { status: 1, error, stdout: '', stderr: '' });
    });
    syncWorker.on('close', (code) => {
      syncWorker = null;
      if (!isQuitting) {
        try {
          fs.appendFileSync(startupLogPath, `[${new Date().toISOString()}] Sync worker exited code=${code}; restart scheduled\n`, 'utf8');
        } catch {}
        setTimeout(startSyncWorker, 2500);
      }
    });
  };

  startSyncWorker();

  createWindow(port, startupLogPath);

  if (recoveredDatabasePath) {
    dialog.showMessageBox({
      type: 'warning',
      title: 'Base SQLite réparée',
      message: 'Yeboox POS a restauré une base locale saine.',
      detail: `La base endommagée a été conservée ici : ${recoveredDatabasePath}`,
      buttons: ['Continuer'],
      defaultId: 0,
      noLink: true
    });
  }
});

app.on('window-all-closed', function () {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('before-quit', () => {
  isQuitting = true;
});

app.on('quit', () => {
  if (phpServer) {
    phpServer.kill();
  }
  if (queueWorker) {
    queueWorker.kill();
  }
  if (syncWorker) {
    syncWorker.kill();
  }
});

app.on('activate', function () {
  if (mainWindow === null) {
    createWindow(activePhpPort);
  }
});
