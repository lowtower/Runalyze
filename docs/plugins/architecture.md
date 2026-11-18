# Plugin architecture

This document defines the proposed target architecture for new and migrated Runalyze plugins. It is an architectural contract for the migration; the interfaces and examples below are design proposals, not APIs that are already available in the application. It also defines the directory and namespace convention for modern plugins; the Composer mapping and runtime integration are introduced incrementally with the first migrated plugin.

The existing plugin system remains supported while this target is introduced incrementally. Existing implementations under `plugin/` continue to use the global `Plugin` / `PluginPanel` base classes and their legacy lifecycle. New code must not extend those classes.

## Goals

- Make plugin dependencies explicit through Symfony dependency injection.
- Keep plugin business logic independently unit-testable.
- Keep account-specific state out of shared service instances.
- Separate plugin metadata, execution, persistence, and presentation.
- Preserve existing installation, activation, ordering, configuration, route, and AJAX behavior during migration.
- Keep a narrow compatibility boundary for existing plugins until they can be migrated.

## Current system and migration boundary

Legacy plugin implementations are discovered from `plugin/` through Composer's classmap and instantiated by `LegacyPluginInstantiator`. Their constructors accept a plugin ID and rely on scoped metadata supplied through `LegacyPluginDataContext`. The base class also reads request superglobals and legacy configuration/global services.

The Symfony integration already has useful boundaries that should be reused:

- `PluginDiscovery` discovers legacy plugin directories.
- `PluginInstallationManager` owns account-scoped installation and activation state.
- `PluginSettingsProvider` resolves installed legacy plugin data and instances.
- `PluginConfigurationStorageInterface` and its Doctrine implementation persist plugin configuration.
- `LegacyPluginInstantiator` preserves the legacy constructor contract.
- `LegacyPluginOutputRenderer` captures output from legacy implementations.

These services are not to be replaced merely to make the new API look different. The new API should coexist with them and use them where their responsibilities match.

## Directory and namespace convention

Modern plugins are self-contained feature modules. Plugin-specific application code, templates, translations, assets, and plugin-local configuration belong to the plugin; shared infrastructure and cross-plugin contracts remain in the application.

The repository uses separate roots for the two generations:

- `plugin/` remains the legacy root. Existing directories, class names, global base classes, and Composer classmap discovery remain supported during migration.
- `plugins/` is the root for modern plugins. New modern plugin code must not be added to the legacy `plugin/` tree.

Each modern plugin has one directory named after its stable, human-readable plugin name in PascalCase. Its PHP namespace is rooted at `Runalyze\\Plugins\\<PluginName>\\`, mapped by Composer PSR-4 from `Runalyze\\Plugins\\` to `plugins/`.

A modern plugin should follow this structure where the corresponding concerns exist:

```text
plugins/
└── Sportler/
    ├── SportlerPanelDataProvider.php
    ├── SportlerPanelPresenter.php
    ├── Configuration/
    ├── templates/
    ├── translations/
    ├── assets/
    └── config/
        └── services.php
```

The directory name and namespace segment are code organization details, not persisted plugin identifiers. The stable plugin key remains explicit metadata and must not be derived from a PHP class name, namespace, or directory name. Renaming or moving implementation files must not change installed plugin records.

### PHP namespaces and class names

- Use `Runalyze\\Plugins\\<PluginName>` as the namespace root for the plugin.
- Keep plugin-specific classes in that namespace or meaningful subnamespaces such as `Configuration`, `Presentation`, or `Infrastructure`.
- Use PascalCase for plugin directory names and PHP class names.
- Do not introduce a generic `Plugin` namespace shared by unrelated feature code.
- Do not place plugin-specific providers, presenters, DTOs, or configuration definitions in `Runalyze\\Bundle\\CoreBundle\\`.
- Keep reusable application services, shared contracts, repositories, and cross-plugin orchestration in the CoreBundle.

### Composer and Symfony integration

The intended Composer mapping is a single PSR-4 prefix:

```json
{
    "autoload": {
        "psr-4": {
            "Runalyze\\Plugins\\": "plugins/"
        }
    }
}
```

This mapping is a target convention and must be added as part of the first code migration that introduces the `plugins/` root; this documentation change does not alter Composer configuration or move existing classes.

Modern plugin services must be registered explicitly with Symfony dependency injection. A plugin-local `config/services.php` may describe its services, but it must be imported by the application configuration; the directory existing on disk does not make it automatically discoverable. Service registration must not rely on scanning the legacy classmap or on runtime container lookups.

Plugin templates, translations, and assets stay within the plugin directory unless an established Symfony integration requires a deliberate application-level registration step. Their paths and loading mechanisms must be explicit and tested.

### Tests

Plugin-specific unit tests remain in the central `tests/` tree and mirror the plugin namespace, for example:

```text
tests/
└── Plugins/
    └── Sportler/
        └── SportlerPanelDataProviderTest.php
```

Use the existing PHPUnit configuration and group conventions. Do not add a plugin-local test runner or duplicate test infrastructure.

### Legacy coexistence

A migrated plugin may temporarily contain both its legacy implementation under `plugin/<legacy-directory>/` and its modern implementation under `plugins/<PluginName>/`. The legacy implementation remains the compatibility path until the modern execution and presentation path has been verified. Do not move legacy files merely to make the directory layout look uniform.

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
| Legacy adapter | Bridge existing `Plugin` implementations into the modern integration boundary without changing their constructors |

