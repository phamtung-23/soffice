// Initiate the page
console.log("Hello from payment-statement create page");

// Fetch all the forms we want to apply custom Bootstrap validation styles to
const forms = document.querySelectorAll(".needs-validation");

const submitBtn = document.getElementById("submitButton");

// Loop over them and prevent submission
Array.from(forms).forEach((form) => {
  form.addEventListener(
    "submit",
    (event) => {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }

      form.classList.add("was-validated");
    },
    false
  );
});

function addRow() {
  // Get the table body element
  const tableBody = document.querySelector(".tableBody");

  // Count current rows to assign row number
  const rowCount = tableBody.rows.length + 1;

  // Create a new row
  const newRow = document.createElement("tr");

  // Define new cells and input fields for the row
  newRow.innerHTML = `
    <td>${rowCount}</td>
    <td><input type="text" name="expense_kind[]" class="form-control" required></td>
    <td><input type="text" name="expense_amount[]" class="form-control" required oninput="toggleExpenseFields(this)"></td>
    <td><input type="text" name="so_hoa_don[]" class="form-control"></td>
    <td><input type="text" name="expense_payee[]" class="form-control" required></td>
    <td><input type="text" name="expense_doc[]" class="form-control"></td>
    <td><input class="form-control" type="file" id="formFile" name="expense_file[]"></td>
    <td class="align-middle"><button onclick="deleteRow(this)"><i class="ph ph-trash"></i></button></td>
  `;

  // Append the new row to the table
  tableBody.appendChild(newRow);
}

// Function to toggle 'disabled' status for corresponding expense fields
function toggleExpenseFields(currentInput) {
  const tableRow = currentInput.closest("tr"); // Locate the current row

  if (currentInput.value) {
    const advanceAmount = currentInput.value.replace(/,/g, ""); // Loại bỏ dấu phẩy
    currentInput.value = formatNumber(advanceAmount); // Chèn dấu phẩy vào số
  }
}

// Toggle the responsive class to show/hide the menu
function toggleMenu() {
  var menu = document.querySelector(".menu");
  menu.classList.toggle("responsive");
}

function deleteRow(button) {
  const row = button.closest("tr");
  // Remove the row from the table
  row.remove();

  // Re-number the rows after deletion
  const tableBody = document.querySelector(".tableBody");
  Array.from(tableBody.rows).forEach((row, index) => {
    row.cells[0].textContent = index + 1;
  });
}

function updateAmountText(currentInput) {
  const advanceAmount = currentInput.value.replace(/,/g, ""); // Loại bỏ dấu phẩy
  currentInput.value = formatNumber(advanceAmount); // Chèn dấu phẩy vào số
}

function formatNumber(num) {
  return num.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

const submitForm = document.getElementById("expenseForm");

submitForm.addEventListener("submit", function (event) {
  event.preventDefault();
  // disable the submit button
  submitBtn.disabled = true;
  const formData = new FormData(submitForm);

  // // Log each key-value pair for debugging
  // for (const [key, value] of formData.entries()) {
  //   console.log(`${key}:`, value);
  // }

  fetch("submit_payment.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("Expenses saved successfully!");
        // enable the submit button
        submitBtn.disabled = false;
        window.location.href = "../../index.php";
      } else {
        alert(data.error);
        // enable the submit button
        submitBtn.disabled = false;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      // enable the submit button
      submitBtn.disabled = false;
    });
});
