<?php

namespace App\Filament\Widgets;

use App\Models\ChargingSession;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class ChargingStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '3s';

    protected function getStats(): array
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        // Determine dominant currency
        $currency = ChargingSession::latest()->value('currency') ?? 'USD';

        // Live KPIs: include Active + Completed
        $stats = ChargingSession::where('currency', $currency)
            ->whereBetween('updated_at', [$start, $end])
            ->selectRaw('SUM(total_cost) as total_sales, SUM(utility_cost) as total_utility, SUM(margin) as total_profit, SUM(total_energy_kwh) as total_energy')
            ->first();

        $energy = $stats->total_energy ?? 0;

        $sales = $stats->total_sales ?? 0;
        $utility = $stats->total_utility ?? 0;
        $profit = $stats->total_profit ?? 0;
        
        return [
            Stat::make('Revenue (Live This Month)', number_format($sales, 2) . ' ' . $currency)
                ->description('Accrued billed amount (active + completed)')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([$sales * 0.5, $sales * 0.7, $sales]) // Fake chart for now or historical
                ->color('success'),

            Stat::make('Operating Cost (Live This Month)', number_format($utility, 2) . ' ' . $currency)
                ->description('Accrued utility costs')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('danger'),

            Stat::make('Net Profit (Live This Month)', number_format($profit, 2) . ' ' . $currency)
                ->description('Accrued revenue - utility cost')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),
                
            Stat::make('Energy Delivered', number_format($energy, 2) . ' kWh')
                ->description('Total energy dispensed')
                ->descriptionIcon('heroicon-m-battery-100')
                ->color('info'),
        ];
    }
}
