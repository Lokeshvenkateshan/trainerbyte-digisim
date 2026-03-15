<?php
$pageTitle = "Digisim Created Successfully";
$pageCSS = "/pages/page-styles/digisim_success.css";

require_once __DIR__ . '/../layout/header.php';



$digisimId = isset($_GET['digisim_id']) ? intval($_GET['digisim_id']) : 0;

if ($digisimId <= 0) {
    header("Location: " . BASE_PATH . "/index.php");
    exit;
}
?>

<div class="success-container">
    <div class="success-card">

        <h2>Simulation Created Successfully</h2>
        <p>Your Digisim has been generated and configured successfully.</p>

        <div class="success-actions">
            <a href="<?= BASE_PATH ?>/manual/manual_page_container.php?step=1&digisim_id=<?= $digisimId ?>" class="btn-secondary">Preview</a>
            <a href="../index.php" class="btn-primary">OK</a>
        </div>
    </div>
</div>

    