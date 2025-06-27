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
      <p><?php var_dump($data['roster']) ?></p>
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
            <?php foreach ($data['roster']['tagesplan'] as $roster): ?>

              <tr>
                <td><?= $roster['mitarbeiter_id'] ?></td>
                <td><?= $roster['kuerzel'] ?></td>
              </tr>

            <?php endforeach; ?>

          </tbody>

        </table>



  </section>
  <section>
    </div>
    <div class="row">
      <div class="col-12">
        <table class="table table-striped">
          <thead>
            <th>Mitarbeiter-ID</th>
            <th colspan="2">Name, Vorname</th>
            <th>Trigramm</th>

          </thead>
          <tbody>
            <?php foreach ($data['staff'] as $staff): ?>

              <tr>
                <td><?= $staff['id'] ?></td>
                <td><?= $staff['attributes']['nachname'] ?></td>
                <td><?= $staff['attributes']['vorname'] ?></td>
                <td><?= $staff['attributes']['kuerzel'] ?></td>
              </tr>

            <?php endforeach; ?>

          </tbody>

        </table>



  </section>
</body>





</html>