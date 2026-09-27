const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('sogerefDesktop', {
    platform: process.platform,
    setTheme: (theme) => ipcRenderer.send('theme:change', ['light', 'dark', 'system'].includes(theme) ? theme : 'system'),
    getLicenseInfo: () => ipcRenderer.invoke('license:info'),
    getAppInfo: () => ipcRenderer.invoke('app:info'),
});
