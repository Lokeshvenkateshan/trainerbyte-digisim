<?php
$pageTitle = "My Simulations Library";
$pageCSS = "/css/library.css";

require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/include/dataconnect.php';

$teamId = $_SESSION['team_id'] ?? 0;

if ($teamId <= 0) {
    header("Location: " . BASE_PATH . "/login.php");
    exit;
}

/* Fetch all digisims for this team */
$stmt = $conn->prepare("
    SELECT d.di_id,
           d.di_name,
           d.di_createddate,
           d.di_status,
           d.di_description
    FROM mg5_digisim d
    INNER JOIN mg5_digisim_category c 
        ON d.di_digisim_category_pkid = c.lg_id
    WHERE c.lg_team_pkid = ?
    ORDER BY d.di_createddate DESC
");

$stmt->bind_param("i", $teamId);
$stmt->execute();
$result = $stmt->get_result();

$simulations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="library-container">
    <h1>My Simulation Library</h1>

    <?php if (empty($simulations)): ?>
        <div class="empty-state">
            <p>No simulations created yet.</p>
            <a href="<?= BASE_PATH ?>/pages/page-container.php" class="btn-primary">
                Create Your First Simulation
            </a>
        </div>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($simulations as $sim): ?>
                <div class="sim-card">
                    <div class="sim-header">
                        <h3><?= htmlspecialchars($sim['di_name']) ?></h3>
                        <span class="status-badge <?= $sim['di_status'] ? 'active' : 'inactive' ?>">
                            <?= $sim['di_status'] ? 'Active' : 'Draft' ?>
                        </span>
                    </div>

                    <div class="sim-body">
                        <?php
                        $description = trim($sim['di_description'] ?? '');
                        if ($description === '') {
                            $description = "No description available for this simulation.";
                        }
                        ?>
                        <p class="sim-description">
                            <?= htmlspecialchars($description) ?>
                        </p>

                        <p class="sim-date">
                            Created: <?= date("d M Y", strtotime($sim['di_createddate'])) ?>
                        </p>
                    </div>

                    <div class="sim-actions">
                        <a href="<?= BASE_PATH ?>/library/view.php?di_id=<?= $sim['di_id'] ?>" class="btn-secondary">
                            View
                        </a>
                        <a href="<?= BASE_PATH ?>/pages/play_simulation.php?digisim_id=<?= $sim['di_id'] ?>" class="btn-primary">
                            Launch
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>