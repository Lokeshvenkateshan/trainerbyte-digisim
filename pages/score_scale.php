<?php
$pageTitle = 'Configure Response Scale';
$pageCSS = '/pages/page-styles/score_scale.css';

require_once __DIR__ . '/../include/dataconnect.php';

$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

if ($simId <= 0) {
    header("Location: page-container.php?step=1");
    exit;
}

$errors = [];
$selectedScaleId = null;

/* GET CURRENT DATA */

$stmt = $conn->prepare("
SELECT ui_score_scale, ui_score_value
FROM mg5_digisim_userinput
WHERE ui_id=? AND ui_team_pkid=?
");

$stmt->bind_param("ii", $simId, $_SESSION['team_id']);
$stmt->execute();
$res = $stmt->get_result();

$existingValues = [];

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $selectedScaleId = $row['ui_score_scale'];

    if (!empty($row['ui_score_value'])) {
        $existingValues = json_decode($row['ui_score_value'], true);
    }
}

$stmt->close();

/* LOAD SCALES */

$scoreTypes = [];
$q = $conn->query("SELECT st_id, st_name FROM mg5_scoretype");

while ($r = $q->fetch_assoc()) {
    $scoreTypes[] = $r;
}

/* LOAD ALL COMPONENTS GROUPED BY SCALE */

$scaleComponents = [];

$c = $conn->query("
SELECT stv_scoretype_pkid, stv_name, stv_value, stv_color
FROM mg5_scoretype_value
ORDER BY stv_value
");

while ($row = $c->fetch_assoc()) {
    $scaleComponents[$row['stv_scoretype_pkid']][] = $row;
}

/* SUBMIT */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $selectedScaleId = intval($_POST['selected_scale']);

    $scaleValues = [];
    $total = 0;

    foreach ($_POST as $key => $value) {

        if (strpos($key, 'component_') === 0) {

            $name = str_replace('component_', '', $key);
            $val = intval($value);

            $scaleValues[$name] = $val;
            $total += $val;
        }
    }

    if ($total <= 0) {
        $errors['total'] = "Total responses must be greater than zero";
    } else {

        $json = json_encode($scaleValues);

        $update = $conn->prepare("
        UPDATE mg5_digisim_userinput
        SET ui_score_scale=?,
        ui_score_value=?,
        ui_cur_step=4
        WHERE ui_id=? AND ui_team_pkid=?
        ");

        $update->bind_param("isii", $selectedScaleId, $json, $simId, $_SESSION['team_id']);
        $update->execute();
        $update->close();

        header("Location: page-container.php?step=4&sim_id=" . $simId);
        exit;
    }
}
?>

<div class="container">

    <div class="page-header">
        <h1>Configure Response Scale</h1>
        <p>Select a scale and define response counts</p>

        <div class="total-box">
            <span>Total Responses</span>
            <strong id="totalResponses"><?= array_sum($existingValues) ?></strong>
        </div>
    </div>


    <form method="POST">

        <!-- SCALE SELECTION -->

        <div class="scale-grid">

            <?php foreach ($scoreTypes as $scale): ?>

                <label class="scale-card <?= $selectedScaleId == $scale['st_id'] ? 'active' : '' ?>">

                    <input type="radio"
                        name="selected_scale"
                        value="<?= $scale['st_id'] ?>"
                        <?= $selectedScaleId == $scale['st_id'] ? 'checked' : '' ?>>

                    <h3><?= htmlspecialchars($scale['st_name']) ?></h3>

                </label>

            <?php endforeach; ?>

        </div>


        <!-- COMPONENTS -->

        <div class="components-section">

            <div id="noScale" class="empty">
                Select a scale to configure values
            </div>

            <?php foreach ($scaleComponents as $scaleId => $components): ?>

                <div class="scale-group" data-scale="<?= $scaleId ?>">

                    <?php foreach ($components as $comp):

                        $name = $comp['stv_name'];
                        $value = $existingValues[$name] ?? 0;
                        $class = strtolower($name);

                    ?>

                        <div class="component-row">

                            <div class="component-info">

                                <span class="priority-icon <?= $class ?>">
                                    <?= strtoupper(substr($name, 0, 1)) ?>
                                </span>

                                <div>
                                    <strong><?= htmlspecialchars($name) ?></strong>
                                    <p><?= strtolower($name) ?> responses</p>
                                </div>

                            </div>

                            <div class="counter">

                                <button type="button" class="minus">−</button>

                                <input type="number"
                                    name="component_<?= htmlspecialchars($name) ?>"
                                    value="<?= $value ?>"
                                    min="0"
                                    class="scale-input">

                                <button type="button" class="plus">+</button>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endforeach; ?>

        </div>


        <?php if (isset($errors['total'])): ?>
            <p class="error"><?= $errors['total'] ?></p>
        <?php endif; ?>


        <div class="form-actions">

            <a href="page-container.php?step=2&sim_id=<?= $simId ?>" class="btn-secondary">Back</a>

            <button type="submit" class="btn-primary">Next</button>

        </div>

    </form>

</div>


<script>
    document.addEventListener("DOMContentLoaded", function() {

        const radios = document.querySelectorAll("input[name='selected_scale']");
        const cards = document.querySelectorAll(".scale-card");
        const groups = document.querySelectorAll(".scale-group");
        const empty = document.getElementById("noScale");
        const inputs = document.querySelectorAll(".scale-input");
        const totalDisplay = document.getElementById("totalResponses");

        /* SHOW SCALE GROUP */

        function showGroup(scaleId) {

            groups.forEach(group => {
                group.style.display = "none";
            });

            const target = document.querySelector('.scale-group[data-scale="' + scaleId + '"]');

            if (target) {
                target.style.display = "block";
                empty.style.display = "none";
            } else {
                empty.style.display = "block";
            }

        }

        /* SCALE CARD CLICK */

        cards.forEach(card => {

            card.addEventListener("click", function() {

                cards.forEach(c => c.classList.remove("active"));

                this.classList.add("active");

                const radio = this.querySelector("input[type='radio']");

                if (radio) {
                    radio.checked = true;
                    showGroup(radio.value);
                }

            });

        });

        /* RADIO CHANGE */

        radios.forEach(radio => {

            radio.addEventListener("change", function() {

                showGroup(this.value);

                cards.forEach(c => c.classList.remove("active"));
                this.closest(".scale-card").classList.add("active");

            });

        });

        /* INITIAL LOAD */

        const checked = document.querySelector("input[name='selected_scale']:checked");

        if (checked) {
            showGroup(checked.value);
            checked.closest(".scale-card").classList.add("active");
        } else {
            empty.style.display = "block";
        }

        /* UPDATE TOTAL */

        function updateTotal() {

            let total = 0;

            inputs.forEach(input => {
                total += parseInt(input.value) || 0;
            });

            totalDisplay.textContent = total;

        }

        /* PLUS BUTTON */

        document.querySelectorAll(".plus").forEach(btn => {

            btn.addEventListener("click", function() {

                const input = this.parentElement.querySelector("input");

                input.value = parseInt(input.value || 0) + 1;

                updateTotal();

            });

        });

        /* MINUS BUTTON */

        document.querySelectorAll(".minus").forEach(btn => {

            btn.addEventListener("click", function() {

                const input = this.parentElement.querySelector("input");

                let value = parseInt(input.value || 0);

                if (value > 0) {
                    input.value = value - 1;
                }

                updateTotal();

            });

        });

        /* INPUT CHANGE */

        inputs.forEach(input => {
            input.addEventListener("input", updateTotal);
        });

        updateTotal();

    });
</script>