<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\ChargingSession;
use Illuminate\Support\Carbon;

class SapExportService
{
    public function getCustomers(bool $onlyUnsynced = false)
    {
        $query = User::query();
        if ($onlyUnsynced) {
            $query->whereNull('sap_synced_at');
        }

        return $query->get()->map(function ($user) {
            return [
                'sap_client_code' => $user->billing_document ?: 'not registered',
                'name' => $user->name,
                'email' => $user->email,
                'razon_social' => $user->billing_razon_social,
                'nit_ci' => $user->billing_document,
                'doc_type' => $user->billing_doc_type,
                'company_id' => $user->company_id,
                'company_name' => $user->company?->name,
                'is_admin' => $user->is_admin,
                'balance' => $user->wallet?->balance ?? 0,
                'synced_at' => $user->sap_synced_at,
            ];
        });
    }

    public function getPayments(bool $onlyUnsynced = false)
    {
        $query = WalletTransaction::with(['user', 'wallet.user']);
        if ($onlyUnsynced) {
            $query->whereNull('sap_synced_at');
        }

        $policy = \App\Models\SystemSetting::get()->invoicing_policy ?? 'recharge';

        return $query->where('type', 'RECHARGE')
            ->where('status', 'COMPLETED')
            ->get()
            ->map(function ($tx) use ($policy) {
                $user = $tx->user ?? ($tx->wallet->user ?? null);
                
                // Label for SAP based on policy
                $label = ($policy === 'recharge') ? 'RECARGA_OFICIAL' : 'ANTICIPO_CLIENTE';

                return [
                    'sap_client_code' => $user?->billing_document ?: 'not registered',
                    'customer_name' => $user?->name ?? 'Unknown',
                    'nit' => $user?->billing_document,
                    'date' => $tx->created_at->toIso8601String(),
                    'amount' => (float) $tx->amount,
                    'currency' => $tx->currency,
                    'payment_method' => $tx->payment_method ?? 'LIBELULA',
                    'bank_receipt' => $tx->bank_receipt_number,
                    'pos_correlative' => $tx->pos_correlative,
                    'external_id_pos' => $tx->external_payment_id,
                    'internal_ref' => $tx->id,
                    'transaction_type_label' => $label,
                    'rfid_tag' => $tx->metadata['rfid_tag'] ?? null,
                    'invoice_number' => $tx->invoice_number,
                    'invoice_url' => $tx->invoice_url,
                    'payment_evidence_path' => $tx->payment_evidence_path,
                    'status' => $tx->status,
                ];
            });
    }

    public function getSessions(bool $onlyUnsynced = false)
    {
        $query = ChargingSession::with(['user', 'station']);
        if ($onlyUnsynced) {
            $query->whereNull('sap_synced_at');
        }

        $policy = \App\Models\SystemSetting::get()->invoicing_policy ?? 'recharge';

        return $query->where('status', 'COMPLETED')
            ->get()
            ->map(function ($session) use ($policy) {
                $startTime = $session->start_time;
                if ($startTime && !($startTime instanceof Carbon)) {
                    $startTime = Carbon::parse($startTime);
                }

                $stopTime = $session->stop_time;
                if ($stopTime && !($stopTime instanceof Carbon)) {
                    $stopTime = Carbon::parse($stopTime);
                }

                // Label for SAP based on policy
                $label = ($policy === 'usage') ? 'FACTURA_ENERGIA' : 'CONSUMO_INTERNO';

                return [
                    'sap_client_code' => $session->user->billing_document ?? 'not registered',
                    'customer_name' => $session->user->name ?? 'Unknown',
                    'nit' => $session->user->billing_document ?? null,
                    'razon_social' => $session->user->billing_razon_social ?? null,
                    'station' => $session->station->name ?? 'Unknown',
                    'start_time' => $startTime?->toIso8601String(),
                    'end_time' => $stopTime?->toIso8601String(),
                    'energy_kwh' => (float) $session->total_energy_kwh,
                    'item_code' => $session->item_code ?? 'EV_CHARGE',
                    'item_description' => $session->item_description ?? 'Suministro de energía eléctrica para vehículo',
                    'price_unit' => $session->rate_kwh,
                    'total_amount' => (float) $session->total_cost,
                    'currency' => $session->currency,
                    'internal_ref' => $session->id,
                    'transaction_type_label' => $label,
                    'rfid_tag_id' => $session->rfid_tag_id,
                    'session_fee' => (float) $session->session_fee,
                    'time_fee' => (float) $session->time_fee,
                    'energy_cost' => (float) $session->energy_cost,
                    'utility_cost' => (float) $session->utility_cost,
                    'margin' => (float) $session->margin,
                ];
            });
    }
}
