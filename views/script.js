flatpickr(".date-entry", {
  dateFormat: "d.m.Y", // DD.MM.YYYY
  allowInput: true, // Manuelle Eingabe zulassen
});

// Funktion, um jeweils aktuelle Schicht aus der DB zu holen

const modDate = document.getElementById("mod-date");
const employeeSelect = document.getElementById("employee");
const trigramInput = document.getElementById("employee-trigram");
const currentShift = document.getElementById("current-shift");
const newShift = document.getElementById("new-shift");
const onCallDay = document.getElementById("on-call-day-scheduled");
const onCallNight = document.getElementById("on-call-night-scheduled");
const changeShift = document.getElementById("change-shift");

// Whenever employee changes: Update hidden trigram input
employeeSelect.addEventListener("change", function () {
  const selectedOption = employeeSelect.options[employeeSelect.selectedIndex];
  trigramInput.value = selectedOption.dataset.trigram;
});

function fetchCurrentShift() {
  const dateValue = modDate.value;
  const employeeId = employeeSelect.value;
  const trigram = trigramInput.value;

  if (!dateValue || !employeeId) {
    return;
  }

  fetch("index.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      ajax: true,
      action: "displayCurrentShift",
      date: dateValue,
      employee_id: employeeId,
      employee_trigram: trigram,
    }),
  })
    .then((response) => response.json())
    //.then((response) => response.text())
    .then((data) => {
      console.log("Roh-Response:", data);
      currentShift.textContent = data.current_shift || "-";
      onCallDay.textContent = data.on_call_day_scheduled || "-";
      onCallNight.textContent = data.on_call_night_scheduled || "-";
      console.log("Trigram für spätere API:", data.trigram);
    })
    .catch((err) => {
      console.error(err);
      currentShift.textContent = "-";
    });
}

modDate.addEventListener("change", fetchCurrentShift);
employeeSelect.addEventListener("change", fetchCurrentShift);

// Auslösen Diensttausch

changeShift.addEventListener("click", function () {
  const dateValueRaw = modDate.value.split(".");
  const dateValue = `${dateValueRaw[2]}-${dateValueRaw[1]}-${dateValueRaw[0]}`;
  //const employeeId = employeeSelect.value;
  const trigram = trigramInput.value;
  const currentShiftValue = currentShift.textContent.trim();
  const newShiftValue = newShift.value;

  console.log("Was wird geschickt?", {
    dateValue,
    currentShiftValue,
    newShiftValue,
    trigram,
  });

  fetch("index.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      ajax: true,
      action: "changeShift",
      date: dateValue,
      current_shift: currentShiftValue,
      new_shift: newShiftValue,
      employee_trigram: trigram,
    }),
  })
    .then((response) => response.text()) // Zum Debuggen lieber erstmal text()
    .then((text) => {
      console.log("Response:", text);
      // Wenn dein PHP später sauberes JSON liefert:
      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        console.error("Kein valides JSON:", text);
        return;
      }

      if (data.success) {
        alert("Dienst wurde erfolgreich getauscht!");
      } else {
        alert("Fehler beim Diensttausch: " + (data.error || "Unbekannt"));
      }
    })
    .catch((err) => {
      console.error("Fetch-Fehler:", err);
    });
});
