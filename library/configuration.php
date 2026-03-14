<?php
$pageTitle = "Configuration";
$pageCSS = "/library/style/configuration.css";

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../include/dataconnect.php';

$digisimId = intval($_GET['di_id'] ?? 0);

if ($digisimId <= 0) {
    echo "Invalid Simulation";
    exit;
}

$stmt = $conn->prepare("
    SELECT di_priority_point,
           di_scoring_logic,
           di_scoring_basis,
           di_total_basis,
           di_result_type
    FROM mg5_digisim
    WHERE di_id = ?
");
$stmt->bind_param("i", $digisimId);
$stmt->execute();
$config = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<div class="config-wrapper">

    <div class="header-row">
        <a href="view.php?di_id=<?= $digisimId ?>" class="btn-back">← Back</a>
        <h1>Inject Configuration</h1>
    </div>

    <div class="config-grid">

        <!-- Priority Points -->
        <div class="config-card">
            <h3>Priority Points</h3>

            <div class="option <?= $config['di_priority_point']==1?'active':'' ?>"
                 onclick="setValue('priority',1,this)">
                <h4>Expert</h4>
                <p>Automated weight calculation based on preset expert patterns</p>
            </div>

            <div class="option <?= $config['di_priority_point']==2?'active':'' ?>"
                 onclick="setValue('priority',2,this)">
                <h4>Manual</h4>
                <p>Custom score assignment for granular prioritization</p>
            </div>
        </div>

        <!-- Scoring Logic -->
        <div class="config-card">
            <h3>Scoring Logic</h3>

            <div class="option <?= $config['di_scoring_logic']==1?'active':'' ?>"
                 onclick="setValue('logic',1,this)">
                <h4>Atleast</h4>
                <p>Minimum threshold to pass</p>
            </div>

            <div class="option <?= $config['di_scoring_logic']==2?'active':'' ?>"
                 onclick="setValue('logic',2,this)">
                <h4>Actual</h4>
                <p>Exact score calculation</p>
            </div>

            <div class="option <?= $config['di_scoring_logic']==3?'active':'' ?>"
                 onclick="setValue('logic',3,this)">
                <h4>Absolute</h4>
                <p>Fixed score threshold</p>
            </div>
        </div>

        <!-- Scoring Basis -->
        <div class="config-card">
            <h3>Scoring Basis</h3>

            <div class="option <?= $config['di_scoring_basis']==1?'active':'' ?>"
                 onclick="setValue('basis',1,this)">
                <h4>All</h4>
                <p>Calculate score based on entire task set</p>
            </div>

            <div class="option <?= $config['di_scoring_basis']==2?'active':'' ?>"
                 onclick="setValue('basis',2,this)">
                <h4>Part</h4>
                <p>Calculate score based on subset of tasks</p>
            </div>

            <div class="option <?= $config['di_scoring_basis']==3?'active':'' ?>"
                 onclick="setValue('basis',3,this)">
                <h4>Minimum</h4>
                <p>Calculate score based on minimum required tasks</p>
            </div>
        </div>

        <!-- Total Basis -->
        <div class="config-card">
            <h3>Total Basis</h3>

            <div class="option <?= $config['di_total_basis']==1?'active':'' ?>"
                 onclick="setValue('total',1,this)">
                <h4>All Tasks</h4>
                <p>Calculate score based on all tasks</p>
            </div>

            <div class="option <?= $config['di_total_basis']==2?'active':'' ?>"
                 onclick="setValue('total',2,this)">
                <h4>Marked Tasks Only</h4>
                <p>Only flagged tasks used for evaluation</p>
            </div>
        </div>

        <!-- Result Type -->
        <div class="config-card">
            <h3>Task Result Display</h3>

            <div class="option <?= $config['di_result_type']==2?'active':'' ?>"
                 onclick="setValue('result',2,this)">
                <h4>Percentage</h4>
                <p>e.g. 85%</p>
            </div>

            <div class="option <?= $config['di_result_type']==3?'active':'' ?>"
                 onclick="setValue('result',3,this)">
                <h4>Raw Score</h4>
                <p>e.g. 42/50</p>
            </div>

            <div class="option <?= $config['di_result_type']==4?'active':'' ?>"
                 onclick="setValue('result',4,this)">
                <h4>Legend</h4>
                <p>Performance tiers</p>
            </div>
        </div>

    </div>

    <div class="footer-actions">
        <button onclick="saveConfig()" class="btn-primary">Save Changes</button>
    </div>

</div>

<script>
let configValues = {
    priority: <?= $config['di_priority_point'] ?>,
    logic: <?= $config['di_scoring_logic'] ?>,
    basis: <?= $config['di_scoring_basis'] ?>,
    total: <?= $config['di_total_basis'] ?>,
    result: <?= $config['di_result_type'] ?>
};

function setValue(type, value, element) {
    configValues[type] = value;

    element.parentNode.querySelectorAll('.option')
        .forEach(el => el.classList.remove('active'));

    element.classList.add('active');
}

function saveConfig() {
    fetch('functions/update_configuration.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            di_id: <?= $digisimId ?>,
            ...configValues
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Configuration updated successfully.");
        } else {
            alert("Error: " + data.error);
        }
    });
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>