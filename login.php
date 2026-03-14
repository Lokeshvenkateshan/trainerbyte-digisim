<?php
session_start();

/* Redirect if already logged in */
if (isset($_SESSION["team_id"])) {
    header("Location: index.php");
    exit;
}

/* Session timeout message */
$sessionMsg = "";
if (isset($_GET["timeout"])) {
    $sessionMsg = "Session expired. Please login again.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Team Login</title>
    <link rel="stylesheet" href="assets/styles/login.css">
</head>

<body>

<?php if ($sessionMsg): ?>
    <div class="session-msg"><?= htmlspecialchars($sessionMsg) ?></div>
<?php endif; ?>

<div class="login-box">

    <h2>Team Login</h2>

    <form id="loginForm">
        <label>Email</label>
        <input type="email" name="team_login" required>

        <label>Password</label>
        <div class="password-field">   
            <input type="password" name="team_password" id="password" required>
            <img src="/trainerbyte-digisim/assets/images/eye-open.svg" class="eye-img"
                onclick="togglePasswordImg('password', this)">
        </div>
        

        <button type="submit" class="btn-login">Login</button>
    </form>

    <button class="btn-signup" onclick="window.location.href='signup.php'">
        Create New Account
    </button>

    <p id="msg"></p>
</div>

<script>
document.getElementById("loginForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const msg = document.getElementById("msg");
    msg.innerText = "";
    msg.className = "";

    const formData = new FormData(this);

    fetch("login_action.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        msg.className = data.status === "success" ? "success" : "error";
        msg.innerText = data.message;

        if (data.status === "success") {
            setTimeout(() => {
                window.location.href = "index.php";
            }, 1000);
        }
    })
    .catch(() => {
        msg.className = "error";
        msg.innerText = "Something went wrong. Please try again.";
    });
});



function togglePasswordImg(id, img) {
    const input = document.getElementById(id);
    if (input.type === "password") {
        input.type = "text";
        img.src = "/trainerbyte-digisim/assets/images/eye-closed.svg";
    } else {
        input.type = "password";
        img.src = "/trainerbyte-digisim/assets/images/eye-open.svg";
    }
}

</script>

</body>
</html>
 