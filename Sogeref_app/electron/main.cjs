const { app, BrowserWindow, ipcMain, nativeTheme, shell } = require('electron');
const { spawn, spawnSync } = require('child_process');
const crypto = require('crypto');
const fs = require('fs');
const http = require('http');
const net = require('net');
const os = require('os');
const path = require('path');

const PRODUCT_NAME = 'SOGEREF ERP';
const PUBLISHER = 'Yetoo Technologies';
const TRIAL_DAYS = 365;
const TRIAL_SECRET = 'sogeref-erp-v1-2026-cd-4c1d9a07';
const REGISTRY_KEY = 'HKCU\\Software\\SOGEREF\\ERP';

let laravelServer;
let mainWindow;
let laravelPort;
let licenseInfo;
let isQuitting = false;

function appRoot() {
    return app.isPackaged ? path.join(process.resourcesPath, 'app') : path.join(__dirname, '..');
}

function desktopStoragePath() {
    return path.join(app.getPath('userData'), 'storage');
}

function themeIcon() {
    return path.join(__dirname, 'icons', nativeTheme.shouldUseDarkColors ? 'sogeref-dark.png' : 'sogeref-light.png');
}

function ensureDirectory(directory) {
    fs.mkdirSync(directory, { recursive: true });
}

function ensureDesktopDirectories() {
    const storage = desktopStoragePath();
    [
        'app/private',
        'app/public',
        'framework/cache/data',
        'framework/sessions',
        'framework/views',
        'logs',
    ].forEach((relativePath) => ensureDirectory(path.join(storage, relativePath)));
    ensureDirectory(path.join(app.getPath('userData'), 'database'));
    ensureDirectory(path.join(app.getPath('userData'), 'license'));
    ensureDirectory(path.join(app.getPath('userData'), 'logs'));
}

function applicationKey() {
    const keyPath = path.join(app.getPath('userData'), '.application-key');

    if (!fs.existsSync(keyPath)) {
        fs.writeFileSync(keyPath, `base64:${crypto.randomBytes(32).toString('base64')}`, { mode: 0o600 });
    }

    return fs.readFileSync(keyPath, 'utf8').trim();
}

function signLicense(data) {
    const payload = [data.edition, data.startedAt, data.expiresAt, data.lastSeenAt].join('|');
    return crypto.createHmac('sha256', TRIAL_SECRET).update(payload).digest('hex');
}

function readLicenseFile() {
    const licensePath = path.join(app.getPath('userData'), 'license', 'exploration.json');

    try {
        const state = JSON.parse(fs.readFileSync(licensePath, 'utf8'));
        return crypto.timingSafeEqual(Buffer.from(state.signature || ''), Buffer.from(signLicense(state))) ? state : null;
    } catch {
        return null;
    }
}

function readRegistryValue(name) {
    if (process.platform !== 'win32') return null;

    const result = spawnSync('reg.exe', ['query', REGISTRY_KEY, '/v', name], { encoding: 'utf8', windowsHide: true });
    if (result.status !== 0) return null;
    const match = result.stdout.match(new RegExp(`${name}\\s+REG_SZ\\s+(.+)$`, 'mi'));

    return match?.[1]?.trim() || null;
}

function writeRegistryValue(name, value) {
    if (process.platform !== 'win32') return;
    spawnSync('reg.exe', ['add', REGISTRY_KEY, '/v', name, '/t', 'REG_SZ', '/d', value, '/f'], { windowsHide: true });
}

function persistLicense(state) {
    const licensePath = path.join(app.getPath('userData'), 'license', 'exploration.json');
    const signedState = { ...state, signature: signLicense(state) };
    fs.writeFileSync(licensePath, JSON.stringify(signedState, null, 2), { mode: 0o600 });
    writeRegistryValue('TrialStartedAt', state.startedAt);
    writeRegistryValue('TrialExpiresAt', state.expiresAt);
    writeRegistryValue('LastSeenAt', state.lastSeenAt);
}

function validateExplorationLicense() {
    const now = new Date();
    const fileState = readLicenseFile();
    const registryStartedAt = readRegistryValue('TrialStartedAt');
    const registryLastSeenAt = readRegistryValue('LastSeenAt');
    const starts = [fileState?.startedAt, registryStartedAt].filter(Boolean).map((value) => new Date(value)).filter((value) => !Number.isNaN(value.getTime()));
    const startedAt = starts.length ? new Date(Math.min(...starts.map((value) => value.getTime()))) : now;
    const expiresAt = new Date(startedAt.getTime() + TRIAL_DAYS * 24 * 60 * 60 * 1000);
    const lastSeenCandidates = [fileState?.lastSeenAt, registryLastSeenAt].filter(Boolean).map((value) => new Date(value)).filter((value) => !Number.isNaN(value.getTime()));
    const lastSeenAt = lastSeenCandidates.length ? new Date(Math.max(...lastSeenCandidates.map((value) => value.getTime()))) : now;
    const clockRollback = now.getTime() < lastSeenAt.getTime() - 24 * 60 * 60 * 1000;
    const expired = now.getTime() > expiresAt.getTime();
    const state = {
        edition: 'exploration',
        startedAt: startedAt.toISOString(),
        expiresAt: expiresAt.toISOString(),
        lastSeenAt: new Date(Math.max(now.getTime(), lastSeenAt.getTime())).toISOString(),
    };

    persistLicense(state);

    return {
        ...state,
        valid: !expired && !clockRollback,
        expired,
        clockRollback,
        daysRemaining: Math.max(0, Math.ceil((expiresAt.getTime() - now.getTime()) / (24 * 60 * 60 * 1000))),
    };
}

