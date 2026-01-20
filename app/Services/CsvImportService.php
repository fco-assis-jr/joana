<?php

namespace App\Services;

use App\Models\JoanaTemp;
use App\Models\ImportLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CsvImportService
{
    protected $importDate;
    protected $importLog;

    public function __construct()
    {
        $this->importDate = Carbon::now();
    }

    /**
     * Process a CSV file
     */
    public function processFile(string $filePath, string $filename, int $importLogId): array
    {
        // Get existing import log
        $this->importLog = ImportLog::find($importLogId);

        if (!$this->importLog) {
            throw new Exception("Import log not found: {$importLogId}");
        }

        try {
            // Open and read CSV file
            $handle = fopen($filePath, 'r');

            if ($handle === false) {
                throw new Exception("Não foi possível abrir o arquivo");
            }

            // Skip the first line (sep=;)
            fgets($handle);

            // Read header
            $header = fgetcsv($handle, 0, ';');

            if ($header === false) {
                throw new Exception("Arquivo CSV inválido");
            }

            $totalRows = 0;
            $importedRows = 0;
            $cnpjsProcessed = [];
            $rowsData = [];

            // Read data rows
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $totalRows++;

                // Parse row data
                $data = $this->parseRow($row);

                if ($data) {
                    $cnpjEmissor = $data['cnpj_emissor'];

                    // Check if we need to delete old records for this CNPJ
                    // Delete ALL records for this CNPJ, regardless of date
                    if (!in_array($cnpjEmissor, $cnpjsProcessed)) {
                        if (JoanaTemp::hasRecords($cnpjEmissor)) {
                            $deletedCount = JoanaTemp::deleteOldRecords($cnpjEmissor);
                            Log::info("Deleted {$deletedCount} old records for CNPJ: {$cnpjEmissor}");
                        }
                        $cnpjsProcessed[] = $cnpjEmissor;
                    }

                    $rowsData[] = $data;

                    // Batch insert every 100 rows for better performance
                    if (count($rowsData) >= 100) {
                        $this->batchInsert($rowsData);
                        $importedRows += count($rowsData);
                        $rowsData = [];
                    }
                }
            }

            // Insert remaining rows
            if (!empty($rowsData)) {
                $this->batchInsert($rowsData);
                $importedRows += count($rowsData);
            }

            fclose($handle);

            // Update import log
            $this->importLog->update([
                'status' => ImportLog::STATUS_COMPLETED,
                'total_rows' => $totalRows,
                'imported_rows' => $importedRows,
                'completed_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => "Arquivo processado com sucesso",
                'total_rows' => $totalRows,
                'imported_rows' => $importedRows,
                'import_log_id' => $this->importLog->id,
            ];

        } catch (Exception $e) {
            Log::error("Import error: " . $e->getMessage());

            if ($this->importLog) {
                $this->importLog->update([
                    'status' => ImportLog::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            }

            return [
                'success' => false,
                'message' => "Erro ao processar arquivo: " . $e->getMessage(),
                'import_log_id' => $this->importLog ? $this->importLog->id : null,
            ];
        }
    }

    /**
     * Parse a CSV row into database format
     */
    protected function parseRow(array $row): ?array
    {
        try {
            // Map CSV columns to database fields based on the header structure
            return [
                'uf' => $row[0] ?? null,
                'chave' => $this->cleanString($row[1] ?? null),
                'numero' => $this->cleanNumber($row[2] ?? null),
                'serie' => $this->cleanNumber($row[3] ?? null),
                'emissao' => $this->parseDate($row[4] ?? null),
                'cnpj_emissor' => $this->cleanString($row[5] ?? null),
                'ie_emissor' => $this->cleanString($row[6] ?? null),
                'razao_social' => $row[7] ?? null,
                // Skip columns 8-10 (CNPJ-CPF DESTINATARIO, IE DESTINATARIO, RAZAO SOCIAL)
                // Column 11 is CFOP (skip)
                // Column 12 is SELAGEM (skip)
                // Column 13 is SITUACAO (skip)
                'tipo' => $row[14] ?? null,
                'valor' => $this->cleanDecimal($row[15] ?? null),
                'vl_bc' => $this->cleanDecimal($row[16] ?? null),
                'vl_icms' => $this->cleanDecimal($row[17] ?? null),
                'vl_icms_st' => $this->cleanDecimal($row[18] ?? null),
                'vl_pis' => $this->cleanDecimal($row[19] ?? null),
                'vl_cofins' => $this->cleanDecimal($row[20] ?? null),
                'rejeitada' => $row[21] ?? 'N',
                'dtimportacao' => $this->importDate,
            ];
        } catch (Exception $e) {
            Log::warning("Error parsing row: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Batch insert rows
     */
    protected function batchInsert(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        DB::connection('oracle')->table('joana_temp')->insert($rows);
    }

    /**
     * Clean string values (remove quotes, etc)
     */
    protected function cleanString(?string $value): ?string
    {
        if (empty($value) || $value === 'null') {
            return null;
        }

        return trim(str_replace("'", "", $value));
    }

    /**
     * Clean numeric values
     */
    protected function cleanNumber(?string $value): ?int
    {
        if (empty($value) || $value === 'null') {
            return null;
        }

        return (int) $value;
    }

    /**
     * Clean decimal values
     */
    protected function cleanDecimal(?string $value): ?float
    {
        if (empty($value) || $value === 'null') {
            return null;
        }

        // Replace comma with period for decimal
        $value = str_replace(',', '.', $value);
        return (float) $value;
    }

    /**
     * Parse date from DD/MM/YYYY format
     */
    protected function parseDate(?string $value): ?string
    {
        if (empty($value) || $value === 'null') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('d/m/Y', $value);
            return $date->format('Y-m-d');
        } catch (Exception $e) {
            Log::warning("Error parsing date: {$value}");
            return null;
        }
    }
}
