function bookService(serviceName) {
    window.location.href =
        "../php/book_redirect.php?service=" +
        encodeURIComponent(serviceName);
}


/* =========================
   BOOKING PAGE
   ========================= */

const serviceSelect = document.getElementById("service");
const hoursSelect = document.getElementById("hours");
const hourlyRate = document.getElementById("hourlyRate");
const totalAmount = document.getElementById("totalAmount");

function calculateTotal() {
    if (!serviceSelect || !hoursSelect) {
        return;
    }

    const selectedOption =
        serviceSelect.options[serviceSelect.selectedIndex];

    const price = Number(
        selectedOption.dataset.price || 0
    );

    const hours = Number(
        hoursSelect.value || 0
    );

    const total = price * hours;

    if (hourlyRate) {
        hourlyRate.textContent = price;
    }

    if (totalAmount) {
        totalAmount.textContent = total;
    }
}

if (serviceSelect) {
    serviceSelect.addEventListener(
        "change",
        calculateTotal
    );
}

if (hoursSelect) {
    hoursSelect.addEventListener(
        "change",
        calculateTotal
    );
}


/* Select service automatically */
const urlParameters =
    new URLSearchParams(window.location.search);

const selectedService =
    urlParameters.get("service");

if (serviceSelect && selectedService) {

    for (
        let i = 0;
        i < serviceSelect.options.length;
        i++
    ) {

        const optionText =
            serviceSelect.options[i]
                .textContent
                .trim()
                .toLowerCase();

        if (
            optionText.startsWith(
                selectedService
                    .trim()
                    .toLowerCase()
            )
        ) {
            serviceSelect.selectedIndex = i;
            break;
        }
    }

    calculateTotal();
}


/* =========================
   REGISTER FORM
   ========================= */

const registerForm =
    document.getElementById("registerForm");

const registerMessage =
    document.getElementById("registerMessage");

if (registerForm) {

    registerForm.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();

            const password =
                document.getElementById(
                    "registerPassword"
                ).value;

            const confirmPassword =
                document.getElementById(
                    "confirmPassword"
                ).value;

            const fullName =
                document.getElementById(
                    "fullName"
                ).value;

            const role =
                document.getElementById(
                    "userRole"
                ).value;

            if (password !== confirmPassword) {

                registerMessage.style.color =
                    "red";

                registerMessage.innerHTML =
                    "Passwords do not match. Please try again.";

                return;
            }

            registerMessage.style.color =
                "green";

            registerMessage.innerHTML =
                "Account created successfully for " +
                fullName +
                " as " +
                role +
                ".";

            registerForm.reset();
        }
    );
}