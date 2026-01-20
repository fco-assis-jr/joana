<?php

namespace App\Jobs;

use App\Services\CsvImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 3;

    protected $filePath;
    protected $filename;
    protected $importLogId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, string $filename, int $importLogId)
    {
        $this->filePath = $filePath;
        $this->filename = $filename;
        $this->importLogId = $importLogId;
    }

    /**
     * Execute the job.
     */
    public function handle(CsvImportService $importService): void
    {
        try {
            Log::info("Starting import for file: {$this->filename}", ['import_log_id' => $this->importLogId]);

            // Update status to processing
            \App\Models\ImportLog::where('id', $this->importLogId)->update([
                'status' => \App\Models\ImportLog::STATUS_PROCESSING,
            ]);

            $result = $importService->processFile($this->filePath, $this->filename, $this->importLogId);

            Log::info("Import completed for file: {$this->filename}", array_merge($result, ['import_log_id' => $this->importLogId]));

            // Clean up temporary file
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }

        } catch (\Exception $e) {
            Log::error("Job failed for file: {$this->filename}", [
                'import_log_id' => $this->importLogId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update status to failed
            \App\Models\ImportLog::where('id', $this->importLogId)->update([
                'status' => \App\Models\ImportLog::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job permanently failed for file: {$this->filename}", [
            'error' => $exception->getMessage()
        ]);
    }
}
