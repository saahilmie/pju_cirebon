<?php

namespace App\Http\Controllers;

use App\Models\PjuData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Events\PjuDataUpdated;

class PhotoUploadController extends Controller
{
    /**
     * Show the bulk photo upload page
     */
    public function index()
    {
        if (!auth()->user()->isAdmin()) return abort(403, 'Unauthorized action. Employees are read-only.');
        return view('photo-upload');
    }

    /**
     * Analyze uploaded files and match to IDPEL
     */
    public function analyze(Request $request)
    {
        if (!auth()->user()->isAdmin()) return response()->json(['success' => false, 'message' => 'Unauthorized action. Employees are read-only.'], 403);

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|image|mimes:jpeg,png,jpg|max:20480',
        ]);

        $results = [];

        foreach ($request->file('files') as $file) {
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $parsed = $this->parseFilename($filename);

            $matchResult = $this->findMatch($parsed);

            // Store temp file
            $tempPath = $file->store('temp-uploads', 'public');

            $results[] = [
                'original_name' => $file->getClientOriginalName(),
                'temp_path' => $tempPath,
                'parsed' => $parsed,
                'match' => $matchResult,
            ];
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Process confirmed uploads
     */
    public function process(Request $request)
    {
        if (!auth()->user()->isAdmin()) return response()->json(['success' => false, 'message' => 'Unauthorized action. Employees are read-only.'], 403);

        $request->validate([
            'items' => 'required|array',
            'items.*.temp_path' => 'required|string',
            'items.*.action' => 'required|in:attach,duplicate,skip',
            'items.*.target_id' => 'nullable|integer',
            'items.*.parsed' => 'required|array',
        ]);

        $processed = 0;
        $duplicated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($request->items as $item) {
            try {
                if ($item['action'] === 'skip') {
                    // Delete temp file
                    Storage::disk('public')->delete($item['temp_path']);
                    $skipped++;
                    continue;
                }

                // Move from temp to permanent
                $tempPath = $item['temp_path'];
                $ext = pathinfo($tempPath, PATHINFO_EXTENSION) ?: 'jpg';
                $newPath = 'pju-photos/' . uniqid() . '.' . $ext;

                Storage::disk('public')->move($tempPath, $newPath);

                if ($item['action'] === 'attach') {
                    // Attach to existing record
                    $pju = PjuData::findOrFail($item['target_id']);

                    // Delete old photo if exists
                    if ($pju->photo) {
                        Storage::disk('public')->delete($pju->photo);
                    }

                    $pju->photo = $newPath;
                    $pju->save();

                    event(new PjuDataUpdated('photo_uploaded', $pju->idpel, auth()->user()->name, $pju->id));
                    $processed++;

                } elseif ($item['action'] === 'duplicate') {
                    // Find source record to duplicate
                    $sourceId = $item['target_id'];
                    $source = PjuData::findOrFail($sourceId);

                    // Create duplicate
                    $newRecord = $source->replicate();
                    $newRecord->is_idpel_main = false;
                    $newRecord->photo = $newPath;

                    // Use coordinates from parsed filename if available
                    $parsed = $item['parsed'];
                    if (!empty($parsed['lat']) && !empty($parsed['lng'])) {
                        $newRecord->koordinat_x = $parsed['lat'];
                        $newRecord->koordinat_y = $parsed['lng'];
                    } else {
                        // Generate nearby coordinates
                        $offset = (mt_rand(-100, 100) / 1000000);
                        $newRecord->koordinat_x = $source->koordinat_x ? $source->koordinat_x + $offset : null;
                        $newRecord->koordinat_y = $source->koordinat_y ? $source->koordinat_y + $offset : null;
                        // Add note about generated coordinates
                        $newRecord->nama = ($source->nama ?: '') . ' [Estimated coordinates]';
                    }

                    $newRecord->save();

                    event(new PjuDataUpdated('created', $newRecord->idpel, auth()->user()->name, $newRecord->id));
                    $duplicated++;
                }

            } catch (\Exception $e) {
                $errors[] = [
                    'file' => $item['temp_path'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Clean up any remaining temp files
        $tempFiles = Storage::disk('public')->files('temp-uploads');
        foreach ($tempFiles as $file) {
            // Delete files older than 1 hour
            if (Storage::disk('public')->lastModified($file) < now()->subHour()->timestamp) {
                Storage::disk('public')->delete($file);
            }
        }

        return response()->json([
            'success' => true,
            'processed' => $processed,
            'duplicated' => $duplicated,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }

    /**
     * Parse filename to extract IDPEL, sequence number, and coordinates
     * 
     * Patterns supported:
     * - 533113714188.jpg (IDPEL only - main)
     * - 533113714188(1).jpg (IDPEL with sequence - cabang)
     * - 533113714188_-6.767,108.556.jpg (IDPEL with coordinates)
     * - 533113714188(2)_-6.767,108.556.jpg (IDPEL with sequence and coordinates)
     */
    private function parseFilename(string $filename): array
    {
        $result = [
            'idpel' => null,
            'sequence' => null, // null = main, 1,2,3... = cabang
            'lat' => null,
            'lng' => null,
        ];

        // Pattern: {IDPEL}({n})_{lat},{lng}
        $pattern = '/^(\d{9,15})(?:\((\d+)\))?(?:_(-?\d+\.?\d*),(-?\d+\.?\d*))?/';

        if (preg_match($pattern, $filename, $matches)) {
            $result['idpel'] = $matches[1] ?? null;
            $result['sequence'] = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : null;
            $result['lat'] = isset($matches[3]) && $matches[3] !== '' ? (float) $matches[3] : null;
            $result['lng'] = isset($matches[4]) && $matches[4] !== '' ? (float) $matches[4] : null;
        }

        return $result;
    }

    /**
     * Find matching record in database
     */
    private function findMatch(array $parsed): array
    {
        if (empty($parsed['idpel'])) {
            return [
                'status' => 'not_found',
                'message' => 'Cannot parse IDPEL from filename',
                'records' => [],
            ];
        }

        // Find records with this IDPEL
        $records = PjuData::where('idpel', $parsed['idpel'])
            ->orderByDesc('is_idpel_main')
            ->get(['id', 'idpel', 'nama', 'koordinat_x', 'koordinat_y', 'is_idpel_main', 'photo']);

        if ($records->isEmpty()) {
            return [
                'status' => 'not_found',
                'message' => 'IDPEL not found in database',
                'records' => [],
            ];
        }

        // Determine action based on sequence
        if ($parsed['sequence'] === null) {
            // Main photo - attach to main record
            $mainRecord = $records->firstWhere('is_idpel_main', true) ?? $records->first();
            return [
                'status' => 'match',
                'message' => 'IDPEL Main - will attach to record',
                'action' => 'attach',
                'target_id' => $mainRecord->id,
                'records' => $records->toArray(),
            ];
        } else {
            // Cabang photo - duplicate from main
            $mainRecord = $records->firstWhere('is_idpel_main', true) ?? $records->first();
            return [
                'status' => 'duplicate',
                'message' => "Branch #{$parsed['sequence']} - will duplicate from main record",
                'action' => 'duplicate',
                'target_id' => $mainRecord->id,
                'records' => $records->toArray(),
            ];
        }
    }

    /**
     * Show the bulk status update page
     */
    public function bulkStatusIndex()
    {
        if (!auth()->user()->isAdmin()) return abort(403, 'Unauthorized action.');
        return view('bulk-status-update');
    }

    /**
     * Analyze uploaded CSV/Excel file for bulk status update
     * Auto-detects IDPEL and KDAM columns
     */
    public function bulkStatusAnalyze(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:102400',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
            return response()->json(['success' => false, 'message' => 'Invalid file type. Only CSV and Excel files are supported.']);
        }

        try {
            // Read file using PhpSpreadsheet
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (count($rows) < 2) {
                return response()->json(['success' => false, 'message' => 'File is empty or has no data rows.']);
            }

            // Get headers and auto-detect columns
            $headers = array_map(function ($h) {
                return strtolower(trim(str_replace(['"', ' '], ['', '_'], $h ?? '')));
            }, $rows[0]);

            $idpelCol = $this->findColumnIndex($headers, ['idpel', 'id_pel', 'idpelanggan', 'id_pelanggan', 'no_idpel']);
            $kdamCol = $this->findColumnIndex($headers, ['kdam', 'status', 'status_kdam']);

            if ($idpelCol === null) {
                return response()->json(['success' => false, 'message' => 'Could not find IDPEL column. Please make sure your file has a column named "IDPEL".']);
            }

            // Parse data rows
            $records = [];
            for ($i = 1; $i < count($rows); $i++) {
                $idpel = trim($rows[$i][$idpelCol] ?? '');
                if (empty($idpel)) continue;

                // Clean IDPEL - remove any spaces
                $idpel = preg_replace('/\s+/', '', $idpel);

                $kdam = $kdamCol !== null ? strtoupper(trim($rows[$i][$kdamCol] ?? '')) : 'M';

                // Validate KDAM value
                if (!in_array($kdam, ['M', 'A'])) {
                    $kdam = 'M'; // Default to M if invalid
                }

                $records[] = ['idpel' => $idpel, 'kdam' => $kdam];
            }

            if (empty($records)) {
                return response()->json(['success' => false, 'message' => 'No valid data found in the file.']);
            }

            // Check against database
            $uniqueIdpels = array_unique(array_column($records, 'idpel'));
            $existingRecords = PjuData::whereIn('idpel', $uniqueIdpels)
                ->select('idpel', 'kdam')
                ->get()
                ->keyBy('idpel');

            $willUpdate = 0;
            $alreadySame = 0;
            $notFound = 0;
            $notFoundIdpels = [];

            foreach ($records as $record) {
                $existing = $existingRecords->get($record['idpel']);
                if (!$existing) {
                    $notFound++;
                    $notFoundIdpels[] = $record['idpel'];
                } elseif (strtoupper($existing->kdam) === $record['kdam']) {
                    $alreadySame++;
                } else {
                    $willUpdate++;
                }
            }

            // Store parsed data in temp file for processing
            $fileKey = 'bulk-status-' . uniqid();
            $tempPath = storage_path('app/temp/' . $fileKey . '.json');
            if (!is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            file_put_contents($tempPath, json_encode($records));

            return response()->json([
                'success' => true,
                'file_key' => $fileKey,
                'preview' => [
                    'total' => count($records),
                    'will_update' => $willUpdate,
                    'already_same' => $alreadySame,
                    'not_found' => $notFound,
                    'not_found_idpels' => array_unique($notFoundIdpels),
                    'kdam_col_found' => $kdamCol !== null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error reading file: ' . $e->getMessage()]);
        }
    }

    /**
     * Process the bulk status update
     */
    public function bulkStatusProcess(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'file_key' => 'required|string',
        ]);

        $fileKey = $request->file_key;
        $tempPath = storage_path('app/temp/' . $fileKey . '.json');

        if (!file_exists($tempPath)) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please upload the file again.']);
        }

        try {
            $records = json_decode(file_get_contents($tempPath), true);

            $updated = 0;
            $skipped = 0;
            $notFoundIdpels = [];

            // Group records by target KDAM value for efficient batch updates
            $updateGroups = [];
            $allIdpels = array_unique(array_column($records, 'idpel'));

            // Get existing records
            $existingRecords = PjuData::whereIn('idpel', $allIdpels)
                ->select('idpel', 'kdam')
                ->get()
                ->keyBy('idpel');

            foreach ($records as $record) {
                $existing = $existingRecords->get($record['idpel']);
                if (!$existing) {
                    $notFoundIdpels[] = $record['idpel'];
                    continue;
                }

                if (strtoupper($existing->kdam) === $record['kdam']) {
                    $skipped++;
                    continue;
                }

                // Group by target KDAM for batch update
                if (!isset($updateGroups[$record['kdam']])) {
                    $updateGroups[$record['kdam']] = [];
                }
                $updateGroups[$record['kdam']][] = $record['idpel'];
            }

            // Execute batch updates
            foreach ($updateGroups as $kdam => $idpels) {
                $count = PjuData::whereIn('idpel', $idpels)->update(['kdam' => $kdam]);
                $updated += $count;
            }

            // Clean up temp file
            @unlink($tempPath);

            // Broadcast event
            try {
                event(new PjuDataUpdated('bulk_status_updated', "Bulk: {$updated} records", auth()->user()->name, null));
            } catch (\Exception $e) {
                \Log::warning('Broadcast failed for bulk status update: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'updated' => $updated,
                'skipped' => $skipped,
                'not_found_idpels' => array_unique($notFoundIdpels),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error processing update: ' . $e->getMessage()]);
        }
    }

    /**
     * Find column index by checking multiple possible names
     */
    private function findColumnIndex(array $headers, array $possibleNames): ?int
    {
        foreach ($headers as $index => $header) {
            if (in_array($header, $possibleNames)) {
                return $index;
            }
        }
        return null;
    }
}

