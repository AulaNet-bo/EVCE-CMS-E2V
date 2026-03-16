<?php
use Illuminate\Support\Facades\DB;
$columns = DB::select("DESCRIBE tariffs");
foreach ($columns as $col) {
    echo "{$col->Field} ({$col->Type})\n";
}
