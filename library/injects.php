<?php
$pageTitle = "Injects";
$pageCSS = "/library/style/injects.css";

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../include/dataconnect.php';

$digisimId = intval($_GET['di_id'] ?? 0);

if ($digisimId <= 0) {
    echo "Invalid Simulation";
    exit;
}

/* Fetch inject messages */
$stmt = $conn->prepare("
    SELECT m.dm_id,
           m.dm_subject,
           m.dm_message,
           m.dm_trigger,
           c.ch_level
    FROM mg5_digisim_message m
    INNER JOIN mg5_sub_channels c
        ON m.dm_injectes_pkid = c.ch_id
    WHERE m.dm_digisim_pkid = ?
    ORDER BY m.dm_id ASC
");

$stmt->bind_param("i", $digisimId);
$stmt->execute();
$result = $stmt->get_result();
$injects = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="library-wrapper">

    <!-- Header Section -->
    <div class="library-header">
        <div class="inject-header-row">
            <a href="view.php?di_id=<?= $digisimId ?>" class="btn-back">
                ← Back
            </a>

            <h1>Inject Configuration</h1>
        </div>
        <p class="inject-subtext">
            Edit inject content and trigger types for this simulation.
        </p>

    
    </div>

    <?php if (empty($injects)): ?>
        <div class="empty-box">
            No injects found for this simulation.
        </div>
    <?php else: ?>

        <div class="inject-grid">

            <?php foreach ($injects as $index => $inject): ?>
                <div class="inject-card">

                    <div class="inject-top">
                        <span class="inject-index">
                            Inject <?= $index + 1 ?>
                        </span>

                        <span class="inject-media">
                            <?= htmlspecialchars(strtoupper($inject['ch_level'])) ?>
                        </span>
                    </div>

                    <div class="form-group">
                        <label>Trigger Type</label>
                        <select class="inject-trigger" data-id="<?= $inject['dm_id'] ?>">
                            <option value="1" <?= $inject['dm_trigger'] == 1 ? 'selected' : '' ?>>Start</option>
                            <option value="2" <?= $inject['dm_trigger'] == 2 ? 'selected' : '' ?>>Task</option>
                            <option value="3" <?= $inject['dm_trigger'] == 3 ? 'selected' : '' ?>>Progressive</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text"
                            class="inject-subject"
                            data-id="<?= $inject['dm_id'] ?>"
                            value="<?= htmlspecialchars($inject['dm_subject']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Message</label>
                        <textarea class="inject-message"
                            data-id="<?= $inject['dm_id'] ?>"
                            rows="5"><?= htmlspecialchars($inject['dm_message']) ?></textarea>
                    </div>

                </div>
            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <div class="inject-footer">
        <button onclick="saveInjects()" class="btn-primary">
            Save Changes
        </button>
    </div>

</div>

</div>

<script>
    function saveInjects() {

        const subjects = document.querySelectorAll('.inject-subject');
        const messages = document.querySelectorAll('.inject-message');
        const triggers = document.querySelectorAll('.inject-trigger');

        let injects = [];

        subjects.forEach((input, index) => {
            injects.push({
                id: input.dataset.id,
                subject: input.value,
                message: messages[index].value,
                trigger: triggers[index].value
            });
        });

        fetch('functions/update_injects.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    di_id: <?= $digisimId ?>,
                    injects: injects
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("Injects updated successfully.");
                } else {
                    alert("Error: " + data.error);
                }
            });
    }
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>