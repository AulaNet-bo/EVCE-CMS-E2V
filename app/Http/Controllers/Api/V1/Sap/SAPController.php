<?php

namespace App\Http\Controllers\Api\V1\Sap;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\ChargingSession;
use Illuminate\Http\Request;

class SAPController extends Controller
{
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
                return $this->exportCustomers($onlyUnsynced);
            case 'payments':
                return $this->exportPayments($onlyUnsynced);
            case 'sessions':
                return $this->exportSessions($onlyUnsynced);
            default:
                return response()->json(['error' => 'Invalid export type. Use: customers, payments, or sessions.'], 400);
        }
    }

    private function exportCustomers($onlyUnsynced)
    {
        $query = User::query();
        if ($onlyUnsynced) {
            $query->whereNull('sap_synced_at');
        }

        $users = $query->get()->map(function ($user) {
            return [
                'sap_client_code' => $user->sap_client_code,
                'name' => $user->name,
                'email' => $user->email,
                'razon_social' => $user->billing_razon_social,
                'nit_ci' => $user->billing_document,
                'doc_type' => $user->billing_doc_type,
                'synced_at' => $user->sap_synced_at,
            ];
        });

        return response()->json($users);
    }

    private function exportPayments($onlyUnsynced)
    {
        $query = WalletTransaction::with('user');
        if ($onlyUnsynced) {
            $query->whereNull('sap_synced_at');
        }

        $payments = $query->where('type', 'RECHARGE')
            ->where('status', 'COMPLETED')
            ->get()
            ->map(function ($tx) {
                return [
                    'sap_client_code' => $tx->user->sap_client_code ?? null,
                    'customer_name' => $tx->user->name ?? 'Unknown',
                    'date' => $tx->created_at->toIso8601String(),
                    'amount' => (float) $tx->amount,
                    'currency' => $tx->currency,
                    'payment_method' => $tx->payment_method,
                    'bank_receipt' => $tx->bank_receipt_number,
                    'pos_correlative' => $tx->pos_correlative,
                    'internal_ref' => $tx->id,
                ];
            });

        return response()->json($payments);
    }

    private function exportSessions($onlyUnsynced)
    {
        $query = ChargingSession::with(['user', 'station']);
        if ($onlyUnsynced) {
            $query->whereNull('sap_synced_at');
        }

        $sessions = $query->where('status', 'COMPLETED')
            ->get()
            ->map(function ($session) {
                return [
                    'sap_client_code' => $session->user->sap_client_code ?? null,
                    'customer_name' => $session->user->name ?? 'Unknown',
                    'nit' => $session->user->billing_document ?? null,
                    'razon_social' => $session->user->billing_razon_social ?? null,
                    'station' => $session->station->name ?? 'Unknown',
                    'start_time' => $session->start_time?->toIso8601String(),
                    'end_time' => $session->stop_time?->toIso8601String(),
                    'energy_kwh' => (float) $session->total_energy_kwh,
                    'item_code' => $session->item_code ?? 'EV_CHARGE',
                    'item_description' => $session->item_description ?? 'Suministro de energía eléctrica para vehículo',
                    'price_unit' => $session->rate_kwh,
                    'total_amount' => (float) $session->total_cost,
                    'currency' => $session->currency,
                    'internal_ref' => $session->id,
                ];
            });

        return response()->json($sessions);
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
