<?php
$pageTitle = "Moderator Manual";
$pageCSS = "/library/style/manual.css";

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../include/dataconnect.php';

$digisimId = intval($_GET['di_id'] ?? 0);

if ($digisimId <= 0) {
    echo "Invalid Simulation";
    exit;
}

/* Fetch moderator manual */
$stmt = $conn->prepare("
    SELECT di_manual
    FROM mg5_digisim
    WHERE di_id = ?
");
$stmt->bind_param("i", $digisimId);
$stmt->execute();
$stmt->bind_result($manualRaw);
$stmt->fetch();
$stmt->close();

$manual = $manualRaw ?? '';
?>

<div class="library-wrapper">

    <div class="header-row">
        <a href="view.php?di_id=<?= $digisimId ?>" class="btn-back">
            ← Back
        </a>
        <h1>Moderator Manual</h1>
    </div>

    <p class="subtext">
        Edit the full moderator manual content.
    </p>

    <div class="editor-card">
        <textarea id="manualContent"
                  rows="22"><?= htmlspecialchars($manual) ?></textarea>
    </div>

    <div class="footer-actions">
        <button onclick="saveManual()" class="btn-primary">
            Save Changes
        </button>
    </div>

</div>

<script>
function saveManual() {

    const content = document.getElementById('manualContent').value;

    fetch('functions/update_manual.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            di_id: <?= $digisimId ?>,
            content: content
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Moderator manual updated successfully.");
        } else {
            alert("Error: " + data.error);
        }
    });
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>