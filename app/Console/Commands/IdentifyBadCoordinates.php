<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PjuData;
use Illuminate\Support\Facades\DB;

class IdentifyBadCoordinates extends Command
{
    protected $signature = 'pju:identify-bad-coords {--fix : Actually fix the data}';
    protected $description = 'Identify PJU data with bad coordinates and try to infer wilayah from alamat';

    // Mapping alamat keywords to nama_kabupaten
    // NOTE: CILIMUS bisa KAB. KUNINGAN atau KAB. CIREBON - hanya infer jika ada "KUNINGAN" juga
    private $alamatToWilayah = [
        // Kuningan areas (pasti Kuningan)
        'KUNINGAN-CILIMUS' => 'KAB. KUNINGAN',
        'CILIMUS KUNINGAN' => 'KAB. KUNINGAN',
        'KUNINGAN CILIMUS' => 'KAB. KUNINGAN',
        'GARAWANGI' => 'KAB. KUNINGAN',
        'CIWARU' => 'KAB. KUNINGAN',
        'CIGUGUR' => 'KAB. KUNINGAN',
        'KUNINGAN' => 'KAB. KUNINGAN',  // Generic kuningan last (setelah combo patterns)
        'KADUGEDE' => 'KAB. KUNINGAN',
        'LURAGUNG' => 'KAB. KUNINGAN',
        'LEBAKWANGI' => 'KAB. KUNINGAN',
        'JALAKSANA' => 'KAB. KUNINGAN',
        'KRAMATMULYA' => 'KAB. KUNINGAN',
        'GARAJATI' => 'KAB. KUNINGAN',
        'MEKARMULYA' => 'KAB. KUNINGAN',
        'PAJAMBON' => 'KAB. KUNINGAN',
        // Cilimus TANPA Kuningan -> tidak di-infer otomatis (perlu review manual)

        // Cirebon Kabupaten areas
        'PAMULIHAN' => 'KAB. CIREBON',
        'MULIHAN' => 'KAB. CIREBON',
        'SUMBER' => 'KAB. CIREBON',
        'WALED' => 'KAB. CIREBON',
        'CILEDUG' => 'KAB. CIREBON',
        'LOSARI' => 'KAB. CIREBON',
        'ASTANAJAPURA' => 'KAB. CIREBON',
        'BABAKAN' => 'KAB. CIREBON',
        'LEMAHABANG' => 'KAB. CIREBON',
        'KARANGSEMBUNG' => 'KAB. CIREBON',
        'KARANGWARENG' => 'KAB. CIREBON',
        'GEBANG' => 'KAB. CIREBON',
        'PALIMANAN' => 'KAB. CIREBON',
        'PLUMBON' => 'KAB. CIREBON',
        'DEPOK' => 'KAB. CIREBON',
        'WERU' => 'KAB. CIREBON',
        'PLERED' => 'KAB. CIREBON',
        'TENGAHTANI' => 'KAB. CIREBON',
        'KEDAWUNG' => 'KAB. CIREBON',
        'GUNUNGJATI' => 'KAB. CIREBON',
        'KAPETAKAN' => 'KAB. CIREBON',
        'KLANGENAN' => 'KAB. CIREBON',
        'ARJAWINANGUN' => 'KAB. CIREBON',
        'PANGURAGAN' => 'KAB. CIREBON',
        'CIWARINGIN' => 'KAB. CIREBON',
        'SUSUKAN' => 'KAB. CIREBON',
        'SEDONG' => 'KAB. CIREBON',
        'GREGED' => 'KAB. CIREBON',
        'BEBER' => 'KAB. CIREBON',
        'TALUN' => 'KAB. CIREBON',
        'DUKUPUNTANG' => 'KAB. CIREBON',
        'MUNDU' => 'KAB. CIREBON',
        'PANGENAN' => 'KAB. CIREBON',

        // Cirebon Kota areas
        'KESAMBI' => 'KOTA CIREBON',
        'HARJAMUKTI' => 'KOTA CIREBON',
        'LEMAHWUNGKUK' => 'KOTA CIREBON',
        'PEKALIPAN' => 'KOTA CIREBON',
        'KEJAKSAN' => 'KOTA CIREBON',

        // Majalengka areas
        'MAJALENGKA' => 'MAJALENGKA',
        'KADIPATEN' => 'MAJALENGKA',
        'JATIWANGI' => 'MAJALENGKA',
        'LIGUNG' => 'MAJALENGKA',
        'SUMBERJAYA' => 'MAJALENGKA',
        'LEUWIMUNDING' => 'MAJALENGKA',
        'PALASAH' => 'MAJALENGKA',
        'MAJA' => 'MAJALENGKA',

        // Indramayu areas  
        'INDRAMAYU' => 'KAB. INDRAMAYU',
        'JATIBARANG' => 'KAB. INDRAMAYU',
        'KARANGAMPEL' => 'KAB. INDRAMAYU',
        'LOHBENER' => 'KAB. INDRAMAYU',
        'SINDANG' => 'KAB. INDRAMAYU',
        'HAURGEULIS' => 'KAB. INDRAMAYU',
        'KROYA' => 'KAB. INDRAMAYU',
        'GANTAR' => 'KAB. INDRAMAYU',
        'CIKEDUNG' => 'KAB. INDRAMAYU',
        'TERISI' => 'KAB. INDRAMAYU',
        'LELEA' => 'KAB. INDRAMAYU',
        'BANGODUA' => 'KAB. INDRAMAYU',
        'TUKDANA' => 'KAB. INDRAMAYU',
        'WIDASARI' => 'KAB. INDRAMAYU',
        'KERTASEMAYA' => 'KAB. INDRAMAYU',
        'SUKAGUMIWANG' => 'KAB. INDRAMAYU',
        'KANDANGHAUR' => 'KAB. INDRAMAYU',
        'BONGAS' => 'KAB. INDRAMAYU',
        'ANJATAN' => 'KAB. INDRAMAYU',
        'SUKRA' => 'KAB. INDRAMAYU',
        'PATROL' => 'KAB. INDRAMAYU',
        'KEDOKAN BUNDER' => 'KAB. INDRAMAYU',
        'PASEKAN' => 'KAB. INDRAMAYU',
        'CANTIGI' => 'KAB. INDRAMAYU',
        'SLIYEG' => 'KAB. INDRAMAYU',
    ];

