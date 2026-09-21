<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\DatabaseCleanup;

abstract class Job
{
    /**
     * @var string[]
     */
    private $Messages = [];

    /**
     * @var int
     */
    protected $RunningSportId;

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
     * @return bool
     */
    protected function isRequested($enum)
    {
        return isset($this->RequestData[$enum]) && $this->RequestData[$enum] === true;
    }

    /**
     * Run job
     */
    abstract public function run();

    /**
     * @param string $string
     */
    final protected function addMessage($string)
    {
        $this->Messages[] = $string;
    }

    /**
     * Add message
     *
     * @param string $what
     * @param mixed $oldValue
     * @param mixed $newValue
     */
    final protected function addSuccessMessage($what, $oldValue, $newValue)
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
    final public function messages()
    {
        return $this->Messages;
    }
}
