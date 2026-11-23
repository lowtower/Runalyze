# Remaining legacy panel migration

The modern panel architecture is described in [architecture.md](architecture.md).
This checklist tracks the remaining legacy panel migration.

## Scope

The Goals panel now uses the modern runtime; one legacy panel directory remains:

| Stable installation key | Legacy directory | Modern implementation | Remaining work |
| --- | --- | --- | --- |
| `RunalyzePluginPanel_Ziele` | Removed | `plugins/Goals/` (calendar, totals, progress calculator, presenter, Twig and configuration) | Verify display and settings parity in browser |
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
4. **Calculations windows:** the legacy shape-plot window now delegates
   its `y` (time range) and `m` (performance model) query validation to
   `RechenspielePlotSelection`. Its URLs, menus, and rendering remain in
   the original PHP window; invalid or non-string parameters fall back to
   `lasthalf` and `tsb`. Next, move the explanation fieldsets and shape plot
   from the Rechenspiele PHP includes to dedicated modern services/templates.
   Retain their URLs and model/time-range selections.
   A new account-scoped repository method provides bounded plot activity
   inputs but is not active in the legacy chart yet. The new
   `RechenspieleShapeDailyAggregator` converts repository rows into
   zero-filled daily TRIMP, distance, weighted VO2max and long-jog arrays,
   including the historical warmup period. It is covered by isolated tests;
   `RechenspieleShapeDataProvider` now connects that aggregator to the
   account-scoped repository, validates date ranges, and applies UTC calendar
   bounds and activity calendar dates in the account's timezone, including
   DST changes and warmup days. It is intentionally not used by the
   existing PHP plot window yet: verify the database session timezone and
   legacy `FROM_UNIXTIME()` behavior against the account timezone before
   activating the new data path. A staged
   `RechenspieleShapeSeriesCalculator` now reuses the existing TSB/Banister
   models to calculate ATL, CTL, TSB, TRIMP and rolling/daily VO2max series.
   A staged `RechenspieleMarathonShapeSeriesCalculator` now prepares
   weighted long-jog points and preceding mileage windows, delegating the
   percentage calculation to the existing `Runalyze\\Sports\\Running\\MarathonShape`
   and its Symfony BasicEndurance configuration. The legacy plot remains
   unchanged. A standalone `RechenspieleShapePlotRenderer` now retains the
   legacy series, axis configuration, colors and plot ID using the existing
   `Plot` implementation. The staged `RechenspieleShapePlotDateRange`
   now provides validated period boundaries, warmup and daily array indices
   for all years, recent windows and individual years, including DST-safe
   calendar arithmetic. Activating the pipeline through the Symfony window
   route, verifying database timezone parity against the live legacy output,
   and preserving historical maxima persistence remain follow-ups.
5. **Calculations administration:** migrate the four existing configuration
   keys, provide modern metadata, and remove the final panel directory.
6. **Final audit:** verify no `plugin/RunalyzePluginPanel_*` directories
   remain, check discovery/installation/configuration, run all plugin tests,
   `composer qa`, container linting, browser smoke checks and coverage gate.

These phases may span multiple PRs. The Goals migration is implemented;
Rechenspiele remains the last legacy panel.
