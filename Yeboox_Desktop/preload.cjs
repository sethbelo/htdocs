const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('yebooxDesktop', {
  isElectron: true,
  platform: process.platform,
  printReceipt: ({ paperWidth, contentHeightMm, html = '', styles = '', silent = false } = {}) => ipcRenderer.invoke('receipt:print', {
    paperWidth: Number(paperWidth),
    contentHeightMm: Number(contentHeightMm),
    html: String(html),
    styles: String(styles),
    silent: silent === true
  }),
  printA4: ({ landscape = false, silent = false } = {}) => ipcRenderer.invoke('document:print-a4', {
    landscape: landscape === true,
    silent: silent === true
  }),
  savePosState: (state = {}) => ipcRenderer.invoke('pos:state:save', state),
  loadPosState: () => ipcRenderer.invoke('pos:state:load'),
  applyDisplaySettings: (settings = {}) => ipcRenderer.invoke('display:apply', settings)
});
