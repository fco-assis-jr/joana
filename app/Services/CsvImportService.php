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
    protected $tableColumns = [];
    protected $csvHeaderMap = [];

    public function __construct()
    {
        $this->importDate = Carbon::now();
        $this->loadTableColumns();
    }

    /**
     * Load Oracle table columns
     */
    protected function loadTableColumns(): void
    {
        try {
            // Get columns from Oracle table
            $columns = DB::connection('oracle')
                ->select("SELECT COLUMN_NAME FROM USER_TAB_COLUMNS WHERE TABLE_NAME = 'JOANA_TEMP' ORDER BY COLUMN_ID");

            foreach ($columns as $column) {
                $this->tableColumns[] = strtolower($column->column_name);
            }

            Log::info("Oracle table columns loaded", ['columns' => $this->tableColumns]);
        } catch (Exception $e) {
            Log::error("Error loading table columns: " . $e->getMessage());
            // Fallback to hardcoded columns if query fails
            $this->tableColumns = [
                'uf', 'chave', 'numero', 'serie', 'emissao', 'cnpj_emissor',
                'ie_emissor', 'razao_social', 'tipo', 'valor', 'vl_bc',
                'vl_icms', 'vl_icms_st', 'vl_pis', 'vl_cofins', 'rejeitada', 'dtimportacao'
            ];
        }
    }

    /**
     * Normalize column name (convert spaces to underscores, lowercase)
     */
    protected function normalizeColumnName(string $name): string
    {
        // Remove quotes, trim, convert to lowercase, replace spaces with underscore
        $normalized = strtolower(trim(str_replace(["'", '"'], '', $name)));
        $normalized = str_replace(' ', '_', $normalized);

        return $normalized;
    }

    /**
     * Map CSV header to Oracle columns
     */
    protected function mapCsvHeader(array $header): void
    {
        $this->csvHeaderMap = [];

        foreach ($header as $index => $columnName) {
            $normalized = $this->normalizeColumnName($columnName);

            // Check if this column exists in Oracle table
            if (in_array($normalized, $this->tableColumns)) {
                $this->csvHeaderMap[$index] = $normalized;
                Log::info("CSV column mapped", ['csv' => $columnName, 'oracle' => $normalized, 'index' => $index]);
            } else {
                Log::info("CSV column skipped (not in Oracle table)", ['csv' => $columnName, 'normalized' => $normalized]);
            }
        }

        Log::info("CSV header mapping complete", ['total_mapped' => count($this->csvHeaderMap)]);
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

            // Map CSV header to Oracle columns
            $this->mapCsvHeader($header);

            if (empty($this->csvHeaderMap)) {
                throw new Exception("Nenhuma coluna do CSV corresponde às colunas da tabela Oracle");
            }

            $totalRows = 0;
            $importedRows = 0;
            $cnpjsProcessed = [];
            $rowsData = [];

            // Read data rows
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $totalRows++;

                // Parse row data dynamically based on header mapping
                $data = $this->parseRowDynamic($row);

                if ($data) {
                    // Check for CNPJ to handle deletion of old records
                    $cnpjEmissor = $data['cnpj_emissor'] ?? null;

                    if ($cnpjEmissor && !in_array($cnpjEmissor, $cnpjsProcessed)) {
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
     * Parse a CSV row dynamically based on header mapping
     */
    protected function parseRowDynamic(array $row): ?array
    {
        try {
            $data = [];

            // Map each CSV column to Oracle column based on header mapping
            foreach ($this->csvHeaderMap as $csvIndex => $oracleColumn) {
                $value = $row[$csvIndex] ?? null;

                // Apply data type conversions based on column name
                $data[$oracleColumn] = $this->convertValue($oracleColumn, $value);
            }

            // Always add dtimportacao if not present
            if (!isset($data['dtimportacao'])) {
                $data['dtimportacao'] = $this->importDate;
            }

            // Set default values for required fields if not mapped
            if (!isset($data['rejeitada'])) {
                $data['rejeitada'] = 'N';
            }

            return $data;

        } catch (Exception $e) {
            Log::warning("Error parsing row: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Convert value based on column type
     */
    protected function convertValue(string $columnName, ?string $value)
    {
        // Handle null/empty values
        if (empty($value) || $value === 'null' || $value === '') {
            return null;
        }

        // Date columns
        if (in_array($columnName, ['emissao', 'dtimportacao'])) {
            return $this->parseDate($value);
        }

        // Integer columns
        if (in_array($columnName, ['numero', 'serie'])) {
            return $this->cleanNumber($value);
        }

        // Decimal columns
        if (in_array($columnName, ['valor', 'vl_bc', 'vl_icms', 'vl_icms_st', 'vl_pis', 'vl_cofins'])) {
            return $this->cleanDecimal($value);
        }

        // String columns (clean quotes)
        if (in_array($columnName, ['chave', 'cnpj_emissor', 'ie_emissor'])) {
            return $this->cleanString($value);
        }

        // Default: return as is (trimmed)
        return trim($value);
    }

    /**
     * Parse a CSV row into database format (DEPRECATED - kept for reference)
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
