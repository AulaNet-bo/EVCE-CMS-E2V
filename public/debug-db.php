<?php
header('Content-Type: text/plain');
\ = 'mysql:host=172.20.0.3;dbname=stevedb';
\ = 'steve';
\ = 'changeme';

try {
    \ = new PDO(\, \, \);
    \ = \->query('SHOW TABLES');
    echo " Tables in stevedb at mysql-local:\n\;
