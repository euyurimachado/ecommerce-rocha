<?php

namespace App\Contracts;

use App\Support\CoreUpdates\BackupResult;

interface BackupProvider
{
    /**
     * Create a restore point before an update pipeline changes the application.
     *
     * Implementations must cover the database and may also snapshot uploaded assets.
     */
    public function createRestorePoint(): BackupResult;
}
