# Plugin architecture

Runalyze discovers and renders all built-in plugins through separate Symfony registries for panels and statistics. See the [plugin development guide](development.md) for registration and testing examples and the [panel migration record](remaining-panel-migration.md) for the final panel cutovers.

## Goals

- Make plugin dependencies explicit through Symfony dependency injection.
- Keep plugin business logic independently unit-testable.
- Keep account-specific state out of shared service instances.
- Separate plugin metadata, execution, persistence, and presentation.
- Preserve existing installation, activation, ordering, configuration, route, and AJAX behavior during migration.

## Registration and installation

- `PluginDiscovery` returns the sorted keys from the panel and statistics registries.
- `PluginInstallationManager` owns account-scoped installation and activation state.
- `PluginSettingsProvider` provides installed metadata for the dashboard and plugin administration.
- `PluginConfigurationStorageInterface` and its Doctrine implementation persist plugin configuration.

Plugin discovery, installation and display resolve registered presenters by stable key. The old `plugin/` tree, Composer classmap entry and global plugin classes have been retired. Existing installation IDs, keys, states, ordering and saved settings still identify the same registered plugins. Installation rows for unavailable plugins are skipped in the dashboard and settings lists and retained in the database.

## Directory and namespace convention

Modern plugins are self-contained feature modules. Plugin-specific application code, templates, translations, assets, and plugin-local configuration belong to the plugin; shared infrastructure and cross-plugin contracts remain in the application.

Each modern plugin has one PascalCase directory. Panels live in `plugins/Panels/<PluginName>/` with namespace `Runalyze\Plugins\Panels\<PluginName>`. Statistics live in `plugins/Statistics/<PluginName>/` with namespace `Runalyze\Plugins\Statistics\<PluginName>`. Both use the Composer PSR-4 prefix `Runalyze\Plugins\`.

The panel and statistics directories use the same file conventions.

```text
plugins/
├── Panels/
│   └── Sportler/
│       ├── SportlerPanelDataProvider.php
│       ├── SportlerPanelPresenter.php
│       ├── templates/
│       └── config/
│           └── services.php
└── Statistics/
    └── MonthlyStats/
        ├── MonthlyStatisticsPresenter.php
        ├── MonthlyStatisticsData.php
        ├── MonthlyStatisticsMetricSelection.php
        ├── templates/
        └── config/
            └── services.php
```

The directory name and namespace segment are code organization details, not persisted plugin identifiers. The stable plugin key remains explicit metadata and must not be derived from a PHP class name, namespace, or directory name. Renaming or moving implementation files must not change installed plugin records.

### PHP namespaces and class names

- Keep plugin-specific classes in that namespace or meaningful subnamespaces such as `Configuration`, `Presentation`, or `Infrastructure`.
- Use PascalCase for plugin directory names and PHP class names.
- Name a dashboard panel presenter `<Feature>PanelPresenter` and a dashboard statistics presenter `<Feature>StatisticsPresenter`. Use the feature name and name supporting classes for their roles, such as `RouteNetworkPresenter` or `TrainingTimeDistribution`.
- Do not introduce a generic `Plugin` namespace shared by unrelated feature code.
- Do not place plugin-specific providers, presenters, DTOs, or configuration definitions in `Runalyze\Bundle\CoreBundle\`.
- Keep reusable application services, shared contracts, repositories, and cross-plugin orchestration in the CoreBundle.

### Composer and Symfony integration

The Composer mapping uses a single PSR-4 prefix:

```json
{
    "autoload": {
        "psr-4": {
            "Runalyze\\Plugins\\": "plugins/"
        }
    }
}
```

This mapping is active in `composer.json` for application code and plugin tests.

Modern plugin services must be registered explicitly with Symfony dependency injection. A plugin-local `config/services.php` may describe its services, but it must be imported by the application configuration; the directory existing on disk does not make it automatically discoverable. Service registration must not rely on scanning the legacy classmap or on runtime container lookups.

Plugin templates, translations, and assets stay within the plugin directory unless an established Symfony integration requires a deliberate application-level registration step. Their paths and loading mechanisms must be explicit and tested.

### Tests

Plugin-specific tests remain in the central `tests/` tree, but use a plugin-owned test namespace. Composer's development autoload maps `Runalyze\Plugins\` to `tests/Plugins/`; test classes live below a plugin-specific `Tests/` directory:

```text
tests/
└── Plugins/
    └── Panels/
        └── Sportler/
            └── Tests/
                └── SportlerPanelDataProviderTest.php
