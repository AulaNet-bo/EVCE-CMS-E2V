<?php

namespace App\Http\Controllers\Api\V1\Sap;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\ChargingSession;
use App\Services\SapExportService;
use Illuminate\Http\Request;

class SapExportController extends Controller
{
    protected SapExportService $sapService;

    public function __construct(SapExportService $sapService)
    {
        $this->sapService = $sapService;
    }

    /**
     * Export data for SAP alignment.
     * 
     * Types: customers, payments, sessions
     */
    public function exportData(Request $request)
    {
        $type = $request->query('type');
        $onlyUnsynced = $request->boolean('only_unsynced', false);

        switch ($type) {
            case 'customers':
                return response()->json($this->sapService->getCustomers($onlyUnsynced));
            case 'payments':
                return response()->json($this->sapService->getPayments($onlyUnsynced));
            case 'sessions':
                return response()->json($this->sapService->getSessions($onlyUnsynced));
            default:
                return response()->json(['error' => 'Invalid export type. Use: customers, payments, or sessions.'], 400);
        }
    }

    /**
     * Mark records as synced.
     */
    public function markSynced(Request $request)
    {
        $request->validate([
            'type' => 'required|in:customers,payments,sessions',
            'ids' => 'required|array',
        ]);

        $type = $request->type;
        $ids = $request->ids;
        $now = now();

        switch ($type) {
            case 'customers':
                User::whereIn('id', $ids)->update(['sap_synced_at' => $now]);
                break;
            case 'payments':
                WalletTransaction::whereIn('id', $ids)->update(['sap_synced_at' => $now]);
                break;
            case 'sessions':
                ChargingSession::whereIn('id', $ids)->update(['sap_synced_at' => $now]);
                break;
        }

        return response()->json(['message' => 'Records marked as synced successfully.']);
    }
}
