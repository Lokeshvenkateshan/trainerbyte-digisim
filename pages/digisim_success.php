<?php
$pageTitle = "Digisim Created Successfully";
$pageCSS = "/css/digisim_success.css";


$digisimId = isset($_GET['digisim_id']) ? intval($_GET['digisim_id']) : 0;

if ($digisimId <= 0) {
    header("Location: " . BASE_PATH . "/index.php");
    exit;
}
?>

<div class="success-container">
    <div class="success-card">
        <div class="success-icon">✅</div>

        <h2>Simulation Created Successfully</h2>
        <p>Your Digisim has been generated and configured successfully.</p>

        <div class="success-actions">
            <a href="../index.php" class="btn-primary">OK</a>
            <a href="./page-container.php" class="btn-secondary">Create New</a>
        </div>
    </div>
</div>

    