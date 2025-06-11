<!DOCTYPE html>
<html lang="de">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $data['title'] ?></title>
</head>

<body>
  <h1><?= "Tageseinteilung für " . $data['planWeekday'] . ", " . $data['planDate'] ?></h1>
  <section>
    <div>
      <p><?php var_dump($data['staff']) ?></p>
      <br>
    </div>
  </section>
  <section>
    </div>
    <div class="row">
      <div class="col-12">
        <table class="table table-striped">
          <thead>
            <th>Mitarbeiter-ID</th>
            <th>Dienst</th>
            <th>Beschreibung</th>
          </thead>
          <tbody>
            <?php foreach ($data['staff'] as $staff): ?>

              <tr>
                <td><?= $staff->mitarbeiter_id ?></td>
                <td><?= $staff->kuerzel ?></td>
              </tr>

            <?php endforeach; ?>

          </tbody>

        </table>



  </section>
</body>





</html>