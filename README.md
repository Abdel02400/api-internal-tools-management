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

Quatre classes génériques qui étendent `QueryParameter` pour éviter la duplication dans les attributs :

- **`EnumQueryParameter`** — prend un array de values (ex: `new EnumQueryParameter(Department::VALUES)`)
- **`PositiveNumberQueryParameter`** — schema `type: number, minimum: 0`
- **`PositiveIntegerQueryParameter`** — schema `type: integer, minimum: 1` (+ `maximum` optionnel)
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

### Value Objects (`src/ValueObject/Number/`)

Deux parseurs typés pour les query params numériques :

- **`NullableFloat::from(mixed $raw): ?float`** — `null`/chaîne vide → `null`, numérique → `float`, sinon throw `InvalidNumericValueException`
- **`NullableInt::from(mixed $raw): ?int`** — idem mais strict integer, throw `InvalidIntegerValueException` sinon

Les deux exceptions portent un message centralisé, attrapé par la factory pour alimenter la `ConstraintViolationList`.

### Mapper (`src/Mapper/`)

`ToolMapper` transforme `Tool` (entité Doctrine) en `ToolOutput` / `ToolDetailOutput` (DTOs). Il encapsule :
- Le flatten `Category → string $category` (juste le nom)
- Le cast `string (DECIMAL) → float` pour `monthlyCost`
- Le calcul de `totalMonthlyCost = monthlyCost × activeUsersCount`

Toute violation d'invariant (ex: entité non persistée) déclenche `InvalidToolStateException`.

## `GET /api/tools/{id}` — détail + usage_metrics

### Flux

```
┌─────────────────────────────────────────────┐
│ ToolItemProvider                            │
├─────────────────────────────────────────────┤
│ 1. ToolIdFactory->create($uriVariables)     │ ← 400 "Must be an integer" si format invalide
│ 2. ToolRepository->find(id)                 │ ← 404 ToolNotFoundException si null
│ 3. UsageLogRepository->getLast30DaysMetrics │ ← SQL natif sur `usage_logs` (table non mappée)
│ 4. ToolMapper->toDetailOutput(tool, metrics)│
└─────────────────────────────────────────────┘
```

Note : pas de `requirements` sur la route `{id}` — on laisse l'ID atteindre le provider pour renvoyer un **400 explicite** sur format invalide plutôt qu'un 404 générique "Resource not found" au niveau du routeur.

### `ToolIdFactory` (`src/Factory/Tool/`)

Extrait et valide l'ID depuis `$uriVariables` (`filter_var` + `FILTER_VALIDATE_INT`), retourne un `int` ou throw `ValidationFailedException` (via `ViolationFactory::integer()`, même pattern que `ListToolsQueryFactory` pour cumuler les violations dans la même `ConstraintViolationList`).

Pas de DTO `GetToolQuery` : pour un simple `int` ce serait du ceremonial vide. Dès qu'un endpoint aura plus d'un param de path/query à valider, ce serait le moment de créer une DTO dédiée.

### `UsageLogRepository` (`src/Repository/`)

Service **DBAL natif** (pas `ServiceEntityRepository`) car la table `usage_logs` est volontairement **non mappée** côté Doctrine (voir le `schema_filter`). Injection directe de `Doctrine\DBAL\Connection`.

Méthode `getLast30DaysMetrics(int $toolId): UsageMetricsOutput` :
- SQL agrégé : `COUNT(*) as total_sessions, COALESCE(AVG(usage_minutes), 0) as avg_minutes`
- Fenêtre : `session_date >= (today - 30 days)` — date calculée côté PHP pour rester portable (pas de `INTERVAL` MySQL-specific)
- Troncature via cast `(int)` sur la moyenne
- Constante `LAST_N_DAYS = 30` — évite le magic number

**Note sur les seed data** : le fichier `docker/mysql/init.sql` contient des `usage_logs` datés de mai-juillet 2025 (fixes). Aujourd'hui étant au-delà de cette fenêtre, l'API renvoie systématiquement `total_sessions: 0, avg_session_minutes: 0` sur la fenêtre 30j. Ce n'est pas un bug — la SQL est validée manuellement avec une fenêtre élargie. En prod avec des données récentes, les valeurs remonteraient naturellement.

### DTOs imbriquées : `UsageMetricsOutput` + `UsageWindowOutput`

La shape JSON attendue est :
```json
"usage_metrics": {
    "last_30_days": {
        "total_sessions": 127,
        "avg_session_minutes": 45
    }
}
```

Deux niveaux → **deux DTOs distincts** (pas un seul DTO plat) :

- **`UsageMetricsOutput`** — conteneur des fenêtres. Porte `#[SerializedName('last_30_days')]` car le NameConverter camelCase→snake_case produirait `last30_days` (pas `last_30_days`) sur une propriété `$last30Days`.
- **`UsageWindowOutput`** — bucket atomique (`totalSessions`, `avgSessionMinutes`). Nom générique pour être réutilisable : ajouter `last_7_days` ou `last_90_days` demain ne demandera qu'une propriété de plus sur `UsageMetricsOutput`, le bucket reste identique.

### Validation ID

`filter_var($rawId, FILTER_VALIDATE_INT)` :
- `'abc'`, `'5.5'`, `null` → `false` → 400 `{id: "Must be an integer"}`
- `'5'`, `'0'`, `'-5'` → entier → `find()` → 200 ou 404 selon l'existence

Les entiers négatifs/zéro ne sont **pas** bloqués côté format (valides au regard de `FILTER_VALIDATE_INT`) — ils donnent simplement un 404 via `find()` puisqu'aucun tool n'existe à ces IDs.

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
| **400** | `{ error: "Validation failed", details: { body: "Malformed JSON body" } }` | `NotEncodableValueException` (JSON invalide) |
| **400** | `{ error: "Validation failed", details: { <field>: "Unknown field" } }` | `ExtraAttributesException` (strict JSON) |
| **400** | `{ error: "Validation failed", details: { <field>: "This field is required" } }` | `MissingConstructorArgumentsException` |
| **400** | `{ error: "Validation failed", details: { <field>: "Invalid value" } }` | `PartialDenormalizationException` (types/enums invalides, cumulés) — cf. note ci-dessous |
| **500** | `{ error: "Internal server error", message: "Database connection failed" }` | `Doctrine\DBAL\Exception` — message standardisé |
| **500** | `{ error: "Internal server error", message: "..." }` | Tout le reste — message brut en dev, **message générique en prod** via `%kernel.debug%` |

