# Internal Tools API

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.4_LTS-000000?style=for-the-badge&logo=symfony&logoColor=white)
![API Platform](https://img.shields.io/badge/API_Platform-4.x-38A3A5?style=for-the-badge&logo=api-platform&logoColor=white)
![Doctrine](https://img.shields.io/badge/Doctrine_ORM-FC6A31?style=for-the-badge&logo=doctrine&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![PHPStan](https://img.shields.io/badge/PHPStan-Level_max-1c7ed6?style=for-the-badge&logo=php&logoColor=white)
![Composer](https://img.shields.io/badge/Composer-2.x-885630?style=for-the-badge&logo=composer&logoColor=white)

API REST pour la gestion des outils SaaS internes de TechCorp Solutions.

## Technologies

- **Langage** : PHP 8.4
- **Framework** : Symfony 7.4 LTS
- **API** : API Platform 4.x
- **ORM** : Doctrine ORM 3
- **Base de données** : MySQL 8.0 (via Docker)
- **Analyse statique** : PHPStan niveau max (extensions Symfony + Doctrine)

## Prérequis

- PHP 8.4+
- Composer 2.x
- Docker Desktop

## Quick Start

```bash
# 1. Installer les dépendances
composer install

# 2. Démarrer la base de données (MySQL + phpMyAdmin)
cd docker && docker compose --profile mysql up -d && cd ..

# 3. Copier .env.local.example vers .env.local
cp .env.local.example .env.local

# 4. Démarrer le serveur de développement
php -S localhost:8000 -t public/
```

- API : http://localhost:8000
- Documentation Swagger : http://localhost:8000/api/docs
- phpMyAdmin : http://localhost:8080 (user : `dev` / password : `dev123`)

## Base de données

La configuration Docker fournie démarre :

- **MySQL 8.0** sur `localhost:3306`
  - Database : `internal_tools`
  - User : `dev` / `dev123`
- **phpMyAdmin** sur `localhost:8080`

Les données initiales (24 outils, catégories, utilisateurs, historique d'usage) sont chargées automatiquement depuis `docker/mysql/init.sql` au premier démarrage du container.

### Configuration locale (.env.local)

Les credentials de connexion vivent dans `.env.local` (non versionné). Un fichier `.env.local.example` committé sert de template.

Exemple `.env.local` :

```env
###> doctrine/doctrine-bundle ###
DATABASE_URL="mysql://dev:dev123@127.0.0.1:3306/internal_tools?serverVersion=8.0&charset=utf8mb4"
###< doctrine/doctrine-bundle ###
```

Tester la connexion :

```bash
php bin/console dbal:run-sql "SELECT COUNT(*) FROM tools"
```

Doit retourner `24` (nombre d'outils chargés par le seed).

### Alignement entité / schéma

Le schéma base reste géré par `docker/mysql/init.sql` (aucune migration Doctrine n'est lancée). Pour vérifier que les entités sont parfaitement alignées avec les tables réelles :

```bash
php bin/console doctrine:schema:validate
```

Doit retourner `The mapping files are correct.` + `The database schema is in sync with the mapping files.`

Un `schema_filter` dans `config/packages/doctrine.yaml` cloisonne Doctrine aux tables mappées (`categories`, `tools`) pour que les tables auxiliaires (`users`, `usage_logs`, `cost_tracking`, etc.) soient ignorées par l'ORM — elles seront requêtées en SQL natif par les services analytics.

## Qualité du code

L'analyse statique est assurée par **PHPStan** au **niveau maximum**, avec les extensions officielles Symfony et Doctrine.

```bash
composer phpstan
```

La configuration se trouve dans `phpstan.dist.neon` à la racine.

## Conventions de modélisation

### Entités (`src/Entity/`)

Seules 2 tables sont mappées en entités Doctrine — celles directement exposées par l'API :

| Entité | Table DB | Rôle |
|---|---|---|
| `Tool` | `tools` | Ressource principale de l'API (CRUD via API Platform) |
| `Category` | `categories` | Catalogue de référence, relation `ManyToOne` avec Tool |

Les 5 autres tables (`users`, `user_tool_access`, `usage_logs`, `cost_tracking`, `access_requests`) ne sont **pas mappées** : elles seront interrogées via des requêtes SQL natives dans des services dédiés aux analytics (Part 2). Ça évite de créer des entités lourdes pour du read-only agrégé.

Points notables sur le mapping :

- **`Tool.monthlyCost`** est typé `string` côté PHP pour préserver la précision de `DECIMAL(10,2)`. Le cast en `float` se fait au niveau des DTOs de sortie.
- **`Tool.ownerDepartment` / `Tool.status`** utilisent `Types::ENUM` natif de Doctrine 3 avec `enumType`. Doctrine dérive automatiquement les valeurs possibles depuis les cases de l'enum PHP — zéro duplication.
- **Lifecycle callbacks** : `Tool` et `Category` gèrent leurs `created_at` / `updated_at` via `#[ORM\PrePersist]` et `#[ORM\PreUpdate]`, pas dans le constructeur (stratégie symétrique et idiomatic).
- **Getters nullable honnêtes** : quand une colonne DB autorise NULL, le getter retourne `?T` au lieu de masquer avec un fallback silencieux — ça évite de cacher des données corrompues.

### Enums PHP (`src/Enum/`)

Les enums servent à typer les valeurs métier dont la liste est connue. Selon le niveau de contrainte de la base, le choix se fait au cas par cas :

| Enum | Contrainte DB | Usage |
|---|---|---|
| `Department` | `ENUM('Engineering', ...)` côté MySQL | Enum obligatoire — la DB refuse toute valeur hors liste |
| `ToolStatus` | `ENUM('active', 'deprecated', 'trial')` | Enum obligatoire — idem |
| `CategoryName` | `VARCHAR(50)` libre | Enum **de référence** — liste les 10 catégories standard pour un usage type-safe côté code, sans forcer l'entité `Category` à les respecter |

Le cas `CategoryName` mérite une explication : la colonne `categories.name` n'est pas contrainte par la base, donc un admin peut insérer une nouvelle catégorie directement en DB. L'entité `Category` conserve un `string $name` libre pour tolérer cette flexibilité, tandis que l'enum `CategoryName` sert de **catalogue fixe** pour toute la partie code qui sait ne traiter que les 10 catégories standard (génération de rapports, filtres, etc.).

## Architecture de l'API (`src/ApiResource/` + `src/State/`)

La config d'API Platform est **entièrement externalisée** des entités via la classe shell `ToolResource`. L'entité `Tool` reste du Doctrine pur, sans aucune annotation `#[ApiResource]`.

```
┌─────────────────────────────────────────────┐
│ GET /api/tools                              │
├─────────────────────────────────────────────┤
│ API Platform schema validator               │ ← valide enum sur query params
│ (via QueryParameter dans ToolResource)      │
└─────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────┐
│ ToolCollectionProvider                      │
├─────────────────────────────────────────────┤
│ 1. ListToolsQueryFactory->create()          │ ← parse params, cast float, collecte violations numeric
│ 2. ValidatorInterface->validate($query)     │ ← Asserts + Callback (min_cost <= max_cost)
│ 3. ToolRepository->search($query)           │ ← filtres DB (WHERE department, monthlyCost >= / <=, JOIN c.name)
│ 4. ToolRepository->countAll()               │ ← total non filtré
│ 5. ToolMapper->toOutput() sur chaque Tool   │
│ 6. Retourne ToolCollectionOutput            │ ← shape contextualisée (voir plus bas)
└─────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────┐
│ JSON snake_case (NameConverter global)      │
└─────────────────────────────────────────────┘
```

### Query parameters réutilisables (`src/ApiResource/QueryParameter/`)

Trois classes génériques qui étendent `QueryParameter` pour éviter la duplication dans les attributs :

- **`EnumQueryParameter`** — prend un array de values (ex: `new EnumQueryParameter(Department::VALUES)`)
- **`PositiveNumberQueryParameter`** — schema `type: number, minimum: 0`
- **`StringQueryParameter`** — schema `type: string` libre

Réutilisables pour toutes les futures ressources (analytics Part 2).

### DTOs (`src/Dto/`)

Organisation **par ressource puis par direction** pour scaler sur plusieurs ressources :

```
src/Dto/
└── Tool/
    ├── Input/          # POST/PUT bodies (à venir)
    ├── Output/         # GET responses (ToolOutput, ToolDetailOutput, ToolCollectionOutput, UsageMetricsOutput)
    └── Query/          # GET query params (ListToolsQuery)
```

Tous les DTOs sont `final readonly class` avec promoted properties.

**`ListToolsQuery`** est **auto-contenue** : elle expose `hasFilters()`, `toFilterArray()` et porte son `#[Assert\Callback]` pour la règle business `min_cost <= max_cost`. Pas besoin d'un validator service dédié — `ValidatorInterface` est injecté directement dans le provider.

### Factory (`src/Factory/`)

**`ListToolsQueryFactory`** construit le DTO depuis la Request Symfony :
- Injection de `RequestStack` (pas de fuite du `$context['request']` d'API Platform)
- Conversion `string → float` sécurisée via `NullableFloat` — si le param n'est pas numérique, on catch `InvalidNumericValueException` et on **collecte** une `ConstraintViolation` (zéro duplication d'erreurs : factory + validator poussent dans la même `ConstraintViolationList`, la `ValidationFailedException` finale regroupe tout)
- Les violations numeric sont construites via `App\Validator\ViolationFactory::numeric()` — centralisé

### Value Objects (`src/ValueObject/`)

`NullableFloat::from(mixed $raw): ?float` encapsule la conversion :
- `null` ou chaîne vide → `null`
- Numérique → cast `float`
- Autre → throw `InvalidNumericValueException` avec message centralisé

### Mapper (`src/Mapper/`)

`ToolMapper` transforme `Tool` (entité Doctrine) en `ToolOutput` / `ToolDetailOutput` (DTOs). Il encapsule :
- Le flatten `Category → string $category` (juste le nom)
- Le cast `string (DECIMAL) → float` pour `monthlyCost`
- Le calcul de `totalMonthlyCost = monthlyCost × activeUsersCount`

Toute violation d'invariant (ex: entité non persistée) déclenche `InvalidToolStateException`.

## Validation

Architecture à **3 couches** qui convergent toutes vers `ValidationFailedException` → 400 unifié :

### 1. Schema API Platform (en amont du provider)
Les `QueryParameter` de `ToolResource` déclarent des schemas JSON (`enum`, `type`) qu'AP valide automatiquement avant d'atteindre notre code. Exemple : `?department=Legal` est rejeté par AP sans appeler le provider.

### 2. Asserts sur les DTOs (contraintes déclaratives)
`ListToolsQuery` porte :
- `#[Assert\PositiveOrZero]` sur `minCost` / `maxCost`
- `#[Assert\Length(max: Category::MAX_NAME_LENGTH)]` sur `category`
- `#[Assert\Callback]` sur `validate()` — règle business cross-field `min_cost <= max_cost`

Messages centralisés dans `App\Validator\Message\ValidationMessage` (`MUST_BE_NUMBER`, `MUST_BE_POSITIVE_OR_ZERO`, `MIN_COST_GREATER_THAN_MAX`, etc.).

### 3. Parsing/Type-coercion dans la Factory
La factory collecte des violations sans throw direct (via `ViolationFactory::numeric()`) quand un param numérique n'est pas convertible. Permet **cumuler** les erreurs de type et les règles Asserts dans une même `ConstraintViolationList`.

### Pourquoi pas d'`#[Assert\Choice]` sur `department` et `status` ?
Parce qu'AP les valide en amont via leur `enum` dans le schema `QueryParameter`. Ajouter un Assert dupliquerait la validation — un commentaire dans `ListToolsQuery` le documente.

### `ViolationFactory` (`src/Validator/ViolationFactory.php`)
Centralise la création de `ConstraintViolation` pour éviter la verbosité de son constructeur (6 params obligatoires). Actuellement : `numeric(field, value)` — à étendre selon les besoins.

## Format d'erreur (`src/EventSubscriber/ApiExceptionSubscriber.php`)

Un `ApiExceptionSubscriber` intercepte toutes les exceptions levées sur les routes `%api_prefix%` + URIs des ressources (aujourd'hui `ToolResource::URI_BASE`). Il les normalise au format du spec :

| HTTP | Forme JSON | Origine |
|---|---|---|
| **400** | `{ error: "Validation failed", details: { field: msg } }` | `ValidationFailedException` (Symfony) ou `ConstraintViolationListAwareExceptionInterface` (AP) |
| **404** | `{ error: "Tool not found", message: "Tool with ID X does not exist" }` | `ToolNotFoundException` |
| **404** | `{ error: "Resource not found", message: "..." }` | Autre `NotFoundHttpException` (route non trouvée, etc.) |
| **500** | `{ error: "Internal server error", message: "Database connection failed" }` | `Doctrine\DBAL\Exception` — message standardisé |
| **500** | `{ error: "Internal server error", message: "..." }` | Tout le reste — message brut en dev, **message générique en prod** via `%kernel.debug%` |

Les JSON sont construits via une classe factory `App\Http\ApiResponse` (factory `build()` privée + méthodes publiques expressives `notFound()`, `validationFailed()`, `internalError()`).

Le prefix API `/api` vient d'un paramètre Symfony `%api_prefix%` partagé entre `config/routes/api_platform.yaml` et le subscriber (via `#[Autowire]`) — une seule source de vérité.

### Messages et statuts (`src/Http/ApiMessage.php` + `App\Http\ApiResponse` constantes)

- **`ApiMessage::noResourceAvailable($resource)`** — "No tools available in the database" (DB vide)
- **`ApiMessage::noMatch($resource)`** — "No tools match the applied filters" (filtres sans résultat)
- **`ApiResponse::MESSAGE_DATABASE_CONNECTION_FAILED`** — "Database connection failed"
- **`ApiResponse::MESSAGE_INTERNAL_ERROR`** — "Internal server error occurred" (fallback prod)

## Shape contextuelle de la réponse `GET /api/tools`

Les champs de `ToolCollectionOutput` apparaissent **uniquement quand ils sont pertinents** (configuration Symfony Serializer `skip_null_values: true`) :

| Scénario | Clés présentes | Exemple |
|---|---|---|
| DB peuplée, aucun filtre | `data`, `total` | `{ "data": [...], "total": 24 }` |
| DB peuplée, avec filtres | `data`, `total`, `filtered`, `filters_applied` | `{ ..., "filtered": 7, "filters_applied": {"department": "Engineering"} }` |
| Filtres sans résultat | idem + `message` | `{ ..., "message": "No tools match the applied filters" }` |
| DB vide, sans filtre | `data`, `total`, `message` | `{ "data": [], "total": 0, "message": "No tools available in the database" }` |
| DB vide, avec filtres | `data`, `total`, `filtered`, `filters_applied`, `message` | idem avec les filtres en plus |

→ Le client comprend la différence entre **DB vide** (problème infra/données) et **filtres trop restrictifs** (résultat normal).

## Structure du projet

```
.
├── bin/                              # Console Symfony
├── config/
│   ├── packages/                     # Configuration bundles
│   ├── routes/api_platform.yaml      # Routes AP (prefix: %api_prefix%)
│   └── services.yaml                 # Parameters (api_prefix)
├── docker/                           # Stack MySQL + phpMyAdmin (fournie avec le test)
├── public/                           # Point d'entrée HTTP (index.php)
├── src/
│   ├── ApiResource/
│   │   ├── QueryParameter/           # Classes QueryParameter réutilisables (Enum, PositiveNumber, String)
│   │   └── Tool/ToolResource.php     # Config AP pour Tool (attribut #[ApiResource])
│   ├── Dto/Tool/
│   │   ├── Input/                    # DTOs POST/PUT (à venir)
│   │   ├── Output/                   # DTOs GET responses
│   │   └── Query/                    # DTOs query params (auto-contenus : Asserts + Callback + helpers)
│   ├── Entity/                       # Entités Doctrine (Tool, Category) avec TABLE_NAME en const
│   ├── Enum/                         # Enums typés (Department, ToolStatus, CategoryName)
│   ├── EventSubscriber/              # ApiExceptionSubscriber pour normaliser les erreurs
│   ├── Exception/
│   │   ├── Domain/                   # Invariants métier violés (InvalidToolStateException, InvalidNumericValueException)
│   │   └── Http/                     # Exceptions mappées à des codes HTTP (ToolNotFoundException)
│   ├── Factory/Tool/                 # ListToolsQueryFactory — Request → DTO + collecte de violations
│   ├── Http/
│   │   ├── ApiMessage.php            # Messages paramétrés (noResourceAvailable, noMatch)
│   │   └── ApiResponse.php           # Factory pour JsonResponse d'erreur (notFound, validationFailed, ...)
│   ├── Mapper/ToolMapper.php         # Transform Tool → DTOs output
│   ├── Repository/                   # Repositories Doctrine (ToolRepository::search + countAll)
│   ├── State/Provider/               # ToolCollectionProvider, ToolItemProvider
│   ├── Validator/
│   │   ├── Message/ValidationMessage.php  # Messages d'erreur génériques
│   │   └── ViolationFactory.php           # Factory pour ConstraintViolation
│   └── ValueObject/                  # NullableFloat — parsing typé safe
└── var/                              # Cache & logs (ignorés par git)
```