function phpConfiguration() {
    if (!app.isPackaged) {
        return { binary: process.env.SOGEREF_PHP_BINARY || 'php', prefix: [] };
    }

    const phpDirectory = path.join(process.resourcesPath, 'php');
    const binary = path.join(phpDirectory, 'php.exe');
    const phpIni = path.join(phpDirectory, 'php.ini');

    if (!fs.existsSync(binary)) {
        throw new Error('Le runtime PHP Windows embarqué est introuvable.');
    }

    return {
        binary,
        prefix: ['-c', phpIni, '-d', `extension_dir=${path.join(phpDirectory, 'ext')}`],
    };
}

function desktopEnvironment(port) {
    const database = path.join(app.getPath('userData'), 'database', 'sogeref.sqlite');

    return {
        ...process.env,
        APP_NAME: PRODUCT_NAME,
        APP_ENV: 'production',
        APP_DEBUG: 'false',
        APP_KEY: applicationKey(),
        APP_URL: `http://127.0.0.1:${port}`,
        SOGEREF_DESKTOP: 'true',
        SOGEREF_EDITION: 'exploration',
        SOGEREF_LICENSE_EXPIRES_AT: licenseInfo.expiresAt,
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: database,
        LARAVEL_STORAGE_PATH: desktopStoragePath(),
        CACHE_STORE: 'file',
        SESSION_DRIVER: 'file',
        QUEUE_CONNECTION: 'sync',
        FILESYSTEM_DISK: 'public',
        LOG_CHANNEL: 'single',
        LOG_LEVEL: 'warning',
    };
}

function runPhp(argumentsList, environment, timeout = 120000) {
    const php = phpConfiguration();

    return new Promise((resolve, reject) => {
        const child = spawn(php.binary, [...php.prefix, ...argumentsList], {
            cwd: appRoot(),
            env: environment,
            windowsHide: true,
        });
        let output = '';
        const timer = setTimeout(() => {
            child.kill();
            reject(new Error(`Délai dépassé pour php ${argumentsList.join(' ')}`));
        }, timeout);

        child.stdout.on('data', (chunk) => { output += chunk.toString(); });
        child.stderr.on('data', (chunk) => { output += chunk.toString(); });
        child.on('error', (error) => {
            clearTimeout(timer);
            reject(error);
        });
        child.on('exit', (code) => {
            clearTimeout(timer);
            if (code === 0) resolve(output);
            else reject(new Error(output || `PHP a quitté avec le code ${code}.`));
        });
    });
}

async function findAvailablePort() {
    return new Promise((resolve, reject) => {
        const server = net.createServer();
        server.unref();
        server.on('error', reject);
        server.listen(0, '127.0.0.1', () => {
            const { port } = server.address();
            server.close(() => resolve(port));
        });
    });
}

async function initializeLaravel() {
    ensureDesktopDirectories();
    laravelPort = Number(process.env.SOGEREF_PORT) || await findAvailablePort();
    const environment = desktopEnvironment(laravelPort);
    const databasePath = environment.DB_DATABASE;
    const isFreshDatabase = !fs.existsSync(databasePath) || fs.statSync(databasePath).size === 0;

    await runPhp(['artisan', 'migrate', '--force', '--no-interaction'], environment);
    if (isFreshDatabase) {
        await runPhp(['artisan', 'db:seed', '--force', '--no-interaction'], environment);
    }

    const php = phpConfiguration();
    const logPath = path.join(app.getPath('userData'), 'logs', 'laravel-server.log');
    const logStream = fs.createWriteStream(logPath, { flags: 'a' });
    laravelServer = spawn(php.binary, [...php.prefix, 'artisan', 'serve', '--host=127.0.0.1', `--port=${laravelPort}`, '--no-reload'], {
        cwd: appRoot(),
        env: environment,
        windowsHide: true,
    });
    laravelServer.stdout.pipe(logStream);
    laravelServer.stderr.pipe(logStream);
    laravelServer.on('exit', () => {
        if (!isQuitting && mainWindow && !mainWindow.isDestroyed()) {
            showStatusWindow('Service arrêté', 'Le service local SOGEREF ERP s’est arrêté. Relancez l’application ou contactez l’administrateur.', 'error');
        }
    });

    await waitForLaravel();
}

async function waitForLaravel() {
    const startedAt = Date.now();

    while (Date.now() - startedAt < 45000) {
        const available = await new Promise((resolve) => {
            const request = http.get(`http://127.0.0.1:${laravelPort}/up`, (response) => {
                response.resume();
                resolve(response.statusCode >= 200 && response.statusCode < 500);
            });
            request.setTimeout(1200, () => request.destroy());
            request.on('error', () => resolve(false));
        });

        if (available) return;
        await new Promise((resolve) => setTimeout(resolve, 350));
    }

    throw new Error('Le serveur local Laravel ne répond pas après 45 secondes.');
}

