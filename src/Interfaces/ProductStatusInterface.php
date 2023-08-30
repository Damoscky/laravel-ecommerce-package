<?php

namespace SbscPackage\Ecommerce\Interfaces;

interface ProductStatusInterface {
    const ACTIVE = "Active";
    const INACTIVE = "Inactive";
    const PENDINGAPPROVAL = "Pending Approval";
    const PENDINGDELETE = "Pending Delete";
    const APPROVED = "Approved";
    const DECLINED = "Declined";
}
