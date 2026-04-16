# Internal Tools API

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.4_LTS-000000?style=for-the-badge&logo=symfony&logoColor=white)
![API Platform](https://img.shields.io/badge/API_Platform-4.x-38A3A5?style=for-the-badge&logo=api-platform&logoColor=white)
![Doctrine](https://img.shields.io/badge/Doctrine_ORM-FC6A31?style=for-the-badge&logo=doctrine&logoColor=white)
![Composer](https://img.shields.io/badge/Composer-2.x-885630?style=for-the-badge&logo=composer&logoColor=white)

API REST pour la gestion des outils SaaS internes de TechCorp Solutions.

## Technologies

- **Langage** : PHP 8.4
- **Framework** : Symfony 7.4 LTS
- **API** : API Platform 4.x
- **ORM** : Doctrine ORM

## Prérequis

- PHP 8.4+
- Composer 2.x

## Quick Start

```bash
# 1. Installer les dépendances
composer install

# 2. Démarrer le serveur de développement
php -S localhost:8000 -t public/
```

L'API sera disponible sur http://localhost:8000
La documentation Swagger est accessible sur http://localhost:8000/api/docs

## Structure du projet

```
.
├── bin/            # Console Symfony
├── config/         # Configuration bundles / routes / services
├── public/         # Point d'entrée HTTP (index.php)
├── src/            # Code source applicatif
└── var/            # Cache & logs (ignorés par git)
```
