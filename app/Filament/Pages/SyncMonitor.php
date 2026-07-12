<?php

namespace App\Filament\Pages;

use App\Models\ChargingSession;
use App\Models\Station;
use App\Services\SteveDataSource;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncMonitor extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?string $navigationLabel = 'Monitor de Sincronización';
    protected static ?string $title = 'Monitor de Sincronización SteVe';

    protected static string $view = 'filament.pages.sync-monitor';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'staff_admin']) ?? false;
    }

    public function getHeaderWidgets(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function() {
                // This is a dummy query because we want to use static data from SteVe
                // Filament tables usually expect an Eloquent query.
                // We'll use a hack to show SteVe data or just use a custom view.
                return ChargingSession::query()->where('id', 0); 
            })
            ->emptyStateHeading('Usa el panel de abajo para ver las transacciones reales en SteVe');
    }

    public function getSteveTransactions(): Collection
    {
        $source = app(\App\Services\SteveDataSource::class);
        $txs = collect($source->getRecentTransactions(20));

        return $txs->map(function($tx) {
            $txId = $tx->transaction_pk ?? $tx->id;
            $cmsSession = ChargingSession::where('transaction_id', (string)$txId)->first();
            
            $collision = false;
            if ($cmsSession) {
                $steveStart = Carbon::parse($tx->start_timestamp);
                $cmsStart = Carbon::parse($cmsSession->start_time);
                if ($steveStart->diffInHours($cmsStart) > 24) {
                    $collision = true;
                }
            }

            return [
                'tx_id' => $txId,
                'tag' => $tx->id_tag,
                'start' => $tx->start_timestamp,
                'stop' => $tx->stop_timestamp,
                'linked' => $cmsSession ? true : false,
                'cms_id' => $cmsSession?->id,
                'cms_status' => $cmsSession?->status,
                'collision' => $collision,
                'energy' => $tx->stop_value ? ($tx->stop_value - $tx->start_value) / 1000 : null,
            ];
        });
    }

    public function getSyncStatus(): array
    {
        $lastMonitor = DB::table('charging_sessions')->max('updated_at');
        
        return [
            'last_monitor' => $lastMonitor ? Carbon::parse($lastMonitor)->diffForHumans() : 'Nunca',
            'active_sessions_cms' => ChargingSession::where('status', 'Active')->count(),
            'starting_sessions_cms' => ChargingSession::where('status', 'Starting')->count(),
        ];
    }
}
