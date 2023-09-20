<?php

namespace SbscPackage\Ecommerce\Helpers;
use Jenssegers\Agent\Facades\Agent;

use App\Models\AuditLog;

class ProcessAuditLog {

    //Store Audit Log
    public static function storeAuditLog($dataToLog)
    {
        if (!is_null($dataToLog)) {

            $auditLog = AuditLog::create([
                'causer_id' => $dataToLog['causer_id'],
                'action_type' => $dataToLog['action_type'],
                'action_id' => $dataToLog['action_id'],
                'log_name' => $dataToLog['log_name'],
                'action' => $dataToLog['action'],
                'ip_address' => request()->ip(),
                'package_type' => "SbscPackage\Ecommerce",
                'description' => $dataToLog['description']
            ]);
        }
    }

}

