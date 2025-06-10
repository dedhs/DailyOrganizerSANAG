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
  'base_url_get_dienste' => 'https://api.planik.ch/api/v1/dienste?filter[mandant_id]=' . API_CONFIG_DATA['team_id'] . '&filter[zwischen]=',
];
