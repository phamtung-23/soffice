// Initiate the page
// console.log("Hello from payment-statement create page");

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
  const tableBody = document.querySelector(".tableBody");
  const rowIndex = tableBody.rows.length;

  const newRow = document.createElement("tr");
  newRow.innerHTML = `
    <td>${rowIndex + 1}</td>
    <td><input type="text" name="expense_kind[]" class="form-control" required></td>
    <td><input type="text" name="expense_amount[]" class="form-control expense-amount" required oninput="toggleExpenseFields(this)"></td>
    <td><input type="text" name="so_hoa_don[]" class="form-control"></td>
    <td><input type="text" name="expense_payee[]" class="form-control expense-payee" required></td>
    <td><input type="text" name="expense_doc[]" class="form-control"></td>
    <td class="text-center align-middle"><input class="form-check-input" type="checkbox" name="expense_vat[]"></td>
    <td><input class="form-control" type="file" name="expense_file[${rowIndex}][]" multiple></td>
    <td class="align-middle">
      <button onclick="deleteRow(this)"><i class="ph ph-trash"></i></button>
    </td>
  `;
  tableBody.appendChild(newRow);
}

// Function to toggle 'disabled' status for corresponding expense fields
function toggleExpenseFields(currentInput) {
  const tableRow = currentInput.closest("tr"); // Locate the current row

  if (currentInput.value) {
    const advanceAmount = currentInput.value.replace(/\./g, ""); // Loại bỏ dấu phẩy
    // check if not a number
    if (isNaN(advanceAmount)) {
      alert("Vui lòng nhập số");
      currentInput.value = "";
      return;
    }
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
  const advanceAmount = currentInput.value.replace(/\./g, ""); // Loại bỏ dấu phẩy
  currentInput.value = formatNumber(advanceAmount); // Chèn dấu phẩy vào số
}

function formatNumber(num) {
  return num.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

const submitForm = document.getElementById("expenseForm");

submitForm.addEventListener("submit", function (event) {
  if (!submitForm.checkValidity()) {
    event.preventDefault();
    event.stopPropagation();
    submitForm.classList.add("was-validated");
  } else {
    event.preventDefault();

    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach((checkbox) => {
      if (!checkbox.checked) {
        checkbox.checked = true;
        checkbox.value = "off";
      }
    });
    // disable the submit button
    submitBtn.disabled = true;
    const formData = new FormData(submitForm);
    // // Log each key-value pair for debugging
    for (const [key, value] of formData.entries()) {
      console.log(`${key}:`, value);
    }

    let timerInterval;
    Swal.fire({
        title: "Saving...!",
        html: "Please wait for a moment.",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
        willClose: () => {
            clearInterval(timerInterval);
        }
    }).then((result) => {
        /* Read more about handling dismissals below */
        // if (result.dismiss === Swal.DismissReason.timer) {
        //     console.log("I was closed by the timer");
        // }
    });

    fetch("submit_payment.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Approval status updated successfully!',
            showConfirmButton: false,
            timer: 1500
          }).then(() => {
            // enable the submit button
            submitBtn.disabled = false;
            window.location.href = "../../index.php";
          });
        } else {
          Swal.close();
          alert(data.error);
          // enable the submit button
          submitBtn.disabled = false;
        }
      })
      .catch((error) => {
        Swal.close();
        console.error("Error:", error);
        // enable the submit button
        submitBtn.disabled = false;
      });
  }
});

