<?php

namespace App\Enums;

enum ShippingStatus: string
{
    case Pending = 'pending';
    case LabelCreated = 'label_created';
    case Paid = 'paid';
    case Generated = 'generated';
    case Posted = 'posted';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case DeliveryFailed = 'delivery_failed';
    case Cancelled = 'cancelled';
    case Paused = 'paused';
}
