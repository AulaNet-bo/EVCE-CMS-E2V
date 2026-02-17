<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DebugSteve extends Command
{
    protected $signature = 'debug:steve';
    protected $description = 'Inspect Steve DB';

    public function handle()
    {
        $this->info("--- Tables in Steve DB ---");
        try {
            $tables = DB::connection('steve')->select('SHOW TABLES');
            $tableNames = [];
            foreach ($tables as $t) {
                $val = array_values((array)$t)[0];
                $this->line("- $val");
                $tableNames[] = $val;
            }

            $this->info("\n--- Recent Transactions ---");
            $txs = DB::connection('steve')->table('transaction')
                ->orderBy('start_timestamp', 'desc') // Checking common column names
                ->limit(3)
                ->get();
            
            if ($txs->isEmpty()) {
                // Try checking by ID desc if timestamp fails
                $txs = DB::connection('steve')->table('transaction')->orderBy('transaction_pk', 'desc')->limit(3)->get();
            }

            foreach ($txs as $tx) {
                $this->line("ID: " . ($tx->transaction_pk ?? $tx->id ?? '?') . " | Tag: " . ($tx->id_tag ?? '?') . " | Start: " . ($tx->start_timestamp ?? '?') . " | Stop: " . ($tx->stop_timestamp ?? 'Active'));
            }

            $this->info("\n--- Checking for Meter Values ---");
            // Check likely table names
            $possibleTables = ['connector_meter_value', 'transaction_meter_value', 'metervalue'];
            foreach ($possibleTables as $tbl) {
                if (in_array($tbl, $tableNames)) {
                    $this->info("Found table: $tbl");
                    $count = DB::connection('steve')->table($tbl)->count();
                    $this->line("Total records: $count");
                    
                    $latest = DB::connection('steve')->table($tbl)->limit(3)->get(); // Just get any to see structure
                    $this->table(array_keys((array)($latest->first() ?? [])), $latest->map(fn($r)=>(array)$r));
                }
            }

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
