const signUpButton = document.getElementById('signUp');
const signInButton = document.getElementById('signIn');
const container = document.getElementById('container');

signUpButton.addEventListener('click', () => {
	container.classList.add("right-panel-active");
});

signInButton.addEventListener('click', () => {
	container.classList.remove("right-panel-active");
});

function validateRegistration() {
    const name = document.getElementById("name");
    const company = document.getElementById("companyname");
    const email = document.getElementById("signup_email");
    const password = document.getElementById("signup_password");
    const repassword = document.getElementById("signup_repassword");

    const name_error = document.getElementById("name_error");
    const company_error = document.getElementById("companyname_error");
    const email_error = document.getElementById("signup_email_error");
    const password_error = document.getElementById("signup_password_error");

    name_error.style.display = "none";
    company_error.style.display = "none";
    email_error.style.display = "none";
    password_error.style.display = "none";

    let isValid = true;

    if (name.value.trim() === "") {
        name_error.textContent = "Name can't be empty";
        name_error.style.display = "block";
        isValid = false;
    }
    if (company.value.trim() === "") {
        company_error.textContent = "Company name can't be empty";
        company_error.style.display = "block";
        isValid = false;
    }
    if (email.value.trim() === "") {
        email_error.textContent = "Email can't be empty";
        email_error.style.display = "block";
        isValid = false;
    }
    if (password.value.trim() === "" || repassword.value.trim() === "") {
        password_error.textContent = "Passwords cannot be empty";
        password_error.style.display = "block";
        isValid = false;
    } else if (password.value.length < 8) {
        password_error.textContent = "Password must be at least 8 characters";
        password_error.style.display = "block";
        isValid = false;
    } else if (password.value !== repassword.value) {
        password_error.textContent = "Passwords do not match";
        password_error.style.display = "block";
        isValid = false;
    }

    return isValid;
}

function validateLogin() {
    const email = document.getElementById("login_email");
    const password = document.getElementById("login_password");
    const email_error = document.getElementById("login_email_error");
    const password_error = document.getElementById("login_password_error");

    email_error.style.display = "none";
    password_error.style.display = "none";

    let isValid = true;

    if (email.value.trim() === "") {
        email_error.textContent = "Email can't be empty";
        email_error.style.display = "block";
        isValid = false;
    }
    if (password.value.trim() === "") {
        password_error.textContent = "Password can't be empty";
        password_error.style.display = "block";
        isValid = false;
    }

    return isValid;
}