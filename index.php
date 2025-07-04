<?php

require_once 'app/app.php';

$planWeekday = "Dienstag";
$planDate = "2025-07-17";


$date = new DateTime($planDate);
$date_formatted = $date->format('d.m.Y');



$login = api_login();
$login_json = json_encode($login);
$login_headers = json_decode($login_json)->headers;
$login_data = [
  'access-token' => $login_headers->access_token,
  'client' => $login_headers->client,
  'uid' => $login_headers->uid,
];

$pdo = new PDO('mysql:host=localhost;dbname=einteilungstool', 'root', '');

$staff = get_mitarbeiter($planDate, $login_data['access-token'], $login_data['uid'], $login_data['client'], $pdo);

$roster = get_dienste($planDate, $login_data['access-token'], $login_data['uid'], $login_data['client'], $pdo);

$roster_table = match_dienste_mitarbeiter($planDate, $roster, $staff, $pdo);


$shifts_ops = ['1', '2', 'N', 'PAS'];

$staff_ops = array_filter($roster_table, function ($e) use ($shifts_ops) {
  return in_array($e['dienst'], $shifts_ops);
});
$staff_ops = array_values($staff_ops);







$view_data = [
  'title' => 'Tageseinteilung',
  'planWeekday' => $planWeekday,
  'planDate' => $date_formatted,
  'staff' => $staff,
  'roster' => $staff_ops,
];

view('index', $view_data);
