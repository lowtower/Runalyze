<?php

namespace Runalyze\Bundle\CoreBundle\Component\Configuration;

use InvalidArgumentException;
use LogicException;
use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\AbstractCategory;

class RunalyzeConfigurationList extends ConfigurationList
{
    /**
     * @var array ['key' => 'className']
     */
    protected array $CategoryMap = [];

    /**
     * @var AbstractCategory[]
     */
    protected array $CategoryPool = [];

    protected ?UnitSystem $UnitSystem = null;

    public function __construct(array $variables = [])
    {
        parent::__construct([]);

        $this->setCategoryMap();
        $this->loadDefaultVariablesFromCategories();

        if ($variables !== []) {
            $this->mergeWith($variables);
        }
    }

    public function mergeWith(array $variables): void
    {
        parent::mergeWith($variables);

        $this->CategoryPool = [];
    }

    public function set(string $key, mixed $value): void
    {
        parent::set($key, $value);

        $categoryName = substr($key, 0, strpos($key, '.'));

        if (isset($this->CategoryPool[$categoryName])) {
            $this->CategoryPool[$categoryName]->set(substr($key, strlen($categoryName) + 1), $value);
        }
    }

    protected function setCategoryMap(): void
    {
        $this->CategoryMap = [
            'activity-form' => Category\ActivityForm::class,
            'activity-view' => Category\ActivityView::class,
            'basic-endurance' => Category\BasicEndurance::class,
            'data' => Category\Data::class,
            'data-browser' => Category\DataBrowser::class,
            'design' => Category\Design::class,
            'general' => Category\General::class,
            'privacy' => Category\Privacy::class,
            'trimp' => Category\Trimp::class,
            'vo2max' => Category\VO2max::class,
        ];
    }

    protected function loadDefaultVariablesFromCategories(): void
    {
        foreach ($this->CategoryMap as $categoryKey => $className) {
            /** @var AbstractCategory $category */
            $category = new $className();

            foreach ($category->getDefaultVariables() as $key => $defaultValue) {
                $this->Variables[$categoryKey . '.' . $key] = $defaultValue;
            }
        }
    }

    /**
     * @return Category\AbstractCategory This category is not persistent, it's created on each call.
     *
     * @throws InvalidArgumentException if the category name is not mapped in CategoryMap
     * @throws LogicException if the mapped class does not exist or does not extend AbstractCategory
     */
    protected function getCategory(string $categoryName): Category\AbstractCategory
    {
        if (! isset($this->CategoryPool[$categoryName])) {
            if (! isset($this->CategoryMap[$categoryName])) {
                throw new InvalidArgumentException('Unknown category key "' . $categoryName . '".');
            }

            if (! class_exists($this->CategoryMap[$categoryName]) || ! is_subclass_of($this->CategoryMap[$categoryName], AbstractCategory::class)) {
                throw new LogicException('Category must be mapped to an class extending AbstractCategory.');
            }

            $this->CategoryPool[$categoryName] = new $this->CategoryMap[$categoryName]($this->getDataForPrefix($categoryName));
        }

        return $this->CategoryPool[$categoryName];
    }

    protected function getDataForPrefix(string $prefix): array
    {
        $prefix .= '.';
        $prefixLength = strlen($prefix);
        $data = [];

        foreach ($this->Variables as $key => $value) {
            if (substr($key, 0, $prefixLength) === $prefix) {
                $data[substr($key, $prefixLength)] = $value;
            }
        }

        return $data;
    }

    /**
     * @return Category\ActivityForm This category is not persistent, it's created on each call.
     */
    public function getActivityForm(): Category\ActivityForm
    {
        /** @var Category\ActivityForm $category */
        $category = $this->getCategory('activity-form');

        return $category;
    }

    /**
     * @return Category\ActivityView This category is not persistent, it's created on each call.
     */
    public function getActivityView(): Category\ActivityView
    {
        /** @var Category\ActivityView $category */
        $category = $this->getCategory('activity-view');

        return $category;
    }

    /**
     * @return Category\BasicEndurance This category is not persistent, it's created on each call.
     */
    public function getBasicEndurance(): Category\BasicEndurance
    {
        /** @var Category\BasicEndurance $category */
        $category = $this->getCategory('basic-endurance');

        return $category;
    }

    /**
     * @return Category\Data This category is not persistent, it's created on each call.
     */
    public function getData(): Category\Data
    {
        /** @var Category\Data $category */
        $category = $this->getCategory('data');

        return $category;
    }

    /**
     * @return Category\DataBrowser This category is not persistent, it's created on each call.
     */
    public function getDataBrowser(): Category\DataBrowser
    {
        /** @var Category\DataBrowser $category */
        $category = $this->getCategory('data-browser');

        return $category;
    }

    /**
     * @return Category\Design This category is not persistent, it's created on each call.
     */
    public function getDesign(): Category\Design
    {
        /** @var Category\Design $category */
        $category = $this->getCategory('design');

        return $category;
    }

    /**
     * @return Category\General This category is not persistent, it's created on each call.
     */
    public function getGeneral(): Category\General
    {
        /** @var Category\General $category */
        $category = $this->getCategory('general');

        return $category;
    }

    /**
     * @return Category\Privacy This category is not persistent, it's created on each call.
     */
    public function getPrivacy(): Category\Privacy
    {
        /** @var Category\Privacy $category */
        $category = $this->getCategory('privacy');

        return $category;
    }

    /**
     * @return Category\Trimp This category is not persistent, it's created on each call.
     */
    public function getTrimp(): Category\Trimp
    {
        /** @var Category\Trimp $category */
        $category = $this->getCategory('trimp');

        return $category;
    }

    /**
     * @return Category\VO2max This category is not persistent, it's created on each call.
     */
    public function getVO2max(): Category\VO2max
    {
        /** @var Category\VO2max $category */
        $category = $this->getCategory('vo2max');

        return $category;
    }

    public function getUnitSystem(): UnitSystem
    {
        if (! $this->UnitSystem instanceof UnitSystem) {
            $this->UnitSystem = new UnitSystem($this);
        }

        return $this->UnitSystem;
    }

    /**
     * @return int [bpm]
     */
    public function getMaximalHeartRate(): int
    {
        return (int) $this->Variables['data.HF_MAX'];
    }

    /**
     * @return int [bpm]
     */
    public function getRestingHeartRate(): int
    {
        return (int) $this->Variables['data.HF_REST'];
    }

    public function getVO2maxCorrectionFactor(): float
    {
        if (is_numeric($this->Variables['vo2max.VO2MAX_MANUAL_CORRECTOR'])) {
            return (float) $this->Variables['vo2max.VO2MAX_MANUAL_CORRECTOR'];
        }

        return (float) $this->Variables['data.VO2MAX_CORRECTOR'];
    }

    public function useVO2maxCorrectionForElevation(): bool
    {
        return $this->Variables['vo2max.VO2MAX_USE_CORRECTION_FOR_ELEVATION'] == 'true';
    }

    public function getCurrentVO2maxShape(): float
    {
        if (is_numeric($this->Variables['vo2max.VO2MAX_MANUAL_VALUE'])) {
            return (float) $this->Variables['vo2max.VO2MAX_MANUAL_VALUE'];
        }

        return (float) $this->Variables['data.VO2MAX_FORM'];
    }
}
