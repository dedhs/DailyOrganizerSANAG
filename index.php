<?php

require_once 'app/app.php';

// Dienstplan abrufen
$planDate = get_date('plan-date');

$date = new DateTime($planDate);
$date_formatted = $date->format('d.m.Y');
$weekdayNumber = $date->format('N');

$daysDE = [
  1 => 'Montag',
  2 => 'Dienstag',
  3 => 'Mittwoch',
  4 => 'Donnerstag',
  5 => 'Freitag',
  6 => 'Samstag',
  7 => 'Sonntag'
];

$planWeekday = $daysDE[$weekdayNumber];

// Add/Delete Dienste
$planModDate = get_date('mod-date');


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


$shifts_ops = ['1', '2', '1WE'];

// TODO 1WE &PAS-Kürzel berücksichtigen

$staff_ops = array_filter($roster_table, function ($e) use ($shifts_ops) {
  return in_array($e['dienst'], $shifts_ops);
});
$staff_ops = array_values($staff_ops);

$night_shift = array_filter($roster_table, function ($e) {
  return in_array($e['dienst'], ['N']);
});
$on_call_night = array_filter($roster_table, function ($e) {
  return $e['pikett_nacht'];
});

$on_call_day = array_filter($roster_table, function ($e) {
  return $e['pikett_tag'];
});


// Dienstvorlagen abrufen

get_dienstvorlagen($login_data['access-token'], $login_data['uid'], $login_data['client'], $pdo);


// add_dienst($planModDate, 'N', 'HAS', $login_data['access-token'], $login_data['uid'], $login_data['client'], $pdo);

delete_dienst($planModDate, 'N', 'HAS', $login_data['access-token'], $login_data['uid'], $login_data['client'], $pdo);

// TODO: Mail mit DP-Änderung auslösen


$view_data = [
  'title' => 'Tageseinteilung',
  'planWeekday' => $planWeekday,
  'planDate' => $date_formatted,
  'staff' => $staff,
  'roster' => $staff_ops,
  'night_shift' => $night_shift,
  'on_call_night' => $on_call_night,
  'on_call_day' => $on_call_day
];

view('index', $view_data);
