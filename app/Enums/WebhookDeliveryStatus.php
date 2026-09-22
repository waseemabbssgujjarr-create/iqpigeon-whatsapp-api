<?php

namespace App\Enums;

enum WebhookDeliveryStatus: string
{
    case Pending = 'pending';
    case Delivering = 'delivering';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Dead = 'dead';
}
