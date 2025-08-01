<?php

const API_CONFIG_DATA = [
  'team_id' => '3645',
  'email' => 'dienstplaner@anasalem.ch',
  'password' => 'apiBern2023!',
];

const API_CONFIG_URLS = [
  'url_api_login' => 'https://api.planik.ch/api/v1/login',
  // 'url_get_dienste' => 'https://api.planik.ch/api/v1/dienste?filter[team_id]=' . API_CONFIG_DATA['team_id'] . '&filter[zwischen]=' . $date . ',' . $date . '&include=mitarbeiter',
  //'url_get_dienste' => "https://api.planik.ch/api/v1/dienste?filter[mandant_id]=3645&filter[zwischen]=2025-06-10,2025-06-10",
  'base_url_get_mitarbeiter' => 'https://api.planik.ch/api/v1/mitarbeiter?filter[mandant_id]=' . API_CONFIG_DATA['team_id'] . '&filter[angestellt_zwischen]=',
  'base_url_get_dienstvorlagen' => 'https://api.planik.ch/api/v1/dienstvorlagen?filter[team_id]=' .  API_CONFIG_DATA['team_id'],
  'base_url_get_dienste' => 'https://api.planik.ch/api/v1/dienste?filter[mandant_id]=' . API_CONFIG_DATA['team_id'] . '&filter[zwischen]=',
  'base_url_planungsverlauf' => 'https://api.planik.ch/api/v1/planungsverlauf/' . API_CONFIG_DATA['team_id']
];

const DB_CONFIG_DATA = [
  'dsn' => 'mysql:host=127.0.0.1;port=3307;dbname=sanag',
  'username' => 'root',
  'password' => 'root'
];
