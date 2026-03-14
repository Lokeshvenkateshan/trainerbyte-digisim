<?php

$pageTitle = "Simulation Created Successfully";
$pageCSS = "/css/digisim_success.css";

$msId = isset($_GET['ms_id']) ? intval($_GET['ms_id']) : 0;

if ($msId <= 0) {
    header("Location: ../index.php");
    exit;
}

?>

<div class="success-container">

```
<div class="success-card">

    <div class="success-icon">✓</div>

    <h2>Multi-Stage Simulation Generated</h2>

    <p>Your simulation stages were successfully created.</p>

    <p>All rounds have been combined into one multistage experience.</p>

    <div class="success-actions">

        <a href="../index.php" class="btn-primary">
            OK
        </a>

    </div>

</div>
```

</div>
