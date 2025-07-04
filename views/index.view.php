<!DOCTYPE html>
<html lang="de">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $data['title'] ?></title>
  <link rel="stylesheet" href="views/styles.css">
</head>

<body>
  <h1><?= "Tageseinteilung für " . $data['planWeekday'] . ", " . $data['planDate'] ?></h1>

  <!-- <section>
    <div>
      <pre><?php // print_r(match_dienste_mitarbeiter($data['roster'], $data['staff'])); 
            ?></pre>
      <p><?php var_dump($data['roster']) ?></p>
      <p><?php var_dump($data['staff']) ?></p>
      <br>
    </div>
  </section> -->

  <section>
    </div>
    <div class="row">
      <div class="col-12">
        <table class="table table-striped">
          <thead>
            <th>Mitarbeiter-ID</th>
            <th>Name, Vorname</th>
            <th>Dienst</th>
            <th>Pikett Tag</th>
            <th>Pikett Nacht</th>
          </thead>
          <tbody>
            <?php foreach ($data['roster'] as $roster): ?>

              <tr>
                <td><?= $roster['mitarbeiter_id'] ?></td>
                <td><?= $roster['name'] ?></td>
                <td><?= $roster['dienst'] ?></td>
                <td><?= $roster['pikett_tag'] ?></td>
                <td><?= $roster['pikett_nacht'] ?></td>
              </tr>

            <?php endforeach; ?>

          </tbody>

        </table>
  </section>


  <section>
    <div class="task-section">
      <div class="task-title">Saal A</div>
      <div class="checkbox-container">
        <?php foreach ($data['roster'] as $roster): ?>
          <label class="checkbox"><input type="checkbox" name="op_a" value="staff_a"> <?= $roster['name'] ?></label>
        <?php endforeach ?>
      </div>
    </div>






  </section>





</body>

</html>