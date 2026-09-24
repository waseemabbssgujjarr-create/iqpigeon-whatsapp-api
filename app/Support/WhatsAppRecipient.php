<?php

namespace App\Support;

final class WhatsAppRecipient
{
    /**
     * Meta Cloud API expects the recipient as digits only (E.164 without '+').
     */
    public static function normalizeForGraph(string $to): string
    {
        return preg_replace('/\D+/', '', $to) ?? '';
    }
}
