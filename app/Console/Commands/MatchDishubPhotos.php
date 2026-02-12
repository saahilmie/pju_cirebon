<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PjuData;
use Illuminate\Support\Facades\DB;

class MatchDishubPhotos extends Command
{
    protected $signature = 'dishub:match-photos
        {--dry-run : Show what would be updated without making changes}
        {--limit=0 : Limit number of records to process}';

    protected $description = 'Match Dishub photos from local folder to database records via Google Drive file ID';

    // Path to the photos folder (relative to public/)
    private string $photosDir = 'dishub_photos';

    public function handle()
    {
        $this->info('=== Dishub Photo Matcher ===');
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        if ($dryRun) {
            $this->warn('DRY RUN MODE — no changes will be made');
        }

        // Step 1: Find all records with Google Drive photo URLs
        $query = PjuData::where('photo', 'LIKE', '%drive.google.com%');

        $totalDriveRecords = $query->count();
        $this->info("Records with Google Drive photo URLs: {$totalDriveRecords}");

        if ($totalDriveRecords === 0) {
            $this->warn('No records with Google Drive URLs found. Nothing to match.');
            return 0;
        }

        // Step 2: Check if photos folder/symlink exists
        $photosPath = public_path($this->photosDir);
        if (!is_dir($photosPath)) {
            $this->error("Photos directory not found: {$photosPath}");
            $this->error("Please create a symlink: public/dishub_photos -> D:\\KP\\foto_dishub_download");
            return 1;
        }

        $this->info("Photos directory: {$photosPath}");

        // Step 3: Build an index of available local photos (filename without extension = Drive file ID)
        $this->info('Building local photo index...');
        $localPhotos = [];
        $iterator = new \DirectoryIterator($photosPath);
        foreach ($iterator as $file) {
            if ($file->isDot() || $file->isDir())
                continue;
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                // filename without extension = Drive file ID
                $fileId = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $localPhotos[$fileId] = $file->getFilename();
            }
        }
        $this->info('Local photos indexed: ' . count($localPhotos));

        // Step 4: Process DB records
        $matched = 0;
        $notFound = 0;
        $noId = 0;

        $records = $limit > 0 ? $query->limit($limit)->get() : $query->get();

        $bar = $this->output->createProgressBar($records->count());
        $bar->start();

        $updates = [];

        foreach ($records as $record) {
            $bar->advance();

            // Extract Drive file ID from URL
            $fileId = $this->extractDriveFileId($record->photo);

            if (!$fileId) {
                $noId++;
                continue;
            }

            // Check if we have this photo locally
            if (isset($localPhotos[$fileId])) {
                $newPath = "/{$this->photosDir}/{$localPhotos[$fileId]}";
                $updates[] = [
                    'id' => $record->id,
                    'old_photo' => $record->photo,
                    'new_photo' => $newPath,
                ];
                $matched++;
            } else {
                $notFound++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Step 5: Apply updates
        if (!$dryRun && count($updates) > 0) {
            $this->info("Applying {$matched} photo updates...");
            $bar2 = $this->output->createProgressBar(count($updates));
            $bar2->start();

            // Batch update for performance
            $chunks = array_chunk($updates, 500);
            foreach ($chunks as $chunk) {
                DB::transaction(function () use ($chunk, $bar2) {
                    foreach ($chunk as $update) {
                        PjuData::where('id', $update['id'])
                            ->update(['photo' => $update['new_photo']]);
                        $bar2->advance();
                    }
                });
            }

            $bar2->finish();
            $this->newLine(2);
        }

        // Summary
        $this->newLine();
        $this->info('=== RESULTS ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Drive URL records', $records->count()],
                ['Matched to local photo', $matched],
                ['Photo not found locally', $notFound],
                ['Could not extract ID', $noId],
            ]
        );

        if ($dryRun && $matched > 0) {
            $this->warn("DRY RUN: {$matched} records would be updated.");
            $this->info('Sample updates:');
            foreach (array_slice($updates, 0, 5) as $u) {
                $this->line("  ID {$u['id']}: {$u['new_photo']}");
            }
        }

        return 0;
    }

    /**
     * Extract Google Drive file ID from various URL formats
     */
    private function extractDriveFileId(string $url): ?string
    {
        // Format: https://drive.google.com/thumbnail?id=FILE_ID&sz=w800
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $m)) {
            return $m[1];
        }

        // Format: https://drive.google.com/file/d/FILE_ID/view
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $m)) {
            return $m[1];
        }

        // Format: https://drive.google.com/open?id=FILE_ID
        if (preg_match('/open\?id=([a-zA-Z0-9_-]+)/', $url, $m)) {
            return $m[1];
        }

        return null;
    }
}
