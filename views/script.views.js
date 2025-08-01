let changedData = false;

// Flatpickr for date fields

flatpickr(".date-entry", {
  locale: "de",
  dateFormat: "d.m.Y", // DD.MM.YYYY
  //defaultDate: document.querySelector(".date-entry").value || "today",
  allowInput: true // Manuelle Eingabe zulassen
});

// Set date in selection field

document.querySelector("#plan-date").addEventListener("change", e => {
  localStorage.setItem("selectedDate", e.target.value);
});

const storedDate = localStorage.getItem("selectedDate");
const storedDateSplitted = storedDate.split(".");
const storedDateYmd = `${storedDateSplitted[2]}-${storedDateSplitted[1]}-${storedDateSplitted[0]}`;

if (storedDate) {
  document.querySelector("#plan-date").value = storedDate;

  if (document.querySelector("#plan-date")._flatpickr) {
    document
      .querySelector("#plan-date")
      ._flatpickr.setDate(storedDate, true, "d.m.Y");
  }
}

// Alert on leaving site w/o saving

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".container-wrapper").forEach(container => {
    container.addEventListener("change", function(e) {
      if (
        e.target.matches('input[type="checkbox"]') ||
        e.target.matches('input[type="radio"]')
      ) {
        changedData = true;
        console.log("Change registered");
      }
    });
  });

  window.addEventListener("beforeunload", function(e) {
    if (changedData) {
      e.preventDefault();
      e.returnValue = "";
      return "";
    }
  });
});

document
  .querySelector('[name="save-changes"]')
  .addEventListener("click", () => {
    changedData = false;
    console.log("Changes saved");
  });

// Save changes on organizer to database

document
  .querySelector('[name="save-changes"]')
  .addEventListener("click", function(e) {
    e.preventDefault();
    const opDoctors = {};
    document.querySelectorAll(".op-checkbox:checked").forEach(cb => {
      const saal = cb.dataset.saal;
      if (!opDoctors[saal]) opDoctors[saal] = [];
      opDoctors[saal].push(cb.value);
    });

    const nightShiftIds = [...document.querySelectorAll(".night-shift")].map(
      el => el.dataset.id
    );

    const onCallNightIds = [...document.querySelectorAll(".on-call-night")].map(
      el => el.dataset.id
    );

    const onCallDayIds = [...document.querySelectorAll(".on-call-day")].map(
      el => el.dataset.id
    );

    // BAUSTELLE
    const lateShiftIds = [...document.querySelectorAll(".late-shift")].map(
      el => el.dataset.id
    );

    const doctorTv = document.querySelector("#doctor-tv:checked").value;

    const pasMorning = Array.from(
      document.querySelectorAll('input[name="pas-morning[]"]:checked')
    ).map(cb => cb.value);

    // Nachmittag
    const pasAfternoon = Array.from(
      document.querySelectorAll('input[name="pas-afternoon[]"]:checked')
    ).map(cb => cb.value);

    const pain = Array.from(
      document.querySelectorAll('input[name="pain[]"]:checked')
    ).map(cb => cb.value);

    // BAUSTELLE ENDE

    const payload = {
      action: "savePlanning",
      date: storedDateYmd,
      nightShift: nightShiftIds,
      onCallNight: onCallNightIds,
      onCallDay: onCallDayIds,
      lateShift: lateShiftIds,
      pain: pain,
      opDoctors: opDoctors,
      doctorTv: doctorTv,
      pasMorning: pasMorning,
      pasAfternoon: pasAfternoon
    };

    fetch("index.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) alert("Einteilung gespeichert!");
        else alert("Fehler: " + data.error);
      });
  });

// Fetch shifts from database

const modDate = document.getElementById("mod-date");
const employeeSelect = document.getElementById("employee");
const trigramInput = document.getElementById("employee-trigram");
const currentShift = document.getElementById("current-shift");
const newShift = document.getElementById("new-shift");
const onCallDay = document.getElementById("on-call-day-scheduled");
const onCallNight = document.getElementById("on-call-night-scheduled");
const changeShift = document.getElementById("change-shift");
const nightShiftList = document.getElementById("night-shift");
const onCallDayList = document.getElementById("on-call-day");
const onCallNightList = document.getElementById("on-call-night");

// Whenever employee changes: Update hidden trigram input
employeeSelect.addEventListener("change", function() {
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
      employee_trigram: trigram
    })
  })
    .then(response => response.json())
    .then(data => {
      console.log("Roh-Response:", data);
      currentShift.textContent = data.current_shift || "-";
      onCallDay.textContent = data.on_call_day_scheduled || "-";
      onCallNight.textContent = data.on_call_night_scheduled || "-";
      console.log("Trigram:", data.trigram);
    })
    .catch(err => {
      console.error(err);
      currentShift.textContent = "-";
    });
}

modDate.addEventListener("change", fetchCurrentShift);
employeeSelect.addEventListener("change", fetchCurrentShift);

// Auslösen Diensttausch

changeShift.addEventListener("click", function() {
  const dateValueRaw = modDate.value.split(".");
  const dateValue = `${dateValueRaw[2]}-${dateValueRaw[1]}-${dateValueRaw[0]}`;
  //const employeeId = employeeSelect.value;
  const trigram = trigramInput.value;
  const currentShiftValue = currentShift.textContent.trim();
  const onCallDayValue = onCallDay.textContent.trim();
  const onCallNightValue = onCallNight.textContent.trim();
  const newShiftValue = newShift.value;
  const newOnCall = document.querySelectorAll(
    'input[name = "new-on-call"]:checked'
  );
  const newOnCallValues = Array.from(newOnCall).map(noc => noc.value);

  console.log("Data to be sent", {
    dateValue,
    currentShiftValue,
    onCallDayValue,
    onCallNightValue,
    newShiftValue,
    newOnCallValues,
    trigram
  });

  console.log(newOnCallValues);

  fetch("index.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      ajax: true,
      action: "changeShift",
      date: dateValue,
      current_shift: currentShiftValue,
      new_shift: newShiftValue,
      current_on_call: [onCallDayValue, onCallNightValue],
      new_on_call: newOnCallValues,
      employee_trigram: trigram
    })
  })
    .then(response => response.text()) // Zum Debuggen lieber erstmal text()
    .then(text => {
      console.log("Response:", text);
      // Wenn dein PHP später sauberes JSON liefert:
      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        console.error("No valid JSON:", text);
        return;
      }

      if (data.success) {
        alert("Dienst wurde erfolgreich getauscht!");
        fetchCurrentShift();
      } else {
        alert("Fehler beim Diensttausch: " + (data.error || "Unbekannt"));
      }
    })
    .catch(err => {
      console.error("Fetch-Error:", err);
    });
});
