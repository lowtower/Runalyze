# Remaining legacy panel migration

The modern panel architecture is described in [architecture.md](architecture.md).
This checklist is based on the legacy `plugin/` tree after PR #261.

## Scope

Only two legacy panel directories remain:

| Stable installation key | Legacy directory | Modern implementation | Remaining work |
| --- | --- | --- | --- |
| `RunalyzePluginPanel_Ziele` | `plugin/RunalyzePluginPanel_Ziele/` | `plugins/Goals/` (calendar, totals, progress calculator, staged presenter and configuration) | Activate modern panel, verify browser parity, remove legacy panel and SQL |
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
2. **Goals dashboard:** `GoalsProgressCalculator` computes the existing
   distance averages, projected totals, remaining targets, and Pace Bunny rows
   without database or global configuration access. The legacy panel now
   delegates its row calculations to this service while keeping its existing
   HTML renderer. `GoalsTrainingDataProvider` and the account-scoped
   `TrainingRepository::findTrainingTotalsSince()` are ready, but the legacy
   SQL path remains until a modern presenter wires the provider into a
   plugin-owned Twig template. Preserve the existing five time ranges and
   legacy layout during the subsequent presentation migration.
   A staged `GoalsPanelPresenter` now assembles account-scoped ranges and
   progress rows from these services, respecting `ziel_show_*`, `ziel_*`
   and `details` without reading global session state. It is deliberately
   registered without the `runalyze.modern_panel` tag: dashboard routing
   must not switch until its Twig template and modern configuration editor
   are complete and tested. The staged `@Goals/panel.html.twig` now preserves
   legacy range toggling, progress rows, and Pace Bunny bars. Its namespace is
   registered, but the panel is still not tagged for modern dashboard routing.
3. **Goals administration:** modern metadata, configuration interface, and
   `@Goals/configuration.html.twig` now preserve `ziel_show_*`, `ziel_*`, and
   `details` keys and defaults. The presenter remains untagged until the
   final browser/CI parity check and removal of the legacy panel, including
   its raw SQL query.
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
