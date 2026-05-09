<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Http;

use APNTalk\FreeSwitchXmlProjection\Enum\DirectoryAction;
use APNTalk\FreeSwitchXmlProjection\Enum\DirectoryPurpose;
use APNTalk\FreeSwitchXmlProjection\Enum\XmlCurlSection;

final readonly class XmlCurlRequest
{
    /**
     * @param array<string, scalar|null> $raw
     * @param array<string, scalar|null> $redacted
     * @param array<string, ?string> $normalized
     */
    public function __construct(
        private array $raw,
        private array $redacted,
        private array $normalized,
    ) {}

    /**
     * @return array<string, scalar|null>
     */
    public function raw(): array
    {
        return $this->raw;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function redacted(): array
    {
        return $this->redacted;
    }

    public function section(): ?XmlCurlSection
    {
        return XmlCurlSection::tryFromNormalized($this->normalized['section'] ?? null);
    }

    public function purpose(): ?DirectoryPurpose
    {
        return DirectoryPurpose::tryFromNormalized($this->normalized['purpose'] ?? null);
    }

    public function action(): ?DirectoryAction
    {
        $action = $this->normalized['action'] ?? $this->normalized['Action'] ?? null;

        return DirectoryAction::tryFromNormalized($action);
    }

    public function user(): ?string
    {
        return $this->normalized['user'] ?? $this->normalized['sip_auth_username'] ?? null;
    }

    public function domain(): ?string
    {
        return $this->normalized['domain'] ?? $this->normalized['sip_auth_realm'] ?? null;
    }

    public function profile(): ?string
    {
        return $this->normalized['profile'] ?? $this->normalized['sip_profile'] ?? null;
    }

    public function ip(): ?string
    {
        return $this->normalized['ip'] ?? null;
    }

    public function freeSwitchHostname(): ?string
    {
        return $this->normalized['FreeSWITCH-Hostname'] ?? $this->normalized['hostname'] ?? null;
    }

    public function sipUserAgent(): ?string
    {
        return $this->normalized['sip_user_agent'] ?? null;
    }

    public function sipAuthUsername(): ?string
    {
        return $this->normalized['sip_auth_username'] ?? null;
    }

    public function sipAuthRealm(): ?string
    {
        return $this->normalized['sip_auth_realm'] ?? null;
    }

    public function isDirectory(): bool
    {
        return $this->section() === XmlCurlSection::Directory;
    }
}