Les JSON sont construits via une classe factory `App\Http\ApiResponse` (factory `build()` privée + méthodes publiques expressives `notFound()`, `validationFailed()`, `internalError()`).

Le prefix API `/api` vient d'un paramètre Symfony `%api_prefix%` partagé entre `config/routes/api_platform.yaml` et le subscriber (via `#[Autowire]`) — une seule source de vérité.

### Format d'erreur spécifique aux endpoints Analytics (Part 2 spec)

Le spec Part 2 §3.2 impose un **format d'erreur distinct** pour les endpoints `/api/analytics/*` :

```json
{
  "error": "Invalid analytics parameter",
  "details": {
    "limit": "Must be positive integer between 1 and 100"
  }
}
```

Deux divergences vs le format tools :
- **`error`** : `"Invalid analytics parameter"` au lieu de `"Validation failed"`
- **Messages** normalisés par nom de paramètre, indépendants des messages techniques d'AP/Symfony (pour coller au texte exact du spec et éviter tout leak)

Le switch est purement **route-based** dans `ApiExceptionSubscriber::isAnalyticsPath()` (préfixe `%api_prefix%/analytics`). Aucune configuration par endpoint — les 5 endpoints analytics héritent automatiquement du format.

Mapping des messages (`ApiExceptionSubscriber::analyticsMessage()`) :

| Nom de param | Message renvoyé |
|---|---|
| `limit` | `"Must be positive integer between 1 and 100"` |
| `min_cost`, `max_cost` | `"Must be a positive number"` |
| `max_users` | `"Must be a non-negative integer"` |
| autre (ex: `sort_by`, `order`) | `"Invalid value"` (fallback générique) |

Les constantes vivent dans `ValidationMessage` (préfixe `ANALYTICS_*`). La méthode `ApiResponse::invalidAnalyticsParameter(array $details)` construit la JSON response.

### Anti-leak sur les erreurs de dénormalisation

