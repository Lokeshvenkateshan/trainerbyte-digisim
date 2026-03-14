<?php

$pageTitle = "Simulation Completed";
$pageCSS   = "/manual/css/manual_success.css";

$digisimId = intval($_GET['digisim_id'] ?? 0);

?>

<div class="success-container">

<h1>Simulation Finished</h1>

<p>Your simulation has been successfully created.</p>

<a class="btn-primary"
href="/trainerbyte-digisim/index.php">

Return to Dashboard

</a>

</div>