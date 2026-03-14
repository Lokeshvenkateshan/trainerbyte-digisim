<?php

session_start();

$pageCSS = "/css/page-container.css";

require_once __DIR__ . '/../include/dataconnect.php';

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$simId = isset($_GET['sim_id']) ? (int)$_GET['sim_id'] : 0;

$steps = [

1 => 'simulation_setup.php',

// next steps will be added later
2 => 'stage_builder.php',
3 => 'processing_configuration.php',
4 => 'review_simulation.php',
// 5 => 'success.php'

];

if (!array_key_exists($step,$steps)) {
    $step = 1;
}

$pageFile = __DIR__.'/'.$steps[$step];

ob_start();
require $pageFile;
$pageContent = ob_get_clean();

require_once __DIR__ . '/../layout/header.php';

echo '<div class="page-container">';
echo $pageContent;
echo '</div>';

require_once __DIR__ . '/../layout/footer.php';
?>
