<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\DatabaseCleanup;

abstract class Job
{
    /**
     * @var string[]
     */
    private array $Messages = [];

    protected int $RunningSportId;

    public function __construct(
        private array $RequestData,
        protected \PDO $PDO,
        protected int $AccountId,
        protected string $DatabasePrefix
    ) {
        $this->RunningSportId = \Runalyze\Configuration::General()->runningSport();
    }

    /**
     * Is task requested?
     *
     * @param string $enum
     */
    protected function isRequested($enum): bool
    {
        return isset($this->RequestData[$enum]) && $this->RequestData[$enum] === true;
    }

    abstract public function run();

    final protected function addMessage(string $string)
    {
        $this->Messages[] = $string;
    }

    /**
     * @param mixed $oldValue
     * @param mixed $newValue
     */
    final protected function addSuccessMessage(string $what, $oldValue, $newValue)
    {
        $this->Messages[] = sprintf(
            __('%s has been recalculated. New value: <strong>%s</strong> (old value: %s)'),
            $what,
            $newValue,
            $oldValue
        );
    }

    /**
     * @return string[]
     */
    final public function messages(): array
    {
        return $this->Messages;
    }
}
