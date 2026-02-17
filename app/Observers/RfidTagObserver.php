<?php

namespace App\Observers;

use App\Models\RfidTag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RfidTagObserver
{
    /**
     * Handle the RfidTag "created" event.
     */
    public function created(RfidTag $rfidTag): void
    {
        $this->syncToSteve($rfidTag);
    }

    /**
     * Handle the RfidTag "updated" event.
     */
    public function updated(RfidTag $rfidTag): void
    {
        $this->syncToSteve($rfidTag);
    }

    /**
     * Handle the RfidTag "deleted" event.
     */
    public function deleted(RfidTag $rfidTag): void
    {
        try {
            $table = $this->resolveSteveTagTable();
            if (!$table) {
                Log::error('Steve tag table not found (expected ocpp_tag or tag).');
                return;
            }

            $idColumn = $this->resolveIdColumn($table);
            if (!$idColumn) {
                Log::error('Steve tag id column not found (expected idTag or id_tag).');
                return;
            }

            DB::connection('steve')
                ->table($table)
                ->where($idColumn, $rfidTag->tag_code)
                ->delete();
                
            Log::info("Tag deleted from Steve ({$table}): {$rfidTag->tag_code}");
        } catch (\Exception $e) {
            Log::error("Failed to delete tag from Steve: " . $e->getMessage());
        }
    }

    /**
     * Syncs the tag to Steve DB
     */
    private function syncToSteve(RfidTag $tag): void
    {
        try {
            $table = $this->resolveSteveTagTable();
            if (!$table) {
                Log::error('Steve tag table not found (expected ocpp_tag or tag).');
                return;
            }

            $idColumn = $this->resolveIdColumn($table);
            if (!$idColumn) {
                Log::error('Steve tag id column not found (expected idTag or id_tag).');
                return;
            }

            // Map CMS expiration to Steve format (or null)
            $expiryDate = $tag->expires_at ? $tag->expires_at->format('Y-m-d H:i:s') : null;

            // Build payload safely depending on column existence
            $payload = [];
            $parentCol = $this->resolveParentColumn($table);
            if ($parentCol) {
                $payload[$parentCol] = null;
            }
            $expiryCol = $this->resolveExpiryColumn($table);
            if ($expiryCol) {
                $payload[$expiryCol] = $tag->is_active ? $expiryDate : now()->subDay();
            }
            $maxCol = $this->resolveMaxActiveColumn($table);
            if ($maxCol) {
                $payload[$maxCol] = 1;
            }
            if ($this->hasColumn($table, 'note')) {
                $payload['note'] = 'Synced from CMS - ' . ($tag->name ?? 'User Tag');
            }

            // Using updateOrInsert to handle both new and existing tags
            DB::connection('steve')->table($table)->updateOrInsert(
                [$idColumn => $tag->tag_code],
                $payload
            );

            Log::info("Tag synced to Steve ({$table}): {$tag->tag_code}");

        } catch (\Exception $e) {
            Log::error("Failed to sync tag to Steve: " . $e->getMessage());
        }
    }

    /**
     * Resolve Steve tag table name safely.
     */
    private function resolveSteveTagTable(): ?string
    {
        if (Schema::connection('steve')->hasTable('ocpp_tag')) {
            return 'ocpp_tag';
        }
        if (Schema::connection('steve')->hasTable('tag')) {
            return 'tag';
        }
        return null;
    }

    private function resolveIdColumn(string $table): ?string
    {
        if ($this->hasColumn($table, 'idTag')) return 'idTag';
        if ($this->hasColumn($table, 'id_tag')) return 'id_tag';
        return null;
    }

    private function resolveParentColumn(string $table): ?string
    {
        if ($this->hasColumn($table, 'parentIdTag')) return 'parentIdTag';
        if ($this->hasColumn($table, 'parent_id_tag')) return 'parent_id_tag';
        return null;
    }

    private function resolveExpiryColumn(string $table): ?string
    {
        if ($this->hasColumn($table, 'expiryDate')) return 'expiryDate';
        if ($this->hasColumn($table, 'expiry_date')) return 'expiry_date';
        return null;
    }

    private function resolveMaxActiveColumn(string $table): ?string
    {
        if ($this->hasColumn($table, 'maxActiveTransactionCount')) return 'maxActiveTransactionCount';
        if ($this->hasColumn($table, 'max_active_transaction_count')) return 'max_active_transaction_count';
        return null;
    }

    /**
     * Safe column check.
     */
    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::connection('steve')->hasColumn($table, $column);
        } catch (\Exception $e) {
            Log::warning("Schema check failed for {$table}.{$column}: " . $e->getMessage());
            return false;
        }
    }
}
