### YuMerch Backend
Un projet réalisé avec Symfony, ce projet est un site de e-commerce dédié aux vêtements et articles de merchandising de Yum.

### Installation
1. Cloner le dépôt : `git clone https://github.com/CamiloBrvo/YuMerch-Backend.git`
2. Installer les dépendances : `composer install`

### Configuration
1. Copier le fichier `.env` : `cp .env.dist .env`
2. Configurer les paramètres de la base de données dans le fichier `.env`

### Base de données
Pour créer la base de données :
```bash
php bin/console doctrine:database:create
```

Pour créer les tables :
```bash
php bin/console doctrine:schema:update --force
```

Pour ajouter les fixtures :
````bash
php bin/console doctrine:fixtures:load
````

Pour vider la base de données sans supprimer les tables :
```bash
php bin/console doctrine:fixtures:load --purge-with-truncate
```

Pour supprimer la base de données :
````bash
php bin/console doctrine:database:drop --force
````

### Serveur
Pour lancer le serveur :
```bash
symfony serve
```

### JWT
PEM pass phrase : doranco

Il faut créer les fichiers puclic.key et private.key, pour ça il faut faire :

```bash
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
```
```bash
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

### Composer
Pour installer les fixtures :
```bash
composer req --dev make doctrine/doctrine-fixtures-bundle
```

Pour installer le faker pour les fixtures :
```bash
composer require --dev fzaninotto/faker
```

composer show doctrine/orm
composer require symfony/validator
composer require symfony/security-bundle
composer require security
composer require symfony/serializer
composer require doctrine/annotations
composer require lexik/jwt-authentication-bundle

composer require symfony/web-profiler-bundle 

composer require --dev symfony/maker-bundle

