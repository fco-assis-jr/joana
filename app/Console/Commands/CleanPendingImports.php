<?php

namespace App\Console\Commands;

use App\Models\ImportLog;
use Illuminate\Console\Command;

class CleanPendingImports extends Command
{
    protected $signature = 'imports:clean-pending';
    protected $description = 'Remove pending import logs older than 10 minutes';

    public function handle()
    {
        $deleted = ImportLog::where('status', ImportLog::STATUS_PENDING)
            ->where('created_at', '<', now()->subMinutes(10))
            ->delete();

        $this->info("Deleted {$deleted} stuck pending import(s)");

        return 0;
    }
}
