
<?php

require_once APP_PATH . 'app/app.php';

// TODO: Man könnte bei den Diensten ebenfalls über die Dienst-Id gehen und diesen die Symbole/Bezeichnungen aus get_dienstvorlagen zuweisen. Normalisierung in diesem Ausmass sinnvoll?
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
}

function fetch_dbdata($table, $sort_by_value, $sort_order, $pdo, array $where = [], ...$values)
{
  $columns = implode(',', $values);

  $whereClauses = [];
  $params = [];

  foreach ($where as $index => $condition) {
    $placeholder = ":v$index";
    $whereClauses[] = "{$condition['column']} {$condition['operator']} $placeholder";
    $params[$placeholder] = $condition['value'];
  }

  $whereSQL = '';
  if (!empty($whereClauses)) {
    $whereSQL = ' WHERE ' . implode(' AND ', $whereClauses);
  }

  $sql = "SELECT $columns FROM $table$whereSQL ORDER BY $sort_by_value $sort_order";


  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

  return $data;
}

function change_shift($date, $current_shift, $new_shift, $current_on_call, $new_on_call, $name, $token, $uid, $client, $pdo)
{

  $new_on_call_day = in_array('new-on-call-day', $new_on_call);
  $new_on_call_night = in_array('new-on-call-night', $new_on_call);

  try {
    $fk_staffId = fetch_dbdata('staff', 'lastname', 'ASC', $pdo, [
      ['column' => 'trigram', 'operator' => '=', 'value' => $name]
    ], 'id');

    $staffId = $fk_staffId[0]['id'];

    $existing_shift = fetch_dbdata('roster', 'fk_staffId', 'ASC', $pdo, [
      ['column' => 'fk_staffId', 'operator' => '=', 'value' => $staffId],
      ['column' => 'date', 'operator' => '=', 'value' => $date]
    ], 'shift', 'on_call_day', 'on_call_night');

    // Change entry in Planik
    // regular shift
    $deleteResult = delete_dienst($date, $current_shift, $name, $token, $uid, $client);
    $addResult = add_dienst($date, $new_shift, $name, $token, $uid, $client);


    // on call
    // on_call_day already in roster?
    if (!empty($existing_shift[0]) && $existing_shift[0]['on_call_day'] === 1) {
      // on_call_day also in new entry?
      if (!$new_on_call_day) {
        $deleteResultOnCallDay = delete_dienst($date, 'P/T', $name, $token, $uid, $client);
      }
    } else {
      if ($new_on_call_day) {
        $addResultOnCallDay = add_dienst($date, 'P/T', $name, $token, $uid, $client);
      }
    }

    // on_call_night already in roster?
    if (!empty($existing_shift[0]) && $existing_shift[0]['on_call_night'] === 1) {
      // on_call_night also in new entry?
      if (!$new_on_call_night) {
        $deleteResultOnCallNight = delete_dienst($date, 'P/N', $name, $token, $uid, $client);
      }
    } else {
      if ($new_on_call_night) {
        $addResultOnCallNight = add_dienst($date, 'P/N', $name, $token, $uid, $client);
      }
    }

    if (!$deleteResult['success'] || !$addResult['success']) {
      return [
        'success' => false,
        'error' => 'API-Fehler beim Diensttausch',
        'details' => [
          'deleteShift' => $deleteResult,
          'addShift' => $addResult,
          'deleteOnCallDay' => $deleteResultOnCallDay,
          'addOnCallDay' => $addResultOnCallDay,
          'deleteOnCallNight' => $deleteResultOnCallNight,
          'addOnCallNight' => $addResultOnCallNight
        ]
      ];
    }


    // make DB entry
    // no existing entry in DB
    if (!isset($existing_shift[0]['shift']) || !isset($existing_shift[0]['on_call_day']) || !isset($existing_shift[0]['on_call_night'])) {

      $sql = "
        INSERT INTO roster (date, fk_staffId, shift, on_call_day, on_call_night, is_active, created, last_modified)
        VALUES (:date, :fk_staffId, :shift, :on_call_day, :on_call_night, true, now(), now())
      ";

      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ':date' => $date,
        ':fk_staffId' => $fk_staffId[0]['id'],
        ':shift' => $new_shift,
        ':on_call_day' => $new_on_call_day ? 1 : 0,
        ':on_call_night' => $new_on_call_night ? 1 : 0
      ]);
    } else
    // existing entry in DB
    {

      $sql = "
        UPDATE roster
        SET
          shift = :shift,
          on_call_day = :on_call_day,
          on_call_night = :on_call_night,
          is_active = true,
          last_modified = now()
        WHERE date = :date AND fk_staffId = :fk_staffId
      ";

      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ':shift' => $new_shift,
        ':on_call_day' => $new_on_call_day ? 1 : 0,
        ':on_call_night' => $new_on_call_night ? 1 : 0,
        ':date' => $date,
        ':fk_staffId' => $fk_staffId[0]['id']
      ]);
    }

    return ['success' => true];
  } catch (Exception $e) {
    return [
      'success' => false,
      'error' => 'Exception: ' . $e->getMessage()
    ];
  }
}
function get_names_by_ids(array $ids, PDO $pdo): array
{
  $ids = array_filter($ids); // Leere Werte raus

  if (empty($ids)) return [];

  $placeholders = implode(',', array_fill(0, count($ids), '?'));

  $sql = "SELECT id, CONCAT(lastname, ', ', firstname) AS name FROM staff WHERE id IN ($placeholders)";
  $stmt = $pdo->prepare($sql);

  // Stelle sicher, dass numerische Indexierung passt
  $stmt->execute(array_values($ids));

  return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
