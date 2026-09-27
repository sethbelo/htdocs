/**
 * Yeboox POS — Offline Store (IndexedDB)
 * Stockage local des produits, commandes en attente, et sessions de caisse
 */

const DB_NAME = 'yeboox_pos_offline';
const DB_VERSION = 1;

class YebooxOfflineStore {
    constructor() {
        this.db = null;
        this.isOnline = navigator.onLine;
        this.syncInProgress = false;
        this._initListeners();
    }

    // ── Initialisation de la base IndexedDB ─────────────
    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Store: Produits (cache local)
                if (!db.objectStoreNames.contains('products')) {
                    const productsStore = db.createObjectStore('products', { keyPath: 'id' });
                    productsStore.createIndex('category_id', 'category_id', { unique: false });
                    productsStore.createIndex('name', 'name', { unique: false });
                }

                // Store: Catégories
                if (!db.objectStoreNames.contains('categories')) {
                    db.createObjectStore('categories', { keyPath: 'id' });
                }

                // Store: Méthodes de paiement
                if (!db.objectStoreNames.contains('payment_methods')) {
                    db.createObjectStore('payment_methods', { keyPath: 'id' });
                }

                // Store: Commandes en attente (queue offline)
                if (!db.objectStoreNames.contains('pending_orders')) {
                    const ordersStore = db.createObjectStore('pending_orders', { keyPath: 'local_id', autoIncrement: true });
                    ordersStore.createIndex('status', 'status', { unique: false });
                    ordersStore.createIndex('created_at', 'created_at', { unique: false });
                }

