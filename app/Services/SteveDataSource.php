<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SteveDataSource
{
    public function source(): string
    {
        return config('steve.data_source', 'redis');
    }

    public function usingRedis(): bool
    {
        return $this->source() === 'redis';
    }

    public function redisPrefix(): string
    {
        return config('steve.redis_prefix', 'steve');
    }

    public function getConnectorsWithStatus(): array
    {
        if (!$this->usingRedis()) {
            return DB::connection('steve')
                ->table('connector')
                ->join('charge_box', 'connector.charge_box_id', '=', 'charge_box.charge_box_id')
                ->select(
                    'charge_box.charge_box_id',
                    'connector.connector_id',
                    'charge_box.last_heartbeat_timestamp',
                    'connector.connector_pk'
                )
                ->get()
                ->map(function ($row) {
                    $row->status = $this->getLatestConnectorStatus((int) $row->connector_pk) ?? 'Unknown';
                    return $row;
                })
                ->all();
        }

        $prefix = $this->redisPrefix();
        $keys = Redis::smembers("{$prefix}:index:connectors");
        $rows = [];

        foreach ($keys as $key) {
            $connector = Redis::hgetall($key);
            if (!$connector) {
                continue;
            }

            $connectorPk = (int) ($connector['connector_pk'] ?? 0);
            $chargeBoxId = $connector['charge_box_id'] ?? null;
            $connectorId = (int) ($connector['connector_id'] ?? 0);

            $chargeBox = $chargeBoxId ? Redis::hgetall("{$prefix}:charge_box:{$chargeBoxId}") : [];
            $statusRow = $connectorPk ? Redis::hgetall("{$prefix}:connector_status:{$connectorPk}") : [];

            $obj = (object) [
                'charge_box_id' => $chargeBoxId,
                'connector_id' => $connectorId,
                'last_heartbeat_timestamp' => $chargeBox['last_heartbeat_timestamp'] ?? null,
                'connector_pk' => $connectorPk,
                'status' => $statusRow['status'] ?? 'Unknown',
            ];
            $rows[] = $obj;
        }

        return $rows;
    }

    public function getLatestConnectorStatus(int $connectorPk): ?string
    {
        if (!$this->usingRedis()) {
            $row = DB::connection('steve')->table('connector_status')
                ->where('connector_pk', $connectorPk)
                ->orderBy('status_timestamp', 'desc')
                ->first();
            return $row->status ?? null;
        }

        $prefix = $this->redisPrefix();
        $row = Redis::hgetall("{$prefix}:connector_status:{$connectorPk}");
        return $row['status'] ?? null;
    }

    public function getRecentTransactions(int $limit = 20): array
    {
        if (!$this->usingRedis()) {
            return DB::connection('steve')->table('transaction')
                ->orderBy('transaction_pk', 'desc')
                ->limit($limit)
                ->get()
                ->all();
        }

        $prefix = $this->redisPrefix();
        $keys = Redis::smembers("{$prefix}:index:transactions");
        $rows = [];
        foreach ($keys as $key) {
            $rows[] = (object) Redis::hgetall($key);
        }

        usort($rows, fn ($a, $b) => ((int) ($b->transaction_pk ?? 0)) <=> ((int) ($a->transaction_pk ?? 0)));
        return array_slice($rows, 0, $limit);
    }

    public function getTransactionById(int $transactionPk): ?object
    {
        if (!$this->usingRedis()) {
            return DB::connection('steve')->table('transaction')->where('transaction_pk', $transactionPk)->first();
        }

        $prefix = $this->redisPrefix();
        $row = Redis::hgetall("{$prefix}:transaction:{$transactionPk}");
        return $row ? (object) $row : null;
    }

    public function getConnectorByPk(int $connectorPk): ?object
    {
        if (!$this->usingRedis()) {
            return DB::connection('steve')->table('connector')->where('connector_pk', $connectorPk)->first();
        }

        $prefix = $this->redisPrefix();
        $row = Redis::hgetall("{$prefix}:connector:{$connectorPk}");
        return $row ? (object) $row : null;
    }

    public function getLatestMeterValue(int $transactionPk, ?string $measurand = null): ?object
    {
        if (!$this->usingRedis()) {
            $q = DB::connection('steve')->table('connector_meter_value')->where('transaction_pk', $transactionPk);
            if ($measurand) {
                $q->where('measurand', $measurand);
            }
            return $q->orderBy('value_timestamp', 'desc')->first();
        }

        $prefix = $this->redisPrefix();
        $keys = Redis::smembers("{$prefix}:index:meter_values");
        $latest = null;

        foreach ($keys as $key) {
            $row = Redis::hgetall($key);
            if (!$row) {
                continue;
            }
            if ((int) ($row['transaction_pk'] ?? -1) !== $transactionPk) {
                continue;
            }
            if ($measurand && ($row['measurand'] ?? null) !== $measurand) {
                continue;
            }

            if (!$latest || (($row['value_timestamp'] ?? '') > ($latest['value_timestamp'] ?? ''))) {
                $latest = $row;
            }
        }

        return $latest ? (object) $latest : null;
    }

    public function getSessionsForSync(?string $since = null, ?int $afterTransactionPk = null, int $limit = 200): array
    {
        if (!$this->usingRedis()) {
            $query = DB::connection('steve')->table('transaction');

            if ($since) {
                $query->where('start_timestamp', '>=', $since);
            } elseif ($afterTransactionPk) {
                $query->where('transaction_pk', '>', $afterTransactionPk);
            }

            return $query->orderBy('transaction_pk', 'asc')->limit($limit)->get()->all();
        }

        $prefix = $this->redisPrefix();
        $keys = Redis::smembers("{$prefix}:index:transactions");
        $rows = [];

        foreach ($keys as $key) {
            $row = Redis::hgetall($key);
            if (!$row) {
                continue;
            }

            $tx = (int) ($row['transaction_pk'] ?? 0);
            if ($afterTransactionPk && $tx <= $afterTransactionPk) {
                continue;
            }

            if ($since && !empty($row['start_timestamp']) && $row['start_timestamp'] < $since) {
                continue;
            }

            $rows[] = (object) $row;
        }

        usort($rows, fn ($a, $b) => ((int) ($a->transaction_pk ?? 0)) <=> ((int) ($b->transaction_pk ?? 0)));
        return array_slice($rows, 0, $limit);
    }
}
