<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PjuData extends Model
{
    use HasFactory;

    protected $table = 'pju_data';

    protected $fillable = [
        'idpel',
        'nama',
        'namapnj',
        'rt',
        'rw',
        'tarif',
        'daya',
        'jumlah_lampu',
        'jumlah_lampu_source',
        'jenislayanan',
        'nomor_meter_kwh',
        'nomor_gardu',
        'nomor_jurusan_tiang',
        'nama_gardu',
        'nomor_meter_prepaid',
        'koordinat_x',
        'koordinat_y',
        'kdam',
        'nama_kabupaten',
        'nama_kecamatan',
        'nama_kelurahan',
        'no_meter',
        'photo',
        'is_idpel_main',
    ];

    protected $casts = [
        'koordinat_x' => 'decimal:14',
        'koordinat_y' => 'decimal:14',
        'daya' => 'integer',
        'jumlah_lampu' => 'integer',
        'is_idpel_main' => 'boolean',
    ];

    // Auto-calculate jumlah_lampu from daya on create/update
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Only auto-calculate if jumlah_lampu is not manually set
            if ($model->daya && ($model->jumlah_lampu_source !== 'manual' || !$model->jumlah_lampu)) {
                // Formula: daya / 125 (average 125W per lamp)
                $model->jumlah_lampu = max(1, round($model->daya / 125));
                $model->jumlah_lampu_source = 'estimated';
            }
        });
    }

    // Get estimated lamp count from daya
    public function getEstimatedLampCountAttribute(): int
    {
        if (!$this->daya)
            return 0;
        return max(1, round($this->daya / 125));
    }

    // Get status meter label
    public function getStatusMeterAttribute(): string
    {
        return match ($this->kdam) {
            'M' => 'Meterisasi',
            'A' => 'Abonemen',
            default => 'Unclear',
        };
    }

    // Count lamps per IDPEL
    public static function countLampsForIdpel(string $idpel): int
    {
        return static::where('idpel', $idpel)->count();
    }

    // Get formatted address
    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->namapnj,
            $this->rt ? "RT {$this->rt}" : null,
            $this->rw ? "RW {$this->rw}" : null,
            $this->nama_kelurahan,
            $this->nama_kecamatan,
        ]);
        return implode(', ', $parts) ?: '-';
    }

    // Get formatted gardu data
    public function getFormattedGarduAttribute(): string
    {
        $gardu = $this->nomor_gardu ?: '-';
        $nama = $this->nama_gardu ?: '-';
        $jurusan = $this->nomor_jurusan_tiang ?: '-';
        return "{$gardu} / {$nama} ({$jurusan})";
    }
}
