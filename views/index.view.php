<!DOCTYPE html>
<html lang="de">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $data['title'] ?></title>
  <link rel="stylesheet" href="views/styles.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>

<body>
  <form action="" method="post">
    <label for="plan-date">Bitte Datum eingeben/auswählen:</label>
    <input type="text" id="plan-date" class="date-entry" name="plan-date" placeholder="TT.MM.JJJJ">
    <button type="submit">Absenden</button>
  </form>





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
    <div>
      <p>Nachtdienst:
        <?php foreach ($data['night_shift'] as $night): ?>
          <?= $night['name'] ?></p>
    <?php endforeach ?>
    </div>
    <div>
      <p>Pikett Nacht:
        <?php foreach ($data['on_call_night'] as $ocn): ?>
          <?= $ocn['name'] ?></p>
    <?php endforeach ?>
    </div>
    <div>
      <p>Pikett Tag:
        <?php foreach ($data['on_call_day'] as $ocd): ?>
          <?= $ocd['name'] ?></p>
    <?php endforeach ?>
    </div>

  </section>


  <section>

    <div class="task-section">

      <div class="task-title">Saal A, B, C, D</div>

      <div class="container-wrapper">

        <div class="task-column">
          <div class="task-title">Saal A</div>
          <div class="checkbox-container">
            <?php foreach ($data['roster'] as $roster): ?>
              <label class="checkbox"><input type="checkbox" name="op_a" value="staff_a"> <?= $roster['name'] ?></label>
            <?php endforeach ?>
          </div>
        </div>

        <div class="task-column">
          <div class="task-title">Saal B</div>
          <div class="checkbox-container">
            <?php foreach ($data['roster'] as $roster): ?>
              <label class="checkbox"><input type="checkbox" name="op_b" value="staff_b"> <?= $roster['name'] ?></label>
            <?php endforeach ?>
          </div>
        </div>

        <div class="task-column">
          <div class="task-title">Saal C</div>
          <div class="checkbox-container">
            <?php foreach ($data['roster'] as $roster): ?>
              <label class="checkbox"><input type="checkbox" name="op_c" value="staff_c"> <?= $roster['name'] ?></label>
            <?php endforeach ?>
          </div>
        </div>

        <div class="task-column">
          <div class="task-title">Saal D</div>
          <div class="checkbox-container">
            <?php foreach ($data['roster'] as $roster): ?>
              <label class="checkbox"><input type="checkbox" name="op_d" value="staff_d"> <?= $roster['name'] ?></label>
            <?php endforeach ?>
          </div>
        </div>

      </div>

    </div>


    <br>

    <form action="" method="post">
      <label for="mod-date">Bitte Datum für die Dienständerung eingeben/auswählen:</label>
      <input type="text" id="mod-date" class="date-entry" name="mod-date" placeholder="TT.MM.JJJJ">
      <button type="submit">Absenden</button>
    </form>



  </section>






  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="views/script.js"></script>



</body>

</html>