# API SaaS Yeboox v2

La v2 étend l’API mobile v1 avec le cloisonnement multi-entreprise, le provisionnement des sociétés, les établissements, les points de vente, les abonnements, le catalogue administrable, le stock, la comptabilité OHADA, les devises et la fiscalité. Les routes v1 restent disponibles pour les terminaux existants.

## Hiérarchie et isolation

```text
Plateforme Yeboox
└── Entreprise
    ├── Utilisateurs
    └── Établissement(s)
        └── Point(s) de vente
            ├── Abonnement
            ├── Produits et stocks
            ├── Ventes et caisse
            ├── Comptabilité
            └── Configuration, devises et taxes
```

Chaque écriture métier porte `company_id`, `establishment_id` et `point_of_sale_id`. Le middleware résout ces identifiants depuis le jeton ou les en-têtes, vérifie l’appartenance de l’utilisateur **avant** le model binding Laravel, puis les scopes Eloquent filtrent automatiquement les requêtes. Une ressource appartenant à un autre locataire répond `404`, sans révéler son existence.

## Connexion et choix du PDV

Base URL : `https://votre-domaine.example/api/v2`

```http
POST /auth/login
Content-Type: application/json
Accept: application/json

{
  "email": "caissier@societe.cd",
  "password": "mot-de-passe",
  "device_name": "Samsung caisse 01",
  "point_of_sale_id": 12
}
```

Le jeton Sanctum mémorise l’entreprise et le PDV sélectionnés. Pour un compte autorisé sur plusieurs PDV :

```http
PUT /context
Authorization: Bearer 1|jeton
Content-Type: application/json

{ "point_of_sale_id": 15 }
```

`GET /context` retourne l’entreprise, l’établissement, le PDV, l’abonnement et les PDV accessibles. Les intégrations serveur peuvent préciser `X-Company-ID` et `X-Point-of-Sale-ID`; les valeurs doivent correspondre au compte, sinon la requête est refusée.

## Abonnement

Chaque PDV possède son propre abonnement (`trial`, `active`, `expired`, `suspended`). Une échéance valide ou une période de grâce autorise les routes métier. Une échéance expirée répond `402`; l’authentification, le contexte et l’administration restent accessibles afin de permettre le renouvellement.

## Routes principales

| Méthode et route | Capacité | Fonction |
|---|---|---|
| `GET /context` | `bootstrap:read` | Contexte actif et PDV disponibles |
| `PUT /context` | `bootstrap:read` | Changer de PDV |
| `GET /bootstrap` | `bootstrap:read` | Référentiels et configuration du terminal |
| `GET /products` | `catalog:read` | Catalogue du PDV |
| `POST /products` | `catalog:write` | Créer un produit |
| `GET/PUT/DELETE /products/{id}` | `catalog:read/write` | Consulter, modifier ou désactiver |
| `GET /stock/movements` | `stock:read` | Historique filtrable |
| `POST /stock/products/{id}/adjust` | `stock:write` | Ajuster atomiquement le stock |
| `GET /accounting/records` | `accounting:read` | Journal OHADA et balance débit/crédit |
| `GET/PUT /configuration` | `configuration:read/write` | Société, devises et taxes |
| `/customers`, `/appointments`, `/register`, `/sales` | capacités v1 | Opérations POS compatibles |

Les opérations d’administration de société exigent aussi `is_company_admin=true`. Les opérations plateforme exigent `is_master_admin=true`.

## Provisionnement master

```http
POST /master/companies
Authorization: Bearer <jeton-master>
Content-Type: application/json

{
  "name": "Yetu Market SARL",
  "legal_name": "Yetu Market SARL",
  "administrator_name": "Administrateur Yetu",
  "administrator_email": "admin@yetu.cd",
  "administrator_password": "MotDePasse-2026",
  "administrator_password_confirmation": "MotDePasse-2026",
  "base_currency": "CDF",
  "secondary_currency": "USD",
  "exchange_rate": 2850,
  "plan_code": "professional",
  "subscription_days": 30
}
```

Cette requête est atomique : elle crée la société, l’établissement principal, le premier PDV, son abonnement, son administrateur, les moyens de paiement, l’unité, la catégorie, le produit générique et les réglages initiaux. En cas d’échec, rien n’est conservé.

Routes master :

- `GET /master/companies`
- `POST /master/companies`
- `GET /master/companies/{id}`
- `PUT /master/companies/{id}`

Routes administrateur société :

- `GET /company/locations`
- `POST /company/establishments`
- `POST /company/establishments/{id}/points-of-sale`
- `PUT /company/points-of-sale/{id}`
- `POST /company/points-of-sale/{id}/subscriptions`
- `GET/POST /company/users`
- `PUT /company/users/{id}`

## Ajustement de stock

```json
{
  "operation": "increase",
  "quantity": 24,
  "reason": "approvisionnement",
  "reference": "BL-2026-0042",
  "unit_cost": 3.5
}
```

`operation` accepte `set`, `increase` ou `decrease`. Le produit est verrouillé pendant la transaction et un stock négatif est refusé.

## Devises et taxes

```http
PUT /configuration

{
  "company_currency": "CDF",
  "secondary_currency": "USD",
  "exchange_rate": 2850,
  "currency_operation": "multiply",
  "product_price_currency": "CDF",
  "default_tax_rate": 16,
  "default_tax_included": true,
  "company_tva": "A1234567"
}
```

Le taux et le mode taxe sont propres au PDV. Les ventes continuent d’être recalculées côté serveur : une application cliente ne doit jamais imposer le prix, la TVA ou le total.

## Réponses et sécurité

| Code | Signification |
|---:|---|
| 200/201 | Opération réussie |
| 401 | Jeton absent ou invalide |
| 402 | Abonnement PDV expiré/suspendu |
| 403 | Capacité, permission ou appartenance refusée |
| 404 | Ressource absente ou appartenant à un autre tenant |
| 409 | Contexte PDV manquant ou conflit métier |
| 422 | Validation échouée |
| 429 | Limite de requêtes atteinte |

Les jetons doivent être stockés dans le Keystore Android ou un coffre équivalent. Le serveur PHP embarqué dans Electron ne doit pas être exposé directement sur Internet : déployez l’API SaaS derrière HTTPS, un reverse proxy et une sauvegarde centralisée.

Le contrat machine de la v2 se trouve dans [`openapi-v2.yaml`](openapi-v2.yaml). La documentation v1 demeure dans [`README.md`](README.md).
