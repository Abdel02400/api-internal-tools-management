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
| **400** | `{ error: "Validation failed", details: { <field>: "..." } }` | `PartialDenormalizationException` (types/enums invalides, cumulés) |
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
│ 5. ToolMapper->toCreatedOutput($tool)       │ → ToolCreatedOutput (12 champs du spec)
└─────────────────────────────────────────────┘
```

### Validations (CreateToolInput)

| Champ | Contraintes |
|---|---|
| `name` | `NotBlank`, `Length(2-100)`, **`UniqueToolName`** (contrainte custom DB) |
| `category_id` | `Positive`, **`ExistingCategory`** (contrainte custom DB) |
| `monthly_cost` | `GreaterThanOrEqual(0)`, `Regex /^\d+(\.\d{1,2})?$/` (max 2 décimales) |
| `owner_department` | Typé `Department` (enum — invalide → 400 via `PartialDenormalizationException`) |
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
| `PartialDenormalizationException` | 400 | `{<field>: "...type error..."}` (agrège les erreurs de types invalides, enums non-matchés, etc.) |

### Shape de la réponse 201

Nouveau DTO dédié `ToolCreatedOutput` qui colle exactement au spec (12 champs) — ni `total_monthly_cost` ni `usage_metrics` (qui seraient triviaux à 0 juste après création). Pas de réutilisation de `ToolDetailOutput` pour ne pas renvoyer de champs absents du contrat.

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

## Documentation Swagger enrichie (`src/OpenApi/`)

Un `OpenApiFactory` décore le factory par défaut d'API Platform pour enrichir la doc `/api/docs` :

- **Exemples multi-scénarios sur les 200** : chaque endpoint GET a plusieurs `examples` nommés pour illustrer les différents formats de réponse (liste complète, avec filtres, pagination, sans résultat, etc.)
- **Exemples des erreurs** : 400 / 404 / 500 sont systématiquement documentés avec un JSON d'exemple conforme à notre format

Les exemples vivent dans des classes dédiées (`src/OpenApi/Example/`) pour séparer la logique de factory de la data :
- `ErrorResponseExample` — 400, 404, 500 reference payloads
- `ToolCollectionExample` — 6 cas de réponse pour `GET /api/tools`
- `ToolDetailExample` — 2 exemples `GET /api/tools/{id}` (détail complet + outil peu utilisé)
- `CreateToolExample` — body d'entrée, 201, 400 (field errors + unknown fields)

Le dev qui ouvre Swagger UI peut **choisir dans un dropdown** quel exemple afficher pour chaque endpoint.

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
│   │   ├── QueryParameter/           # Classes QueryParameter réutilisables (Enum, PositiveNumber, PositiveInteger, String)
│   │   └── Tool/ToolResource.php     # Config AP pour Tool (attribut #[ApiResource])
│   ├── Dto/Tool/
│   │   ├── Input/                    # DTOs body POST/PUT (CreateToolInput — Asserts + contraintes custom DB)
│   │   ├── Output/                   # DTOs responses (ToolCollectionOutput, ToolOutput, ToolDetailOutput, ToolCreatedOutput, Usage*)
│   │   └── Query/                    # DTOs query params (ListToolsQuery — Asserts + Callback + helpers pagination/sort)
│   ├── Entity/                       # Entités Doctrine (Tool, Category) avec TABLE_NAME en const
│   ├── Enum/                         # Enums typés (Department, ToolStatus, CategoryName, SortBy, SortOrder)
│   ├── EventSubscriber/              # ApiExceptionSubscriber pour normaliser les erreurs
│   ├── Exception/
│   │   ├── Domain/                   # Invariants métier violés (InvalidToolStateException, InvalidNumericValueException, InvalidIntegerValueException)
│   │   └── Http/                     # Exceptions mappées à des codes HTTP (ToolNotFoundException)
│   ├── Factory/Tool/                 # ListToolsQueryFactory (Request → DTO), ToolIdFactory (uriVariables → int validé)
│   ├── Http/
│   │   ├── ApiMessage.php            # Messages paramétrés (noResourceAvailable, noMatch, pageOutOfRange)
│   │   └── ApiResponse.php           # Factory pour JsonResponse d'erreur (notFound, validationFailed, internalError, ...)
│   ├── Mapper/ToolMapper.php         # Transform Tool → DTOs output
│   ├── OpenApi/
│   │   ├── Example/                  # Constantes d'exemples JSON (ErrorResponse, ToolCollection, ToolDetail)
│   │   └── OpenApiFactory.php        # Decorator enrichissant Swagger (exemples 200 + réponses 400/404/500)
│   ├── Repository/                   # ToolRepository (ORM), UsageLogRepository (DBAL natif pour `usage_logs` non mappée)
│   ├── State/
│   │   ├── Processor/                # ToolPersistProcessor (POST create)
│   │   └── Provider/                 # ToolCollectionProvider, ToolItemProvider
│   ├── Validator/
│   │   ├── Constraint/               # Contraintes custom (UniqueToolName, ExistingCategory) + validators
│   │   ├── Message/ValidationMessage.php  # Messages d'erreur génériques
│   │   └── ViolationFactory.php           # Factory pour ConstraintViolation
│   └── ValueObject/Number/           # NullableFloat, NullableInt — parsing typé safe
└── var/                              # Cache & logs (ignorés par git)
```
