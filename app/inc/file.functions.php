<?php

function get_staff($date)
{
  // ("GET", "https://planik.salem-anaesthesie.ch/dienstplan/tagesplan/" + plan_datum)
  // plan_datum-Fomrat: "YYYY-MM-DD"
  $url = "https://planik.salem-anaesthesie.ch/dienstplan/tagesplan/" . $date;
  $response = file_get_contents($url);

  if ($response === FALSE) {
    die("<h3>Fehler beim API-Aufruf</h3>");
  }

  $data = json_decode($response);
  return $data;
}
