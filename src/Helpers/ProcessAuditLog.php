<?php

namespace SbscPackage\Ecommerce\Helpers;

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
                'package_type' => "SbscPackage\Ecommerce",
                'description' => $dataToLog['description']
            ]);
        }
    }

}

