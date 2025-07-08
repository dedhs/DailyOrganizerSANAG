<?php

function view($template, $data = [])
{
  require APP_PATH . "views/layout.view.php";
}

function getPlanDate()
{
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputDate = $_POST['plan-date'] ?? '';

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
