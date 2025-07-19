<?php

require_once 'app/app.php';


$login = api_login();
$login_json = json_encode($login);
$login_headers = json_decode($login_json)->headers;
$login_data = [
  'access-token' => $login_headers->access_token,
  'client' => $login_headers->client,
  'uid' => $login_headers->uid,
];

$pdo = new PDO('mysql:host=localhost;dbname=einteilungstool', 'root', '');



$currentShift = 'kein Dienst bzw. Dienstplan noch nicht freigegeben';
$onCallDay = '-';
$onCallNight = '-';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Prüfe: Kommt es von deinem JS?
  $input = json_decode(file_get_contents('php://input'), true);

  $action = $input['action'] ?? null;

  switch ($action) {
    case 'displayCurrentShift':
      if (isset($input['ajax']) && $input['ajax'] === true) {
        header('Content-Type: application/json');

        $date = $input['date'] ?? null;
        $employeeId = $input['employee_id'] ?? null;
        $trigram = $input['employee_trigram'] ?? null;

        if ($date && $employeeId) {

          $dateObj = DateTime::createFromFormat('d.m.Y', $date);
          $dateForDb = $dateObj ? $dateObj->format('Y-m-d') : $date;

          $pdo = new PDO('mysql:host=localhost;dbname=einteilungstool', 'root', '');

          $result = fetch_dbdata(
            'roster',
            'shift',
            'ASC',
            $pdo,
            [
              ['column' => 'date', 'operator' => '=', 'value' => $dateForDb],
              ['column' => 'fk_staffId', 'operator' => '=', 'value' => $employeeId],
              ['column' => 'is_active', 'operator' => '=', 'value' => true]
            ],
            'shift',
            'on_call_day',
            'on_call_night'
          );

          if ($result && count($result) > 0) {
            $currentShift = $result[0]['shift'];
            $onCallDay = $result[0]['on_call_day'];
            $onCallNight = $result[0]['on_call_night'];
          }
        }

        echo json_encode([
          'current_shift' => $currentShift,
          'on_call_day_scheduled' => $onCallDay,
          'on_call_night_scheduled' => $onCallNight,
          'trigram' => $trigram
        ]);
        exit;
      }




    case 'changeShift':
      $date = $input['date'] ?? null;
      $currentShift = $input['current_shift'] ?? null;
      $newShift = $input['new_shift'] ?? null;
      $trigram = $input['employee_trigram'] ?? null;

      if (!$date || !$currentShift || !$newShift || !$trigram) {
        echo json_encode([
          'success' => false,
          'error' => 'Missing parameters',
          'debug' => [
            'date' => $date,
            'currentShift' => $currentShift,
            'newShift' => $newShift,
            'trigram' => $trigram
          ]
        ]);
        exit;
      }

      $result = change_shift(
        $date,
        $currentShift,
        $newShift,
        $trigram,
        $login_data['access-token'],
        $login_data['uid'],
        $login_data['client'],
        $pdo
      );

      echo json_encode(['success' => $result ? true : false]);

      exit;
  }
}


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



get_dienstvorlagen($login_data['access-token'], $login_data['uid'], $login_data['client'], $pdo);

$employee = fetch_dbdata('staff',  'lastname', 'ASC', $pdo, [], 'lastname', 'firstname', 'trigram', 'id');

$shifts = fetch_dbdata('shiftTemplates',  'shift_symbol', 'ASC', $pdo, [], 'shift_id', 'shift_symbol');

// change_shift($planModDate, $currentShift, $_POST['shift'], $_POST['employee-trigram'], $login_data['access-token'], $login_data['uid'], $login_data['client'], $pdo);

// add_dienst($planModDate, $_POST['shift'], $_POST['employee-trigram'], $login_data['access-token'], $login_data['uid'], $login_data['client']);

// delete_dienst($planModDate, $_POST['shift'], $_POST['employee-trigram'], $login_data['access-token'], $login_data['uid'], $login_data['client']);

// TODO: Mail mit DP-Änderung auslösen


$view_data = [
  'title' => 'Tageseinteilung',
  'planWeekday' => $planWeekday,
  'planDate' => $date_formatted,
  'staff' => $staff,
  'roster' => $staff_ops,
  'night_shift' => $night_shift,
  'on_call_night' => $on_call_night,
  'on_call_day' => $on_call_day,
  'employee' => $employee,
  'current_shift' => 'Datum/Mitarbeiter noch nicht ausgewählt',
  'on_call_day_scheduled' => '',
  'on_call_night_scheduled' => '',
  'shifts' => $shifts
];

view('index', $view_data);
