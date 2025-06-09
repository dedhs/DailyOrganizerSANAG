<?php

$date = "2025-06-10";

$url = "https://planik.salem-anaesthesie.ch/dienstplan/tagesplan/" . $date;
$response = file_get_contents($url);

$data = json_decode($response, true);
print_r($data);
