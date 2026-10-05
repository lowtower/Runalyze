<?php

namespace Runalyze\Bundle\CoreBundle\Component\Notifications\Message;

use Runalyze\Profile\Notifications\MessageTypeProfile;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Yaml\Yaml;

class TemplateBasedMessage implements MessageInterface
{
    /**
     * @var string base path relative to root
     */
    public const BASE_PATH = '../../../../../data/views/notifications/';

    protected string $TemplateName;

    protected ?array $TemplateContent = null;

    /**
     * @param string $templateName relative to self::BASE_PATH
     * @param null|int $Lifetime [days] (only required for insert progress)
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        string $templateName,
        protected ?int $Lifetime = null
    ) {
        if (! file_exists(__DIR__ . '/' . self::BASE_PATH . $templateName)) {
            throw new \InvalidArgumentException(sprintf('Given template "%s" cannot be found in "%s".', $templateName, __DIR__ . '/' . self::BASE_PATH));
        }

        $this->TemplateName = $templateName;
    }

    public function getMessageType(): int
    {
        return MessageTypeProfile::TEMPLATE_BASED_MESSAGE;
    }

    public function getData(): string
    {
        return $this->TemplateName;
    }

    public function getLifetime(): ?int
    {
        return $this->Lifetime;
    }

    public function getText(?\Symfony\Contracts\Translation\TranslatorInterface $translator = null): string
    {
        $this->loadTemplateIfNotDoneYet();

        return $this->TemplateContent['text'] ?? '';
    }

    public function hasLink(): bool
    {
        $this->loadTemplateIfNotDoneYet();

        return isset($this->TemplateContent['link']) && ! empty($this->TemplateContent['link']);
    }

    public function getLink(?RouterInterface $router = null): string
    {
        $this->loadTemplateIfNotDoneYet();

        return $this->TemplateContent['link'] ?? '';
    }

    protected function loadTemplateIfNotDoneYet()
    {
        $this->TemplateContent ??= Yaml::parse(file_get_contents(__DIR__ . '/' . self::BASE_PATH . $this->TemplateName));
    }

    public function isLinkInternal(): bool
    {
        $this->loadTemplateIfNotDoneYet();

        return isset($this->TemplateContent['internal']) || (isset($this->TemplateContent['link']) && ! str_starts_with($this->TemplateContent['link'], 'http'));
    }

    public function getWindowSizeForInternalLink(): string
    {
        $this->loadTemplateIfNotDoneYet();

        return $this->TemplateContent['window_size'] ?? '';
    }
}
