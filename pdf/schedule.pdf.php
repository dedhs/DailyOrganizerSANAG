<?php

require_once '../app/app.php';

use Twig\Loader\FilesystemLoader;
use Twig\Environment;


// 1. Twig einrichten

$loader = new FilesystemLoader(__DIR__); // HTML-Templates liegen im gleichen Verzeichnis
$twig = new Environment($loader);

$pdo = new PDO(DB_CONFIG_DATA['dsn'], DB_CONFIG_DATA['username'], DB_CONFIG_DATA['password']);

$date = $_POST['plan-date'] ?? $_GET['plan-date'] ?? date('Y-m-d');

$stmt = $pdo->prepare("SELECT * FROM dailyOrganizer WHERE date = :date LIMIT 1");
$stmt->execute([':date' => $date]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  die("Keine Einteilung für dieses Datum gefunden.");
}

$op_doctors = json_decode($row['op_doctors'], true);
$pas_morning_ids = json_decode($row['pas_morning'], true);
$pas_afternoon_ids = json_decode($row['pas_afternoon'], true);
$late_shift_ids = json_decode($row['late_shift'], true);
$pain_ids = json_decode($row['pain'], true);
$night_shift_ids = json_decode($row['night_shift'], true);
$on_call_night_ids = json_decode($row['on_call_night'], true);
$doctor_tv_id = $row['doctor_tv'];

$all_ids = array_merge(
  array_values($op_doctors ? array_merge(...array_values($op_doctors)) : []),
  $pas_morning_ids ?? [],
  $pas_afternoon_ids ?? [],
  $late_shift_ids ?? [],
  $pain_ids ?? [],
  $night_shift_ids ?? [],
  $on_call_night_ids ?? [],
  [$doctor_tv_id]
);

$name_map = get_names_by_ids(array_unique($all_ids), $pdo);

$opDoctors = json_decode($row['op_doctors'] ?? '{}', true);
$tv = $row['doctor_tv'] ?? 'NN';
$pas_morning = implode(', ', json_decode($row['pas_morning'] ?? '[]'));
$pas_afternoon = implode(', ', json_decode($row['pas_afternoon'] ?? '[]'));
$late_shift = implode(', ', json_decode($row['late_shift'] ?? '[]'));
$pain = implode(', ', json_decode($row['pain'] ?? '[]'));
$nacht = implode(', ', json_decode($row['night_shift'] ?? '[]'));
$pikett = implode(', ', json_decode($row['on_call_night'] ?? '[]'));
$tagpikett = implode(', ', json_decode($row['on_call_day'] ?? '[]'));


// 2. Daten für das Template

$data = [
  'datum' => get_german_weekday($date) . ", " . date('d.m.Y', strtotime($date)),
  'tv' => $name_map[$doctor_tv_id] ?? '–',
  'pas_morning' => implode('	· ', array_map(fn($id) => $name_map[$id] ?? '-', $pas_morning_ids)),
  'pas_afternoon' => implode('	· ', array_map(fn($id) => $name_map[$id] ?? '-', $pas_afternoon_ids)),
  'late_shift' => implode('	· ', array_map(fn($id) => $name_map[$id] ?? '-', $late_shift_ids)),
  'pain' => implode('	· ', array_map(fn($id) => $name_map[$id] ?? '-', $pain_ids)),
  'nacht' => implode('	·  ', array_map(fn($id) => $name_map[$id] ?? '–', $night_shift_ids)),
  'pikett' => implode('	·  ', array_map(fn($id) => $name_map[$id] ?? '–', $on_call_night_ids)),
  'saele' => []
];

foreach ($op_doctors as $saal => $ids) {
  $arzt_namen = array_map(fn($id) => $name_map[$id] ?? '–', $ids);
  $data['saele'][] = [
    'name' => 'Saal ' . strtoupper($saal),
    'arzt' => implode('	· ', $arzt_namen),
    'pflege' => '', // Wenn Pflegepersonal folgt
    'ende' => ''
  ];
}



// 3. HTML aus Template generieren

$template = $twig->load('schedule.pdf.twig');
$html = $template->render($data);


// 4. PDF mit mPDF erzeugen

$mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/../tmp']);
$mpdf->WriteHTML($html);
$mpdf->Output('Einteilung.pdf', \Mpdf\Output\Destination::INLINE);
