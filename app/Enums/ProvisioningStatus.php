<?php

namespace App\Enums;

enum ProvisioningStatus: string
{
    case Registered = 'registered';
    case CheckoutPending = 'checkout_pending';
    case PaymentConfirmed = 'payment_confirmed';
    case Provisioning = 'provisioning';
    case Active = 'active';
    case ProvisioningFailed = 'provisioning_failed';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
}