```

The corresponding namespace is:

```php
namespace Runalyze\Plugins\Panels\Sportler\Tests;
```

Panel tests mirror their source layout under `tests/Plugins/Panels/<PluginName>/Tests/` and statistics tests under `tests/Plugins/Statistics/<PluginName>/Tests/`. Tests for shared application infrastructure stay in the corresponding application test namespace.

### Panel metadata and configuration

`ModernPanelInterface` supplies the stable key, display name, and description for administration as well as the rendering contract. Sports uses the informational configuration window because it has no plugin-specific settings.

TagsSummary is the first legacy-free panel with user configuration. It implements
`ModernPanelConfigurationInterface` in addition to the presenter contract.
The optional configuration contract supplies a plugin-local Twig
template, account-scoped configuration view data, and a validated update
operation. The existing `plugin-config` route handles modern configuration,
activation and AJAX reloads while retaining the legacy URL and form behavior.
The TagsSummary selection handler is shared between dashboard changes and
configuration POSTs; inactive installations are accepted only on the explicit
configuration path. Modern plugins without configuration support continue to
display the no-options message.

Equipment also supports user configuration. It reuses
`ModernPanelConfigurationInterface` and its existing account-scoped selection
handler to save the selected equipment type from the Twig configuration form.
The original equipment-management and table links are part of its modern panel
template and continue to use their existing Symfony routes. Its legacy
characterization tests have been replaced by modern presenter tests for usage
bar clamping and configuration ownership.

Sportler (Body values) is also legacy-free. Its modern provider now owns the three
integer settings (`wunschgewicht`, `plot_points`, `plot_timerange`), validation
and account-scoped persistence. The configuration form uses the existing
`ModernPanelConfigurationInterface`, the stable legacy keys, and the original
submit input/wrapper layout. Both former `Plot.*.php` includes are obsolete
because the Sportler Twig panel already renders its weight, heart-rate and
body-composition plots from account-scoped history queries. Remove those includes
together with the obsolete legacy class, not independently while it is still used.

Prognose is a deliberate two-step migration. Its dashboard runs through the modern
presenter, and its panel configuration now uses
`ModernPanelConfigurationInterface` with a plugin-owned Twig form. Distance
settings remain stored in kilometers and are converted to/from the account's
preferred display unit only at the configuration boundary; prediction model
and marathon-shape settings retain their existing keys and defaults. The
legacy `RunalyzePluginPanel_Prognose` class has been removed after migrating
both window routes to use the modern account-scoped presenter for their
configuration. The stable plugin key and legacy window URLs are unchanged.

The interactive calculator reuses `src/CoreBundle/Model/Prognosis/PrognosisModel`
for constructing the four prediction models and uses an account-scoped
personal-best provider. Legacy form identifiers are mapped at the window
boundary; one reference result is sufficient for Steffny and Cameron. The calculator uses `PrognoseCalculatorWindowRenderer` as its modern entry point.
The legacy `Prognose_PrognosisWindow` class has been removed. The Symfony
controller retains the existing calculator URL and account-scoped installation
checks, initializes the legacy frontend configuration boundary, and explicitly
passes the effective VO2max shape, marathon shape, pace unit, and distance unit
to the renderer. The renderer prepares GET defaults and POST values through
`PrognoseCalculatorFormInput`, creates the selected model through
`PrognoseCalculatorModelFactory`, and delegates row calculations to
`PrognoseCalculatorPredictionRows`. The account-scoped
`PrognoseCalculatorPersonalBestProvider` supplies activity details.
`PrognoseCalculatorResultTableRenderer` renders the results, and
`PrognoseCalculatorModelHintRenderer` preserves the model-specific notices.

The calculator uses `@Prognose/calculator_form.html.twig` and
`@Prognose/calculator_result_table.html.twig`. These retain the original
AJAX/no-automatic-reload behavior, submitted field names, model visibility
JavaScript, column layout classes, and unit wrappers. Only trusted, generated
activity links are inserted as raw HTML in the result table. The window renderer
also formats reference-result summaries from explicit units rather than reading
global distance settings. The legacy output response factory remains in the
controller to preserve the HTTP/AJAX response contract until that cross-cutting
integration is separately modernized.
The trend window now loads its official race-result series through the
account-scoped `RaceresultRepository` instead of direct SQL and a cache key
shared across accounts. The training-history query also comes from the
account-scoped `TrainingRepository`, preserving the legacy VO2max weighting
and date-based rows. `PrognoseTrendSeriesCalculator` computes the two historical
prediction series from explicitly supplied inputs. The
`PrognoseTrendDataProvider` now reads VO2max and marathon-shape settings from
the account-scoped `ConfigurationManager`, constructs an isolated endurance
model, and invokes the calculator. The legacy plot includes
(`plugin/RunalyzePluginPanel_Prognose/window.plot.php` and `Plot.Form.php`)
have been removed. The existing Symfony route now renders
`@Prognose/trend_window.html.twig`, and `PrognoseTrendPlotRenderer` assembles
the existing Plot JavaScript and filters optional Wettkampf results. The
stable AJAX URL and full-page compatibility wrapper remain. The underlying
global `Plot` class and legacy `BasicEndurance` model are still compatibility
boundaries for subsequent modernization.

Before deleting another legacy class, verify installation, settings metadata,
configuration POST/defaults, activation/deactivation, optional plot/window routes,
and legacy tests. Move meaningful characterization assertions into the modern
feature tests rather than retaining tests tied to an obsolete class.

## Target architecture

A modern plugin is a Symfony-managed service implementing a small application-owned contract. Its dependencies are injected through its constructor like any other service. The container owns its lifetime; application code must not instantiate plugin classes with `new`.

The plugin service is stateless between invocations. In particular, it must not retain an account, plugin installation ID, request, configuration values, or calculated result in mutable service properties. Symfony services are shared by default, so storing per-account or per-request data on a plugin service would risk leaking state between requests or users.

### Responsibilities

| Concern | Responsibility |
| --- | --- |
| Plugin implementation | Plugin-specific business rules and orchestration, using injected collaborators |
| Plugin definition/metadata | Stable key, supported plugin type, human-readable metadata and required capabilities |
| Plugin registry | Resolve available modern plugin services by stable key and reject unknown or duplicate keys |
| Execution context | Pass the current account, installation identity, request-derived input and configuration explicitly for one invocation |
| Installation manager | Own account-scoped installation, activation and ordering; reuse the existing service where applicable |
| Configuration | Read and write values through an explicit account/plugin-scoped interface; do not access global configuration or DB directly |
| Presentation | Convert execution results into view data and render through Twig/controllers; do not echo HTML from business logic |

The boundaries should remain small. Do not introduce a generic plugin framework, event bus, service locator, or abstraction for capabilities that no current plugin needs.

### Service registration and resolution

Modern plugins should be registered as Symfony services and identified by an explicit stable key. Use a dedicated service tag (or an equivalent explicit registration mechanism) and inject the tagged services as an iterable into a registry.

The registry is the only resolution boundary. It builds a key-to-plugin map, validates duplicate keys during construction, and provides an explicit lookup operation. Callers must not receive the container or a PSR-11 container interface and must not request arbitrary services by class name.

Register each presenter with the service tag for its plugin type. `PluginDiscovery` reads the keys exposed by those registries.

Panels that need request-specific navigation can implement the optional `ModernPanelRequestHandlerInterface`. The controller passes query parameters to that handler before calling `present()`. The handler must validate every parameter it consumes and scope any selected entity to the authenticated account. Presenters remain focused on producing view data and must not read request globals or retain request data on the shared service.

A modern plugin's key is application metadata, not its PHP class name or directory name. This allows implementations to move without changing persisted installation records.

### Implemented panel contract

The first implemented type-specific contract is `ModernPanelInterface` in `src/CoreBundle/Plugin/Panel/`. It deliberately models panels only:

```php
interface ModernPanelInterface
{
    public function getKey(): string;

