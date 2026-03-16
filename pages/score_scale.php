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
$existingValues = [];

/* LOAD CURRENT DATA */
$stmt = $conn->prepare("
    SELECT ui_score_scale, ui_score_value
    FROM mg5_digisim_userinput
    WHERE ui_id=? AND ui_team_pkid=?
");
$stmt->bind_param("ii", $simId, $_SESSION['team_id']);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $selectedScaleId = $row['ui_score_scale'];
    if (!empty($row['ui_score_value'])) {
        $existingValues = json_decode($row['ui_score_value'], true);
    }
}
$stmt->close();

/* LOAD SCALE TYPES */
$scoreTypes = [];
$q = $conn->query("SELECT st_id, st_name FROM mg5_scoretype");
while ($r = $q->fetch_assoc()) {
    $scoreTypes[] = $r;
}

/* LOAD SCALE COMPONENTS */
$scaleComponents = [];
$c = $conn->query("
    SELECT stv_scoretype_pkid, stv_name, stv_value, stv_color
    FROM mg5_scoretype_value
    ORDER BY stv_value
");
while ($row = $c->fetch_assoc()) {
    $scaleComponents[$row['stv_scoretype_pkid']][] = $row;
}

/* FORM SUBMIT */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedScaleId = intval($_POST['selected_scale']);

    if (!$selectedScaleId) {
        $errors['scale'] = "Please select a scale";
    }
    $scaleValues = [];
    $total = 0;

    if (isset($scaleComponents[$selectedScaleId])) {

        foreach ($scaleComponents[$selectedScaleId] as $component) {

            $name = $component['stv_name'];
            $field = 'component_' . $name;

            $val = isset($_POST[$field]) ? intval($_POST[$field]) : 0;

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
            SET ui_score_scale=?, ui_score_value=?, ui_cur_step=4
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

<!-- ✅ Unified Page Layout -->
<div class="page-layout">
    <div class="page-content">
        <?php include 'stepper.php'; ?>

        <div class="content-container">
            <div class="page-header">
                <div>
                    <h1>Configure Response Scale</h1>
                    <p>Select a scale set and define response requirements.</p>
                </div>

                <div class="total-card">
                    <span>Total Responses</span>
                    <strong id="totalResponses"><?= array_sum($existingValues) ?></strong>
                </div>
            </div>

            <form method="POST" class="scale-form" id="scaleform">

                <!-- SCALE SELECTION -->
                <div class="scale-grid">
                    <?php foreach ($scoreTypes as $scale): ?>
                        <label class="scale-card <?= $selectedScaleId == $scale['st_id'] ? 'active' : '' ?>">
                            <input type="radio" name="selected_scale" value="<?= $scale['st_id'] ?>"
                                <?= $selectedScaleId == $scale['st_id'] ? 'checked' : '' ?>>
                            <h3><?= htmlspecialchars($scale['st_name']) ?></h3>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- for errors -->
                <?php if (isset($errors['scale'])): ?>
                    <p class="error"><?= $errors['scale'] ?></p>
                <?php endif; ?>

                <!-- COMPONENT AREA -->
                <div class="scale-values-card">
                    <div class="scale-values-header">⚙ Configure Scale Values</div>

                    <div id="noScale" class="empty-state">
                        Select a scale to configure values
                    </div>

                    <?php foreach ($scaleComponents as $scaleId => $components): ?>
                        <div class="scale-group" data-scale="<?= $scaleId ?>" style="display:none;">
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
                                        <input type="number" name="component_<?= htmlspecialchars($name) ?>"
                                            value="<?= $value ?>" min="0" class="scale-input">
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

            </form>
        </div>
    </div>


</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const radios = document.querySelectorAll("input[name='selected_scale']");
        const groups = document.querySelectorAll(".scale-group");
        const empty = document.getElementById("noScale");
        const inputs = document.querySelectorAll(".scale-input");
        const totalDisplay = document.getElementById("totalResponses");

        function showGroup(scaleId) {
            groups.forEach(group => group.style.display = "none");
            const target = document.querySelector('.scale-group[data-scale="' + scaleId + '"]');
            if (target) {
                target.style.display = "block";
                empty.style.display = "none";
            } else {
                empty.style.display = "block";
            }
        }

        radios.forEach(radio => {
            radio.addEventListener("change", function() {
                showGroup(this.value);
            });
        });

        const checked = document.querySelector("input[name='selected_scale']:checked");
        if (checked) showGroup(checked.value);

        function updateTotal() {
            let total = 0;
            document.querySelectorAll(".scale-group:not([style*='display: none']) .scale-input")
                .forEach(input => total += parseInt(input.value) || 0);
            totalDisplay.textContent = total;
        }

        document.querySelectorAll(".plus").forEach(btn => {
            btn.addEventListener("click", function() {
                const input = this.parentElement.querySelector("input");
                input.value = parseInt(input.value || 0) + 1;
                updateTotal();
            });
        });

        document.querySelectorAll(".minus").forEach(btn => {
            btn.addEventListener("click", function() {
                const input = this.parentElement.querySelector("input");
                let value = parseInt(input.value || 0);
                if (value > 0) input.value = value - 1;
                updateTotal();
            });
        });

        inputs.forEach(input => input.addEventListener("input", updateTotal));
        updateTotal();
    });
</script>