                // Store: Paramètres & Métadonnées sync
                if (!db.objectStoreNames.contains('sync_meta')) {
                    db.createObjectStore('sync_meta', { keyPath: 'key' });
                }
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                console.log('[OfflineStore] IndexedDB initialized');
                resolve(this);
            };

            request.onerror = (event) => {
                console.error('[OfflineStore] IndexedDB error:', event.target.error);
                reject(event.target.error);
            };
        });
    }

    // ── Gestion connectivité ────────────────────────────
    _initListeners() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this._dispatchStatus('online');
            this.syncPendingOrders();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            this._dispatchStatus('offline');
        });

        // Écouter les messages du Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', (event) => {
                if (event.data?.type === 'SYNC_TRIGGER') {
                    this.syncPendingOrders();
                }
            });
        }
    }

    _dispatchStatus(status) {
        window.dispatchEvent(new CustomEvent('yeboox:connectivity', { detail: { status } }));
        console.log(`[OfflineStore] Connectivity: ${status}`);
    }

    // ════════════════════════════════════════════════════
    // PRODUITS — Cache local
    // ════════════════════════════════════════════════════

    async cacheProducts(products) {
        const tx = this.db.transaction('products', 'readwrite');
        const store = tx.objectStore('products');
        // Clear existing
        store.clear();
        products.forEach(p => store.put(p));
        await tx.complete;
        await this._setMeta('products_cached_at', new Date().toISOString());
        console.log(`[OfflineStore] Cached ${products.length} products`);
    }

    async getProducts() {
        return this._getAll('products');
    }

    async getProductsByCategory(categoryId) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('products', 'readonly');
            const store = tx.objectStore('products');
            const index = store.index('category_id');
            const request = index.getAll(categoryId);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // ════════════════════════════════════════════════════
    // CATÉGORIES — Cache local
    // ════════════════════════════════════════════════════

    async cacheCategories(categories) {
        const tx = this.db.transaction('categories', 'readwrite');
        const store = tx.objectStore('categories');
        store.clear();
        categories.forEach(c => store.put(c));
        await tx.complete;
        console.log(`[OfflineStore] Cached ${categories.length} categories`);
    }

    async getCategories() {
        return this._getAll('categories');
    }

    // ════════════════════════════════════════════════════
    // MÉTHODES DE PAIEMENT — Cache local
    // ════════════════════════════════════════════════════

    async cachePaymentMethods(methods) {
        const tx = this.db.transaction('payment_methods', 'readwrite');
        const store = tx.objectStore('payment_methods');
        store.clear();
        methods.forEach(m => store.put(m));
        await tx.complete;
    }

    async getPaymentMethods() {
        return this._getAll('payment_methods');
    }

    // ════════════════════════════════════════════════════
    // COMMANDES OFFLINE — Queue locale
    // ════════════════════════════════════════════════════

    /**
     * Enregistrer une commande en local quand offline
     */
    async saveOfflineOrder(orderData) {
        const order = {
            ...orderData,
            status: 'pending_sync',
            created_at: new Date().toISOString(),
            local_order_number: `OFF-${Date.now()}`,
        };

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('pending_orders', 'readwrite');
            const store = tx.objectStore('pending_orders');
            const request = store.add(order);
            request.onsuccess = () => {
                order.local_id = request.result;
                console.log(`[OfflineStore] Saved offline order #${order.local_order_number}`);
                this._dispatchOrderEvent('saved', order);
                resolve(order);
            };
            request.onerror = () => reject(request.error);
        });
    }

    async getPendingOrders() {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('pending_orders', 'readonly');
            const store = tx.objectStore('pending_orders');
            const index = store.index('status');
            const request = index.getAll('pending_sync');
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async getPendingOrderCount() {
        const orders = await this.getPendingOrders();
        return orders.length;
    }

    async markOrderSynced(localId) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('pending_orders', 'readwrite');
            const store = tx.objectStore('pending_orders');
            const getReq = store.get(localId);
            getReq.onsuccess = () => {
                const order = getReq.result;
                if (order) {
                    order.status = 'synced';
                    order.synced_at = new Date().toISOString();
                    store.put(order);
                }
                resolve();
            };
            getReq.onerror = () => reject(getReq.error);
        });
    }

    // ════════════════════════════════════════════════════
    // SYNCHRONISATION
    // ════════════════════════════════════════════════════

    /**
     * Synchroniser les données depuis le serveur (pull)
     */
    async pullFromServer() {
        if (!this.isOnline) return { success: false, reason: 'offline' };

        try {
            const token = document.querySelector('meta[name="api-token"]')?.content;
            const response = await fetch('/api/sync/pull', {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': token ? `Bearer ${token}` : '',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                }
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();

            if (data.products) await this.cacheProducts(data.products);
            if (data.categories) await this.cacheCategories(data.categories);
            if (data.payment_methods) await this.cachePaymentMethods(data.payment_methods);

            await this._setMeta('last_pull', new Date().toISOString());
            console.log('[OfflineStore] Pull sync completed');
            return { success: true, data };
        } catch (error) {
            console.error('[OfflineStore] Pull sync failed:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Pousser les commandes offline vers le serveur (push)
     */
    async syncPendingOrders() {
        if (!this.isOnline || this.syncInProgress) return;

        const pending = await this.getPendingOrders();
        if (pending.length === 0) return;

        this.syncInProgress = true;
        this._dispatchOrderEvent('sync_start', { count: pending.length });

        let synced = 0;
        let failed = 0;

        for (const order of pending) {
            try {
                const token = document.querySelector('meta[name="api-token"]')?.content;
                const response = await fetch('/api/sync/push', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': token ? `Bearer ${token}` : '',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(order),
                });

                if (response.ok) {
                    await this.markOrderSynced(order.local_id);
                    synced++;
                } else {
                    failed++;
                }
            } catch (error) {
                console.error(`[OfflineStore] Sync failed for order ${order.local_id}:`, error);
                failed++;
            }
        }

        this.syncInProgress = false;
        this._dispatchOrderEvent('sync_complete', { synced, failed });
        console.log(`[OfflineStore] Sync complete: ${synced} synced, ${failed} failed`);
    }

    // ════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════

    async _getAll(storeName) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(storeName, 'readonly');
            const store = tx.objectStore(storeName);
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async _setMeta(key, value) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('sync_meta', 'readwrite');
            const store = tx.objectStore('sync_meta');
            store.put({ key, value });
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject();
        });
    }

    async _getMeta(key) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('sync_meta', 'readonly');
            const store = tx.objectStore('sync_meta');
            const request = store.get(key);
            request.onsuccess = () => resolve(request.result?.value || null);
            request.onerror = () => reject(request.error);
        });
    }

    _dispatchOrderEvent(type, detail = {}) {
        window.dispatchEvent(new CustomEvent('yeboox:order', { detail: { type, ...detail } }));
    }
}

// ── Singleton global ────────────────────────────────────
window.YebooxOffline = new YebooxOfflineStore();

// Auto-init quand le DOM est prêt
document.addEventListener('DOMContentLoaded', async () => {
    try {
        await window.YebooxOffline.init();
        // Pull initial si online
        if (navigator.onLine) {
            await window.YebooxOffline.pullFromServer();
        }
    } catch (error) {
        console.error('[OfflineStore] Init failed:', error);
    }
});
