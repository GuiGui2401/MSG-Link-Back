# MSG Link Backend

Backend API pour MSG Link - Application de messagerie anonyme avec monétisation.

## Fonctionnalités

- **Messagerie anonyme** - Envoi et réception de messages anonymes
- **Confessions publiques** - Feed de confessions publiques
- **Chat temps réel** - Conversations privées avec WebSocket (Laravel Reverb)
- **Abonnement Premium** - Révélation d'identité des expéditeurs (450 FCFA/mois)
- **Système de cadeaux** - Monétisation pour les créateurs
- **Portefeuille** - Gestion des gains et retraits Mobile Money
- **Notifications push** - Via Firebase Cloud Messaging

## Prérequis

- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js 18+ (pour les assets frontend si nécessaire)

## Installation

### 1. Cloner le projet

```bash
git clone <repository-url>
cd msg-link-backend
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configuration de l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configurer la base de données

Créez une base de données MySQL et mettez à jour le fichier `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=msg_link
DB_USERNAME=root
DB_PASSWORD=votre_mot_de_passe
```

### 5. Exécuter les migrations

```bash
php artisan migrate
```

### 6. Configurer les services externes

Voir la section [Configuration des services](#configuration-des-services) ci-dessous.

### 7. Démarrer le serveur

```bash
# Serveur API
php artisan serve

# Serveur WebSocket (dans un autre terminal)
php artisan reverb:start

# Worker de queue (dans un autre terminal)
php artisan queue:work
```

## Configuration des services

### Reverb (WebSocket - Chat temps réel)

Générez vos propres clés :

```bash
# Générer les clés
openssl rand -hex 16  # Pour REVERB_APP_KEY
openssl rand -hex 32  # Pour REVERB_APP_SECRET
```

```env
REVERB_APP_ID=votre-app-id
REVERB_APP_KEY=votre-app-key
REVERB_APP_SECRET=votre-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### CinetPay (Paiements)

1. Créez un compte sur [CinetPay](https://cinetpay.com)
2. Récupérez vos clés API dans le dashboard

```env
CINETPAY_API_KEY=votre_api_key
CINETPAY_SITE_ID=votre_site_id
CINETPAY_SECRET_KEY=votre_secret_key
CINETPAY_BASE_URL=https://api-checkout.cinetpay.com/v2
CINETPAY_NOTIFY_URL=https://votre-domaine.com/api/v1/payments/webhook/cinetpay
CINETPAY_TRANSFER_PASSWORD=votre_mot_de_passe_transfert
```

### LigosApp (Paiements alternatif)

1. Créez un compte sur [LigosApp](https://www.lygosapp.com)
2. Générez une clé API dans le [dashboard](https://pay.lygosapp.com/dashboard/api-keys)

```env
LIGOSAPP_API_KEY=lygosapp-xxxx-xxxx-xxxx-xxxxxxxxxxxx
LIGOSAPP_BASE_URL=https://api.lygosapp.com/v1
```

Documentation : [docs.lygosapp.com](https://docs.lygosapp.com)

### Firebase (Notifications Push)

1. Créez un projet sur [Firebase Console](https://console.firebase.google.com)
2. Allez dans Paramètres > Comptes de service
3. Cliquez sur "Générer une nouvelle clé privée"
4. Placez le fichier JSON téléchargé dans `storage/app/firebase/`

```env
FIREBASE_CREDENTIALS=storage/app/firebase/votre-fichier-firebase.json
FIREBASE_PROJECT_ID=votre-project-id
```

### InTouch (Paiements - Optionnel)

```env
INTOUCH_API_KEY=votre_api_key
INTOUCH_SECRET=votre_secret
INTOUCH_BASE_URL=https://api.intouch.com
```

## Structure du projet

```
app/
├── Http/Controllers/Api/V1/    # Contrôleurs API
├── Models/                      # Modèles Eloquent
├── Services/
│   ├── Payment/                 # Services de paiement
│   │   ├── PaymentServiceInterface.php
│   │   ├── CinetPayService.php
│   │   └── LigosAppService.php
│   └── NotificationService.php  # Service Firebase
└── Traits/
    └── HasWallet.php            # Trait pour le portefeuille

config/
├── msglink.php                  # Configuration de l'application
└── services.php                 # Configuration des services tiers

database/migrations/             # Migrations de base de données
routes/api.php                   # Routes API
```

## Routes API principales

### Authentification
- `POST /api/v1/auth/register` - Inscription
- `POST /api/v1/auth/login` - Connexion
- `POST /api/v1/auth/logout` - Déconnexion

### Messages anonymes
- `POST /api/v1/messages` - Envoyer un message anonyme
- `GET /api/v1/messages` - Lister ses messages reçus

### Confessions
- `GET /api/v1/confessions` - Feed des confessions
- `POST /api/v1/confessions` - Publier une confession

### Premium
- `GET /api/v1/premium/pricing` - Tarifs premium
- `POST /api/v1/premium/subscribe` - S'abonner

### Paiements (Webhooks)
- `POST /api/v1/payments/webhook/cinetpay` - Webhook CinetPay
- `POST /api/v1/payments/webhook/ligosapp` - Webhook LigosApp
- `GET /api/v1/payments/status/{reference}` - Statut d'un paiement

### Portefeuille
- `GET /api/v1/wallet` - Solde et transactions
- `POST /api/v1/wallet/withdraw` - Demande de retrait

## Configuration de l'application

Les paramètres métier sont dans `config/msglink.php` :

| Paramètre | Description | Défaut |
|-----------|-------------|--------|
| `PREMIUM_PRICE` | Prix abonnement premium (FCFA) | 450 |
| `PLATFORM_FEE_PERCENT` | Commission sur les cadeaux (%) | 5 |
| `MIN_WITHDRAWAL_AMOUNT` | Retrait minimum (FCFA) | 1000 |
| `WITHDRAWAL_FEE` | Frais de retrait (FCFA) | 0 |
| `DEFAULT_PAYMENT_PROVIDER` | Provider par défaut | cinetpay |

## Commandes utiles

```bash
# Vider le cache
php artisan config:clear
php artisan cache:clear

# Reconstruire le cache de config
php artisan config:cache

# Voir les routes
php artisan route:list

# Lancer les tests
php artisan test

# Vérifier les migrations en attente
php artisan migrate:status
```

## Déploiement en production

1. Configurez les variables d'environnement sur votre serveur
2. Mettez `APP_ENV=production` et `APP_DEBUG=false`
3. Configurez les URLs de webhook avec votre domaine de production
4. Configurez un superviseur pour les workers de queue
5. Configurez Nginx/Apache comme reverse proxy

```bash
# Optimisation pour la production
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Sécurité

- Les credentials Firebase sont stockés dans `storage/app/firebase/` (gitignore)
- Ne commitez jamais le fichier `.env`
- Utilisez des webhooks HTTPS en production
- Validez les signatures des webhooks de paiement

## Support

Pour toute question ou problème, ouvrez une issue sur le repository.

## Licence

Propriétaire - Tous droits réservés.
