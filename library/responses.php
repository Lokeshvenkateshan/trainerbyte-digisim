<?php
$pageTitle = "Responses";
$pageCSS = "/library/style/responses.css";

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../include/dataconnect.php';

$digisimId = intval($_GET['di_id'] ?? 0);

if ($digisimId <= 0) {
    echo "Invalid Simulation";
    exit;
}

/* Get score type for this simulation */
$stmt = $conn->prepare("
    SELECT di_scoretype_id
    FROM mg5_digisim
    WHERE di_id = ?
");
$stmt->bind_param("i", $digisimId);
$stmt->execute();
$stmt->bind_result($scoreTypeId);
$stmt->fetch();
$stmt->close();

/* Fetch score values */
$scoreValues = [];
$stmt = $conn->prepare("
    SELECT stv_id, stv_name
    FROM mg5_scoretype_value
    WHERE stv_scoretype_pkid = ?
    ORDER BY stv_value DESC
");
$stmt->bind_param("i", $scoreTypeId);
$stmt->execute();
$result = $stmt->get_result();
$scoreValues = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Fetch responses */
$stmt = $conn->prepare("
    SELECT dr_id,
           dr_tasks,
           dr_score_pkid
    FROM mg5_digisim_response
    WHERE dr_digisim_pkid = ?
    ORDER BY dr_order ASC
");
$stmt->bind_param("i", $digisimId);
$stmt->execute();
$result = $stmt->get_result();
$responses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="library-wrapper">

    <div class="response-header-row">
        <a href="view.php?di_id=<?= $digisimId ?>" class="btn-back">
            ← Back
        </a>

        <h1>Response Configuration</h1>
    </div>

    <p class="response-subtext">
        Edit response statements and assign score classifications.
    </p>

    <?php if (empty($responses)): ?>
        <div class="empty-box">
            No responses found for this simulation.
        </div>
    <?php else: ?>

        <div class="response-grid">

            <?php foreach ($responses as $index => $response): ?>
                <div class="response-card">

                    <div class="response-top">
                        <span class="response-index">
                            Response <?= $index + 1 ?>
                        </span>
                    </div>

                    <div class="form-group">
                        <label>Statement</label>
                        <textarea class="response-text"
                                  data-id="<?= $response['dr_id'] ?>"
                                  rows="4"><?= htmlspecialchars($response['dr_tasks']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Score Type</label>
                        <select class="response-score"
                                data-id="<?= $response['dr_id'] ?>">
                            <?php foreach ($scoreValues as $score): ?>
                                <option value="<?= $score['stv_id'] ?>"
                                    <?= $response['dr_score_pkid'] == $score['stv_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($score['stv_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>
            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <div class="response-footer">
        <button onclick="saveResponses()" class="btn-primary">
            Save Changes
        </button>
    </div>

</div>

<script>
function saveResponses() {

    const texts = document.querySelectorAll('.response-text');
    const scores = document.querySelectorAll('.response-score');

    let responses = [];

    texts.forEach((input, index) => {
        responses.push({
            id: input.dataset.id,
            statement: input.value,
            score: scores[index].value
        });
    });

    fetch('functions/update_responses.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            di_id: <?= $digisimId ?>,
            responses: responses
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Responses updated successfully.");
        } else {
            alert("Error: " + data.error);
        }
    });
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>