# PostgreSQL central et ventes hors ligne

## Architecture retenue

La connexion Laravel principale du projet est désormais **PostgreSQL**
(`DB_CONNECTION=pgsql`). Elle porte les entreprises, utilisateurs, catalogues,
stocks, ventes, paiements et écritures comptables du SaaS.

Yeboox Desktop conserve **SQLite** comme base opérationnelle du terminal. Un
encaissement, le stock, la comptabilité OHADA et l'impression restent donc
fonctionnels sans Internet. Dans la même transaction que la vente, une ligne
est ajoutée à `sync_outbox`.

Le processus `php artisan sync:sales --watch` tente ensuite d'envoyer les
événements vers `POST /api/v2/sync/sales`. En cas de coupure, l'événement reste
sur disque et bénéficie d'un délai progressif avant la tentative suivante.
Electron démarre et supervise automatiquement ce processus.

Le serveur SaaS utilise **PostgreSQL** comme connexion Laravel par défaut. Il
authentifie le terminal par `X-Yeboox-Sync-Token`, vérifie l'entreprise et le
point de vente, puis stocke l'UUID dans `sync_receipts`. La contrainte unique sur
cet UUID rend chaque reprise idempotente : une vente ne peut être créée qu'une
fois, même si la réponse réseau a été perdue.

## Configuration serveur PostgreSQL

Copier `.env.cloud.example` vers `.env`, renseigner `APP_KEY`, les paramètres
PostgreSQL et une URL HTTPS publique, puis exécuter :

```bash
php artisan migrate --force
php artisan optimize
```

Le serveur central doit utiliser :

```dotenv
APP_RUNTIME_MODE=cloud
DB_CONNECTION=pgsql
OFFLINE_SYNC_ENABLED=false
```

## Configuration d'un terminal

Le terminal doit conserver `DB_CONNECTION=sqlite` et définir :

```dotenv
APP_RUNTIME_MODE=desktop
OFFLINE_SYNC_ENABLED=true
OFFLINE_SYNC_URL=https://api.votre-domaine.cd
OFFLINE_SYNC_TOKEN=une-cle-longue-et-unique
```

Après rotation d'une clé, stocker uniquement son SHA-256 dans
`pos_sync_credentials`. La clé en clair ne doit jamais être enregistrée sur le
serveur.

## Initialisation et diagnostic

Pour mettre à jour la base PostgreSQL principale :

```bash
php artisan migrate --force
php artisan sync:status
```

Pour reprendre une ancienne base SQLite avant la première bascule :

```bash
APP_RUNTIME_MODE=desktop OFFLINE_SYNC_ENABLED=true DB_CONNECTION=sqlite \
SQLITE_DATABASE=database/database.sqlite php artisan sync:backfill-sales
php artisan central:bootstrap --source=offline --force
```

`central:bootstrap` sert à la migration initiale contrôlée. Après la mise en
production, les terminaux passent exclusivement par l'API HTTPS et ne reçoivent
pas les identifiants PostgreSQL.

Pour démarrer localement le projet sur sa base PostgreSQL principale :

```bash
php artisan serve --host=127.0.0.1 --port=8010
```

Les sauvegardes PostgreSQL, le TLS, la supervision du serveur et la rotation des
clés doivent être activés avant une exposition publique.
