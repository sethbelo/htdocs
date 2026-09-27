# API mobile Yeboox v1

> La nouvelle API multi-entreprise est documentée dans [`SAAS_V2.md`](SAAS_V2.md) et son contrat OpenAPI dans [`openapi-v2.yaml`](openapi-v2.yaml). La v1 reste maintenue pour les terminaux déjà déployés.

Cette API permet à une application Android ou à un terminal externe de consulter le catalogue, gérer les clients et rendez-vous, piloter sa caisse et enregistrer des ventes dans la même base que Yeboox.

## Adresse et transport

- Base URL : `https://votre-domaine.example/api/v1`
- Format : JSON UTF-8
- Authentification : jeton Sanctum dans `Authorization: Bearer <token>`
- En production, HTTPS est obligatoire. Ne publiez jamais le serveur PHP embarqué d’Electron directement sur Internet.
- Limites : 5 connexions/minute par adresse et courriel, puis 120 requêtes/minute par utilisateur.

Le contrat complet importable dans Postman, Insomnia et Android Studio se trouve dans [`openapi.yaml`](openapi.yaml).

## Connexion d’un appareil

```http
POST /api/v1/auth/login
Accept: application/json
Content-Type: application/json

{
  "email": "caissier@example.com",
  "password": "mot-de-passe",
  "device_name": "Samsung caisse 01"
}
```

La réponse contient le jeton une seule fois, sa date d’expiration, l’utilisateur et ses capacités. Stockez le jeton dans Android Keystore/EncryptedSharedPreferences, jamais dans un fichier ou dans les logs.

```http
Authorization: Bearer 1|jeton...
Accept: application/json
```

Un nouvel appel avec le même `device_name` remplace l’ancien jeton de cet appareil. `DELETE /auth/logout` révoque seulement le jeton courant.

## Capacités et permissions

Chaque requête doit satisfaire deux contrôles simultanés : la capacité du jeton et la permission actuelle du compte. Retirer une permission dans Yeboox bloque donc immédiatement l’appareil, même si son jeton n’est pas expiré.

| Capacité | Fonction |
|---|---|
| `bootstrap:read` | Configuration publique du terminal |
| `catalog:read` | Catalogue de produits |
| `customers:read`, `customers:write` | Clients |
| `appointments:read`, `appointments:write` | Rendez-vous |
| `register:read`, `register:write` | Session de caisse |
| `sales:read`, `sales:write` | Historique et encaissement |

## Démarrage Android recommandé

1. Appeler `/auth/login` et sauvegarder le jeton de façon chiffrée.
2. Appeler `/bootstrap` pour récupérer société, devise, taux, moyens de paiement, catégories et unités.
3. Charger `/products?per_page=50`, puis suivre `links.next` jusqu’à `null`.
4. Charger les clients et rendez-vous de la même manière.
5. Consulter `/register/current`; ouvrir la caisse si `data` vaut `null`.
6. Envoyer chaque encaissement avec un UUID stable dans `client_reference`.

Les listes utilisent une pagination par curseur. Pour une actualisation légère, envoyer `updated_after=2026-09-05T10:30:00Z`. Une synchronisation complète périodique reste recommandée afin de détecter les suppressions.

## Encaissement et idempotence

```json
{
  "client_reference": "550e8400-e29b-41d4-a716-446655440000",
  "customer_id": 12,
  "fulfillment_type": "takeaway",
  "discount_amount": 0,
  "items": [
    { "product_id": 4, "quantity": 2 }
  ],
  "payments": [
    { "method": "cash", "currency": "USD", "amount": 10, "received": 20 }
  ]
}
```

Le client n’envoie jamais le prix ni la TVA. Le serveur relit les produits, recalcule les montants, verrouille le stock, contrôle la caisse, enregistre les paiements et génère les écritures OHADA dans une transaction unique.

Si Android renvoie la même `client_reference` après une coupure, l’API retourne la vente existante avec `200 OK` et l’en-tête `X-Idempotent-Replay: true`. Une création normale retourne `201 Created`.

Les montants de vente sont conservés en USD, devise comptable de base actuelle. Un paiement CDF est divisé par le taux figé lors de l’ouverture de caisse. Pour un paiement autre que les espèces, `provider` et `reference` sont obligatoires.

## Codes de réponse

| Code | Signification |
|---:|---|
| 200 | Lecture, modification ou répétition idempotente réussie |
| 201 | Ressource créée |
| 401 | Jeton absent, invalide ou expiré |
| 403 | Capacité ou permission insuffisante |
| 404 | Ressource inexistante |
| 409 | Référence de commande déjà utilisée par un autre utilisateur |
| 422 | Données invalides ou règle métier refusée |
| 429 | Trop de requêtes |

Les erreurs de validation suivent ce format :

```json
{
  "message": "Les données fournies ne sont pas valides.",
  "errors": {
    "payments": ["La somme convertie des paiements doit correspondre au total calculé par le serveur."]
  }
}
```

## Exemple Retrofit

```kotlin
interface YebooxApi {
    @POST("api/v1/auth/login")
    suspend fun login(@Body body: LoginRequest): ApiEnvelope<LoginResponse>

    @GET("api/v1/products")
    suspend fun products(
        @Header("Authorization") bearer: String,
        @Query("cursor") cursor: String? = null,
        @Query("per_page") perPage: Int = 50,
    ): CursorEnvelope<ProductDto>

    @POST("api/v1/sales")
    suspend fun createSale(
        @Header("Authorization") bearer: String,
        @Body body: CreateSaleRequest,
    ): ApiEnvelope<SaleDto>
}
```

Configurez des délais réseau, une file d’attente locale et des reprises exponentielles uniquement pour les requêtes possédant une `client_reference`. N’effectuez pas de reprise automatique aveugle sur l’ouverture ou la fermeture de caisse.
