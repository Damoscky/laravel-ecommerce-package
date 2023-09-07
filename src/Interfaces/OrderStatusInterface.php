<?php

namespace SbscPackage\Ecommerce\Interfaces;

interface OrderStatusInterface {
    const SHIPPED = "Shipped";
    const DELIVERED = "Delivered";
    const CANCELLED = "Cancelled";
    const RETURNED = "Returned";
    const PROCESSING = "Processing";
    const PENDING = "Pending";
}
