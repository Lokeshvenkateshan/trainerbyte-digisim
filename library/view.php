<?php
$pageTitle = "Simulation Review";
$pageCSS   = "/library/style/view.css";

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../include/dataconnect.php';

$teamId = $_SESSION['team_id'] ?? 0;
$diId   = isset($_GET['di_id']) ? intval($_GET['di_id']) : 0;

if ($teamId <= 0 || $diId <= 0) {
    header("Location: " . BASE_PATH . "/library.php");
    exit;
}

/* Secure validation */
$stmt = $conn->prepare("
    SELECT d.di_name, d.di_description
    FROM mg5_digisim d
    INNER JOIN mg5_digisim_category c 
        ON d.di_digisim_category_pkid = c.lg_id
    WHERE d.di_id = ?
    AND c.lg_team_pkid = ?
    LIMIT 1
");
$stmt->bind_param("ii", $diId, $teamId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: " . BASE_PATH . "/library.php");
    exit;
}

$digisim = $result->fetch_assoc();
$stmt->close();
?>

<div class="dashboard-container">
    <div class="header-row">
        <a href="<?= BASE_PATH ?>/library.php" class="btn-back">← Back</a>
        <h1><?= htmlspecialchars($digisim['di_name']) ?></h1>
    </div>
    <p class="sim-desc">
        <?= htmlspecialchars($digisim['di_description'] ?? '') ?>
    </p>

    <h2 class="section-title">Review Components</h2>

    <div class="component-grid">

        <!-- COMPANY PROFILE -->
        <div class="component-card">
            <div class="icon">🏢</div>
            <h3>Company Profile</h3>
            <p>View and edit the generated organization case study.</p>
            <a href="<?= BASE_PATH ?>/library/casestudy.php?di_id=<?= $diId ?>">View & Edit →</a>
        </div>

        <!-- INJECTS -->
        <div class="component-card">
            <div class="icon">📨</div>
            <h3>Injects</h3>
            <p>Review all simulation inject messages.</p>
            <a href="<?= BASE_PATH ?>/library/injects.php?di_id=<?= $diId ?>">
                View & Edit →
            </a>
        </div>

        <!-- RESPONSES -->
        <div class="component-card">
            <div class="icon">📝</div>
            <h3>Response Tasks</h3>
            <p>Review and modify participant response options.</p>
            <a href="<?= BASE_PATH ?>/library/responses.php?di_id=<?= $diId ?>">View & Edit →</a>
        </div>

        <!-- ANSWER KEY -->
        <div class="component-card">
            <div class="icon">🔑</div>
            <h3>Answer Key</h3>
            <p>Review logic mapping and explanations.</p>
            <a href="<?= BASE_PATH ?>/library/answerkey.php?di_id=<?= $diId ?>"
                class="section-btn">
                View & Edit →
            </a>
        </div>

        <!-- ANSWER KEY -->
        <div class="component-card">
            <div class="icon">🔑</div>
            <h3>Moderatore Manual</h3>
            <p>Review and assess your game.</p>
            <a href="<?= BASE_PATH ?>/library/manual.php?di_id=<?= $diId ?>"
                class="section-btn">
                View & Edit →
            </a>
        </div>

        <!-- CONFIGURATION -->
        <div class="component-card">
            <div class="icon">⚙️</div>
            <h3>Configuration</h3>
            <p>Review scoring logic and processing settings.</p>
            <a href="configuration.php?di_id=<?= $diId ?>">View & Edit →</a>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>