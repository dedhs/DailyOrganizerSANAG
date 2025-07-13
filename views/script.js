flatpickr(".date-entry", {
  dateFormat: "d.m.Y", // DD.MM.YYYY
  allowInput: true, // Manuelle Eingabe zulassen
});

// Funktion, um jeweils aktuelle Schicht aus der DB zu holen

const modDate = document.getElementById("mod-date");
const employeeSelect = document.getElementById("employee");
const trigramInput = document.getElementById("employee-trigram");
const currentShift = document.getElementById("current-shift");
const onCallDay = document.getElementById("on-call-day-scheduled");
const onCallNight = document.getElementById("on-call-night-scheduled");

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
