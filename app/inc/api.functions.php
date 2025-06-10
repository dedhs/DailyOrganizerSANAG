<?php

require_once APP_PATH . 'app/app.php';


function api_login(): ?array
{
  $url = API_CONFIG_URLS['url_api_login'];
  $password = API_CONFIG_DATA['password'];
  $email = API_CONFIG_DATA['email'];

  $postData = json_encode([
    'benutzer' => [
      'email' => $email,
      'password' => $password
    ]
  ]);

  $ch = curl_init($url);

  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HEADER => true, // ← wichtig für Header + Body
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'Content-Length: ' . strlen($postData)
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2
  ]);

  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  if (curl_errno($ch)) {
    $error = curl_error($ch);
    curl_close($ch);
    echo "cURL-Fehler: $error\n";
    return null;
  }

  $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $headerRaw = substr($response, 0, $headerSize);
  $body = substr($response, $headerSize);

  curl_close($ch);

  // Header parsen in assoziatives Array
  $headerLines = explode("\r\n", trim($headerRaw));
  $headers = [];

  foreach ($headerLines as $line) {
    if (strpos($line, ':') !== false) {
      [$key, $value] = explode(':', $line, 2);
      $headers[trim($key)] = trim($value);
    }
  }

  if ($httpCode >= 200 && $httpCode < 300) {
    return [
      'status' => $httpCode,
      'headers' => [
        'access_token' => $headers['access-token'] ?? null,
        'client' => $headers['client'] ?? null,
        'uid' => $headers['uid'] ?? null
      ]
    ];
  } else {
    echo "API-Fehler (HTTP $httpCode): $body\n";
    return null;
  }
}


function get_dienste($date, $token, $uid, $client)
{
  $url = API_CONFIG_URLS['base_url_get_dienste'] . $date . ',' . $date;
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); -- no post data handed over to API, can be deleted if working!
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'access-token: ' . $token,
    'uid: ' . $uid,
    'client: ' . $client
  ]);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

  $response = curl_exec($ch);

  $data = json_decode($response);

  return $data;
}
