<?php

namespace Akti\Services\Contracts;

/**
 * Interface EmailServiceInterface.
 */
interface EmailServiceInterface
{
    /**
     * Envia dados ou notificação.
     *
     * @param string $toEmail To email
     * @param string $toName To name
     * @param string $subject Assunto
     * @param string $bodyHtml Body html
     * @return array
     */
    public function send(string $toEmail, string $toName, string $subject, string $bodyHtml): array;

    /**
     * Envia dados ou notificação.
     *
     * @param int $campaignId Campaign id
     * @return array
     */
    public function sendCampaign(int $campaignId): array;

    /**
     * Envia dados ou notificação.
     *
     * @param int $campaignId Campaign id
     * @param string $testEmail Test email
     * @return array
     */
    public function sendTest(int $campaignId, string $testEmail): array;
}
