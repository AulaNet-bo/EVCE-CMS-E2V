<?php

namespace App\Console\Commands;

use App\Models\RfidTag;
use App\Services\SteveDataSource;
use Illuminate\Console\Command;

class SyncSteveTags extends Command
{
    protected $signature = 'steve:sync-tags {--limit=500 : Max tags to process}';

    protected $description = 'Sync RFID tags from Steve source (Redis/MySQL) into CMS rfid_tags table';

    public function handle(SteveDataSource $source): int
    {
        $limit = (int) $this->option('limit');
        $this->info("Starting Steve tag sync ({$source->source()})...");

        $rows = collect($source->getTagsForSync($limit));
        if ($rows->isEmpty()) {
            $this->warn('No tags found in source.');
            return self::SUCCESS;
        }

        $synced = 0;
        foreach ($rows as $row) {
            $tagCode = $row['id_tag'] ?? $row['idTag'] ?? null;
            if (!$tagCode) {
                continue;
            }

            $blocked = (string) ($row['blocked'] ?? '0');
            $isActive = !in_array(strtolower($blocked), ['1', 'true', 'yes'], true);

            $expiresAt = $row['expiry_date'] ?? $row['expiryDate'] ?? null;
            if ($expiresAt === '') {
                $expiresAt = null;
            }

            $name = $row['note'] ?? null;
            if (is_string($name)) {
                // Avoid observer feedback loop: "Synced from CMS - ..."
                $name = preg_replace('/^(Synced from CMS\s*-\s*)+/i', '', $name) ?: null;
                if ($name !== null) {
                    $name = mb_substr($name, 0, 120);
                }
            }

            RfidTag::updateOrCreate(
                ['tag_code' => $tagCode],
                [
                    'name' => $name,
                    'is_active' => $isActive,
                    'expires_at' => $expiresAt,
                ]
            );

            $synced++;
        }

        $this->info("Synced {$synced} RFID tags.");
        return self::SUCCESS;
    }
}
