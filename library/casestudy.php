<?php
$pageTitle = "Company Profile";
$pageCSS   = "/library/style/casestudy.css";

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../include/dataconnect.php';

$teamId = $_SESSION['team_id'] ?? 0;
$diId   = isset($_GET['di_id']) ? intval($_GET['di_id']) : 0;

if ($teamId <= 0 || $diId <= 0) {
    header("Location: " . BASE_PATH . "/library.php");
    exit;
}

/* Validate ownership */
$stmt = $conn->prepare("
    SELECT d.di_casestudy
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

$row = $result->fetch_assoc();
$stmt->close();

$data = [];
if (!empty($row['di_casestudy'])) {
    $data = json_decode($row['di_casestudy'], true);
}

$companyName = $data['company_name'] ?? '';
$title       = $data['title'] ?? '';
$intro       = $data['introduction'] ?? '';
?>

<div class="content-container">
    <div class="header-row">
    <a href="view.php?di_id=<?= $diId ?>" class="btn-back">← Back</a>

    <h1>Edit Company Profile</h1>
    </div>

    <form id="companyForm">

        <input type="hidden" name="di_id" value="<?= $diId ?>">

        <div class="form-group">
            <label>Company Name</label>
            <input type="text" name="company_name"
                   value="<?= htmlspecialchars($companyName) ?>" required>
        </div>

        <div class="form-group">
            <label>Professional Title</label>
            <input type="text" name="title"
                   value="<?= htmlspecialchars($title) ?>" required>
        </div>

        <div class="form-group">
            <label>Introduction</label>
            <textarea name="introduction" rows="12"
                      required><?= htmlspecialchars($intro) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="button" onclick="saveCompany()" class="btn-primary">
                Save Changes
            </button>
        </div>

    </form>

</div>

<script>
function saveCompany() {
    const formData = new FormData(document.getElementById('companyForm'));

    const BASE_PATH = "<?= BASE_PATH ?>/library/functions/";
    fetch(BASE_PATH + "update_casestudy.php", {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Company profile updated successfully");
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(err => {
        alert("Unexpected error occurred.");
        console.error(err);
    });
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>