import http from 'node:http';
import {createRequire} from 'node:module';

const require = createRequire(import.meta.url);
const {NFC} = require('nfc-pcsc');
const PORT = 8976;
const DATA_LENGTH = 64;
const START_BLOCK = 4;

let activeReader = null;
let activeCard = null;
const waiters = new Set();
const nfc = new NFC();

nfc.on('reader', reader => {
    activeReader = reader;
    process.stdout.write(`Lecteur NFC connecté : ${reader.reader.name}\n`);

    reader.on('card', card => {
        activeReader = reader;
        activeCard = card;
        for (const resolve of waiters) resolve({reader, card});
        waiters.clear();
    });
    reader.on('card.off', () => {
        if (activeReader === reader) activeCard = null;
    });
    reader.on('end', () => {
        if (activeReader === reader) {
            activeReader = null;
            activeCard = null;
        }
    });
    reader.on('error', error => process.stderr.write(`Erreur lecteur : ${error.message}\n`));
});

nfc.on('error', error => process.stderr.write(`Erreur PC/SC : ${error.message}\n`));

function waitForCard(timeoutMs = 15000) {
    if (activeReader && activeCard) return Promise.resolve({reader: activeReader, card: activeCard});

    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => {
            waiters.delete(done);
            reject(new Error('Aucune carte détectée sur le lecteur ACS.'));
        }, timeoutMs);
        const done = value => {
            clearTimeout(timer);
            resolve(value);
        };
        waiters.add(done);
    });
}

async function readPayload(reader) {
    try {
        const buffer = await reader.read(START_BLOCK, DATA_LENGTH, 4);
        const text = buffer.toString('utf8').replace(/\0+$/g, '');
        return text.startsWith('YBX1:') ? text.slice(5).trim() : '';
    } catch (_) {
        return '';
    }
}

async function readCard() {
    const {reader, card} = await waitForCard();
    return {uid: card.uid || '', token: await readPayload(reader), reader: reader.reader.name};
}

async function writeCard(payload) {
    if (!/^[A-Za-z0-9]{32,64}$/.test(payload.token || '')) throw new Error('Jeton de carte invalide.');
    const {reader, card} = await waitForCard();
    const data = Buffer.alloc(DATA_LENGTH);
    data.write(`YBX1:${payload.token}`, 0, DATA_LENGTH, 'utf8');

    try {
        await reader.write(START_BLOCK, data, 4);
    } catch (error) {
        throw new Error(`Écriture refusée par la carte. Utilisez une NTAG/MIFARE Ultralight inscriptible (${error.message}).`);
    }

    return {uid: card.uid || '', token: payload.token, reader: reader.reader.name};
}

function json(response, status, body) {
    response.writeHead(status, {
        'Content-Type': 'application/json; charset=utf-8',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Headers': 'Content-Type',
        'Access-Control-Allow-Methods': 'POST, OPTIONS',
    });
    response.end(JSON.stringify(body));
}

function requestBody(request) {
    return new Promise((resolve, reject) => {
        let body = '';
        request.on('data', chunk => {
            body += chunk;
            if (body.length > 4096) reject(new Error('Requête trop volumineuse.'));
        });
        request.on('end', () => {
            try { resolve(body ? JSON.parse(body) : {}); }
            catch (_) { reject(new Error('Requête JSON invalide.')); }
        });
    });
}

http.createServer(async (request, response) => {
    if (request.method === 'OPTIONS') return json(response, 204, {});
    if (request.method !== 'POST') return json(response, 405, {message: 'Méthode non autorisée.'});

    try {
        if (request.url === '/v1/cards/read') return json(response, 200, await readCard());
        if (request.url === '/v1/cards/write') return json(response, 200, await writeCard(await requestBody(request)));
        return json(response, 404, {message: 'Route inconnue.'});
    } catch (error) {
        return json(response, 422, {message: error.message});
    }
}).listen(PORT, '127.0.0.1', () => {
    process.stdout.write(`Connecteur Yeboox ACS prêt sur http://127.0.0.1:${PORT}\n`);
    process.stdout.write('Branchez le lecteur ACS puis présentez une carte NTAG ou MIFARE Ultralight.\n');
});