    // Valid coordinate bounds for Cirebon area
    private $minLat = -8.5;
    private $maxLat = -5.5;
    private $minLng = 106;
    private $maxLng = 110;

    public function handle()
    {
        $this->info('🔍 Mencari data dengan koordinat aneh...');
        $this->newLine();

        // Find data with bad coordinates
        $badCoords = PjuData::where(function ($query) {
            $query->whereNull('koordinat_x')
                ->orWhereNull('koordinat_y')
                ->orWhere('koordinat_x', 0)
                ->orWhere('koordinat_y', 0)
                ->orWhere('koordinat_x', '<', $this->minLat)
                ->orWhere('koordinat_x', '>', $this->maxLat)
                ->orWhere('koordinat_y', '<', $this->minLng)
                ->orWhere('koordinat_y', '>', $this->maxLng);
        })
            ->select('id', 'idpel', 'koordinat_x', 'koordinat_y', 'nama_kabupaten', 'nama_kecamatan', 'nama_kelurahan', 'namapnj')
            ->get();

        $this->info("📊 Ditemukan {$badCoords->count()} data dengan koordinat aneh:");
        $this->newLine();

        $canFix = [];
        $cannotFix = [];

        foreach ($badCoords as $data) {
            $inferredWilayah = $this->inferWilayahFromAlamat($data);

            if ($inferredWilayah) {
                $canFix[] = [
                    'id' => $data->id,
                    'idpel' => $data->idpel,
                    'koordinat' => "{$data->koordinat_x}, {$data->koordinat_y}",
                    'current_wilayah' => $data->nama_kabupaten ?: '-',
                    'inferred_wilayah' => $inferredWilayah,
                    'alamat' => $this->getAlamatString($data),
                ];
            } else {
                $cannotFix[] = [
                    'id' => $data->id,
                    'idpel' => $data->idpel,
                    'koordinat' => "{$data->koordinat_x}, {$data->koordinat_y}",
                    'current_wilayah' => $data->nama_kabupaten ?: '-',
                    'alamat' => $this->getAlamatString($data),
                ];
            }
        }

        // Show data that can be fixed
        if (count($canFix) > 0) {
            $this->info("✅ Data yang bisa di-infer wilayahnya: " . count($canFix));
            $this->table(
                ['ID', 'IDPEL', 'Koordinat', 'Wilayah Saat Ini', 'Wilayah Inferred', 'Alamat'],
                array_map(fn($d) => [$d['id'], $d['idpel'], $d['koordinat'], $d['current_wilayah'], $d['inferred_wilayah'], substr($d['alamat'], 0, 40)], $canFix)
            );
        }

        $this->newLine();

        // Show data that cannot be fixed
        if (count($cannotFix) > 0) {
            $this->warn("⚠️ Data yang tidak bisa di-infer (perlu review manual): " . count($cannotFix));
            $this->table(
                ['ID', 'IDPEL', 'Koordinat', 'Wilayah Saat Ini', 'Alamat'],
                array_map(fn($d) => [$d['id'], $d['idpel'], $d['koordinat'], $d['current_wilayah'], substr($d['alamat'], 0, 50)], $cannotFix)
            );
        }

        // If --fix flag is passed, actually update the data
        if ($this->option('fix') && count($canFix) > 0) {
            $this->newLine();
            if ($this->confirm('Apakah anda yakin ingin update nama_kabupaten untuk data yang bisa di-infer?')) {
                $updated = 0;
                foreach ($canFix as $data) {
                    PjuData::where('id', $data['id'])->update([
                        'nama_kabupaten' => $data['inferred_wilayah']
                    ]);
                    $updated++;
                }
                $this->info("✅ Berhasil update {$updated} data!");
            }
        }

        $this->newLine();
        $this->info('📝 Ringkasan:');
        $this->line("   Total data koordinat aneh: {$badCoords->count()}");
        $this->line("   Bisa di-infer: " . count($canFix));
        $this->line("   Perlu review manual: " . count($cannotFix));

        if (!$this->option('fix') && count($canFix) > 0) {
            $this->newLine();
            $this->comment('💡 Jalankan dengan --fix untuk update data yang bisa di-infer:');
            $this->comment('   php artisan pju:identify-bad-coords --fix');
        }

        return 0;
    }

    private function inferWilayahFromAlamat($data): ?string
    {
        // Combine all address fields
        $alamat = strtoupper(implode(' ', array_filter([
            $data->nama_kecamatan,
            $data->nama_kelurahan,
            $data->namapnj,
        ])));

        // Check each keyword
        foreach ($this->alamatToWilayah as $keyword => $wilayah) {
            if (str_contains($alamat, $keyword)) {
                return $wilayah;
            }
        }

        return null;
    }

    private function getAlamatString($data): string
    {
        return implode(', ', array_filter([
            $data->namapnj,
            $data->nama_kecamatan,
            $data->nama_kelurahan,
        ]));
    }
}
