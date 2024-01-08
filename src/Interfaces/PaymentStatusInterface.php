<?php

namespace SbscPackage\Ecommerce\Interfaces;

interface PaymentStatusInterface
{
    const SUCCESS = "Successful";
    const FAILED = "Failed";
    const PENDING = "Pending";
}
