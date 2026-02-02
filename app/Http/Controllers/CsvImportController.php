<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCsvImport;
use App\Models\ImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CsvImportController extends Controller
{
    /**
     * Show the upload form
     */
    public function index()
    {
        $recentImports = ImportLog::orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return view('joana.index', compact('recentImports'));
    }

    /**
     * Handle file uploads
     */
    public function upload(Request $request)
    {
        \Log::info('Upload request received', [
            'has_files' => $request->hasFile('files'),
            'files_count' => $request->hasFile('files') ? count($request->file('files')) : 0,
            'all_input' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'files' => 'required|array|max:11',
            'files.*' => 'required|file|mimes:csv,txt|max:102400', // Max 100MB per file
        ], [
            'files.required' => 'Por favor, selecione pelo menos um arquivo',
            'files.max' => 'Você pode enviar no máximo 11 arquivos por vez',
            'files.*.mimes' => 'Apenas arquivos CSV são permitidos',
            'files.*.max' => 'O tamanho máximo por arquivo é 100MB',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $uploadedFiles = [];
        $errors = [];

        foreach ($request->file('files') as $file) {
            try {
                $originalName = $file->getClientOriginalName();

                \Log::info('Processing file', ['original_name' => $originalName]);

                // Sanitize filename: remove spaces and special characters
                $sanitizedName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $originalName);
                $filename = time() . '_' . uniqid() . '_' . $sanitizedName;

                \Log::info('Sanitized filename', ['sanitized' => $filename]);

                // Build destination path
                $destinationPath = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
                $fullPath = $destinationPath . DIRECTORY_SEPARATOR . $filename;

                \Log::info('Paths', [
                    'destination_dir' => $destinationPath,
                    'full_path' => $fullPath,
                    'dir_exists' => is_dir($destinationPath),
                    'dir_writable' => is_writable($destinationPath)
                ]);

                // Ensure directory exists and is writable
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0775, true);
                    \Log::info('Created temp directory');
                }

                // Move uploaded file
                $moved = $file->move($destinationPath, $filename);

                \Log::info('File moved', [
                    'moved' => $moved !== false,
                    'full_path' => $fullPath,
                    'exists' => file_exists($fullPath),
                    'size' => file_exists($fullPath) ? filesize($fullPath) : 0
                ]);

                // Verify file exists before dispatching
                if (!file_exists($fullPath)) {
                    throw new \Exception("Arquivo não foi salvo corretamente: " . $fullPath);
                }

                // Create pending import log
                $importLog = ImportLog::create([
                    'filename' => $originalName,
                    'status' => ImportLog::STATUS_PENDING,
                    'started_at' => now(),
                ]);

                \Log::info('Import log created', ['log_id' => $importLog->id]);

                // Dispatch job to queue with import log ID
                ProcessCsvImport::dispatch($fullPath, $originalName, $importLog->id);

                \Log::info('Job dispatched', ['file' => $originalName, 'import_log_id' => $importLog->id]);

                $uploadedFiles[] = [
                    'filename' => $originalName,
                    'import_log_id' => $importLog->id,
                    'status' => 'queued'
                ];

            } catch (\Exception $e) {
                \Log::error('Upload error', ['file' => $file->getClientOriginalName(), 'error' => $e->getMessage()]);
                $errors[] = [
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ];
            }
        }

        \Log::info('Upload completed', [
            'uploaded_count' => count($uploadedFiles),
            'errors_count' => count($errors)
        ]);

        return response()->json([
            'success' => true,
            'message' => count($uploadedFiles) . ' arquivo(s) enviado(s) para processamento',
            'files' => $uploadedFiles,
            'errors' => $errors
        ]);
    }

    /**
     * Get import status
     */
    public function status($id)
    {
        $importLog = ImportLog::find($id);

        if (!$importLog) {
            return response()->json([
                'success' => false,
                'message' => 'Log de importação não encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $importLog
        ]);
    }

    /**
     * Get recent imports
     */
    public function recent()
    {
        // Clean up old pending records (older than 10 minutes)
        ImportLog::where('status', ImportLog::STATUS_PENDING)
            ->where('created_at', '<', now()->subMinutes(10))
            ->delete();

        $imports = ImportLog::orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $imports
        ]);
    }
}
