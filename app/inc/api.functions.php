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

function get_mitarbeiter($date, $token, $uid, $client, $pdo)
{
  $url = API_CONFIG_URLS['base_url_get_mitarbeiter'] . $date . ',' . $date;

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'access-token: ' . $token,
    'uid: ' . $uid,
    'client: ' . $client
  ]);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

  $response = curl_exec($ch);

  curl_close($ch);

  $data = json_decode($response, true);

  if (!isset($data['data'])) {
    return ['error' => 'Keine Daten gefunden'];
  }

  $api_ids = [];

  foreach ($data['data'] as $m) {
    $id = $m['id'];
    $lastname = $m['attributes']['nachname'] ?? '';
    $firstname = $m['attributes']['vorname'] ?? '';
    $trigram = $m['attributes']['kuerzel'] ?? '';

    $api_ids[] = $id;

    $stmt = $pdo->prepare("
            INSERT INTO staff (id, lastname, firstname, trigram, status, created, last_modified)
            VALUES (:id, :lastname, :firstname, :trigram, true, now(), now())
            ON DUPLICATE KEY UPDATE
              lastname = VALUES(lastname),
              firstname = VALUES(firstname),
              trigram = VALUES(trigram),
              status = true
        ");

    $stmt->execute([
      ':id' => $id,
      ':lastname' => $lastname,
      ':firstname' => $firstname,
      ':trigram' => $trigram
    ]);
  }

  // Alle Mitarbeiter, die NICHT mehr in der API sind, auf status = false setzen
  if (count($api_ids) > 0) {
    $in = implode(',', array_fill(0, count($api_ids), '?'));
    $stmt = $pdo->prepare("
            UPDATE staff
            SET status = false, last_modified = now()
            WHERE id NOT IN ($in)
        ");
    $stmt->execute($api_ids);
  }

  return [
    'success' => true,
    'imported' => count($api_ids)
  ];
}

function get_dienste($date, $token, $uid, $client, $pdo)
{
  $url = API_CONFIG_URLS['base_url_get_dienste'] . $date . ',' . $date;
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'access-token: ' . $token,
    'uid: ' . $uid,
    'client: ' . $client
  ]);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

  $response = curl_exec($ch);

  curl_close($ch);

  $data = json_decode($response);

  if (!isset($data->data)) {
    return json_encode(['error' => 'Keine Daten gefunden']);
  }

  // Array with unique ids to check roster for active vs inactive entries
  $valid_ids = [];
  $valid_keys_on_call_day = [];
  $valid_keys_on_call_night = [];

  foreach ($data->data as $data_item) {
    $attributes = $data_item->attributes ?? null;

    if ($attributes && isset($attributes->mitarbeiter_id) && isset($attributes->kuerzel)) {

      $id = $attributes->mitarbeiter_id;
      $shift = $attributes->kuerzel;
    }

    $valid_ids[] = $date . '#' . $id;


    if ($shift === 'P/N') {
      $shift = null;
      $on_call_night = 1;
      $valid_keys_on_call_night[] = "$date#$id#on_call_night";
    } else {
      $on_call_night = 0;
    }

    if ($shift === 'P/T') {
      $shift = null;
      $on_call_day = 1;
      $valid_keys_on_call_day[] = "$date#$id#on_call_day";
    } else {
      $on_call_day = 0;
    }

    $stmt = $pdo->prepare("
            INSERT INTO roster (date, fk_staffId, shift, on_call_day, on_call_night, is_active, created, last_modified)
            VALUES (:date, :fk_staffId, :shift, :on_call_day, :on_call_night, true, now(), now())
            ON DUPLICATE KEY UPDATE
              shift = CASE
                WHEN VALUES(shift) IS NOT NULL THEN VALUES(shift)
                ELSE shift
              END,
              on_call_night = VALUES(on_call_night) OR on_call_night,
              on_call_day = VALUES(on_call_day) OR on_call_day,
              last_modified = now()
        ");

    $stmt->execute([
      ':date' => $date,
      ':fk_staffId' => $id,
      ':shift' => $shift,
      ':on_call_day' => $on_call_day,
      ':on_call_night' => $on_call_night
    ]);
  }

  // Detect inactive entries

  $valid_ids = array_unique($valid_ids);

  if (count($valid_ids) > 0) {

    // Generate Array with placeholders for prepared statements
    $id_placeholders = implode(',', array_fill(0, count($valid_ids), '?'));

    $on_call_day_placeholders = implode(',', array_fill(0, count($valid_keys_on_call_day), '?'));

    $on_call_night_placeholders = implode(',', array_fill(0, count($valid_keys_on_call_night), '?'));

    $sql1 = "
    UPDATE roster
    SET
      last_modified = CASE WHEN is_active = true THEN now() ELSE last_modified END,
      is_active = CASE WHEN is_active = true THEN false ELSE is_active END
    WHERE date = ?
      AND CONCAT(date, '#', fk_staffId) NOT IN ($id_placeholders)
  ";
    $params1 = array_merge([$date], $valid_ids);
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute($params1);


    if ($on_call_day_placeholders != null) {
      $sql2 = "
    UPDATE roster
    SET
      on_call_day = false
      WHERE date = ?
        AND CONCAT(date, '#', fk_staffId, '#', 'on_call_day') NOT IN ($on_call_day_placeholders)
  ";
      $params2 = array_merge([$date], $valid_keys_on_call_day);
      $stmt2 = $pdo->prepare($sql2);
      $stmt2->execute($params2);
    }

    if ($on_call_night_placeholders != null) {
      $sql3 = "
    UPDATE roster
    SET
      on_call_night = false
      WHERE date = ?
        AND CONCAT(date, '#', fk_staffId, '#', 'on_call_night') NOT IN ($on_call_night_placeholders)
  ";
      $params3 = array_merge([$date], $valid_keys_on_call_night);
      $stmt3 = $pdo->prepare($sql3);
      $stmt3->execute($params3);
    }
  } else {
    $stmt = $pdo->prepare("UPDATE roster SET is_active = false WHERE date = ?");
    $stmt->execute([$date]);
  }

  return [
    'success' => true,
    'imported/updated' => count($valid_ids)
  ];
}

function match_dienste_mitarbeiter($date, $roster, $staff, $pdo)
{
  $sql_staff = "SELECT id, lastname, firstname FROM staff WHERE status = 1";
  $stmt_staff = $pdo->prepare($sql_staff);
  $stmt_staff->execute();
  $staff = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);

  $sql_roster = "SELECT fk_staffId, shift, on_call_day, on_call_night FROM roster WHERE date = :date";
  $stmt_roster = $pdo->prepare($sql_roster);
  $stmt_roster->execute([':date' => $date]);
  $roster = $stmt_roster->fetchAll(PDO::FETCH_ASSOC);

  $staff_list = [];
  foreach ($staff as $s) {
    $staff_list[$s['id']] = $s;
  }

  $result = [];



  foreach ($roster as $r) {
    $id = $r['fk_staffId'];
    if (isset($staff_list[$id])) {
      $s = $staff_list[$id];
      $result[] = [
        'mitarbeiter_id' => $id,
        'name' => $s['lastname'] . ', ' . $s['firstname'],
        'dienst' => $r['shift'],
        'pikett_tag' => $r['on_call_day'],
        'pikett_nacht' => $r['on_call_night']
      ];
    }
  }

  // Sorting names in alphabetical order
  usort($result, function ($a, $b) {
    return strcmp($a['name'], $b['name']);
  });

  return $result;
};
