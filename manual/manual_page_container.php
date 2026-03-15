<?php

session_start();

// $pageCSS = "/manual/css/manual.css";

require_once __DIR__ . '/../include/dataconnect.php';

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$digisimId = isset($_GET['digisim_id']) ? (int)$_GET['digisim_id'] : 0;

$steps = [

    1 => 'manual_simulation_setup.php',
    2 => 'manual_inject_setup.php',
    3 => 'manual_response_setup.php',
    4 => 'manual_processing_configuration.php',
    5 => 'manual_answer_manual.php',
    6 => 'manual_success.php'

];

if (!array_key_exists($step, $steps)) {
    $step = 1;
}

$pageFile = __DIR__ . '/' . $steps[$step];

ob_start();
require $pageFile;
$pageContent = ob_get_clean();

/* LOAD HEADER */

$hideNavbar = true;
require_once __DIR__ . '/../layout/header.php';

/* PRINT PAGE */

echo '<div class="page-container">';
echo $pageContent;
echo '</div>';

/* FOOTER */

require_once __DIR__ . '/../layout/footer.php';