    public function getName(): string;

    public function getDescription(): string;

    public function getTemplate(): string;

    public function shouldReloadAfterTraining(): bool;

    public function present(Account $account, Plugin $installation): array;
}
```

`ModernPanelRegistry` receives services tagged with `runalyze.modern_panel`, indexes them by their stable key, and rejects duplicate keys. The controller verifies the account-scoped installation before calling the presenter. The `shouldReloadAfterTraining()` flag preserves the existing dashboard behavior: Sports panels are refreshed after training changes, while Sportler panels are not.

This contract is intentionally not a universal plugin API. Other plugin types should receive their own small contracts only when a real migration needs them. The conceptual `PluginDefinition` / `PluginContext` / `PluginResult` design is therefore not implemented and must not be treated as a current runtime API.

| Module under `plugins/Panels/` | Dashboard presenter | Stable installation key |
| --- | --- | --- |
| `Equipment/` | `EquipmentPanelPresenter` | `RunalyzePluginPanel_Equipment` |
| `Goals/` | `GoalsPanelPresenter` | `RunalyzePluginPanel_Ziele` |
| `Prognose/` | `PrognosePanelPresenter` | `RunalyzePluginPanel_Prognose` |
| `Rechenspiele/` | `RechenspielePanelPresenter` | `RunalyzePluginPanel_Rechenspiele` |
| `Sportler/` | `SportlerPanelPresenter` | `RunalyzePluginPanel_Sportler` |
| `Sports/` | `SportsPanelPresenter` | `RunalyzePluginPanel_Sports` |
| `TagsSummary/` | `TagsSummaryPanelPresenter` | `RunalyzePluginPanel_TagsSummary` |

Inspect panel registration with `php bin/console debug:container --tag=runalyze.modern_panel`.

### Implemented statistics contract

`ModernStatisticsInterface` exposes the stable key, display name, description and Twig template. Its `present(Account $account, Plugin $installation, InputBag $query)` method returns the template data for a single invocation. `ModernStatisticsRegistry` indexes presenters tagged with `runalyze.modern_stat`. Each plugin registers its services in its own `config/services.php`, imported by the application. Its templates are registered with a plugin-specific Twig namespace.

`DashboardStatisticsProvider` builds the active tabs and Miscellaneous menu from installed `stat` records and presenter metadata. The dashboard and `/my/plugin/{id}` share the rendering path in `AbstractPluginsAwareController`, which resolves the installation for the authenticated account. Filter links refresh `statistics-inner` over AJAX. `StatisticsFilterFactory` supplies account-scoped sport and date filters for presenters that use them.

- `ModernStatisticsConfigurationInterface` supplies the optional configuration form, its view data, and its update method. `PluginController` handles it through `/call/call.Plugin.config.php`, including configuration of inactive installations.
- `ModernStatisticsRequestHandlerInterface` handles validated query-driven changes before rendering. Tag and weather equipment selections and fun-race markers verify ownership before writing to the installation's configuration. `StatisticsInstallation::requireId()` validates the installation's identity, account, type, and stable key before reading or writing stored settings.
- Existing installations retain their `RunalyzePluginStat_*` keys, `stat` type, IDs, activation state, order, and saved `plugin_conf` values. The implementation switch requires no database migration.
- The route-network and temperature windows use Symfony controllers at `/plugin/RunalyzePluginStat_Strecken/window.routenet.php` and `/plugin/RunalyzePluginStat_Wetter/window.php`. Statistiken and Wettkampf also supply the public athlete year summaries through the athlete's sharing settings. Prognose reads the optional Wettkampf `fun_ids` setting as activity IDs through `PluginConfRepository`.

The legacy statistics classes and plugin-specific PHP includes have been removed. The historical keys below still appear in installation records and in compatibility URLs. [Migration issue #282](https://github.com/lowtower/Runalyze/issues/282)

| Module under `plugins/Statistics/` | Dashboard presenter | Stable installation key |
| --- | --- | --- |
| `Analyse/` | `AnalysisStatisticsPresenter` | `RunalyzePluginStat_Analyse` |
| `Hoehenmeter/` | `ElevationStatisticsPresenter` | `RunalyzePluginStat_Hoehenmeter` |
| `MonthlyStats/` | `MonthlyStatisticsPresenter` | `RunalyzePluginStat_MonthlyStats` |
| `Rekorde/` | `RecordStatisticsPresenter` | `RunalyzePluginStat_Rekorde` |
| `Statistiken/` | `SummaryStatisticsPresenter` | `RunalyzePluginStat_Statistiken` |
| `Strecken/` | `RouteStatisticsPresenter` | `RunalyzePluginStat_Strecken` |
| `Tag/` | `TagStatisticsPresenter` | `RunalyzePluginStat_Tag` |
| `Trainingspartner/` | `TrainingPartnerStatisticsPresenter` | `RunalyzePluginStat_Trainingspartner` |
| `Trainingszeiten/` | `TrainingTimeStatisticsPresenter` | `RunalyzePluginStat_Trainingszeiten` |
| `Wetter/` | `WeatherStatisticsPresenter` | `RunalyzePluginStat_Wetter` |
| `Wettkampf/` | `RaceStatisticsPresenter` | `RunalyzePluginStat_Wettkampf` |

Inspect registration with `php bin/console debug:container --tag=runalyze.modern_stat`. `composer test:plugin` runs the plugin tests, including statistics dashboard, filter, settings and account-isolation coverage. The CI workflow runs the full PHPUnit suite and compares statement coverage with the base branch.

### Execution and account isolation

The execution boundary receives an account and installation identity from the authenticated application flow. It must verify that the installation belongs to that account before loading configuration or invoking a plugin. A plugin must not choose its own account from request parameters.

Account-specific configuration is supplied as an explicit snapshot or through a narrow account-scoped configuration service. Persistence remains outside the plugin implementation. Defaults, validation, writes, and cache invalidation belong to the configuration boundary.

Never keep account-specific values in static properties, singleton plugin services, process-wide globals, or a shared mutable context. Tests must cover two accounts invoking the same plugin service in sequence and prove that neither invocation can observe the other's state.

### Lifecycle and plugin types

A modern plugin service is constructed by Symfony and should normally be stateless. Per-invocation setup belongs in the execution method or in short-lived collaborators created by the application boundary, not in a custom constructor receiving an installation ID.

Plugin types should be explicit and typed. The initial migration should support only the type being migrated; do not design all legacy types speculatively. Each type may define its own execution input and result/presentation contract while sharing metadata, registration, installation, and configuration boundaries.

Installation is separate from service registration:

1. Service registration makes an implementation available to the application.
2. Installation creates an account-scoped record using the stable plugin key and type.
3. Activation and ordering update that account-scoped record.
4. Execution resolves the registered implementation and the account's installation state.
5. Uninstallation removes the account-scoped state and invokes only explicitly defined cleanup behavior.

A plugin must not be considered installed merely because its service exists in the container.

### Presentation and HTTP integration

Modern plugin business logic must not echo output, manipulate output buffers, read `$_GET` / `$_POST`, or construct Symfony responses.

Controllers or dedicated presenters translate validated HTTP input into an invocation context, execute the plugin, and render the typed result with Twig. Existing routes and AJAX response contracts remain stable during migration. A new plugin type must define its rendering boundary before the first implementation is migrated.

The existing overlay wrapper and AJAX reload commands still serve compatibility URLs such as `/my/plugin/{id}` and `/call/call.Plugin.config.php`.

### Configuration

Modern plugins declare configuration metadata separately from persistence. Configuration definitions should describe keys, types, defaults, constraints, and presentation hints without constructing legacy form fields.

The application-owned configuration boundary is responsible for:

- scoping every read and write to the authenticated account and installed plugin;
- applying defaults consistently;
- validating submitted values;
- persisting through Doctrine-backed storage;
- invalidating only the relevant cache entries.

The panel and statistics configuration contracts use the existing Doctrine-backed storage. Presenters and request handlers supply defaults and validate changes for their own installation before persisting the existing setting keys.

## Testing strategy

Tests are part of the plugin contract, not an afterthought.

- **Unit tests:** test plugin business rules with constructor-injected test doubles and explicit invocation contexts. They must not boot the kernel or require a database/session.
- **Registry tests:** cover key resolution, unknown keys, duplicate keys, and registration metadata.
- **Configuration tests:** cover defaults, validation, account isolation, persistence, and targeted cache invalidation at the configuration boundary.
- **Execution tests:** cover account/install ownership, activation rules, and isolation when the same service is invoked for different accounts.
- **Presentation tests:** test Twig rendering separately from business logic.
- **Functional tests:** cover the Symfony route, authorization, request validation, and existing AJAX/HTTP contracts.
- **Compatibility tests:** cover preserved installation keys, routes, account settings, and how the application handles an unavailable installed plugin.

Prefer the existing PHPUnit group conventions. Add a new test class only after checking whether an existing test class already owns the relevant behavior.

## Migration sequence

Migrate one real plugin at a time:

1. Select a plugin with a bounded responsibility and identify its current data, configuration, routes, and rendering dependencies.
2. Extract or reuse independently testable business logic where possible.
3. Add the smallest modern contract and registry capability required by that plugin type.
4. Register the implementation as a Symfony service with constructor injection.
5. Adapt the existing application boundary to execute the modern implementation while preserving installation and HTTP behavior.
6. Add unit and boundary tests, including account-isolation coverage.
7. Keep the legacy implementation operational until the replacement is verified; remove obsolete legacy code only in a separate, evidence-based step.

Do not bulk-convert plugin directories, change all routes, or migrate the legacy form system as part of the first modern plugin.

## Non-goals and constraints

- No service locator or direct container access from plugins.
- No account-specific mutable state in shared services.
- No direct global DB access, session singleton, request superglobals, or output buffering in modern plugin code.
- No requirement to preserve compatibility for third-party plugin implementations.
- No simultaneous rewrite of all plugin types or the existing plugin catalog.
- No new persistence abstraction where an existing repository/service already owns the responsibility.

## First validated panel slice: Sportler

The first implementation step introduces `plugins/Panels/Sportler/SportlerPanelDataProvider.php` as a Symfony-managed, stateless service under the `Runalyze\Plugins\Panels\Sportler` namespace. Its invocation receives the authenticated `Account` and the persisted `Plugin` installation explicitly. It rejects installations belonging to another account, installations with a different stable key, non-panel installations, and inactive installations; hidden installations remain executable so the existing hidden-panel behavior can be preserved.

The provider reuses `UserRepository` for the latest body values and the account-scoped weight/body-composition histories, and `PluginConfigurationStorageInterface` for the existing `wunschgewicht`, `plot_points`, and `plot_timerange` settings. The legacy defaults are applied at this boundary. The provider returns typed data only; rendering and chart presentation remain outside it. The service is registered through `plugins/Panels/Sportler/config/services.php`, which is imported explicitly by the application service configuration. Its unit test lives at `tests/Plugins/Panels/Sportler/Tests/SportlerPanelDataProviderTest.php`. This is a panel-specific plugin service, not a generic plugin execution contract.

### Sportler presentation and HTTP integration

The next migration slice routes the installed Sportler panel through the modern rendering path without changing the persisted plugin key or database schema:

- `PluginInstallationManager::getInstallationForAccount()` resolves an installation by both account and installation ID. The repository query applies both predicates.
- `SportlerPanelPresenter` adapts the provider data for panel presentation and preserves the legacy behavior of duplicating a single history point for Flot.
- The panel template is owned by `plugins/Panels/Sportler/templates/` and loaded through the `@Sportler` Twig namespace.
- `AbstractPluginsAwareController` selects this path by the stable key `RunalyzePluginPanel_Sportler` through the panel registry.
- The modern template reuses the common panel wrapper, body-value presentation macros, Flot initialization, chart IDs, and panel interaction hooks.

This is deliberately a concrete panel integration, not a general plugin registry or universal presenter contract. Functional coverage belongs in the existing `DefaultControllerTest` suite.

## Second validated panel slice: Sports

The Sports dashboard panel is migrated through the existing panel contract:

- `SportsPanelPresenter` is a stateless Symfony service registered from `plugins/Panels/Sports/config/services.php` with the `runalyze.modern_panel` tag.
- It injects the existing `SportRepository`, `ConfigurationManager`, and Symfony's `ClockInterface`; it does not introduce a new repository, data provider, or statistics abstraction. The week boundary is calculated from the account's modern configuration instead of reading the legacy static configuration facade.
- It preserves the legacy week, month, year, and all-time queries, including the configured week start and the server-time conversion already owned by `SportRepository`.
- Its Twig template lives at `plugins/Panels/Sports/templates/panel.html.twig` and is loaded through the `@Sports` namespace.
- `AbstractPluginsAwareController` resolves the panel by the stable key `RunalyzePluginPanel_Sports`. The dashboard wrapper remains refreshable after training changes; the Sportler panel retains its separate no-reload behavior.

The presenter test is located at `tests/Plugins/Panels/Sports/Tests/SportsPanelPresenterTest.php`; dashboard integration coverage is added to the existing `DefaultControllerTest`.

## Third validated panel slice: Equipment

The Equipment dashboard panel reuses the existing Doctrine entities and repositories:

- `EquipmentPanelPresenter` uses `EquipmentRepository` and the panel-specific, account-scoped `EquipmentTypeRepository::findAllForPanel()` lookup. It preserves active-first and distance-descending equipment ordering, usage bars, and the existing search-window links.
- `EquipmentPanelSelectionHandler` persists a selected equipment type only after verifying that the type belongs to the authenticated account. It uses `PluginConfigurationStorageInterface`; it does not access the database directly.
- `ModernPanelRequestHandlerInterface` is an optional extension for panels that need validated query parameters before presentation. The controller invokes it before rendering; panels without request-specific behavior remain unchanged.
- The panel template is owned by `plugins/Panels/Equipment/templates/` and loaded through the `@Equipment` Twig namespace.
- Presenter and selection-handler coverage lives in `tests/Plugins/Panels/Equipment/Tests/EquipmentPanelPresenterTest.php`; repository ordering and account isolation are covered by `EquipmentRepositoryTest`, and the existing `DefaultControllerTest` covers dashboard rendering and type selection through the panel route.
## Open design decisions

Resolve these against each future concrete migration rather than speculating now:

- The type-specific contracts required if another plugin type is introduced.
- Whether configuration for each future plugin type is supplied as an immutable snapshot or a narrow scoped accessor.
- The typed result/view-model shape for each future plugin type.
- Whether any legacy installation metadata needs a schema change (prefer not; retain stable keys where possible).

Each migration PR should include a small working example and tests that exercise its contract end to end. Update this document when implementation evidence settles the remaining open decisions.
