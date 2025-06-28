<?php

require_once 'app/app.php';

$planWeekday = "Dienstag";
$planDate = "2025-06-27";


$login = api_login();
$login_json = json_encode($login);
$login_headers = json_decode($login_json)->headers;
$login_data = [
  'access-token' => $login_headers->access_token,
  'client' => $login_headers->client,
  'uid' => $login_headers->uid,
];

$pdo = new PDO('mysql:host=localhost;dbname=einteilungstool', 'root', '');
$result = get_mitarbeiter($planDate, $login_data['access-token'], $login_data['uid'], $login_data['client'], $pdo);
print_r($result);

//$staff = get_mitarbeiter($planDate, $login_data['access-token'], $login_data['uid'], $login_data['client']);

//$roster_raw = get_dienste($planDate, $login_data['access-token'], $login_data['uid'], $login_data['client']);

//$roster = match_dienste_mitarbeiter($roster_raw, $staff);

$view_data = [
  'title' => 'Tageseinteilung',
  'planWeekday' => $planWeekday,
  'planDate' => $planDate,
  'staff' => $staff,
  'roster' => $roster,
];

view('index', $view_data);
