<?php
$pageTitle = "Answer Key";
$pageCSS = "/library/style/answerkey.css";

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../include/dataconnect.php';

$digisimId = intval($_GET['di_id'] ?? 0);

if ($digisimId <= 0) {
    echo "Invalid Simulation";
    exit;
}

/* Fetch answer key */
$stmt = $conn->prepare("
    SELECT di_answerkey
    FROM mg5_digisim
    WHERE di_id = ?
");
$stmt->bind_param("i", $digisimId);
$stmt->execute();
$stmt->bind_result($answerKeyRaw);
$stmt->fetch();
$stmt->close();

$answerKey = $answerKeyRaw ?? '';
?>

<div class="library-wrapper">

    <div class="header-row">
        <a href="view.php?di_id=<?= $digisimId ?>" class="btn-back">
            ← Back
        </a>
        <h1>Answer Key</h1>
    </div>

    <p class="subtext">
        Edit the full debrief narrative and learning objectives.
    </p>

    <div class="editor-card">
        <textarea id="answerKey"
                  rows="18"><?= htmlspecialchars($answerKey) ?></textarea>
    </div>

    <div class="footer-actions">
        <button onclick="saveAnswerKey()" class="btn-primary">
            Save Changes
        </button>
    </div>

</div>

<script>
function saveAnswerKey() {

    const content = document.getElementById('answerKey').value;

    fetch('functions/update_answerkey.php', {
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
            alert("Answer key updated successfully.");
        } else {
            alert("Error: " + data.error);
        }
    });
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>