<?php
$file = 'D:\KP\Data PLN\DIL_SALDO_MASK_202510_53CRB\DATA_UTAMA_1.csv';
$handle = fopen($file, 'r');
$header = fgetcsv($handle, 0, ';');
echo "Header count: " . count($header) . "\n";
foreach ($header as $i => $col) {
    echo "$i: [$col]\n";
}
echo "\n--- First data row ---\n";
$row = fgetcsv($handle, 0, ';');
foreach ($row as $i => $val) {
    echo "$i: [$val]\n";
}
fclose($handle);
