<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

class DisposableDomainProvider
{
    private ?array $domains = null;

    public function __construct(
        private readonly string $projectDir
    ) {}

    public function getDomains(): array
    {
        if ($this->domains !== null) {
            return $this->domains;
        }

        $filePath = $this->projectDir . '/var/email/disposable-domains.txt';

        if (! file_exists($filePath)) {
            return $this->domains = $this->getFallback();
        }

        $domains = [];

        $file = fopen($filePath, 'r');
        if ($file === false) {
            return $this->domains = $this->getFallback();
        }

        while (($line = fgets($file)) !== false) {
            $domain = trim($line);
            if ($domain !== '') {
                $domains[$domain] = true;
            }
        }

        fclose($file);

        if ($domains === []) {
            return $this->domains = $this->getFallback();
        }

        return $this->domains = $domains;
    }

    /**
     * @return array<string, bool>
     */
    private function getFallback(): array
    {
        return [
            'tempmail.com' => true,
            'guerrillamail.com' => true,
            '10minutemail.com' => true,
            'mailinator.com' => true,
            'temp-mail.org' => true,
            'throwaway.email' => true,
        ];
    }
}
