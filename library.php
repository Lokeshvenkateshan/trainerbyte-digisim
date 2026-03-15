<?php
$pageTitle = "Exercise Library";
$pageCSS = "/css/library.css";

require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/include/dataconnect.php';

$teamId = $_SESSION['team_id'] ?? 0;

if ($teamId <= 0) {
    header("Location: " . BASE_PATH . "/login.php");
    exit;
}

/* Fetch simulations */

$stmt = $conn->prepare("
SELECT d.di_id,
       d.di_name,
       d.di_createddate,
       d.di_description,
       d.di_injects_id,
       d.di_response_id
FROM mg5_digisim d
INNER JOIN mg5_digisim_category c 
        ON d.di_digisim_category_pkid = c.lg_id
WHERE c.lg_team_pkid = ?
ORDER BY d.di_createddate DESC
");

$stmt->bind_param("i", $teamId);
$stmt->execute();
$result = $stmt->get_result();

$simulations = [];

while ($row = $result->fetch_assoc()) {

    $digisimId = $row['di_id'];

    /* COUNT TASKS */

    $taskCount = 0;

    $t = $conn->query("
SELECT COUNT(*) as cnt
FROM mg5_digisim_response
WHERE dr_digisim_pkid=$digisimId
");

    if ($r = $t->fetch_assoc()) {
        $taskCount = $r['cnt'];
    }

    /* COUNT INJECTS */

    $injectCount = 0;

    $i = $conn->query("
SELECT COUNT(*) as cnt
FROM mg5_digisim_message
WHERE dm_digisim_pkid=$digisimId
");

    if ($r = $i->fetch_assoc()) {
        $injectCount = $r['cnt'];
    }

    $row['tasks'] = $taskCount;
    $row['injects'] = $injectCount;

    $simulations[] = $row;
}

$stmt->close();
?>

<div class="library-container">

    <h1>Exercise Library</h1>
    <p class="subtext">Manage and deploy your simulation scenarios.</p>

    <div class="card-grid">

        <!-- CREATE NEW CARD -->

        <div class="sim-card create-card">

            <h3>Create New Simulation</h3>

            <small>start from a template or scratch</small>

            <div class="create-buttons">

                <a href="<?= BASE_PATH ?>/pages/page-container.php"
                    class="btn-ai">

                    AI Assisted

                </a>

                <a href="<?= BASE_PATH ?>/manual/manual_page_container.php"
                    class="btn-manual">

                    Do It Yourself

                </a>

            </div>

        </div>


        <?php foreach ($simulations as $sim): ?>

            <a href="<?= BASE_PATH ?>/manual/manual_page_container.php?step=1&digisim_id=<?= $sim['di_id'] ?>"
                class="sim-card">

                <h3><?= htmlspecialchars($sim['di_name']) ?></h3>

                <p class="sim-description">
                    <?= htmlspecialchars($sim['di_description'] ?? "No description available") ?>
                </p>

                <div class="sim-stats">

                    <div>
                        <span class="stat-value"><?= $sim['tasks'] ?></span>
                        <span class="stat-label">Tasks</span>
                    </div>

                    <div>
                        <span class="stat-value"><?= $sim['injects'] ?></span>
                        <span class="stat-label">Injects</span>
                    </div>

                </div>

                <p class="sim-date">
                    Created: <?= date("d M Y", strtotime($sim['di_createddate'])) ?>
                </p>

            </a>

        <?php endforeach; ?>

    </div>

</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>