function browserWindowOptions() {
    return {
        width: 1440,
        height: 920,
        minWidth: 1100,
        minHeight: 700,
        show: false,
        title: PRODUCT_NAME,
        icon: themeIcon(),
        backgroundColor: nativeTheme.shouldUseDarkColors ? '#090b10' : '#f7f8fb',
        ...(process.platform === 'darwin' ? { titleBarStyle: 'hiddenInset' } : {}),
        webPreferences: {
            contextIsolation: true,
            sandbox: true,
            devTools: !app.isPackaged,
            preload: path.join(__dirname, 'preload.cjs'),
        },
    };
}

function secureWindow(window) {
    window.webContents.session.setPermissionRequestHandler((_webContents, _permission, callback) => callback(false));
    window.webContents.setWindowOpenHandler(({ url }) => {
        if (/^https?:\/\//i.test(url)) shell.openExternal(url);
        return { action: 'deny' };
    });
    window.webContents.on('will-navigate', (event, url) => {
        const localOrigin = laravelPort ? `http://127.0.0.1:${laravelPort}` : null;
        if (url.startsWith('file://')) return;
        if (localOrigin && !url.startsWith(localOrigin)) {
            event.preventDefault();
            if (/^https?:\/\//i.test(url)) shell.openExternal(url);
        }
    });
}

function createMainWindow() {
    mainWindow = new BrowserWindow(browserWindowOptions());
    mainWindow.setMenu(null);
    secureWindow(mainWindow);
    mainWindow.once('ready-to-show', () => mainWindow.show());
    mainWindow.loadURL(`http://127.0.0.1:${laravelPort}`);
    updateNativeBranding();
}

function showStatusWindow(title, message, status = 'error') {
    if (!mainWindow || mainWindow.isDestroyed()) {
        mainWindow = new BrowserWindow(browserWindowOptions());
        mainWindow.setMenu(null);
        secureWindow(mainWindow);
        mainWindow.once('ready-to-show', () => mainWindow.show());
    }

    mainWindow.loadFile(path.join(__dirname, 'status.html'), {
        query: {
            title,
            message,
            status,
            expires: licenseInfo?.expiresAt || '',
        },
    });
}

function updateNativeBranding() {
    if (!mainWindow || mainWindow.isDestroyed()) return;
    const icon = themeIcon();
    mainWindow.setBackgroundColor(nativeTheme.shouldUseDarkColors ? '#090b10' : '#f7f8fb');
    if (process.platform === 'win32') mainWindow.setIcon(icon);
    if (process.platform === 'darwin' && app.dock) app.dock.setIcon(icon);
}

ipcMain.on('theme:change', (_event, theme) => {
    nativeTheme.themeSource = ['light', 'dark', 'system'].includes(theme) ? theme : 'system';
    updateNativeBranding();
});
ipcMain.handle('license:info', () => licenseInfo);
ipcMain.handle('app:info', () => ({ name: PRODUCT_NAME, version: app.getVersion(), publisher: PUBLISHER, packaged: app.isPackaged }));
nativeTheme.on('updated', updateNativeBranding);

if (!app.requestSingleInstanceLock()) {
    app.quit();
} else {
    app.on('second-instance', () => {
        if (mainWindow) {
            if (mainWindow.isMinimized()) mainWindow.restore();
            mainWindow.focus();
        }
    });

    app.whenReady().then(async () => {
        app.setAppUserModelId('cd.sogeref.erp');
        ensureDesktopDirectories();
        licenseInfo = validateExplorationLicense();

        if (!licenseInfo.valid) {
            const title = licenseInfo.expired ? 'Licence d’exploration expirée' : 'Horloge système non valide';
            const message = licenseInfo.expired
                ? `La période d’exploration de ${TRIAL_DAYS} jours est terminée. Contactez Yetoo Technologies pour renouveler la licence.`
                : 'Une modification importante de la date système a été détectée. Rétablissez la date correcte ou contactez Yetoo Technologies.';
            showStatusWindow(title, message, licenseInfo.expired ? 'expired' : 'clock');
            return;
        }

        try {
            await initializeLaravel();
            createMainWindow();
        } catch (error) {
            const errorLog = path.join(app.getPath('userData'), 'logs', 'startup-error.log');
            fs.writeFileSync(errorLog, `${new Date().toISOString()}\n${error.stack || error.message}\n`, { flag: 'a' });
            showStatusWindow('Démarrage impossible', 'Le moteur local PHP/SQLite n’a pas pu démarrer. Consultez le journal de démarrage ou contactez Yetoo Technologies.', 'error');
        }
    });
}

app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0 && licenseInfo?.valid && laravelPort) createMainWindow();
});
app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') app.quit();
});
app.on('before-quit', () => {
    isQuitting = true;
    if (laravelServer) laravelServer.kill();
});