Les messages générés par Symfony pour les erreurs de type/enum (`"This value should be of type App\Enum\ToolStatus"`, `"of type ToolStatus|null"`, etc.) **exposent des noms de classe internes** — mauvais pour une API publique. API Platform convertit ces `PartialDenormalizationException` en `ConstraintViolation` avec `root = null` (au lieu de l'objet cible pour une vraie violation Asserts).

Le subscriber exploite cette distinction : quand `$violation->getRoot() === null`, la violation vient d'une erreur de dénormalisation → le message est remplacé par `ValidationMessage::INVALID_VALUE` ("Invalid value"). Les vraies violations Asserts applicatives (Length, Regex, Url, contraintes custom, etc.) ont `root === l'objet DTO` → leur message centralisé est préservé.

→ Trade-off assumé : on perd la liste des valeurs valides pour un enum invalide (on ne dit pas "Must be one of: active, deprecated, trial"), mais on ne leak **aucun** nom de classe interne. Les valeurs valides restent documentées côté Swagger (via le schema de l'input DTO + les exemples).

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
| DB peuplée, avec filtres | + `filtered`, `filters_applied` | `{ ..., "filtered": 7, "filters_applied": {"department": "Engineering"} }` |
| Avec pagination | + `pagination_applied` | `{ ..., "pagination_applied": {"page": 2, "limit": 10, "total_pages": 3} }` |
| Avec tri | + `sort_applied` | `{ ..., "sort_applied": {"sort_by": "cost", "order": "desc"} }` |
| Filtres sans résultat | + `message` | `{ ..., "message": "No tools match the applied filters" }` |
| DB vide, sans filtre | `data`, `total`, `message` | `{ "data": [], "total": 0, "message": "No tools available in the database" }` |
| Page hors range | + `message` | `{ ..., "message": "Page exceeds available range (max page: 3)" }` |

→ Le client distingue **DB vide** (problème infra/données), **filtres trop restrictifs** (résultat normal) et **page hors range** (erreur client de pagination).

## Pagination et tri

### Params supportés

| Param | Type | Default | Contrainte |
|---|---|---|---|
| `page` | integer | `1` (si pagination activée) | ≥ 1 |
| `limit` | integer | `10` (si pagination activée) | 1 ≤ limit ≤ 100 |
| `sort_by` | enum | — | `cost` \| `name` \| `date` |
| `order` | enum | `asc` (si sort_by présent) | `asc` \| `desc` |

**Règle** : pas de pagination par défaut. Si **aucun** des 2 params `page`/`limit` n'est envoyé, l'API renvoie le dataset complet. Dès qu'**un** est présent, les defaults complètent l'autre.

### Pourquoi `pagination_applied` et `sort_applied` séparés de `filters_applied` ?

Pagination et tri ne sont **pas des filtres** — ils ne réduisent pas le jeu de résultats, ils slice/ordonnent. Les garder dans des champs distincts sépare clairement les 3 intentions client :
- `filters_applied` = critères de recherche
- `pagination_applied` = tranche demandée + **`total_pages`** calculé sur le jeu filtré (info utile pour le frontend qui navigue dans les pages)
- `sort_applied` = ordonnancement

### `total_pages` et page hors range

`total_pages` se base sur le **filtered count** (pas le total global), donc reflète le nombre de pages réellement disponibles pour la requête courante. Si `page > total_pages` → data vide + message `"Page exceeds available range (max page: X)"`.

## `POST /api/tools` — création

### Flux

```
┌─────────────────────────────────────────────┐
│ Serializer Symfony (strict JSON)            │ ← rejet champs inconnus + collect denormalization errors
│                                             │   → PartialDenormalizationException si types invalides
│                                             │   → ExtraAttributesException si champs inconnus
│                                             │   → NotEncodableValueException si JSON malformé
└─────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────┐
│ ValidatorInterface (auto AP)                │ ← Asserts + contraintes custom
│                                             │   → ValidationFailedException (cumulée)
└─────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────┐
│ ToolPersistProcessor                        │
├─────────────────────────────────────────────┤
│ 1. CategoryRepository->find(categoryId)     │ ← garde-fou "race condition" (validé en amont)
│ 2. new Tool($name, $category, $cost, $dept) │ ← status & active_users_count = defaults entité
│ 3. setDescription/setVendor/setWebsiteUrl   │
│ 4. em->persist + em->flush                  │
│ 5. ToolMapper->toWriteOutput($tool)         │ → ToolWriteOutput (12 champs du spec)
└─────────────────────────────────────────────┘
```

### Validations (CreateToolInput)

| Champ | Contraintes |
|---|---|
| `name` | `NotBlank`, `Length(2-100)`, **`UniqueToolName`** (contrainte custom DB) |
| `category_id` | `Positive`, **`ExistingCategory`** (contrainte custom DB) |
| `monthly_cost` | `GreaterThanOrEqual(0)`, `Regex /^\d+(\.\d{1,2})?$/` (max 2 décimales) |
| `owner_department` | Typé `Department` (enum — invalide → 400 `"Invalid value"` via `PartialDenormalizationException`) |
| `vendor` | `NotBlank`, `Length(max: 100)` |
| `description` | Optional, pas de contrainte (colonne `TEXT` libre) |
| `website_url` | Optional, `Url`, `Length(max: 255)` |

### Contraintes custom DB-dépendantes (`src/Validator/Constraint/`)

- **`UniqueToolName`** — query `ToolRepository::findOneBy(['name' => ...])`. Message centralisé (`NAME_ALREADY_EXISTS`).
- **`ExistingCategory`** — query `CategoryRepository::find($id)`. Message centralisé (`CATEGORY_NOT_FOUND`).

Ces contraintes passent par le flow `ValidationFailedException` classique → 400 unifié (même format que les autres violations).

### Strict JSON (rejet champs inconnus) — POST et PUT

Activé au **niveau opération** (pas global) via `denormalizationContext` :

```php
denormalizationContext: [
    AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
    DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
],
```

- **`ALLOW_EXTRA_ATTRIBUTES => false`** — toute clé non déclarée dans `CreateToolInput` fait throw `ExtraAttributesException` → 400 `{<field>: "Unknown field"}`.
- **`COLLECT_DENORMALIZATION_ERRORS => true`** — agrège toutes les erreurs de type/dénormalisation dans une seule `PartialDenormalizationException` au lieu de throw à la première → le client voit **toutes** les erreurs d'un coup. Combiné avec la validation Asserts (qui cumule déjà via `ConstraintViolationList`), le comportement est cohérent : **un seul 400 avec toutes les violations**, peu importe leur nature.

Cas couverts par `ApiExceptionSubscriber::extractDeserializationDetails()` :

| Exception | HTTP | Shape |
|---|---|---|
| `NotEncodableValueException` | 400 | `{body: "Malformed JSON body"}` |
| `ExtraAttributesException` | 400 | `{<field>: "Unknown field"}` |
| `MissingConstructorArgumentsException` | 400 | `{<field>: "This field is required"}` |
| `PartialDenormalizationException` | 400 | `{<field>: "Invalid value"}` (cumulé pour tous les champs au type/enum invalide — message générique pour ne pas leaker les noms de classe internes, cf. section "Anti-leak" plus haut) |

### Shape de la réponse 201

DTO dédié `ToolWriteOutput` (partagé avec PUT) qui colle exactement au spec (12 champs) — ni `total_monthly_cost` ni `usage_metrics` (qui seraient triviaux à 0 juste après création). Pas de réutilisation de `ToolDetailOutput` pour ne pas renvoyer de champs absents du contrat.

```json
{
  "id": 25, "name": "Linear", "description": "...", "vendor": "Linear",
  "website_url": "https://linear.app", "category": "Development",
  "monthly_cost": 8.00, "owner_department": "Engineering",
  "status": "active", "active_users_count": 0,
  "created_at": "...", "updated_at": "..."
}
```

Le `status` par défaut (`active`) vient du default property de l'entité (`Tool::$status = ToolStatus::Active`), pas du processor — cohérent avec le pattern "entity owns its invariants".

## `PUT /api/tools/{id}` — mise à jour partielle

### Flux

```
┌─────────────────────────────────────────────┐
│ Route AP (read: false)                      │ ← important : AP ne fait PAS de read automatique
│ Serializer (strict JSON, collect errors)    │   → UpdateToolInput (tous champs optional)
└─────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────┐
│ ValidatorInterface (auto AP)                │ ← Asserts sur les champs fournis uniquement
│                                             │   (Symfony skip les Asserts sur `null`)
└─────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────┐
│ ToolUpdateProcessor                         │
├─────────────────────────────────────────────┤
│ 1. ToolIdFactory->create($uriVariables)     │ ← 400 si id non-entier
│ 2. ToolRepository->find(id)                 │ ← 404 ToolNotFoundException si null
│ 3. assertNameAvailable($data, $tool)        │ ← check unicité si name change, exclu self
│ 4. applyChanges($data, $tool)               │ ← setters sur les champs non-null uniquement
│ 5. em->flush() (pas de persist — déjà géré) │ ← déclenche PreUpdate → updated_at auto
│ 6. ToolMapper->toWriteOutput($tool)         │
└─────────────────────────────────────────────┘
```

### Pourquoi `read: false` ?

Par défaut AP déclenche un "read" (via un provider par défaut) avant l'étape write, pour fournir l'entité existante au processor. Avec `input: UpdateToolInput::class`, ce read n'a pas de sens — le processor reçoit un DTO neuf, pas l'entité. Sans `read: false`, le pipeline coinçait en 404 au niveau du routage. On désactive et on load la Tool nous-mêmes dans le processor (via `ToolIdFactory` + `ToolRepository`, cohérent avec `ToolItemProvider`).

### Unicité du `name` — exclusion de soi-même

Le `UniqueToolName` utilisé sur `CreateToolInput` ne convient pas pour PUT : si le client envoie `name: "Confluence"` sur tool 5 (dont le nom actuel EST "Confluence"), le validator rejetterait à tort. Solution : check dédié dans le processor (`assertNameAvailable`) après avoir chargé l'outil courant — si le nom change **ET** qu'un autre tool porte déjà ce nom, throw `ValidationFailedException` via `ViolationFactory::nameAlreadyExists()`. Passe par le flow 400 unifié (même shape que les autres violations).

### `UpdateToolInput` vs `CreateToolInput`

| | `CreateToolInput` | `UpdateToolInput` |
|---|---|---|
| Champs | 5 obligatoires + 2 optionnels | **Tous optionnels** (valeurs par défaut = `null`) |
| NotBlank | oui | non — on tolère l'absence |
| `UniqueToolName` | sur `name` | non (check processor pour exclure self) |
| Asserts (Length, Url, Regex, etc.) | appliqués systématiquement | **appliqués uniquement si valeur fournie** (Symfony skip sur null) |

### Sémantique "champ non fourni"

Si un champ est **absent** du JSON ou envoyé à `null`, il reste **inchangé**. Le DTO ne distingue pas les deux cas (sur `?string $description = null`, les deux arrivent comme `null` côté PHP).

**Limitation actuelle** : le client ne peut pas **unset** explicitement un champ nullable (`description`, `vendor`, `website_url`) via PUT. S'il envoie `"description": null`, on interprète ça comme "ne touche pas". Pour unset, il faudrait soit :
- Accéder au raw JSON pour détecter `array_key_exists` vs valeur à null
- Introduire un wrapper avec un état "présent avec valeur null"

Non implémenté car pas demandé par le spec. **Feature à envisager si un besoin métier apparaît** (ex: vider une description suite à retrait d'un contrat).

### Réutilisation de `ToolWriteOutput`

Même DTO que pour POST (shape identique, 12 champs). Ancien nom `ToolCreatedOutput` renommé en `ToolWriteOutput` pour refléter l'usage partagé (POST et PUT). Le mapper expose `toWriteOutput()` en lieu et place de `toCreatedOutput()`.

### `updated_at` auto

Géré par le lifecycle callback `#[ORM\PreUpdate]` de l'entité `Tool` — il se déclenche automatiquement sur `em->flush()` dès qu'un champ change. Si le PUT n'applique **aucun** changement effectif (body vide, ou valeurs identiques), PreUpdate ne fire pas → `updated_at` reste identique. Comportement Doctrine standard, acceptable.

## Analytics — `GET /api/analytics/department-costs`

Premier endpoint de la couche Analytics (Part 2). Agrège les coûts par département sur les outils `status = 'active'` uniquement (règle globale du spec pour tous les endpoints analytics).

### Flux

```
┌─────────────────────────────────────────────────────┐
│ DepartmentCostsQueryFactory->create()               │ ← parse sort_by / order depuis Request
│                                                     │   (enums validés par AP en amont via
│                                                     │    EnumQueryParameter schema)
└─────────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────┐
│ DepartmentCostCollectionProvider                    │
├─────────────────────────────────────────────────────┤
│ 1. ValidatorInterface->validate($query)             │ ← ceinture + bretelles (défauts appliqués)
│ 2. DepartmentCostRepository->aggregate($query)      │ ← SQL natif GROUP BY owner_department
│ 3. DepartmentCostMapper->toCollection($rows)        │ ← calcul cost_percentage + summary
└─────────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────┐
│ DepartmentCostCollectionOutput                      │ → JSON { data, summary, message? }
└─────────────────────────────────────────────────────┘
```

### SQL (DBAL natif)

```sql
SELECT
    t.owner_department AS department,
    COUNT(t.id) AS tools_count,
    SUM(t.active_users_count) AS total_users,
    SUM(t.monthly_cost) AS total_cost,
    SUM(t.monthly_cost) / COUNT(t.id) AS average_cost_per_tool
FROM tools t
WHERE t.status = 'active'
GROUP BY t.owner_department
ORDER BY {column} {direction}
```

`cost_percentage` et `most_expensive_department` sont calculés côté PHP (le premier nécessite le total compagnie global, le second fait un tie-break alphabétique après max).

### Tri supporté (`?sort_by=...&order=...`)

| Valeur `sort_by` | Colonne SQL | Défaut |
|---|---|---|
| `total_cost` | `total_cost` | ✅ (+ `order=desc`) |
| `tools_count` | `tools_count` | |
| `total_users` | `total_users` | |
| `average_cost_per_tool` | `average_cost_per_tool` | |
| `department` | `CAST(department AS CHAR)` — voir note | |

**Note sur `department`** : la colonne `owner_department` est un ENUM MySQL — un `ORDER BY` natif trie par **ordre de déclaration** des cases (pas alphabétique). Le `CAST(... AS CHAR)` force un tri alphabétique stable côté client.

### Règles métier (clarifications spec)

- **`cost_percentage`** = `(département.total_cost / company.total_cost) * 100`, arrondi à 1 décimale. La somme sur tous les départements vaut 100% (tolérance ±0.1%).
- **`average_cost_per_tool`** = `total_cost / tools_count`, arrondi à 2 décimales.
- **`most_expensive_department`** = département avec le plus haut `total_cost`. Tie-break alphabétique ASC si égalité.
- **Départements sans outils actifs** : naturellement exclus (le `GROUP BY` ne les fait pas apparaître). `departments_count` reflète donc le nombre de départements ayant au moins 1 outil actif.

### Shape contextuelle (empty DB)

Même logique que `GET /api/tools` :

| Scénario | Shape |
|---|---|
| DB peuplée | `{ data: [...], summary: { total_company_cost, departments_count, most_expensive_department } }` |
| Aucun outil actif | `{ data: [], summary: { total_company_cost: 0 }, message: "No analytics data available - ..." }` |

`departments_count` et `most_expensive_department` sont omis via `skip_null_values` quand il n'y a aucune donnée (nullable dans `DepartmentCostSummary`).

### Helpers shared analytics (`src/Helper/`)

- **`NumberFormatter::money(float): float`** — arrondi 2 décimales pour les montants
- **`NumberFormatter::percent(float): float`** — arrondi 1 décimale pour les pourcentages
- **`ScalarCast::toInt/toFloat/toString(mixed): scalar`** — narrowing des valeurs `mixed` retournées par DBAL (MySQL renvoie les agrégats DECIMAL comme string via PDO)

Réutilisables pour les 4 endpoints analytics à venir (`expensive-tools`, `tools-by-category`, `low-usage-tools`, `vendor-summary`).

### Prise en compte par l'infra existante

- **`ApiExceptionSubscriber::HANDLED_RESOURCE_URIS`** — élargi avec `/analytics` (préfixe) pour couvrir les 5 endpoints analytics d'un seul tenant
- **`OpenApiFactory`** — nouveau branche `isDepartmentCostsPath()` avec 2 exemples 200 (`with_data` + `empty_db`)
- **`ApiMessage::NO_ANALYTICS_DATA`** — constante dédiée pour le message "empty DB"

## Analytics — `GET /api/analytics/expensive-tools`

Top outils coûteux avec rating d'efficacité basé sur le ratio `cost_per_user` vs moyenne compagnie. Règle globale : `status = 'active'` uniquement.

### Query params

| Param | Type | Défaut | Contrainte |
|---|---|---|---|
| `limit` | int | `10` | 1 ≤ limit ≤ 100 |
| `min_cost` | float | — | ≥ 0 |

### Flux

```
┌──────────────────────────────────────────────────────┐
│ ExpensiveToolsQueryFactory->create()                 │ ← parse limit/min_cost, cumule violations
└──────────────────────────────────────────────────────┘
              ↓
┌──────────────────────────────────────────────────────┐
│ ExpensiveToolCollectionProvider                      │
├──────────────────────────────────────────────────────┤
│ 1. ValidatorInterface->validate($query)              │
│ 2. repository->findAllFiltered($query)               │ ← tous les outils filtrés (pas limité)
│ 3. repository->computeCompanyAverageCostPerUser()    │ ← SUM(cost)/SUM(users) sur actifs users>0
│ 4. ExpensiveToolMapper->toCollection($rows, $avg, $q)│ ← classifie + limite + agrège savings
└──────────────────────────────────────────────────────┘
```

### Pourquoi "fetch all filtered" puis limiter en PHP ?

`potential_savings_identified` = somme des coûts des outils `efficiency_rating = "low"` sur **toute** la pool filtrée (pas juste le top N affiché). Si on limitait en SQL, on ne pourrait pas calculer cette somme correctement (on n'aurait pas accès aux outils low hors des top N).

Approche : le repository ramène TOUS les outils filtrés triés desc, le mapper calcule les ratings sur tous, somme les "low" pour `potential_savings`, puis slice le top N pour `data`. Pour la taille du dataset (quelques dizaines d'outils), le coût du fetch-all est négligeable.

### Règles métier (clarifications spec)

#### `cost_per_user`

- Formule : `monthly_cost / active_users_count`
- Outils à **0 user** : `cost_per_user = null`. Via `skip_null_values` global (cohérent avec le reste de l'API), le champ est **omis** du JSON. Le client déduit "non calculable" de `active_users_count: 0`.

#### `avg_cost_per_user_company`

- Formule : `SUM(monthly_cost) / SUM(active_users_count)` sur **tous** les outils actifs
- **Outils à 0 users exclus du calcul** (ils fausseraient le dénominateur)
- C'est une **baseline globale fixe** : indépendante du filtre `min_cost`. Les ratings sont toujours comparés à cette moyenne compagnie, pas à une moyenne du sous-ensemble filtré.
- Cas limite : si AUCUN outil actif n'a de users, `avg = 0` (graceful fallback).

#### `efficiency_rating` (enum `EfficiencyRating`)

Basé sur le ratio `cost_per_user / avg_cost_per_user_company` :

| Ratio | Rating |
|---|---|
| < 0.5 | `excellent` |
| 0.5 ≤ ratio < 0.8 | `good` |
| 0.8 ≤ ratio ≤ 1.2 | `average` |
| > 1.2 | `low` |
| `cost_per_user = null` (0 users) | `low` **forcé** |

Centralisé dans `src/Helper/EfficiencyClassifier::classify()`. Réutilisable pour d'autres endpoints analytics.

#### `potential_savings_identified`

- Somme des `monthly_cost` des outils avec `efficiency_rating = "low"` dans la pool filtrée
- Sur 0 outils "low" → `0`
- Arrondi 2 décimales

#### `total_tools_analyzed`

- Count des outils qui matchent le filtre (status=active + min_cost si fourni)
- **Indépendant de `limit`** — représente la taille de la pool analysée, pas ce qui est retourné

### Shape contextuelle

| Scénario | Shape |
|---|---|
| Données présentes | `{ data: [...], analysis: {...} }` |
| Filtre `min_cost` sans résultat | `{ data: [], analysis: { total_tools_analyzed: 0, avg_cost_per_user_company, potential_savings_identified: 0 }, message: "No tools match the applied filters" }` |
| Aucun outil actif en DB | `{ data: [], analysis: { total_tools_analyzed: 0, avg_cost_per_user_company: 0, potential_savings_identified: 0 }, message: "No analytics data available - ..." }` |

Les 2 messages distincts (réutilisés depuis `ApiMessage`) permettent au client de différencier "filtre trop restrictif" (résultat normal) vs "aucune donnée dispo" (problème source).

## Analytics — `GET /api/analytics/tools-by-category`

Répartition des outils par catégorie. Aucun query param — l'endpoint retourne toujours l'agrégation complète (triée par coût décroissant).

### Flux

```
┌─────────────────────────────────────────────────────┐
│ ToolsByCategoryCollectionProvider                   │ ← provider minimal (pas de DTO query,
│                                                     │   pas de validator call)
│  repository->aggregate() → mapper->toCollection()   │
└─────────────────────────────────────────────────────┘
```

Pas de `QueryDto`/`QueryFactory` puisque pas de params à parser — le provider appelle directement le repository. Cohérent avec le principe "pas de ceremonial pour rien" qui guide le reste du code.

### SQL

```sql
SELECT
    c.name AS category_name,
    COUNT(t.id) AS tools_count,
    SUM(t.monthly_cost) AS total_cost,
    SUM(t.active_users_count) AS total_users,
    CASE
        WHEN SUM(t.active_users_count) > 0
        THEN SUM(t.monthly_cost) / SUM(t.active_users_count)
        ELSE NULL
    END AS average_cost_per_user
FROM categories c
INNER JOIN tools t ON t.category_id = c.id
WHERE t.status = 'active'
GROUP BY c.id, c.name
ORDER BY total_cost DESC
```

- **INNER JOIN** : les catégories sans outil actif sont naturellement exclues. Cohérent avec `department-costs` (un département sans outil → absent).
- **`CASE WHEN` sur `average_cost_per_user`** : gère la division par zéro au niveau SQL. Si toute la catégorie a 0 user, l'agrégat est `NULL` → le mapper omet le champ et la catégorie est excluse du calcul `most_efficient_category`.

### Règles métier (clarifications spec)

| Champ | Règle |
|---|---|
| `total_users` | Somme des `active_users_count`, **pas de déduplication** (un user peut utiliser plusieurs outils → il compte plusieurs fois) |
| `percentage_of_budget` | `(catégorie.total_cost / company.total_cost) × 100`, somme = 100% (tolérance ±0.1%) |
| `average_cost_per_user` | `SUM(cost) / SUM(users)` par catégorie ; **omis** si 0 users (div/0 via `skip_null_values`) |
| `most_expensive_category` | Plus haut `total_cost`. Tie-break alphabétique ASC. |
| `most_efficient_category` | Plus bas `average_cost_per_user` **non-null** (catégories sans users exclues). Tie-break alphabétique ASC. `null` si aucune catégorie n'a de users. |

### Shape contextuelle

| Scénario | Shape |
|---|---|
| Données présentes | `{ data: [...], insights: { most_expensive_category, most_efficient_category } }` |
| Aucun outil actif | `{ data: [], insights: {}, message: "No analytics data available - ..." }` |

Les deux champs d'`insights` sont nullable dans `ToolsByCategoryInsights` → omis via `skip_null_values` si pas de données.

## Analytics — `GET /api/analytics/low-usage-tools`

Outils actifs sous-utilisés. Le CFO/IT director veut identifier des candidats à la résiliation ou au downgrade — l'endpoint attribue à chaque outil un `warning_level` basé sur son coût-par-user, et une `potential_action` prête à afficher.

### Query params

| Param | Type | Défaut | Contrainte |
|---|---|---|---|
| `max_users` | int | `5` | ≥ 0 |

`max_users=0` est valide (filtrer les outils orphelins uniquement). D'où la nouvelle `NonNegativeIntegerQueryParameter` (minimum: 0) — la `PositiveIntegerQueryParameter` existante n'accepte que ≥ 1.

### Flux

```
┌─────────────────────────────────────────────────────┐
│ LowUsageToolsQueryFactory->create()                 │ ← parse max_users (entier, cumule violations)
└─────────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────┐
│ LowUsageToolCollectionProvider                      │
├─────────────────────────────────────────────────────┤
│ 1. ValidatorInterface->validate($query)             │
│ 2. repository->findUnderutilized($query)            │ ← WHERE active_users_count <= :max_users
│ 3. LowUsageToolMapper->toCollection($rows)          │ ← classifie + agrège savings
└─────────────────────────────────────────────────────┘
```

### SQL

```sql
SELECT t.id, t.name, t.monthly_cost, t.active_users_count,
       t.vendor, t.owner_department AS department
FROM tools t
WHERE t.status = 'active'
  AND t.active_users_count <= :max_users
ORDER BY t.active_users_count ASC, t.monthly_cost DESC
```

Les outils à 0 users sont **toujours inclus** (0 ≤ n'importe quel seuil ≥ 0). Tri : moins utilisé d'abord, puis le plus coûteux en tête à égalité de users (priorité d'action pour le CFO).

### Règles métier (clarifications spec)

#### `warning_level` (enum `WarningLevel`)

Basé sur `cost_per_user` (centralisé dans `src/Helper/WarningLevelClassifier`) :

| `cost_per_user` | Level |
|---|---|
| < 20€ | `low` |
| 20€ ≤ cpu ≤ 50€ | `medium` |
| > 50€ | `high` |
| Non calculable (0 users) | `high` **forcé** |

#### `potential_action`

Dérivé du `warning_level` via `WarningLevel::recommendedAction()` (méthode sur l'enum) :

| Level | Action |
|---|---|
| `high` | `"Consider canceling or downgrading"` |
| `medium` | `"Review usage and consider optimization"` |
| `low` | `"Monitor usage trends"` |

#### `potential_monthly_savings`

Somme des `monthly_cost` des outils `high` **+** `medium` uniquement (les `low` sont surveillés mais pas candidats à suppression). `potential_annual_savings = potential_monthly_savings × 12`. Arrondis 2 décimales.

#### `total_underutilized_tools`

Count des outils retournés (le filtre `max_users` est appliqué en SQL → tous les rows qualifient).

### Shape contextuelle

| Scénario | Shape |
|---|---|
| Données présentes | `{ data: [...], savings_analysis: { total, monthly, annual } }` |
| Aucun outil actif | `{ data: [], savings_analysis: { 0, 0, 0 }, message: "No analytics data..." }` |

## Analytics — `GET /api/analytics/vendor-summary`

Analyse par fournisseur : consolide les coûts, users, départements et efficience par vendor. Use case : identifier les vendors coûteux à négocier, les mono-outils à consolider, et les plus efficients à privilégier.

### SQL

```sql
SELECT
    t.vendor AS vendor,
    COUNT(t.id) AS tools_count,
    SUM(t.monthly_cost) AS total_monthly_cost,
    SUM(t.active_users_count) AS total_users,
    GROUP_CONCAT(DISTINCT t.owner_department ORDER BY t.owner_department ASC SEPARATOR ',') AS departments,
    CASE WHEN SUM(t.active_users_count) > 0
         THEN SUM(t.monthly_cost) / SUM(t.active_users_count)
         ELSE NULL END AS average_cost_per_user
FROM tools t
WHERE t.status = 'active' AND t.vendor IS NOT NULL
GROUP BY t.vendor
ORDER BY total_monthly_cost DESC
```

Points notables :
- **`GROUP_CONCAT(DISTINCT ... ORDER BY ... ASC SEPARATOR ',')`** : concat des départements uniques, tri alphabétique, séparés par virgule — exactement ce que demande le spec. `DISTINCT` dédoublonne, `ORDER BY` garantit la déterminisme.
- **`t.vendor IS NOT NULL`** : défensif (le spec dit vendor obligatoire pour les POST, mais la colonne DB est nullable — des données legacy peuvent exister).
- **`CASE WHEN`** sur `average_cost_per_user` : évite la division par zéro au niveau SQL si tous les outils du vendor ont 0 users.

### Règles métier (clarifications spec)

#### `vendor_efficiency` (enum `VendorEfficiency`)

Seuils **absolus** en € par user (différents de `EfficiencyClassifier` qui travaille en ratio vs moyenne) :

| `average_cost_per_user` | Efficiency |
|---|---|
| < 5€ | `excellent` |
| 5-15€ | `good` |
| 15-25€ | `average` |
| > 25€ | `poor` |
| non calculable (0 user total) | `poor` **forcé** |

Labels distincts du EfficiencyClassifier (`excellent/good/average/**poor**` vs `excellent/good/average/**low**`) — je garde la terminologie exacte du spec pour chaque endpoint.

#### `single_tool_vendors`

Count des vendors qui ont **exactement 1** outil actif. Calculé en PHP pendant l'itération sur les rows du mapper (incrément quand `tools_count === 1`).

#### `most_expensive_vendor`

Plus haut `total_monthly_cost`. Tie-break alphabétique ASC.

#### `most_efficient_vendor`

Plus bas `average_cost_per_user` **non-null**. Tie-break alphabétique ASC. `null` si aucun vendor n'a de users (edge case).

### Shape contextuelle

| Scénario | Shape |
|---|---|
| Données présentes | `{ data: [...], vendor_insights: { single_tool_vendors, most_expensive_vendor, most_efficient_vendor } }` |
| Aucun outil actif | `{ data: [], vendor_insights: { single_tool_vendors: 0 }, message: "No analytics data..." }` |

`most_expensive_vendor` et `most_efficient_vendor` sont nullable dans `VendorSummaryInsights` → omis via `skip_null_values` quand il n'y a pas de données. `single_tool_vendors` est non-null avec défaut 0 — toujours présent.

## Documentation Swagger enrichie (`src/OpenApi/`)

Un `OpenApiFactory` décore le factory par défaut d'API Platform pour enrichir la doc `/api/docs` :

- **Exemples multi-scénarios sur les 200** : chaque endpoint GET a plusieurs `examples` nommés pour illustrer les différents formats de réponse (liste complète, avec filtres, pagination, sans résultat, etc.)
- **Exemples des erreurs** : 400 / 404 / 500 sont systématiquement documentés avec un JSON d'exemple conforme à notre format

Les exemples vivent dans des classes dédiées (`src/OpenApi/Example/`) pour séparer la logique de factory de la data :
- `ErrorResponseExample` — 400, 404, 500 reference payloads
- `ToolCollectionExample` — 6 cas de réponse pour `GET /api/tools`
- `ToolDetailExample` — 2 exemples `GET /api/tools/{id}` (détail complet + outil peu utilisé)
- `CreateToolExample` — body d'entrée, 201, 400 (field errors + unknown fields)
- `UpdateToolExample` — body partiel, 200, 400 (field errors + id invalide + unknown fields)
- `Analytics\DepartmentCostExample` — GET analytics department-costs, 200 (données présentes + empty DB)
- `Analytics\ExpensiveToolExample` — GET analytics expensive-tools, 200 (with_data + no_match + empty_db) + 400
- `Analytics\ToolsByCategoryExample` — GET analytics tools-by-category, 200 (with_data + empty_db)
- `Analytics\LowUsageToolExample` — GET analytics low-usage-tools, 200 (with_data + empty_db) + 400
- `Analytics\VendorSummaryExample` — GET analytics vendor-summary, 200 (with_data + empty_db)

Le dev qui ouvre Swagger UI peut **choisir dans un dropdown** quel exemple afficher pour chaque endpoint.

### Regroupement par tag OpenAPI

Par défaut API Platform crée un tag par `shortName` → Swagger UI affiche 7 sections (Tool + 1 par ressource analytics) dispersées. `OpenApiFactory` écrase les tags des 5 opérations `/analytics/*` vers un tag unique **`Analytics`** (avec une description métier), et filtre les tags top-level pour ne conserver que ceux effectivement référencés par au moins une opération.

Résultat côté Swagger UI : **2 sections uniquement**, `Tool` et `Analytics` (dépliable, description métier visible). Les 5 endpoints analytiques apparaissent sous la section `Analytics`.

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
│   │   ├── Analytics/                # Config AP pour les endpoints analytics (DepartmentCostAnalyticsResource, ...)
│   │   ├── QueryParameter/           # Classes QueryParameter réutilisables (Enum, PositiveNumber, PositiveInteger, NonNegativeInteger, String)
│   │   └── Tool/ToolResource.php     # Config AP pour Tool (attribut #[ApiResource])
│   ├── Dto/
│   │   ├── Analytics/                # DTOs analytics par endpoint (DepartmentCost/{Query,Output}/, ...)
│   │   └── Tool/
│   │       ├── Input/                # DTOs body POST/PUT (CreateToolInput, UpdateToolInput — Asserts + contraintes custom DB)
│   │       ├── Output/               # DTOs responses (ToolCollectionOutput, ToolOutput, ToolDetailOutput, ToolWriteOutput, Usage*)
│   │       └── Query/                # DTOs query params (ListToolsQuery — Asserts + Callback + helpers pagination/sort)
│   ├── Entity/                       # Entités Doctrine (Tool, Category) avec TABLE_NAME en const
│   ├── Enum/                         # Enums typés (Department, ToolStatus, CategoryName, SortBy, SortOrder, DepartmentCostSortBy, EfficiencyRating, WarningLevel, VendorEfficiency)
│   ├── EventSubscriber/              # ApiExceptionSubscriber pour normaliser les erreurs
│   ├── Exception/
│   │   ├── Domain/                   # Invariants métier violés (InvalidToolStateException, InvalidNumericValueException, InvalidIntegerValueException)
│   │   └── Http/                     # Exceptions mappées à des codes HTTP (ToolNotFoundException)
│   ├── Factory/
│   │   ├── Analytics/                # Factories query params pour analytics (DepartmentCostsQueryFactory, ...)
│   │   └── Tool/                     # ListToolsQueryFactory (Request → DTO), ToolIdFactory (uriVariables → int validé)
│   ├── Helper/                       # Helpers partagés (NumberFormatter, ScalarCast, EfficiencyClassifier, WarningLevelClassifier, VendorEfficiencyClassifier — utilisés par la couche Analytics)
│   ├── Http/
│   │   ├── ApiMessage.php            # Messages paramétrés (noResourceAvailable, noMatch, pageOutOfRange)
│   │   └── ApiResponse.php           # Factory pour JsonResponse d'erreur (notFound, validationFailed, internalError, ...)
│   ├── Mapper/
│   │   ├── Analytics/                # Mappers row SQL → DTOs output analytics (DepartmentCostMapper, ...)
│   │   └── ToolMapper.php            # Transform Tool → DTOs output
│   ├── OpenApi/
│   │   ├── Example/                  # Constantes d'exemples JSON (ErrorResponse, ToolCollection, ToolDetail)
│   │   └── OpenApiFactory.php        # Decorator enrichissant Swagger (exemples 200 + réponses 400/404/500)
│   ├── Repository/                   # ToolRepository (ORM), UsageLogRepository (DBAL), Analytics/ (DBAL natifs par endpoint)
│   ├── State/
│   │   ├── Processor/                # ToolPersistProcessor (POST), ToolUpdateProcessor (PUT)
│   │   └── Provider/                 # ToolCollectionProvider, ToolItemProvider, Analytics/ (providers par endpoint)
│   ├── Validator/
│   │   ├── Constraint/               # Contraintes custom (UniqueToolName, ExistingCategory) + validators
│   │   ├── Message/ValidationMessage.php  # Messages d'erreur génériques
│   │   └── ViolationFactory.php           # Factory pour ConstraintViolation
│   └── ValueObject/Number/           # NullableFloat, NullableInt — parsing typé safe
└── var/                              # Cache & logs (ignorés par git)
```

## Pistes d'amélioration — à reprendre en V2

Liste des points que je n'ai pas eu le temps de traiter dans la fenêtre du test mais que j'aurais attaqués en priorité dans une V2. Classés par impact.

### Qualité & couverture

- **Tests automatisés (PHPUnit)** — aucun test n'est livré faute de temps. Priorité à mettre en place :
  - **Tests fonctionnels** sur chaque route (`ApiTestCase` d'API Platform) pour valider contractuellement le shape JSON, les codes HTTP, les messages d'erreur et les filtres/pagination/tri
  - **Tests unitaires** sur la logique métier isolée : `EfficiencyClassifier`, `WarningLevelClassifier`, `VendorEfficiencyClassifier`, `NumberFormatter`, les mappers analytics (calculs cost_percentage, tie-breaks, savings)
  - **Tests d'intégration DB** sur les repositories DBAL natifs (les SQL agrégés avec `GROUP BY`, `CASE WHEN`, `GROUP_CONCAT` sont la zone la plus fragile — un fixtures set dédié permettrait de garantir la non-régression des calculs)

### Observabilité

- **Logs structurés sur les erreurs** — actuellement `ApiExceptionSubscriber` convertit les exceptions en réponses JSON mais ne log **rien**. Tout ce qui tombe dans le `500 fallback` (exception inattendue) disparaît silencieusement en prod. À ajouter :
  - Injecter `Psr\Log\LoggerInterface` dans le subscriber
  - Log `warning` sur les 400/404 (utile pour détecter des abus ou bugs clients)
  - Log `error` avec stacktrace sur les 500 (avant de renvoyer le message générique)
  - Mapping des exceptions → niveaux de log cohérents

### Sécurité

- **Restriction des routes exposées** — API Platform peut auto-générer des endpoints par défaut au-delà de ceux explicitement documentés (ex: `/api/contexts/*`, `/api/docs.jsonld`, `/api/errors/*`). À verrouiller :
  - Auditer toutes les routes réellement exposées via `bin/console debug:router` et désactiver celles qui ne font pas partie du spec
  - Désactiver complètement Swagger UI en prod (ou le mettre derrière une auth) — `api_platform.enable_swagger_ui: false` en `prod`
  - Désactiver les formats non nécessaires (JSON-LD, HAL, etc.) déjà fait via `formats: [JsonEncoder::FORMAT]` sur les `ApiResource`, à vérifier au niveau global
  - Mettre en place une auth (JWT ou Symfony Security) — aucun endpoint n'est sécurisé actuellement

### Outillage pour le reviewer

- **Collection Postman / Bruno / Hoppscotch** — fournir un fichier `collection.json` avec tous les endpoints, cas nominal + cas d'erreur, variables d'environnement pré-remplies. Le reviewer teste en un clic sans avoir à rédiger les requêtes. À committer à côté de `docker/` dans un dossier `docs/` ou `collections/`.

### Architecture & organisation

- **Refactor `src/OpenApi/OpenApiFactory.php`** — le fichier a grossi (~290 lignes) avec des `match` branchant sur `isDepartmentCostsPath`, `isExpensiveToolsPath`, etc. À splitter :
  - Un `OpenApiEnricherInterface` avec une méthode `supports(string $path, string $method): bool` et `enrich(OpenApiOperation): OpenApiOperation`
  - Une implémentation par endpoint (`DepartmentCostEnricher`, `ExpensiveToolEnricher`, etc.)
  - Le factory principal itère sur la liste des enrichers (injectés via un tagged service)
  - Ajouter un nouvel endpoint = ajouter une classe, sans toucher au factory

- **Refactor `src/EventSubscriber/ApiExceptionSubscriber.php`** — monolithique lui aussi (~200 lignes) avec plusieurs `if ($exception instanceof X)` en cascade. À splitter :
  - Un `ExceptionHandlerInterface` avec `supports(Throwable): bool` et `handle(Throwable): JsonResponse`
  - Une implémentation par type d'exception (`ValidationFailedHandler`, `DeserializationHandler`, `ToolNotFoundHandler`, `DbalExceptionHandler`, `GenericErrorHandler`)
  - Subscriber réduit au dispatch : trouve le handler qui `supports()`, délègue
  - Ajouter un nouveau type d'erreur = ajouter un handler, sans toucher au subscriber

- **Sous-dossiers plus granulaires** — actuellement certains dossiers groupent par ressource (`Dto/Tool/`, `Dto/Analytics/<endpoint>/`) mais pas partout. À uniformiser si d'autres ressources s'ajoutent — par ex. `src/Repository/Tool/` dédié, cohérent avec le pattern analytics.

- **Déduplication des exemples Swagger** — les valeurs comme `"Confluence"`, `"Engineering"`, `"2025-05-01"` sont hardcodées dans plusieurs fichiers de `src/OpenApi/Example/` (ex: `ToolDetailExample::FOUND` et `UpdateToolExample::UPDATED` partagent des champs). Une refacto propre :
  - Créer un `ExampleFixtures` avec des constantes partagées (`CONFLUENCE_ID = 5`, `CONFLUENCE_NAME = 'Confluence'`, `CONFLUENCE_VENDOR = 'Atlassian'`, etc.)
  - Les `Example` composent leur payload à partir de ces constantes
  - Bénéfice : quand on corrige une donnée (ex: renommer `Confluence` en autre chose), on touche 1 seul endroit — plus de dérive entre exemples

### Couche domaine / métier

- **Tests d'integration avec données fixtures dédiées** — les seed `init.sql` datent de 2025 (usage_logs en particulier) alors qu'on est en 2026, donc les métriques `usage_metrics.last_30_days` retournent systématiquement 0 en l'état. Une fixture "récente" permettrait de valider le comportement réel de l'agrégat. Contournement actuel : SQL vérifiée manuellement sur fenêtre élargie (documenté section `GET /api/tools/{id}`).
