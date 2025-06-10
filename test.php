<?php

$date = "2025-06-10";

$url = "https://planik.salem-anaesthesie.ch/dienstplan/tagesplan/" . $date;
$response = file_get_contents($url);

$data = json_decode($response);
print_r($data);

foreach ($data->tagesplan as $staff) {
  // list($staff_id, $staff_name, $staff_firstname, $staff_trigramm, $staff_duty) = $data;
  // echo $staff_name;
  // echo $staff . " " . gettype($staff) . "<br>";
  $array = explode(", ", $staff);
  // print_r($array);
  list($staff_id, $staff_name, $staff_firstname, $staff_trigramm, $staff_duty) = $array;
  echo $staff_name . "<br>";
}
