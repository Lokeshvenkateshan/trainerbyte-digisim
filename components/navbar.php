<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$navImg = $_SESSION['team_image'] ?? '/ms-digisim/assets/images/user.png';

?>


<link rel="stylesheet" href="/trainerbyte-digisim/assets/css/navbar.css">

<nav class="navbar">
    <!-- LEFT : LOGO -->
    <div class="nav-left">
        <img src="/trainerbyte-digisim/assets/images/logo.png" class="logo" alt="Logo">
    </div>

    <!-- CENTER : MENU -->
    <div class="nav-center">
        <a href="/trainerbyte-digisim/index.php"
           class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
           Home
        </a>

       

        <a href="/trainerbyte-digisim/pages/page-container.php"
           class="<?= $currentPage === 'step1.php' ? 'active' : '' ?>">
           Digisim
        </a>

        <a href="/trainerbyte-digisim/library.php"
           class="<?= $currentPage === 'myevent.php' ? 'active' : '' ?>">
           Library
        </a>

        <!-- <a href="/trainerbyte-digisim/multistage/multistagedigisim.php"
           class="<?= $currentPage === 'simulation_setup.php' ? 'active' : '' ?>">
           MultiStage
        </a> -->
    </div>

    <!--  PROFILE + LOGOUT -->
    <div class="nav-right">
    <a href="/ms-digisim/profile.php">
        <img src="<?= $navImg ?>" class="nav-user" alt="User">
    </a>
        <form action="/ms-digisim/logout.php" method="post">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
</nav>
