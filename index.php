<?php

require 'app/app.php';

$planWeekday = "Dienstag";
$planDate = "2025-06-10";

$staff = get_staff($planDate);


$view_data = [
  'title' => 'Tageseinteilung',
  'planWeekday' => $planWeekday,
  'planDate' => $planDate,
  'staff' => $staff->tagesplan,
];

view('index', $view_data);
