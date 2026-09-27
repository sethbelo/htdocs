const ACS_BRIDGE_URL = 'http://127.0.0.1:8976';

async function bridgeRequest(path, body = {}) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 2500);

    try {
        const response = await fetch(`${ACS_BRIDGE_URL}${path}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(body),
            signal: controller.signal,
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(data.message || `Le connecteur ACS a refusé l’opération (${response.status}).`);
            error.bridgeReachable = true;
            throw error;
        }
        return data;
    } finally {
        clearTimeout(timeout);
    }
}

function decodeNdefRecord(record) {
    try {
        return JSON.parse(new TextDecoder(record.encoding || 'utf-8').decode(record.data));
    } catch (_) {
        return null;
    }
}

async function webNfcWrite(payload) {
    if (!('NDEFReader' in window)) throw new Error('Web NFC n’est pas disponible dans ce navigateur. Démarrez le connecteur local ACS/PC-SC.');

    const reader = new NDEFReader();
    await reader.write({records: [{recordType: 'text', data: JSON.stringify(payload)}]});

    return {uid: `NDEF-${payload.token.slice(0, 24)}`, token: payload.token, readerType: 'web-nfc'};
}

async function webNfcRead() {
    if (!('NDEFReader' in window)) throw new Error('Web NFC n’est pas disponible dans ce navigateur. Démarrez le connecteur local ACS/PC-SC.');

    const controller = new AbortController();
    const reader = new NDEFReader();
    const result = new Promise((resolve, reject) => {
        const timeout = setTimeout(() => {
            controller.abort();
            reject(new Error('Aucune carte détectée. Réessayez en maintenant la carte sur le lecteur.'));
        }, 15000);

        reader.addEventListener('readingerror', () => {
            clearTimeout(timeout);
            reject(new Error('La carte NFC a été détectée mais son contenu est illisible.'));
        }, {once: true});

        reader.addEventListener('reading', event => {
            clearTimeout(timeout);
            const payload = Array.from(event.message.records).map(decodeNdefRecord).find(Boolean) || {};
            resolve({uid: event.serialNumber || `NDEF-${(payload.token || '').slice(0, 24)}`, token: payload.token || '', readerType: 'web-nfc'});
        }, {once: true});
    });

    await reader.scan({signal: controller.signal});
    return result;
}

window.YebooxNfc = {
    async write({cardNumber, token}) {
        const payload = {application: 'Yeboox', version: 1, card_number: cardNumber, token};

        if (window.YebooxAcsBridge?.writeCard) return window.YebooxAcsBridge.writeCard(payload);

        try {
            const result = await bridgeRequest('/v1/cards/write', payload);
            return {uid: result.uid, token, readerType: result.reader || 'acs-pcsc'};
        } catch (error) {
            if (error.bridgeReachable) throw error;
            return webNfcWrite(payload);
        }
    },

    async read() {
        if (window.YebooxAcsBridge?.readCard) return window.YebooxAcsBridge.readCard();

        try {
            const result = await bridgeRequest('/v1/cards/read');
            return {uid: result.uid || '', token: result.token || '', readerType: result.reader || 'acs-pcsc'};
        } catch (error) {
            if (error.bridgeReachable) throw error;
            return webNfcRead();
        }
    },
};
