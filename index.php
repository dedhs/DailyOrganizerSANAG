<?php

require_once 'app/app.php';

$planWeekday = "Dienstag";
$planDate = "2025-06-10";


$login = api_login();
$login_json = json_encode($login);
$login_headers = json_decode($login_json)->headers;
$login_data = [
  'access-token' => $login_headers->access_token,
  'client' => $login_headers->client,
  'uid' => $login_headers->uid,
];

$staff = get_dienste($planDate, $login_data['access-token'], $login_data['uid'], $login_data['client']);


$view_data = [
  'title' => 'Tageseinteilung',
  'planWeekday' => $planWeekday,
  'planDate' => $planDate,
  'staff' => $staff,
];

view('index', $view_data);
