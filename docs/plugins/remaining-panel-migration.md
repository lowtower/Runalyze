# Legacy panel migration record

The modern panel architecture is described in [architecture.md](architecture.md).
This record documents the last two panel cutovers and the removal of shared legacy discovery.

## Scope

Goals and Rechenspiele completed the panel cutover to Symfony.

| Stable installation key | Modern implementation |
| --- | --- |
| `RunalyzePluginPanel_Ziele` | `plugins/Panels/Goals/` (calendar, totals, progress calculator, presenter, Twig and configuration) |
| `RunalyzePluginPanel_Rechenspiele` | `plugins/Panels/Rechenspiele/` (dashboard presenter, data provider, calculator, explanation-window provider, configuration) |

The panel and statistics registries now supply the plugin catalog, installation metadata and display paths. The `plugin/` directory, filesystem discovery and Composer classmap entry have been removed. The original window URLs are served by Symfony controllers. See the [plugin architecture](architecture.md).

## Migration rules

- Preserve each stable `RunalyzePluginPanel_*` key and installed plugin records.
- Implement `ModernPanelInterface` and add
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

The legacy execution paths and filesystem references were removed after the final panel cutover. The migration is tracked in [issue #103](https://github.com/lowtower/Runalyze/issues/103) and [issue #282](https://github.com/lowtower/Runalyze/issues/282).
