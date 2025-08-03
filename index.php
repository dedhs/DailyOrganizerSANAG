<?php

require_once 'app/app.php';

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

$loader = new FilesystemLoader(APP_PATH . 'views');
$twig = new Environment($loader);

$pdo = new PDO(DB_CONFIG_DATA['dsn'], DB_CONFIG_DATA['username'], DB_CONFIG_DATA['password']);


$planDate = get_date('plan-date');
$planModDate = get_date('mod-date');
$date = new DateTime($planDate);

// Checking for existing plan for selected date
$existingPlan = null;

$dateObj = $date;
$planDateDb = $dateObj ? $dateObj->format('Y-m-d') : null;

if ($planDateDb) {
  $stmt = $pdo->prepare("SELECT * FROM dailyOrganizer WHERE date = :date");
  $stmt->execute([':date' => $planDateDb]);
  $existingPlan = $stmt->fetch(PDO::FETCH_ASSOC);
}


$login = api_login();
$login_json = json_encode($login);
$login_headers = json_decode($login_json)->headers;
$login_data = [
  'access-token' => $login_headers->access_token,
  'client' => $login_headers->client,
  'uid' => $login_headers->uid,
];



$currentShift = 'kein Dienst bzw. Dienstplan noch nicht freigegeben';
$onCallDay = '-';
$onCallNight = '-';

if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  isset($_SERVER['CONTENT_TYPE']) &&
  str_contains($_SERVER['CONTENT_TYPE'], 'application/json')
) {
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

          //$pdo = new PDO('mysql:host=localhost;dbname=einteilungstool', 'root', '');

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
      $currentOnCall = $input['current_on_call'] ?? null;
      $newOnCall = $input['new_on_call'] ?? null;
      $trigram = $input['employee_trigram'] ?? null;

      if (!$date || !$currentShift || !$newShift || !$trigram) {
        echo json_encode([
          'success' => false,
          'error' => 'Missing parameters',
          'debug' => [
            'date' => $date,
            'currentShift' => $currentShift,
            'newShift' => $newShift,
            'currentOnCall' => $currentOnCall,
            'newOnCall' => $newOnCall,
            'trigram' => $trigram
          ]
        ]);

        exit;
      }

      $result = change_shift(
        $date,
        $currentShift,
        $newShift,
        $currentOnCall,
        $newOnCall,
        $trigram,
        $login_data['access-token'],
        $login_data['uid'],
        $login_data['client'],
        $pdo
      );

      echo json_encode(['success' => $result ? true : false]);

      exit;


    case 'savePlanning':

      $stmt = $pdo->prepare("
        INSERT INTO dailyOrganizer (
          date, op_doctors, doctor_tv, pas_morning, pas_afternoon, pain, on_call_night, late_shift, night_shift
          ) 
        VALUES (
          :date, :op_doctors, :doctor_tv, :pas_morning, :pas_afternoon, :pain, :on_call_night, :late_shift, :night_shift
          )
        ON DUPLICATE KEY UPDATE
          op_doctors = VALUES(op_doctors),
          doctor_tv = VALUES(doctor_tv),
          pas_morning = VALUES(pas_morning),
          pas_afternoon = VALUES(pas_afternoon),
          pain = VALUES(pain),
          on_call_night = VALUES(on_call_night),
          late_shift = VALUES(late_shift);
          night_shift = VALUES(night_shift);
        ");

      try {
        $stmt->execute([
          ':date' => $input['date'],
          ':op_doctors' => json_encode($input['opDoctors']),
          ':doctor_tv' => $input['doctorTv'],
          ':pas_morning' => json_encode($input['pasMorning']),
          ':pas_afternoon' => json_encode($input['pasAfternoon']),
          ':pain' => json_encode($input['pain']),
          ':on_call_night' => json_encode($input['onCallNight']),
          ':late_shift' => json_encode($input['lateShift']),
          ':night_shift' => json_encode($input['nightShift'])
        ]);

        echo json_encode(['success' => true]);
        exit;
      } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
      }
  }
}



$staff = get_mitarbeiter($planDate, $login_data['access-token'], $login_data['uid'], $login_data['client'], $pdo);

$roster = get_dienste($planDate, $login_data['access-token'], $login_data['uid'], $login_data['client'], $pdo);

$roster_table = match_dienste_mitarbeiter($planDate, $roster, $staff, $pdo);

$shifts_day = ['1', '2', '1kurz', '2kurz', '1 PR', '2 PR', '1WE', '1WE PR', 'PAS', 'E', '📞', '💻', 'O', 'N'];
$shifts_ops = ['1', '2', '1kurz', '2kurz', '1 PR', '2 PR', '1WE', '1WE PR', 'E'];

$staff_day = array_filter($roster_table, function ($e) use ($shifts_day) {
  return in_array($e['dienst'], $shifts_day);
});

$staff_ops = array_filter($roster_table, function ($e) use ($shifts_ops) {
  return in_array($e['dienst'], $shifts_ops);
});
$staff_ops = array_values($staff_ops);

$late_shift = array_filter($roster_table, function ($e) {
  return in_array($e['dienst'], ['2']);
});

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

$employee = fetch_dbdata('staffPlanik',  'lastname', 'ASC', $pdo, [], 'lastname', 'firstname', 'trigram', 'id');

$shifts = fetch_dbdata('shiftTemplates',  'shift_symbol', 'ASC', $pdo, [], 'shift_id', 'shift_symbol');

$nurses = fetch_dbdata('staff', 'firstname', 'ASC', $pdo, [['column' => 'role_id', 'operator' => '=', 'value' => 2]], 'id', 'firstname', 'lastname', 'email_work');

var_dump($nurses);


// TODO: Mail mit DP-Änderung auslösen


if ($existingPlan) {
  $existingPlan['op_doctors'] = json_decode($existingPlan['op_doctors'], true);
}


$view_data = [
  'title' => 'Tageseinteilung',
  'planWeekday' => get_german_weekday($planDate),
  'planDate' => $date->format('d.m.Y'),
  'staff' => $staff,
  'roster' => $staff_day,
  'op_staff' => $staff_ops,
  'late_shift' => $late_shift,
  'night_shift' => $night_shift,
  'on_call_night' => $on_call_night,
  'on_call_day' => $on_call_day,
  'employee' => $employee,
  'current_shift' => 'Datum/Mitarbeiter noch nicht ausgewählt',
  'on_call_day_scheduled' => '',
  'on_call_night_scheduled' => '',
  'shifts' => $shifts,
  'nurses' => $nurses,
  'existingPlan' => $existingPlan
];

$template = $twig->load('index.view.twig');

echo $twig->render('index.view.twig', $view_data);
