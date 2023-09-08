<?php

namespace SbscPackage\Ecommerce\Interfaces;

interface OrderStatusInterface {
    const SHIPPED = "Shipped";
    const DELIVERED = "Delivered";
    const CANCELLED = "Cancelled";
    const PENDING_COMPLETION = "Pending Completion";
    const PENDING_RETURNED = "Pending Returned";
    const RETURNED = "Returned";
    const PROCESSING = "Processing";
    const PENDING = "Pending";
}
