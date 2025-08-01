<?php

function view($html)
{
  require APP_PATH . "views/layout.html.twig";
}


function get_date($type)
{
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputDate = $_POST[$type] ?? '';

    if ($inputDate) {
      $date = DateTime::createFromFormat('d.m.Y', $inputDate);

      if ($date && $date->format('d.m.Y') === $inputDate) {
        // Erfolg! Gib es in Y-m-d zurück
        return $date->format('Y-m-d');
      }
    }
  }
  return null; // Wenn nichts da ist oder ungültig
}

function get_german_weekday($date)
{
  $date = new DateTime($date);
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

  return $daysDE[$weekdayNumber];
}
