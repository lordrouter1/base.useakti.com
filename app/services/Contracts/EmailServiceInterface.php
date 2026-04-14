<?php

namespace Akti\Services\Contracts;

interface EmailServiceInterface
{
    public function send(string $toEmail, string $toName, string $subject, string $bodyHtml): array;

    public function sendCampaign(int $campaignId): array;

    public function sendTest(int $campaignId, string $testEmail): array;
}
