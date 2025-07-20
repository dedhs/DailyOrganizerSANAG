<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

// 1. Twig einrichten
$loader = new FilesystemLoader(__DIR__); // HTML-Templates liegen im gleichen Verzeichnis
$twig = new Environment($loader);

// 2. Daten für das Template
$data = [
  'datum' => 'Montag, 29.04.2025',
  'tv' => 'Dr. Müller',
  'spaetdienste' => 'Meier, Schulz',
  'pikett' => 'Dr. Sommer',
  'tel_pikett' => '031 123 45 67',
  'nacht' => 'Dr. Winter',
  'anmerkungen' => 'Rapport um 14:00 Uhr. Nachmeldungen beachten.',
  'saele' => [
    ['name' => 'Saal A', 'arzt' => 'Dr. Meier', 'pflege' => 'Frau Huber', 'ende' => '15:30'],
    ['name' => 'Saal B', 'arzt' => 'Dr. Schulz', 'pflege' => 'Herr Frei', 'ende' => '16:00'],
    ['name' => 'Saal C', 'arzt' => 'Dr. Hoffmann', 'pflege' => 'Frau Steiner', 'ende' => '14:45'],
  ]
];

// 3. HTML aus Template generieren
$template = $twig->load('einteilung.pdf.html');
$html = $template->render($data);

// 4. PDF mit mPDF erzeugen
$mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/../tmp']);
$mpdf->WriteHTML($html);
$mpdf->Output('Einteilung.pdf', \Mpdf\Output\Destination::INLINE);
