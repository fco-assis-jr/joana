<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class JoanaTemp extends Model
{
    protected $connection = 'oracle';
    protected $table = 'joana_temp';
    public $timestamps = false;

    protected $fillable = [
        'uf',
        'chave',
        'numero',
        'serie',
        'emissao',
        'cnpj_emissor',
        'ie_emissor',
        'razao_social',
        'tipo',
        'valor',
        'vl_bc',
        'vl_icms',
        'vl_icms_st',
        'vl_pis',
        'vl_cofins',
        'rejeitada',
        'dtimportacao',
    ];

    protected $casts = [
        'emissao' => 'date',
        'dtimportacao' => 'date',
        'numero' => 'integer',
        'serie' => 'integer',
        'valor' => 'decimal:2',
        'vl_bc' => 'decimal:2',
        'vl_icms' => 'decimal:2',
        'vl_icms_st' => 'decimal:2',
        'vl_pis' => 'decimal:2',
        'vl_cofins' => 'decimal:2',
    ];

    /**
     * Delete all old records for the same cnpj_emissor (regardless of date)
     */
    public static function deleteOldRecords(string $cnpjEmissor)
    {
        return static::where('cnpj_emissor', $cnpjEmissor)->delete();
    }

    /**
     * Check if records exist for cnpj_emissor (regardless of date)
     */
    public static function hasRecords(string $cnpjEmissor): bool
    {
        return static::where('cnpj_emissor', $cnpjEmissor)->exists();
    }

    /**
     * Get count of records for a CNPJ
     */
    public static function getRecordsCount(string $cnpjEmissor): int
    {
        return static::where('cnpj_emissor', $cnpjEmissor)->count();
    }
}
