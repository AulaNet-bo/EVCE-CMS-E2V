<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=stevedb', 'steve', 'changeme');
$s = $pdo->query('SHOW TABLES');
while($r = $s->fetch()) {
    echo $r[0]."\n";
}
