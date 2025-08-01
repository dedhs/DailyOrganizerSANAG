<?php

require_once '../app/app.php';

use Twig\Loader\FilesystemLoader;
use Twig\Environment;


// 1. Twig einrichten

$loader = new FilesystemLoader(__DIR__); // HTML-Templates liegen im gleichen Verzeichnis
$twig = new Environment($loader);



$data = [
  'date' => 'Mittwoch, 30.07.2025',
  'saele' => [
    ['name' => 'Saal A', 'person' => 'Kalbermatter, R.'],
    ['name' => 'Saal B', 'person' => 'XYZ'],
    ['name' => 'Saal C', 'person' => ''],
    ['name' => 'Saal D', 'person' => ''],
    ['name' => 'Saal E', 'person' => ''],
    ['name' => 'Saal F', 'person' => 'Bansi, A.'],
    ['name' => 'Saal G', 'person' => ''],
    ['name' => 'Endo', 'person' => ''],
  ],
];




$html = $twig->render(
  'test_layout.pdf.twig',
  $data
);

$mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/../tmp']);
$mpdf->WriteHTML($html);
$mpdf->Output('EinteilungTest.pdf', \Mpdf\Output\Destination::INLINE);


$mpdf->WriteHTML($html);
$mpdf->Output();
