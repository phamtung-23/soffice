
function convertNumberToTextVND(total) {
  try {
      let rs = "";
      let ch = ["không", "một", "hai", "ba", "bốn", "năm", "sáu", "bảy", "tám", "chín"];
      let rch = ["lẻ", "mốt", "", "", "", "lăm"];
      let u = ["", "mươi", "trăm", "ngàn", "", "", "triệu", "", "", "tỷ", "", "", "ngàn", "", "", "triệu"];
      let nstr = total.toString();
      let n = Array.from(nstr).reverse().map(Number);
      let len = n.length;

      for (let i = len - 1; i >= 0; i--) {
          if (i % 3 === 2) {
              if (n[i] === 0 && n[i - 1] === 0 && n[i - 2] === 0) continue;
          } else if (i % 3 === 1) {
              if (n[i] === 0) {
                  if (n[i - 1] === 0) continue;
                  else {
                      rs += " " + rch[n[i]];
                      continue;
                  }
              }
              if (n[i] === 1) {
                  rs += " mười";
                  continue;
              }
          } else if (i !== len - 1) {
              if (n[i] === 0) {
                  if (i + 2 <= len - 1 && n[i + 2] === 0 && n[i + 1] === 0) continue;
                  rs += " " + (i % 3 === 0 ? u[i] : u[i % 3]);
                  continue;
              }
              if (n[i] === 1) {
                  rs += " " + ((n[i + 1] === 1 || n[i + 1] === 0) ? ch[n[i]] : rch[n[i]]);
                  rs += " " + (i % 3 === 0 ? u[i] : u[i % 3]);
                  continue;
              }
              if (n[i] === 5) {
                  if (n[i + 1] !== 0) {
                      rs += " " + rch[n[i]];
                      rs += " " + (i % 3 === 0 ? u[i] : u[i % 3]);
                      continue;
                  }
              }
          }
          rs += (rs === "" ? " " : ", ") + ch[n[i]];
          rs += " " + (i % 3 === 0 ? u[i] : u[i % 3]);
      }

      rs = rs.trim().replace(/lẻ,|mươi,|trăm,|mười,/g, match => match.slice(0, -1));

      if (rs.slice(-1) !== " ") {
          rs += " đồng";
      } else {
          rs += "đồng";
      }

      return rs.charAt(0).toUpperCase() + rs.slice(1);
  } catch (ex) {
      console.error(ex);
      return "";
  }
}

function updateAmountText(number) {
  // const advanceAmountInput = document.getElementById('advance-amount');
  // const advanceAmount = advanceAmountInput.value.replace(/,/g, ''); // Loại bỏ dấu phẩy
  // advanceAmountInput.value = formatNumber(advanceAmount); // Chèn dấu phẩy vào số
  const advanceAmountText = convertNumberToTextVND(number);
  document.getElementById('advance-amount-words').value = advanceAmountText;
  console.log(advanceAmountText);
}

function formatNumber(num) {
  return num.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}


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

// Toggle the responsive class to show/hide the menu
function toggleMenu() {
    var menu = document.querySelector(".menu");
    menu.classList.toggle("responsive");
  }