document.getElementById("addRowPayment").addEventListener("click", function () {
  // Lấy container chứa các hàng hiện tại
  const container = document.getElementById("payment-info-container");
  const rowCount = container.querySelectorAll(".row.mb-3.row-payment").length + 1;

  // Tạo một hàng mới
  const newRow = document.createElement("div");
  newRow.classList.add(
    "row",
    "mb-3",
    "row-payment",
    "mt-3",
    "ps-4",
    "d-flex",
    "align-items-center"
  );

  // Nội dung HTML của hàng mới
  newRow.innerHTML = `
    <div class="col-sm-2 pb-2">
            <input type="text" class="form-control" name="customFieldName[]" placeholder="Ex: Custom Value Name" >
          </div>
          <div class="col-sm-2 pb-2">
            <input type="text" class="form-control" name="customField[]" placeholder="Ex: 1.000.000"  oninput="toggleExpenseFields(this)">
          </div>
          <div class="col-sm-1 d-flex pb-2 flex-column">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="customUnit_${rowCount}" id="customUnit_${rowCount}_VND" value="VND" checked>
                <label class="form-check-label" for="customUnit_${rowCount}_VND">VND</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="customUnit_${rowCount}" id="customUnit_${rowCount}_USD" value="USD">
                <label class="form-check-label" for="customUnit_${rowCount}_USD">USD</label>
            </div>
          </div>
          <div class="col-sm-2 d-flex pb-2">
            <label for="customVat" class="col-form-label">V.A.T</label>
            <div class="input-group ps-2">
              <input type="text" class="form-control" name="customVat[]" placeholder="%" >
              <span class="input-group-text">%</span>
            </div>
          </div>
          <div class="form-check col-sm-2 d-flex flex-column gap-2 align-items-start pb-2">
            <select class="form-select" aria-label="Default select example" name="customContSet[]" >
              <option selected disabled value="">Choose Cont/Set</option>
              <option value="cont">Cont</option>
              <option value="set">Set</option>
            </select>
          </div>
          <div class="form-check col-sm-1 d-flex gap-2 align-items-center pb-2">
            <input class="form-check-input" type="checkbox" name="customIncl[]">
            <label class="form-check-label">
              INCL
            </label>
          </div>
          <div class="form-check col-sm-1 d-flex gap-2 align-items-center pb-2">
            <input class="form-check-input" type="checkbox" name="customExcl[]">
            <label class="form-check-label">
              EXCL
            </label>
          </div>
          <div class="form-check col-sm-1 d-flex justify-content-end gap-2 align-items-center pb-2">
            <button onclick="deleteRowPayment(this)"><i class="ph ph-trash"></i></button>
          </div>
  `;

  // Thêm hàng mới vào container
  container.appendChild(newRow);
});

function deleteRowPayment(button) {
  // Find the parent row (div) containing the button and remove it
  const row = button.closest('.row');
  if (row) {
    row.remove();
  }
}



const soTienInput = document.getElementById('ops_total');

document.addEventListener('DOMContentLoaded', function() {
  // Initialize the total amount for "ops" payees
  updateTotalOpsAmount();

  // Initialize `data-prev-value` for all `expense-payee` inputs
  document.querySelectorAll('.expense-payee').forEach(payeeInput => {
    payeeInput.setAttribute('data-prev-value', payeeInput.value.trim().toLowerCase());
  });
});

document.addEventListener('input', function(event) {
  if (event.target.classList.contains('expense-amount')) {
    updateAmountText(event.target); // Format the input value
    updateTotalOpsAmount(); // Recalculate the total
  }

  if (event.target.classList.contains('expense-payee')) {
    handlePayeeChange(event.target);
  }
});

function updateTotalOpsAmount() {
  const rows = document.querySelectorAll('.tableBody tr');
  let totalOpsAmount = 0;

  rows.forEach(row => {
    const amountInput = row.querySelector('.expense-amount');
    const payeeInput = row.querySelector('.expense-payee');

    if (payeeInput && payeeInput.value.trim().toLowerCase() === 'ops') {
      const amount = parseFloat(amountInput.value.replace(/\./g, '')) || 0; // Strip commas for calculation
      totalOpsAmount += amount;
    }
  });

  // console.log('Total expense amount for payee "ops":', totalOpsAmount);
  soTienInput.value = formatNumber(totalOpsAmount.toString());
}

function handlePayeeChange(payeeInput) {
  const row = payeeInput.closest('tr');
  const amountInput = row.querySelector('.expense-amount');
  const previousValue = payeeInput.getAttribute('data-prev-value') || '';
  const newValue = payeeInput.value.trim().toLowerCase();
  const amount = parseFloat(amountInput.value.replace(/\./g, '')) || 0;

  if (previousValue === 'ops' && newValue !== 'ops') {
    updateTotalOpsAmount(); // Recalculate after removing 'ops'
  } else if (previousValue !== 'ops' && newValue === 'ops') {
    updateTotalOpsAmount(); // Recalculate after adding 'ops'
  }

  // Update the previous value
  payeeInput.setAttribute('data-prev-value', newValue);
}

