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
        return view('photo-upload');
    }

    /**
     * Analyze uploaded files and match to IDPEL
     */
    public function analyze(Request $request)
    {
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
}
