# Remaining legacy panel migration

The modern panel architecture is described in [architecture.md](architecture.md).
This checklist is based on the legacy `plugin/` tree after PR #261.

## Scope

Only two legacy panel directories remain:

| Stable installation key | Legacy directory | Modern implementation | Remaining work |
| --- | --- | --- | --- |
| `RunalyzePluginPanel_Ziele` | `plugin/RunalyzePluginPanel_Ziele/` | `plugins/Goals/` (calendar ranges and account-scoped totals provider) | Wire modern totals into goal calculations, configuration persistence, Twig view, metadata and removal of legacy panel |
| `RunalyzePluginPanel_Rechenspiele` | `plugin/RunalyzePluginPanel_Rechenspiele/` | `plugins/Rechenspiele/` (dashboard presenter, data provider, calculator) | Migrate configuration fields and explanation/plot windows, remove the old class and PHP includes |

The `RunalyzePluginStat_*` directories are **statistics plugins**, not panels.
They need a separate modernization contract and migration scope; relocating
them under `plugins/` as though they were panels would break their lifecycle.

## Migration rules

- Preserve each stable `RunalyzePluginPanel_*` key and installed plugin records.
- Reuse `ModernPanelInterface`, `ModernPanelMetadataInterface`, and
  `ModernPanelConfigurationInterface` where needed.
- Keep configuration keys and defaults exactly as stored for existing users.
- Use account-scoped repositories, not direct `DB::getInstance()` queries.
- Preserve existing window URLs and the current AJAX response behavior.
- Register new services in the plugin-local `config/services.php`, import it in
  `config/services.php`, and register namespaced Twig templates.
- Add behavioral regression tests before removing each legacy class.
- Delete each legacy directory only after its replacement, including windows
  and configuration, has been tested in CI and the browser.

## Migration phases

1. **Goals calendar ranges:** extract time ranges to `GoalsTimeRangeProvider`
   and retain the existing panel through a compatibility delegation.
2. **Goals dashboard:** use `GoalsTrainingDataProvider` and the account-scoped
   `TrainingRepository::findTrainingTotalsSince()` aggregate, then extract goal
   computations and pace-bunny bars into modern services and a Twig template.
   The provider converts range-start wall-clock times to the timezone-neutral
   timestamp representation used by `Training.time`. The legacy SQL path remains
   in use until the modern presenter replaces the panel.
3. **Goals administration:** implement metadata and configuration interfaces,
   preserving `ziel_show_*`, `ziel_*`, and `details` keys. Remove the
   legacy class and characterize the new presenter/configuration behavior.
4. **Calculations windows:** move the explanation fieldsets and shape plot
   from the Rechenspiele PHP includes to dedicated modern services/templates.
   Retain their URLs and model/time-range selections.
5. **Calculations administration:** migrate the four existing configuration
   keys, provide modern metadata, and remove the final panel directory.
6. **Final audit:** verify no `plugin/RunalyzePluginPanel_*` directories
   remain, check discovery/installation/configuration, run all plugin tests,
   `composer qa`, container linting, browser smoke checks and coverage gate.

These phases may span multiple PRs. An isolated calendar extraction does **not**
mean that the Goals panel or Rechenspiele panel is already legacy-free.