The boundaries should remain small. Do not introduce a generic plugin framework, event bus, service locator, or abstraction for capabilities that no current plugin needs.

### Service registration and resolution

Modern plugins should be registered as Symfony services and identified by an explicit stable key. Use a dedicated service tag (or an equivalent explicit registration mechanism) and inject the tagged services as an iterable into a registry.

The registry is the only resolution boundary. It builds a key-to-plugin map, validates duplicate keys during construction, and provides an explicit lookup operation. Callers must not receive the container or a PSR-11 container interface and must not request arbitrary services by class name.

The registry must not silently fall back from a missing modern key to filesystem scanning. Legacy discovery remains the responsibility of `PluginDiscovery`; the compatibility boundary decides which implementation path applies.

A modern plugin's key is application metadata, not its PHP class name or directory name. This allows implementations to move without changing persisted installation records.

### Proposed contract shape

The final PHP interfaces should be deliberately small. The following is a conceptual shape, not copy-and-paste code for the current application:

```php
interface ModernPluginInterface
{
    public function definition(): PluginDefinition;

    public function execute(PluginContext $context): PluginResult;
}
```

- `PluginDefinition` is immutable metadata: stable key, plugin type, display name and description. It must not contain account-specific values.
- `PluginContext` is immutable invocation data. It explicitly carries the authenticated account and installation identity, validated input, and the configuration snapshot needed for this execution.
- `PluginResult` is a typed result appropriate to the plugin type. Prefer data structures over HTML strings or direct output.
- Dependencies used by the implementation are constructor-injected services, not members of `PluginContext`.

The exact type-specific result contracts should be introduced only when the first real plugin type is migrated. A panel, a statistic provider, and an interactive tool have different presentation and lifecycle needs; one universal result object with many optional fields would obscure those differences.

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

The legacy adapter may continue to use `LegacyPluginOutputRenderer` for old implementations. The renderer is not part of the modern plugin contract.

### Configuration

Modern plugins declare configuration metadata separately from persistence. Configuration definitions should describe keys, types, defaults, constraints, and presentation hints without constructing legacy form fields.

The application-owned configuration boundary is responsible for:

- scoping every read and write to the authenticated account and installed plugin;
- applying defaults consistently;
- validating submitted values;
- persisting through Doctrine-backed storage;
- invalidating only the relevant cache entries.

The existing `PluginConfigurationStorageInterface` is a persistence boundary for the legacy compatibility facade. Do not expose `PluginConfiguration` or legacy value objects as the modern API. The modern configuration contract should be designed alongside the first migrated plugin that needs it.

## Testing strategy

Tests are part of the plugin contract, not an afterthought.

- **Unit tests:** test plugin business rules with constructor-injected test doubles and explicit invocation contexts. They must not boot the kernel or require a database/session.
- **Registry tests:** cover key resolution, unknown keys, duplicate keys, and registration metadata.
- **Configuration tests:** cover defaults, validation, account isolation, persistence, and targeted cache invalidation at the configuration boundary.
- **Execution tests:** cover account/install ownership, activation rules, and isolation when the same service is invoked for different accounts.
- **Presentation tests:** test Twig rendering separately from business logic.
- **Functional tests:** cover the Symfony route, authorization, request validation, and existing AJAX/HTTP contracts.
- **Compatibility tests:** keep regression coverage for legacy construction, configuration, output capture, and behavior while the adapter is in use.

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
- No change to legacy plugin constructor contracts.
- No account-specific mutable state in shared services.
- No direct global DB access, session singleton, request superglobals, or output buffering in modern plugin code.
- No requirement to preserve compatibility for third-party plugin implementations.
- No simultaneous rewrite of all plugin types or the existing plugin catalog.
- No new persistence abstraction where an existing repository/service already owns the responsibility.

## First validated panel slice: Sportler

The first implementation step introduces `plugins/Sportler/SportlerPanelDataProvider.php` as a Symfony-managed, stateless service under the `Runalyze\\Plugins\\Sportler` namespace. Its invocation receives the authenticated `Account` and the persisted `Plugin` installation explicitly. It rejects installations belonging to another account, installations with a different stable key, non-panel installations, and inactive installations; hidden installations remain executable so the existing hidden-panel behavior can be preserved.

The provider reuses `UserRepository` for the latest body values and the account-scoped weight/body-composition histories, and `PluginConfigurationStorageInterface` for the existing `wunschgewicht`, `plot_points`, and `plot_timerange` settings. The legacy defaults are applied at this boundary. The provider returns typed data only; rendering and chart presentation remain outside it. The service is registered through `plugins/Sportler/config/services.php`, which is imported explicitly by the application service configuration. Its unit test lives at `tests/Plugins/Sportler/SportlerPanelDataProviderTest.php`. This is a panel-specific plugin service, not a generic plugin execution contract.

## Open design decisions

Resolve these against the first concrete migration rather than speculating now:

- Which plugin type is the best first vertical slice.
- The exact metadata and type-specific execution interfaces.
- Whether configuration is supplied as an immutable snapshot or a narrow scoped accessor for that plugin type.
- The exact typed result/view-model shape for each migrated plugin type.
- Whether any legacy installation metadata needs a schema change (prefer not; retain stable keys where possible).

The first implementation PR should include a small working example and tests that exercise the chosen contract end to end. Update this document when implementation evidence settles the open decisions.
