
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
