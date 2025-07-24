<?php

require_once '../app/app.php';


use Twig\Loader\FilesystemLoader;
use Twig\Environment;

// 1. Twig einrichten
$loader = new FilesystemLoader(__DIR__); // HTML-Templates liegen im gleichen Verzeichnis
//$loader = new FilesystemLoader(APP_PATH . 'views');
$twig = new Environment($loader);

$pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=einteilungstool', 'root', 'root');

$date = $_POST['plan-date'] ?? $_GET['plan-date'] ?? date('Y-m-d');

$weekdays = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
$datum = new DateTime($dateFormatted);
$weekday = $weekdays[$datum->format('w')];



$stmt = $pdo->prepare("SELECT * FROM dailyOrganizer WHERE date = :date LIMIT 1");
$stmt->execute([':date' => $date]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  die("Keine Einteilung für dieses Datum gefunden.");
}


$op_doctors = json_decode($row['op_doctors'], true);
$night_shift_ids = json_decode($row['night_shift'], true);
$on_call_night_ids = json_decode($row['on_call_night'], true);
$doctor_tv_id = $row['doctor_tv'];

$all_ids = array_merge(
  array_values($op_doctors ? array_merge(...array_values($op_doctors)) : []),
  $night_shift_ids ?? [],
  $on_call_night_ids ?? [],
  [$doctor_tv_id]
);




$name_map = get_names_by_ids(array_unique($all_ids), $pdo);



$opDoctors = json_decode($row['op_doctors'] ?? '{}', true);
$tv = $row['doctor_tv'] ?? 'NN';
$nacht = implode(', ', json_decode($row['night_shift'] ?? '[]'));
$pikett = implode(', ', json_decode($row['on_call_night'] ?? '[]'));
$tagpikett = implode(', ', json_decode($row['on_call_day'] ?? '[]'));






// 2. Daten für das Template



$data = [
  'datum' => date('l, d.m.Y', strtotime($date)),
  'tv' => $name_map[$doctor_tv_id] ?? '–',
  'nacht' => implode(', ', array_map(fn($id) => $name_map[$id] ?? '–', $night_shift_ids)),
  'pikett' => implode(', ', array_map(fn($id) => $name_map[$id] ?? '–', $on_call_night_ids)),
  'saele' => []
];

foreach ($op_doctors as $saal => $ids) {
  $arzt_namen = array_map(fn($id) => $name_map[$id] ?? '–', $ids);
  $data['saele'][] = [
    'name' => 'Saal ' . strtoupper($saal),
    'arzt' => implode(', ', $arzt_namen),
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
