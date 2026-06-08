<?php
$mysqli = new mysqli('localhost','root','123','cuti_karyawan');
if ($mysqli->connect_error) {
    echo 'ERR: ' . $mysqli->connect_error;
    exit(1);
}
$res = $mysqli->query('SHOW COLUMNS FROM saldo_cuti');
if (! $res) {
    echo 'ERR: ' . $mysqli->error;
    exit(1);
}
echo "=== SALDO_CUTI SCHEMA ===\n";
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . '|' . $row['Type'] . "\n";
}
