<?php

namespace App\Http\Controllers;

use App\Models\PjuData;
use App\Events\PjuDataUpdated;
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
        $search = $request->get('search', '');
        $regionals = $request->get('regional') ? explode(',', $request->get('regional')) : [];
        $statuses = $request->get('status') ? explode(',', $request->get('status')) : [];
        $idpels = $request->get('idpel') ? explode(',', $request->get('idpel')) : [];

        $query = PjuData::select([
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
            'is_idpel_main',
        ]);

        // Server-side Regional filter
        if (!empty($regionals)) {
            $query->where(function ($q) use ($regionals) {
                foreach ($regionals as $regional) {
                    if ($regional === 'No Regional') {
                        $q->orWhereNull('nama_kabupaten')
                          ->orWhere('nama_kabupaten', '');
                    } else {
                        $coreName = strtoupper($regional);
                        $coreName = str_replace(['KAB. ', 'KOTA '], '', $coreName);
                        $q->orWhere('nama_kabupaten', 'ILIKE', '%' . $coreName . '%');
                    }
                }
            });
        }

        // Server-side Status filter
        if (!empty($statuses)) {
            $query->where(function ($q) use ($statuses) {
                $directStatuses = array_filter($statuses, fn($s) => $s !== 'Unclear');
                if (!empty($directStatuses)) {
                    $q->orWhereIn('kdam', $directStatuses);
                }
                if (in_array('Unclear', $statuses)) {
                    $q->orWhereNull('kdam')
                      ->orWhere('kdam', '');
                }
            });
        }

        // Server-side IDPEL filter
        if (!empty($idpels)) {
            $query->where(function ($q) use ($idpels) {
                foreach ($idpels as $idpel) {
                    if ($idpel === 'NO_IDPEL') {
                        $q->orWhere('idpel', 'LIKE', '% - %');
                    } else {
                        $q->orWhere('idpel', $idpel);
                    }
                }
            });
        }

        // If search parameter is provided, search across ALL important fields
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('idpel', 'ILIKE', "%{$search}%")
                    ->orWhere('nama', 'ILIKE', "%{$search}%")
                    ->orWhere('namapnj', 'ILIKE', "%{$search}%")
                    ->orWhere('nama_gardu', 'ILIKE', "%{$search}%")
                    ->orWhere('nomor_gardu', 'ILIKE', "%{$search}%")
                    ->orWhere('nomor_jurusan_tiang', 'ILIKE', "%{$search}%")
                    ->orWhere('nama_kabupaten', 'ILIKE', "%{$search}%")
                    ->orWhere('nama_kecamatan', 'ILIKE', "%{$search}%")
                    ->orWhere('nama_kelurahan', 'ILIKE', "%{$search}%")
                    ->orWhere('nomor_meter_kwh', 'ILIKE', "%{$search}%")
                    ->orWhere('nomor_meter_prepaid', 'ILIKE', "%{$search}%");
            });
            // When searching, don't apply limit or apply higher limit
            $limit = min($request->get('limit', 1000), 5000);
        }

        $data = $query->limit($limit)
            ->get()
            ->map(function ($item) {
                $item->jenis_layanan = $item->jenislayanan ?: ($item->nomor_meter_prepaid ? 'PRABAYAR' : 'PASKABAYAR');
                return $item;
            });

        return response()->json(['data' => $data, 'search' => $search, 'total' => $data->count()]);
    }

    /**
     * Export ALL PJU data to Excel (no limit)
     */
    public function exportExcel(Request $request)
    {
        // Get ALL data without limit
        $query = PjuData::select([
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
            'is_idpel_main',
        ]);

        // Apply filters if provided
        $region = $request->get('region');
        $status = $request->get('status');
        $search = $request->get('search');

        if ($region) {
            $query->where('nama_kabupaten', $region);
        }
        if ($status) {
            if (strtolower($status) === 'unclear') {
                // Unclear = no KDAM or has generated IDPEL (pattern: "code - area / region")
                $query->where(function ($q) {
                    $q->whereNull('kdam')
                        ->orWhere('kdam', '')
                        ->orWhereNotIn('kdam', ['M', 'A'])
                        ->orWhere('idpel', 'LIKE', '% - %'); // Generated IDPEL pattern
                });
            } else {
                $query->where('kdam', $status);
            }
        }
        if ($search) {
            $query->where('idpel', 'LIKE', "%{$search}%");
        }

        // Remove $data = $query->get() to prevent memory exhaustion

        // Create CSV response (more efficient for large data)
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="pju_data_export_' . date('Y-m-d_His') . '.csv"',
        ];

        $columns = [
            'IDPEL',
            'Nama',
            'Alamat',
            'RT',
            'RW',
            'Tarif',
            'Daya',
            'Jenis Layanan',
            'No Meter KWH',
            'No Gardu',
            'No Tiang',
            'Nama Gardu',
            'No Meter Prepaid',
            'Latitude',
            'Longitude',
            'Status (KDAM)',
            'Wilayah Dishub',
            'Kecamatan',
            'Kelurahan',
            'Photo',
            'Is Main IDPEL'
        ];

        $callback = function () use ($query, $columns) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel to recognize UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($file, $columns);

            // Data rows (use chunking to save memory)
            $query->chunk(500, function ($data) use ($file) {
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->idpel,
                        $row->nama,
                        $row->namapnj,
                        $row->rt,
                        $row->rw,
                        $row->tarif,
                        $row->daya,
                        $row->jenislayanan ?: ($row->nomor_meter_prepaid ? 'PRABAYAR' : 'PASKABAYAR'),
                        $row->nomor_meter_kwh,
                        $row->nomor_gardu,
                        $row->nomor_jurusan_tiang,
                        $row->nama_gardu,
                        $row->nomor_meter_prepaid,
                        $row->koordinat_x,
                        $row->koordinat_y,
                        $row->kdam,
                        $row->nama_kabupaten,
                        $row->nama_kecamatan,
                        $row->nama_kelurahan,
                        $row->photo ? 'Yes' : 'No',
                        $row->is_idpel_main ? 'Yes' : 'No',
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isAdmin()) return response()->json(['success' => false, 'message' => 'Unauthorized action. Employees are read-only.'], 403);


        $request->validate([
            'idpel' => 'required|string|max:255',
            'nama' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png|max:20480',
            'is_idpel_main' => 'nullable|boolean',
        ]);

        $data = $request->except('photo');

        // Handle is_idpel_main - ensure only one per IDPEL
        $isMain = $request->input('is_idpel_main') == '1' || $request->input('is_idpel_main') === true;
        if ($isMain) {
            // Unset any existing IDPEL Main for this IDPEL
            PjuData::where('idpel', $request->idpel)
                ->where('is_idpel_main', true)
                ->update(['is_idpel_main' => false]);
            $data['is_idpel_main'] = true;
        } else {
            $data['is_idpel_main'] = false;
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('pju-photos', 'public');
            $data['photo'] = $path;
        }

        $pju = PjuData::create($data);

        // Broadcast event for real-time updates (don't let broadcast failures break the save)
        try {
            event(new PjuDataUpdated('created', $pju->idpel, auth()->user()->name, $pju->id));
        } catch (\Exception $e) {
            \Log::warning('Broadcast failed for PJU create: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Data successfully added', 'data' => $pju]);
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->isAdmin()) return response()->json(['success' => false, 'message' => 'Unauthorized action. Employees are read-only.'], 403);


        $pju = PjuData::findOrFail($id);

        $request->validate([
            'idpel' => 'required|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png|max:20480',
            'is_idpel_main' => 'nullable|boolean',
        ]);

        $data = $request->except('photo');

        // Handle is_idpel_main - ensure only one per IDPEL
        $isMain = $request->input('is_idpel_main') == '1' || $request->input('is_idpel_main') === true;
        if ($isMain) {
            // Unset any existing IDPEL Main for this IDPEL (except current)
            PjuData::where('idpel', $request->idpel)
                ->where('id', '!=', $id)
                ->where('is_idpel_main', true)
                ->update(['is_idpel_main' => false]);
            $data['is_idpel_main'] = true;
        } else {
            $data['is_idpel_main'] = false;
        }

        if ($request->hasFile('photo')) {
            if ($pju->photo) {
                Storage::disk('public')->delete($pju->photo);
            }
            $path = $request->file('photo')->store('pju-photos', 'public');
            $data['photo'] = $path;
        }

        $pju->update($data);

        // Broadcast event for real-time updates (don't let broadcast failures break the save)
        try {
            event(new PjuDataUpdated('updated', $pju->idpel, auth()->user()->name, $pju->id));
        } catch (\Exception $e) {
            \Log::warning('Broadcast failed for PJU update: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Data successfully updated', 'data' => $pju]);
    }

    public function destroy($id)
    {
        if (!auth()->user()->isAdmin()) return response()->json(['success' => false, 'message' => 'Unauthorized action. Employees are read-only.'], 403);


        $pju = PjuData::findOrFail($id);
        $idpel = $pju->idpel;

        if ($pju->photo) {
            Storage::disk('public')->delete($pju->photo);
        }

        $pju->delete();

        // Broadcast event for real-time updates (don't let broadcast failures break the delete)
        try {
            event(new PjuDataUpdated('deleted', $idpel, auth()->user()->name, $id));
        } catch (\Exception $e) {
            \Log::warning('Broadcast failed for PJU delete: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Data successfully deleted']);
    }

    /**
     * Update only the photo for a PJU record (for employees)
     */
    public function updatePhoto(Request $request, $id)
    {
        if (!auth()->user()->isAdmin()) return response()->json(['success' => false, 'message' => 'Unauthorized action. Employees are read-only.'], 403);


        $pju = PjuData::findOrFail($id);

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png|max:20480',
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($pju->photo) {
                Storage::disk('public')->delete($pju->photo);
            }
            // Store new photo
            $path = $request->file('photo')->store('pju-photos', 'public');
            $pju->photo = $path;
            $pju->save();

            // Broadcast event for real-time updates
            try {
                event(new PjuDataUpdated('photo_uploaded', $pju->idpel, auth()->user()->name, $pju->id));
            } catch (\Exception $e) {
                \Log::warning('Broadcast failed for PJU photo update: ' . $e->getMessage());
            }
        }

        return response()->json(['success' => true, 'message' => 'Photo uploaded successfully', 'data' => $pju]);
    }

    /**
     * Smart Import CSV with flexible column mapping and update-empty-fields mode
     * - Auto-detects column names from various formats
     * - Updates only empty fields for existing IDPEL records
     * - Reports which columns were recognized vs ignored
     */
    public function importCsv(Request $request)
    {
        if (!auth()->user()->isAdmin()) return response()->json(['success' => false, 'message' => 'Unauthorized action. Employees are read-only.'], 403);


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

        $originalHeaders = $headers; // Keep original for reporting
        $headers = array_map(function ($h) {
            return strtolower(trim(str_replace(['"', ' '], ['', '_'], $h)));
        }, $headers);

        // Define column mappings (flexible names -> database field)
        $columnMappings = [
            'idpel' => ['idpel', 'id_pel', 'idpelanggan', 'id_pelanggan', 'no_idpel'],
            'nama' => ['nama', 'name', 'nama_pelanggan'],
            'namapnj' => ['namapnj', 'nama_pnj', 'alamat', 'address'],
            'rt' => ['rt'],
            'rw' => ['rw'],
            'tarif' => ['tarif', 'tariff'],
            'daya' => ['daya', 'power', 'watt'],
            'jenislayanan' => ['jenislayanan', 'jenis_layanan', 'layanan', 'service'],
            'nomor_meter_kwh' => ['nomor_meter_kwh', 'no_meter_kwh', 'meter_kwh', 'kwh'],
            'nomor_gardu' => ['nomor_gardu', 'no_gardu', 'gardu'],
            'nomor_jurusan_tiang' => ['nomor_jurusan_tiang', 'no_jurusan', 'jurusan_tiang', 'tiang'],
            'nama_gardu' => ['nama_gardu'],
            'nomor_meter_prepaid' => ['nomor_meter_prepaid', 'no_meter_prepaid', 'prepaid'],
            'koordinat_x' => ['koordinat_x', 'x', 'longitude', 'lon', 'lng', 'long'],
            'koordinat_y' => ['koordinat_y', 'y', 'latitude', 'lat'],
            'kdam' => ['kdam', 'status_meter', 'status', 'meterisasi'],
            'nama_kabupaten' => ['nama_kabupaten', 'kabupaten', 'kab', 'nama_kab', 'wilayah', 'region'],
            'nama_kecamatan' => ['nama_kecamatan', 'kecamatan', 'kec', 'nama_kec'],
            'nama_kelurahan' => ['nama_kelurahan', 'kelurahan', 'kel', 'nama_kel', 'desa'],
        ];

        // Track which columns are recognized
        $recognizedColumns = [];
        $unrecognizedColumns = [];

        foreach ($headers as $i => $header) {
            $found = false;
            foreach ($columnMappings as $dbField => $aliases) {
                if (in_array($header, $aliases)) {
                    $recognizedColumns[$header] = $dbField;
                    $found = true;
                    break;
                }
            }
            if (!$found && !empty($header)) {
                $unrecognizedColumns[] = $originalHeaders[$i] ?? $header;
            }
        }

        // Get all existing data for update mode
        $existingData = PjuData::whereNotNull('idpel')->get()->keyBy('idpel');

        $imported = 0;
        $updated = 0;
        $duplicates = 0;
        $errors = 0;
        $processed = 0;
        $batchData = [];
        $batchSize = 500;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $processed++;

            // Handle column count mismatch
            $headerCount = count($headers);
            $rowCount = count($row);

            if ($rowCount < $headerCount) {
                $row = array_pad($row, $headerCount, '');
            } elseif ($rowCount > $headerCount) {
                $row = array_slice($row, 0, $headerCount);
            }

            $data = array_combine($headers, $row);

            // Get IDPEL
            $idpel = null;
            foreach ($columnMappings['idpel'] as $alias) {
                if (isset($data[$alias]) && !empty(trim($data[$alias]))) {
                    $idpel = trim($data[$alias]);
                    break;
                }
            }

            // Prepare new data from CSV
            $newData = $this->extractDataFromRow($data, $columnMappings);

            // Check if IDPEL exists - UPDATE EMPTY FIELDS instead of skipping
            if ($idpel && isset($existingData[$idpel])) {
                $existing = $existingData[$idpel];
                $fieldsToUpdate = [];

                // Only update fields that are currently empty/null in database
                foreach ($newData as $field => $value) {
                    if ($value !== null && $value !== '') {
                        $existingValue = $existing->$field;
                        if ($existingValue === null || $existingValue === '') {
                            $fieldsToUpdate[$field] = $value;
                        }
                    }
                }

                // Update if there are empty fields to fill
                if (!empty($fieldsToUpdate)) {
                    try {
                        PjuData::where('idpel', $idpel)->update($fieldsToUpdate);
                        $updated++;
                    } catch (\Exception $e) {
                        \Log::error('Update error: ' . $e->getMessage());
                        $errors++;
                    }
                } else {
                    $duplicates++; // No changes needed
                }
                continue;
            }

            // New record - prepare for batch insert
            $newData['idpel'] = $idpel;
            $newData['created_at'] = now();
            $newData['updated_at'] = now();
            $batchData[] = $newData;

            // Batch insert
            if (count($batchData) >= $batchSize) {
                $imported += $this->batchInsertWithFallback($batchData);
                $batchData = [];
            }
        }

        // Insert remaining data
        if (count($batchData) > 0) {
            $imported += $this->batchInsertWithFallback($batchData);
        }

        fclose($handle);

        return response()->json([
            'success' => true,
            'imported' => $imported,
            'updated' => $updated,
            'duplicates' => $duplicates,
            'errors' => $errors,
            'processed' => $processed,
            'recognized_columns' => array_values(array_unique($recognizedColumns)),
            'unrecognized_columns' => $unrecognizedColumns,
            'message' => "Imported {$imported} new, updated {$updated} existing records."
        ]);
    }

    /**
     * Extract data from row based on column mappings
     */
    private function extractDataFromRow($data, $columnMappings)
    {
        $result = [];

        foreach ($columnMappings as $dbField => $aliases) {
            if ($dbField === 'idpel')
                continue; // Handle separately

            $value = null;
            foreach ($aliases as $alias) {
                if (isset($data[$alias]) && !empty(trim($data[$alias]))) {
                    $value = trim($data[$alias]);
                    break;
                }
            }

            // Special handling for coordinates
            if (in_array($dbField, ['koordinat_x', 'koordinat_y'])) {
                $value = $this->parseCoordinate($value);
            }

            // Special handling for daya (power)
            if ($dbField === 'daya' && $value !== null) {
                $value = (int) preg_replace('/[^\d]/', '', $value);
                if ($value > 2147483647)
                    $value = null;
            }

            $result[$dbField] = $value;
        }

        return $result;
    }

    /**
     * Batch insert with fallback to single inserts
     */
    private function batchInsertWithFallback($batchData)
    {
        $imported = 0;
        try {
            PjuData::insert($batchData);
            $imported = count($batchData);
        } catch (\Exception $e) {
            \Log::warning('Batch insert failed, falling back to single inserts');
            foreach ($batchData as $row) {
                try {
                    PjuData::insert([$row]);
                    $imported++;
                } catch (\Exception $e2) {
                    \Log::error('Single insert error: ' . $e2->getMessage());
                }
            }
        }
        return $imported;
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

    /**
     * Bulk Update KDAM status for selected IDs
     */
    public function bulkUpdate(Request $request)
    {
        if (!auth()->user()->isAdmin()) return response()->json(['success' => false, 'message' => 'Unauthorized action. Employees are read-only.'], 403);

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:pju_data,id',
            'kdam' => 'nullable|string|in:M,A',
        ]);

        $ids = $request->ids;
        $kdam = $request->kdam;
        $updatedCount = 0;

        // Iterate instead of mass-update to track the color markers based on previous kdam
        foreach ($ids as $id) {
            $pju = PjuData::find($id);
            if (!$pju) continue;

            $oldKdam = $pju->kdam;
            
            // Set new KDAM
            $pju->kdam = $kdam;

            // Set color marker logic
            if ($kdam === 'M') {
                if (empty($oldKdam)) {
                    $pju->update_color_marker = 'orange'; // From Unclear to Meter
                } elseif ($oldKdam === 'A') {
                    $pju->update_color_marker = 'purple'; // From Abodemen to Meter
                }
            } else {
                // If they update to Abodemen or Unclear, we can reset or leave it. 
                // Let's reset the color marker to null.
                $pju->update_color_marker = null;
            }

            $pju->save();
            $updatedCount++;
        }

        // Trigger real-time event for refresh
        try {
            event(new PjuDataUpdated('bulk_updated', 'multiple', auth()->user()->name, 0));
        } catch (\Exception $e) {
            \Log::warning('Broadcast failed for PJU bulk update: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true, 
            'message' => "Successfully updated {$updatedCount} records."
        ]);
    }

    /**
     * Bulk Delete for selected IDs
     */
    public function bulkDelete(Request $request)
    {
        if (!auth()->user()->isAdmin()) return response()->json(['success' => false, 'message' => 'Unauthorized action. Employees are read-only.'], 403);

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:pju_data,id',
        ]);

        $ids = $request->ids;
        $deletedCount = 0;

        foreach ($ids as $id) {
            $pju = PjuData::find($id);
            if (!$pju) continue;

            if ($pju->photo) {
                Storage::disk('public')->delete($pju->photo);
            }
            $pju->delete();
            $deletedCount++;
        }

        try {
            event(new PjuDataUpdated('bulk_deleted', 'multiple', auth()->user()->name, 0));
        } catch (\Exception $e) {
            \Log::warning('Broadcast failed for PJU bulk delete: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true, 
            'message' => "Successfully deleted {$deletedCount} records."
        ]);
    }
}
