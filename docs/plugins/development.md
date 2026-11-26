# Developing a Runalyze plugin

## Implement the presenter

| | Dashboard panel | Statistics tab |
| --- | --- | --- |
| Module directory | `plugins/Panels/<Feature>/` | `plugins/Statistics/<Feature>/` |
| PHP namespace | `Runalyze\Plugins\Panels\<Feature>` | `Runalyze\Plugins\Statistics\<Feature>` |
| Presenter contract | `ModernPanelInterface` | `ModernStatisticsInterface` |
| Symfony service tag | `runalyze.modern_panel` | `runalyze.modern_stat` |

Both presenters provide `getKey()`, `getName()`, `getDescription()`, and `getTemplate()`. The plugin administration uses the name and description. A statistics description can also accept an account, for example to display the account's preferred units. It must also work when the caller omits the account.

Return the stable installation key from `getKey()`. When moving an existing plugin, keep its key and stored settings so the existing installation continues to resolve. See the installed keys in the [plugin architecture](architecture.md).

Inject repositories and other services in the constructor. Use the `Account` and `Plugin` installation passed to `present()` to scope each call and return the view data for the plugin-owned Twig template. Panels use `shouldReloadAfterTraining()` to tell the dashboard whether to refresh the panel after a training change. Statistics presenters receive the request's `InputBag` in `present()` to apply their filters.

Start with the [Sports panel presenter](../../plugins/Panels/Sports/SportsPanelPresenter.php) and its [panel template](../../plugins/Panels/Sports/templates/panel.html.twig), or the [Monthly Stats presenter](../../plugins/Statistics/MonthlyStats/MonthlyStatisticsPresenter.php) and its [statistics template](../../plugins/Statistics/MonthlyStats/templates/statistics.html.twig).

Extend `my/panels/base.html.twig` for a panel and read the presenter data from `panel_data`. The controller also supplies `plugin_id` and `is_hidden`. For statistics, extend `my/statistics/base.html.twig` and read the keys returned from `present()` directly in the template.

## Register the service and template

Define the presenter and its plugin-specific dependencies in the module's `config/services.php`. The Monthly Stats module registers its presenter with this service definition.

```php
<?php

declare(strict_types=1);

use Runalyze\Plugins\Statistics\MonthlyStats\MonthlyStatisticsPresenter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()
        ->set(MonthlyStatisticsPresenter::class)
        ->autowire()
        ->tag('runalyze.modern_stat');
};
```

Symfony imports each module's `config/services.php` automatically. The [Sports service definition](../../plugins/Panels/Sports/config/services.php) shows the equivalent registration for a panel.

Put Twig templates in the module's `templates/` directory. Twig uses the module directory name as its namespace, so `MonthlyStats/templates/statistics.html.twig` is available as `@MonthlyStats/statistics.html.twig`. Use distinct names for modules that have templates across `Panels` and `Statistics`.

For a dedicated plugin URL, put the controller in the module's `Controller/` directory and use Symfony `#[Route]` attributes. Controllers in this directory are registered as services and their routes are discovered automatically. See the [Prognose window controller](../../plugins/Panels/Prognose/Controller/PrognoseWindowController.php) for an example with an existing route.

Rebuild the Symfony production cache after deploying a new module or changing its configuration, routes, or template directories.

## Verify installation and rendering

The registry indexes each presenter by its stable key. Install the plugin from the plugin administration and enable it for a test account. Runalyze stores installation and activation state per account and resolves the presenter from that installation when it renders the dashboard or refreshes the plugin via `/my/plugin/{id}`.

When moving an existing plugin, check that its existing installation still renders and that configuration defaults, saved values, and existing URLs still work. Test with two accounts when the plugin reads or writes account-specific data.

## Add configuration or request handling when needed

Implement `ModernPanelConfigurationInterface` or `ModernStatisticsConfigurationInterface` to supply a settings template, view data, and an update method. Those interfaces also make the settings window available from the installation confirmation and the plugin administration. Use `PluginConfigurationStorageInterface` for installation settings. Validate the installation and check account ownership of any submitted entity IDs before writing.

For query-driven actions, the optional `ModernPanelRequestHandlerInterface` receives a query parameter array and `ModernStatisticsRequestHandlerInterface` receives an `InputBag`. The controller calls the handler before rendering, so the presenter can read the updated state. See the account-scoped selection handling in [TagsSummary](../../plugins/Panels/TagsSummary/TagsSummaryPanelPresenter.php) and [Weather](../../plugins/Statistics/Wetter/WeatherStatisticsPresenter.php).

## Run the tests

Mirror the module's path in `tests/Plugins/<Panels or Statistics>/<Feature>/Tests/` and use PHPUnit's `plugin` group. Test a presenter with injected collaborators and explicit account and installation fixtures. Test calculations directly when the plugin separates them from presentation. The [Sports presenter test](../../tests/Plugins/Panels/Sports/Tests/SportsPanelPresenterTest.php) and [Monthly Stats data test](../../tests/Plugins/Statistics/MonthlyStats/Tests/MonthlyStatisticsDataTest.php) run without booting Symfony.

```bash
php vendor/bin/phpunit -c phpunit.xml.dist \
    tests/Plugins/Panels/Sports/Tests/SportsPanelPresenterTest.php \
    tests/Plugins/Statistics/MonthlyStats/Tests/MonthlyStatisticsDataTest.php
composer test:plugin
```

Use the existing [dashboard](../../tests/CoreBundle/Controller/DefaultControllerTest.php) and [plugin controller](../../tests/CoreBundle/Controller/PluginControllerTest.php) tests as examples for exercising registered services through the HTTP entry points. With the application configured, check service discovery for both plugin types and validate the container.

```bash
php bin/console debug:container --tag=runalyze.modern_panel
php bin/console debug:container --tag=runalyze.modern_stat
php bin/console debug:router --show-controllers
php bin/console lint:container
```
