// Initiate the page
console.log('Hello from payment-statement create page');


// Fetch all the forms we want to apply custom Bootstrap validation styles to
const forms = document.querySelectorAll('.needs-validation')

// Loop over them and prevent submission
Array.from(forms).forEach(form => {
  form.addEventListener('submit', event => {
    if (!form.checkValidity()) {
      event.preventDefault()
      event.stopPropagation()
    }

    form.classList.add('was-validated')
  }, false)
})

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
    <td><input required type="text" name="expense_kind[]" class="form-control"></td>
    <td><input type="number" name="expense_amount[]" class="form-control" required oninput="toggleExpenseFields(this, 'expense_amount1[]')"></td>
    <td><input type="number" name="expense_amount1[]" class="form-control" required oninput="toggleExpenseFields(this, 'expense_amount[]')"></td>
    <td><input required type="text" name="expense_payee[]" class="form-control"></td>
    <td><input type="text" name="expense_doc[]" class="form-control"></td>
    <td class="align-middle"><button type="button" onclick="deleteRow(this)"><i class="ph ph-trash"></i></button></td>
  `;

  // Append the new row to the table
  tableBody.appendChild(newRow);
}

// Function to toggle 'disabled' status for corresponding expense fields
function toggleExpenseFields(currentInput, otherInputName) {
  const tableRow = currentInput.closest('tr'); // Locate the current row
  const otherInput = tableRow.querySelector(`input[name="${otherInputName}"]`);
  
  if (currentInput.value) {
    const advanceAmount = currentInput.value.replace(/,/g, ''); // Loại bỏ dấu phẩy
    currentInput.value = formatNumber(advanceAmount); // Chèn dấu phẩy vào số
    otherInput.disabled = true;
  } else {
    otherInput.disabled = false;
  }
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
  const advanceAmount = currentInput.value.replace(/,/g, ''); // Loại bỏ dấu phẩy
  currentInput.value = formatNumber(advanceAmount); // Chèn dấu phẩy vào số
}

function formatNumber(num) {
  return num.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
