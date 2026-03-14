document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("signupForm");
  const msg = document.getElementById("msg");

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    const password = document.getElementById("team_password").value;
    const confirm = document.getElementById("confirm_password").value;

    if (password !== confirm) {
      msg.style.color = "red";
      msg.innerText = "Password and Confirm Password do not match";
      return;
    }

    const formData = new FormData(form);

    fetch("signup_action.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        msg.style.color = data.status === "success" ? "green" : "red";
        msg.innerText = data.message;

        if (data.status === "success") {
          setTimeout(() => {
            window.location.href = "index.php";
            
          }, 1000);
        }
      })
      .catch((err) => {
        msg.className = data.status === "success" ? "success" : "error";
        msg.innerText = "Something went wrong";
      });
  });
});
