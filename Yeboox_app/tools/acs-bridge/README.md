# Connecteur Yeboox ACS

Ce service local relie Yeboox aux lecteurs PC/SC comme l’ACS ACR122U. Il lit les UID de cartes ISO 14443-3 et écrit le jeton Yeboox sur les cartes NTAG/MIFARE Ultralight inscriptibles.

```bash
cd tools/acs-bridge
npm install
npm start
```

Le connecteur écoute uniquement sur `127.0.0.1:8976`. Sur Linux, `pcscd` et les bibliothèques `libpcsclite` doivent être installés. macOS et Windows fournissent normalement PC/SC avec le système.
