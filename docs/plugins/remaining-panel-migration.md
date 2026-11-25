# Remaining legacy panel migration

The modern panel architecture is described in [architecture.md](architecture.md).
This checklist tracks the remaining legacy panel migration.

## Scope

The Goals and Rechenspiele panels now use the modern runtime; no legacy panel directory remains:

| Stable installation key | Legacy directory | Modern implementation | Remaining work |
| --- | --- | --- | --- |
| `RunalyzePluginPanel_Ziele` | Removed | `plugins/Goals/` (calendar, totals, progress calculator, presenter, Twig and configuration) | Verify display and settings parity in browser |
| `RunalyzePluginPanel_Rechenspiele` | Removed | `plugins/Rechenspiele/` (dashboard presenter, data provider, calculator, explanation-window provider, configuration) | Verify display and settings parity in browser |

All eleven built-in statistics plugins have migrated to `plugins/Statistics/`.
The remaining legacy panel is the last on-disk implementation using the shared
`plugin/` discovery and Composer classmap. See the
[statistics contract and migration status](architecture.md#implemented-statistics-contract).

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

1. **Goals calendar ranges:** `GoalsTimeRangeProvider` covers all five legacy ranges.
2. **Goals dashboard:** `GoalsPanelPresenter` is tagged as
   `runalyze.modern_panel` and reuses `GoalsTrainingDataProvider`,
   `TrainingRepository::findTrainingTotalsSince()`, and
   `GoalsProgressCalculator`. The dashboard renders through
   `@Goals/panel.html.twig` using the legacy range-switching conventions.
   The old panel class, its direct SQL, and its reflection tests are removed;
   modern presenter, calculation, template, repository, and controller tests
   cover the replacement.
3. **Goals administration:** `GoalsPanelPresenter` implements modern
   metadata and configuration interfaces with the stable plugin key. The
   `@Goals/configuration.html.twig` editor preserves `ziel_show_*`,
   `ziel_*`, and `details`, including checkbox defaults and active-state
   management. Confirm the dashboard and configuration behavior in a real
   browser before merging the activation PR.
4. **Calculations windows:** the shape-plot window is served by the Symfony
   route `rechenspiele-shape-window` through `RechenspielePlotSelection`,
   the account-scoped plot data provider chain and the
   `RechenspieleShapePlotOutputAdapter`, retaining the legacy URLs, menus
   and rendering. The explanation window is now served by the Symfony route
   `rechenspiele-explanation-window` and renders
   `@Rechenspiele/explanation-window.html.twig`; all three legacy fieldsets
   (ATL/CTL/TSB, effective VO2max grid, marathon shape) are reproduced by
   `RechenspieleExplanationWindowProvider` and the account-scoped
   `TrainingRepository::findEffectiveVo2maxHistoryFor()` /
   `findBasicEnduranceRunsFor()` queries, without legacy `Formular`
   includes or direct database access.
5. **Calculations administration:** `RechenspielePanelPresenter` implements
   modern metadata and configuration interfaces with the stable plugin key.
   The `@Rechenspiele/configuration.html.twig` editor preserves the four
   boolean keys `show_trimpvalues`, `show_trimpvalues_extra`,
   `show_vo2max` and `show_basicendurance`, including their defaults and
   active-state management. The legacy panel directory, its class, its window
   scripts and the superseded characterization test are removed; provider,
   presenter, template, repository and controller tests cover the
   replacement.
6. **Final audit:** verify no `plugin/RunalyzePluginPanel_*` directories
   remain, check discovery/installation/configuration, run all plugin tests,
   `composer qa`, container linting, browser smoke checks and coverage gate.

These phases may span multiple PRs. The Goals and Rechenspiele migrations are
implemented; no `plugin/RunalyzePluginPanel_*` directories remain. The final
audit (phase 6) is pending.
