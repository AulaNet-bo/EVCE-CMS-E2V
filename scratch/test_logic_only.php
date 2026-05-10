<?php

// Mocking basic classes since we can't bootstrap Laravel easily here
class Model {
    public $attributes = [];
    public function __construct($attr = []) { $this->attributes = $attr; }
    public function __get($key) { return $this->attributes[$key] ?? null; }
    public function setRelation($name, $val) { $this->$name = $val; }
}
class Tariff extends Model {
    public function getCurrentPrices() {
        return ['block' => 1, 'price_kwh' => 1.0, 'price_session' => 1.5, 'currency' => 'USD'];
    }
}
class ChargingSession extends Model {}
class Carbon {
    public $time;
    public function __construct($t) { $this->time = is_string($t) ? strtotime($t) : $t; }
    public static function parse($t) { return new self($t); }
    public static function now() { return new self(time()); }
    public function diffInSeconds($other) { return abs($this->time - $other->time); }
    public function diffInMinutes($other) { return round(abs($this->time - $other->time) / 60); }
    public function format($f) { return date($f, $this->time); }
    public function lt($other) { return $this->time < $other->time; }
    public function gt($other) { return $this->time > $other->time; }
    public function copy() { return new self($this->time); }
    public function addDay() { $this->time += 86400; return $this; }
    public function startOfDay() { $this->time = strtotime(date('Y-m-d 00:00:00', $this->time)); return $this; }
    public function max($other) { return $this->time > $other->time ? $this : $other; }
    public function min($other) { return $this->time < $other->time ? $this : $other; }
}

// Minimal BillingService logic for testing (copying the relevant parts)
class BillingServiceTest {
    public function calculateSessionCost($session, $kwh, $stopTime = null) {
        $tariff = $session->tariff;
        $start = $session->start_time;
        $stop = $stopTime ?? Carbon::now();
        $durationSeconds = max(1, $stop->diffInSeconds($start));

        $blocks = [
            ['index' => 1, 'start' => '00:00:00', 'end' => '08:00:00', 'price_kwh' => 1.00, 'cost_kwh' => 0.50],
            ['index' => 2, 'start' => '08:00:00', 'end' => '16:00:00', 'price_kwh' => 2.00, 'cost_kwh' => 1.00],
        ];

        $totalEnergyCost = 0;
        $breakdown = [];
        foreach ($blocks as $block) {
            $overlap = $this->getSecondsOverlap($start, $stop, $block['start'], $block['end']);
            if ($overlap > 0) {
                $prop = $overlap / $durationSeconds;
                $blockKwh = $kwh * $prop;
                $blockPrice = $blockKwh * $block['price_kwh'];
                $totalEnergyCost += $blockPrice;
                $breakdown[] = ['block' => $block['index'], 'energy' => $blockKwh, 'cost' => $blockPrice];
            }
        }

        $sessionFee = 1.50;
        $total = $sessionFee + max($totalEnergyCost, ($kwh > 0 ? 1.0 * $blocks[0]['price_kwh'] : 0));

        return ['total' => $total, 'breakdown' => $breakdown];
    }

    private function getSecondsOverlap($start, $stop, $blockStart, $blockEnd) {
        $bStart = strtotime(date('Y-m-d ', $start->time) . $blockStart);
        $bEnd = strtotime(date('Y-m-d ', $start->time) . $blockEnd);
        $overlapStart = max($start->time, $bStart);
        $overlapEnd = min($stop->time, $bEnd);
        return max(0, $overlapEnd - $overlapStart);
    }
}

// Run Tests
$service = new BillingServiceTest();
$start = Carbon::parse('2026-05-03 07:00:00');
$stop = Carbon::parse('2026-05-03 09:00:00');
$session = new ChargingSession(['start_time' => $start]);
$session->tariff = new Tariff();

$result = $service->calculateSessionCost($session, 10.0, $stop);

echo "Test Multi-Block (07:00-09:00, 10kWh):\n";
echo "Total: " . $result['total'] . " (Expected: 16.5)\n";
foreach ($result['breakdown'] as $b) {
    echo "Block " . $b['block'] . ": " . $b['energy'] . " kWh, Cost: " . $b['cost'] . "\n";
}

if (abs($result['total'] - 16.5) < 0.01) echo "RESULT: PASS\n";
else echo "RESULT: FAIL\n";
