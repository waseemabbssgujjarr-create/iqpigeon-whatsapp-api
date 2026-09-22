<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';
    case Unpaid = 'unpaid';
    case Incomplete = 'incomplete';
    case IncompleteExpired = 'incomplete_expired';
    case Paused = 'paused';

    public static function fromStripe(string $stripeStatus): self
    {
        return self::tryFrom($stripeStatus) ?? self::Incomplete;
    }

    public function isBillable(): bool
    {
        return in_array($this, [self::Trialing, self::Active, self::PastDue], true);
    }
}
