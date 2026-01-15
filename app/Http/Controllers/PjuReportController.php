<?php

namespace App\Http\Controllers;

use App\Models\PjuData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PjuReportController extends Controller
{
    public function index()
    {
        return view('pju-report');
    }

    public function getData(Request $request)
    {
        $limit = $request->get('limit', 100);

        $data = PjuData::select([
            'id',
            'idpel',
            'nama',
            'namapnj',
            'rt',
            'rw',
            'tarif',
            'daya',
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
            'photo',
        ])
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $item->jenis_layanan = $item->jenislayanan ?: ($item->nomor_meter_prepaid ? 'PRABAYAR' : 'PASKABAYAR');
                return $item;
            });

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'idpel' => 'required|string|max:20',
            'nama' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png|max:20480',
        ]);

        $data = $request->except('photo');

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('pju-photos', 'public');
            $data['photo'] = $path;
        }

        $pju = PjuData::create($data);

        return response()->json(['success' => true, 'message' => 'Data successfully added', 'data' => $pju]);
    }

    public function update(Request $request, $id)
    {
        $pju = PjuData::findOrFail($id);

        $request->validate([
            'idpel' => 'required|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png|max:20480',
        ]);

        $data = $request->except('photo');

        if ($request->hasFile('photo')) {
            if ($pju->photo) {
                Storage::disk('public')->delete($pju->photo);
            }
            $path = $request->file('photo')->store('pju-photos', 'public');
            $data['photo'] = $path;
        }

        $pju->update($data);

        return response()->json(['success' => true, 'message' => 'Data successfully updated', 'data' => $pju]);
    }

    public function destroy($id)
    {
        $pju = PjuData::findOrFail($id);

        if ($pju->photo) {
            Storage::disk('public')->delete($pju->photo);
        }

        $pju->delete();

        return response()->json(['success' => true, 'message' => 'Data successfully deleted']);
    }

    /**
     * Import CSV with duplicate detection and auto-delimiter detection
     */
    public function importCsv(Request $request)
    {
        set_time_limit(600); // 10 minutes for large files

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:102400', // 100MB max
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');

        // Read first line to detect delimiter
        $firstLine = fgets($handle);
        rewind($handle);

        // Detect delimiter - check for semicolon or comma
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';

        // Get headers from first row
        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            return response()->json(['success' => false, 'error' => 'Could not read CSV headers']);
        }

        $headers = array_map(function ($h) {
            return strtolower(trim(str_replace(['"', ' '], ['', '_'], $h)));
        }, $headers);

        // Get all existing IDPELs for duplicate checking
        $existingIdpels = PjuData::pluck('idpel')->toArray();
        $existingIdpelsFlipped = array_flip($existingIdpels);

        $imported = 0;
        $duplicates = 0;
        $errors = 0;
        $processed = 0;
        $batchData = [];
        $batchSize = 500; // Insert in batches

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $processed++;

            if (count($row) !== count($headers)) {
                $errors++;
                continue;
            }

            $data = array_combine($headers, $row);

            // Get IDPEL - try common column names
            $idpel = $data['idpel'] ?? $data['id_pel'] ?? $data['idpelanggan'] ?? null;

            if (!$idpel) {
                $errors++;
                continue;
            }

            // Check for duplicate
            if (isset($existingIdpelsFlipped[$idpel])) {
                $duplicates++;
                continue;
            }

            // Parse coordinates - handle various formats
            $koordinatX = $this->parseCoordinate($data['koordinat_x'] ?? $data['x'] ?? $data['longitude'] ?? $data['lon'] ?? null);
            $koordinatY = $this->parseCoordinate($data['koordinat_y'] ?? $data['y'] ?? $data['latitude'] ?? $data['lat'] ?? null);

            // Prepare data for insert
            $batchData[] = [
                'idpel' => $idpel,
                'nama' => $data['nama'] ?? $data['name'] ?? null,
                'namapnj' => $data['namapnj'] ?? $data['nama_pnj'] ?? null,
                'rt' => $data['rt'] ?? null,
                'rw' => $data['rw'] ?? null,
                'tarif' => $data['tarif'] ?? null,
                'daya' => $data['daya'] ?? $data['power'] ?? null,
                'jenislayanan' => $data['jenislayanan'] ?? $data['jenis_layanan'] ?? null,
                'nomor_meter_kwh' => $data['nomor_meter_kwh'] ?? $data['no_meter_kwh'] ?? null,
                'nomor_gardu' => $data['nomor_gardu'] ?? $data['no_gardu'] ?? null,
                'nomor_jurusan_tiang' => $data['nomor_jurusan_tiang'] ?? $data['no_jurusan'] ?? null,
                'nama_gardu' => $data['nama_gardu'] ?? null,
                'nomor_meter_prepaid' => $data['nomor_meter_prepaid'] ?? $data['no_meter_prepaid'] ?? null,
                'koordinat_x' => $koordinatX,
                'koordinat_y' => $koordinatY,
                'kdam' => $data['kdam'] ?? $data['status_meter'] ?? null,
                'nama_kabupaten' => $data['nama_kabupaten'] ?? $data['kabupaten'] ?? null,
                'nama_kecamatan' => $data['nama_kecamatan'] ?? $data['kecamatan'] ?? null,
                'nama_kelurahan' => $data['nama_kelurahan'] ?? $data['kelurahan'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Add to existing list to catch duplicates within same file
            $existingIdpelsFlipped[$idpel] = true;

            // Batch insert
            if (count($batchData) >= $batchSize) {
                try {
                    PjuData::insert($batchData);
                    $imported += count($batchData);
                } catch (\Exception $e) {
                    $errors += count($batchData);
                }
                $batchData = [];
            }
        }

        // Insert remaining data
        if (count($batchData) > 0) {
            try {
                PjuData::insert($batchData);
                $imported += count($batchData);
            } catch (\Exception $e) {
                $errors += count($batchData);
            }
        }

        fclose($handle);

        return response()->json([
            'success' => true,
            'imported' => $imported,
            'duplicates' => $duplicates,
            'errors' => $errors,
            'processed' => $processed,
            'message' => "Successfully imported {$imported} records."
        ]);
    }

    /**
     * Parse coordinate from various formats
     */
    private function parseCoordinate($value)
    {
        if (!$value)
            return null;

        // Remove any non-numeric characters except . - ,
        $value = trim($value);

        // Handle comma as decimal separator
        if (preg_match('/^\d+,\d+$/', $value)) {
            $value = str_replace(',', '.', $value);
        }

        // Handle format like "107°41'35.4" (degrees minutes seconds)
        if (preg_match('/(\d+)[°](\d+)[\'′](\d+\.?\d*)[\"″]?([NSEW])?/i', $value, $matches)) {
            $degrees = floatval($matches[1]);
            $minutes = floatval($matches[2]);
            $seconds = floatval($matches[3]);
            $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

            // Handle south or west
            if (isset($matches[4]) && in_array(strtoupper($matches[4]), ['S', 'W'])) {
                $decimal = -$decimal;
            }
            return $decimal;
        }

        // Standard decimal format
        if (is_numeric($value)) {
            return floatval($value);
        }

        return null;
    }
}
