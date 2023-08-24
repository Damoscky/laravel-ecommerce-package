<?php

namespace App\Interfaces;

interface EmployeeStatusInterface
{
    const APPROVED = "Approved";
    const ACTIVE = "Active";
    const PENDING = "Pending";
    const DECLINED = "Declined";
    const PROBATION = "Probation";
    const EXITED = "Exited";
    const RETURNING = "Returning";
}
