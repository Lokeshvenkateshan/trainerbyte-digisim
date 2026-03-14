<!DOCTYPE html>
<html>
<head>
    <title>Team Signup</title>
    <link rel="stylesheet" href="assets/styles/signup.css">
</head>

<body>

<div class="signup-box">

    <h2>Create Account</h2>

    <form id="signupForm">

        <label>Name</label>
        <input type="text" name="team_name" required>

        <label>Email</label>
        <input type="email" name="team_login" required>

        <label>Organization</label>
        <input type="text" name="team_org" required>

        <label>Password</label>
        <input type="password" name="team_password" id="team_password" required>

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" id="confirm_password" required>

        <button type="submit" class="btn-signup">Sign Up</button>
    </form>

    <button class="btn-login" onclick="window.location.href='login.php'">
        Back to Login
    </button>

    <p id="msg"></p>

</div>

<script src="js/signup.js"></script>

</body>
</html>
