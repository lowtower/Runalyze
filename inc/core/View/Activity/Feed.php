<?php

namespace Runalyze\View\Activity;

use PicoFeed\Syndication\Rss20FeedBuilder;
use PicoFeed\Syndication\Rss20ItemBuilder;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Services\Activity\ActivityContextFactory;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Twig\ValueExtension;
use Runalyze\Util\LocalTime;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Feed
{
    /**
     * @var array
     */
    protected $Activities;

    /**
     * @var Rss20FeedBuilder
     */
    protected $FeedBuilder;

    /**
     * @var \Symfony\Contracts\Translation\TranslatorInterface
     */
    protected $Translator;

    /**
     * @var ActivityContextFactory
     */
    protected $ActivityContextFactory;

    /**
     * @var UrlGeneratorInterface
     */
    protected $UrlGenerator;

    /**
     * @var ConfigurationManager
     */
    protected $ConfigurationManager;

    /**
     * @var UnitSystem
     */
    protected $UnitSystem;

    public function __construct(\Symfony\Contracts\Translation\TranslatorInterface $translator, ActivityContextFactory $activityContextFactory, UrlGeneratorInterface $urlGenerator, ConfigurationManager $configurationManager)
    {
        $this->FeedBuilder = new Rss20FeedBuilder();
        $this->ActivityContextFactory = $activityContextFactory;
        $this->Translator = $translator;
        $this->UrlGenerator = $urlGenerator;
        $this->ConfigurationManager = $configurationManager;
        $this->FeedBuilder->withDate(new \DateTime());
        $this->UnitSystem = $configurationManager->getList()
            ->getUnitSystem();
    }

    public function setTranslator(\Symfony\Contracts\Translation\TranslatorInterface $translator): void
    {
        $this->Translator = $translator;
    }

    /**
     * @return string
     */
    public function buildFeed()
    {
        $this->createItems();
        return $this->FeedBuilder->build();
    }

    /**
     * @param array $activities
     */
    public function setActivities($activities): self
    {
        $this->Activities = $activities;
        return $this;
    }

    public function setFeedAuthor($author): self
    {
        $this->FeedBuilder->withAuthor($author);
        return $this;
    }

    /**
     * @param string $title
     */
    public function setFeedTitle($title): self
    {
        $this->FeedBuilder->withTitle($title);
        return $this;
    }

    /**
     * @param string $feed
     */
    public function setFeedUrl($feed): self
    {
        $this->FeedBuilder->withFeedUrl($feed);
        return $this;
    }

    /**
     * @param string $site
     */
    public function setSiteUrl($site): self
    {
        $this->FeedBuilder->withSiteUrl($site);
        return $this;
    }

    public function setFeedDate(\DateTime $date): self
    {
        $this->FeedBuilder->withDate($date);
        return $this;
    }

    private function createItemContent(Training $activity, ValueExtension $valueDecorator): string
    {
        $content = '<b>' . $this->Translator->trans('Sport') . '</b>: ' . $activity->getSport()->getName();

        if ($activity->getType() !== null) {
            $content .= '<br><b>' . $this->Translator->trans('Activity type') . '</b>: ' . $activity->getType()->getName();
        }

        $content .= '<br><b>' . $this->Translator->trans('Date') . '</b>: ' . (new LocalTime($activity->getTime()))->format('d.m.Y');
        $content .= '<br><b>' . $this->Translator->trans('Duration') . '</b>: ' . (new \DateTime())->setTimezone(new \DateTimeZone('UTC'))->setTimestamp($activity->getS())->format('H:i:s');

        if ($activity->getDistance()) {
            $content .= '<br><b>' . $this->Translator->trans('Distance') . '</b>: ' . $valueDecorator->distance($activity->getDistance());
            $content .= '<br><b>' . $this->Translator->trans('Pace') . '</b>: ' . $valueDecorator->pace($activity->getS() / $activity->getDistance(), $activity->getSport()->getSpeedUnit());
        }

        if ($activity->getNotes()) {
            $content .= '<br><b>' . $this->Translator->trans('Notes') . '</b>:<br>' . $activity->getNotes();
        }

        if ($activity->isPublic()) {
            $content .= '<br><a href="' . $this->UrlGenerator->generate('shared-activity', [
                'activityHash' => base_convert((int) $activity->getId(), 10, 35),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $content .= '?utm_medium=feed&utm_campaign=feed">' . $this->Translator->trans('View full activity') . '</a>';
        }

        return $content;
    }

    private function createItem(Training $activity): void
    {
        $item = new Rss20ItemBuilder($this->FeedBuilder);
        $valueDecorator = new ValueExtension($this->ConfigurationManager);

        $item->withTitle($this->getFeedTitle($activity, $valueDecorator));
        $item->withPublishedDate(new LocalTime($activity->getTime()));
        $item->withContent($this->createItemContent($activity, $valueDecorator));
        $item->withAuthor($activity->getAccount()->getUsername());

        if ($activity->isPublic()) {
            $item->withUrl($this->UrlGenerator->generate('shared-activity', [
                'activityHash' => base_convert((int) $activity->getId(), 10, 35),
            ], UrlGeneratorInterface::ABSOLUTE_URL) . '?utm_medium=feed&utm_campaign=feed');
        }

        $this->FeedBuilder->withItem($item);
    }

    private function getFeedTitle(Training $activity, ValueExtension $valueDecorator): string
    {
        $title = $activity->getSport()
            ->getName();

        if ($activity->getType() !== null) {
            $title .= ' (' . $activity->getType()->getName() . ')';
        }

        $title .= ': ' . (new \DateTime())->setTimezone(new \DateTimeZone('UTC'))->setTimestamp($activity->getS())->format('H:i:s') . 'h';

        if ($activity->getDistance() > 0) {
            $title .= ' - ' . str_replace('&nbsp;', ' ', $valueDecorator->distance($activity->getDistance()));
        }

        if ($activity->getTitle() != '') {
            $title .= ' - ' . $activity->getTitle();
        }

        return $title;
    }

    private function createItems(): void
    {
        if ($this->Activities) {
            foreach ($this->Activities as $activity) {
                $this->createItem($activity);
            }
        }
    }
}
