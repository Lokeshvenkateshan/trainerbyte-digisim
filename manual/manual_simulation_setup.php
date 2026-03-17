<?php

$pageTitle = "Manual Simulation Setup";
$pageCSS = "/manual/css/manual_simulation.css";

/* Material Symbols — inject into <head> via extra head tags */
$pageHeadExtra = '
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
<style>
.material-symbols-outlined {
    font-variation-settings: "FILL" 0, "wght" 400, "GRAD" 0, "opsz" 24;
    vertical-align: middle;
    user-select: none;
}
</style>
';

require_once __DIR__ . '/../include/dataconnect.php';

$teamId = $_SESSION['team_id'];

$digisimId = isset($_GET['digisim_id']) ? intval($_GET['digisim_id']) : 0;

$companyName = "";
$simulationTitle = "";
$simulationContext = "";
$coverImg = "";
$simulationDescription = "";



/* LOAD EXISTING SIMULATION*/

if ($digisimId) {

    $stmt = $conn->prepare("
        SELECT di_name,di_description,di_casestudy,di_coverimg
        FROM mg5_digisim
        WHERE di_id=?
    ");

    $stmt->bind_param("i", $digisimId);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    $simulationTitle = $row['di_name'] ?? "";
    $simulationDescription = $row['di_description'] ?? "";
    $coverImg = $row['di_coverimg'] ?? "";

    $data = json_decode($row['di_casestudy'], true);

    $companyName = $data['company_name'] ?? "";
    $simulationContext = $data['introduction'] ?? "";
}



/* 
FORM SUBMIT
*/

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $companyName = trim($_POST['company_name'] ?? '');
    $simulationTitle = trim($_POST['simulation_title'] ?? '');
    $simulationContext = trim($_POST['simulation_context'] ?? '');
    $simulationDescription = trim($_POST['simulation_description'] ?? '');

    if (!$companyName) $errors[] = "Organization name is required.";
    if (!$simulationTitle) $errors[] = "Simulation title is required.";
    if (!$simulationContext) $errors[] = "Simulation context is required.";



    /* 
    HANDLE IMAGE UPLOAD
    */

    $coverImageName = $coverImg;

    if (!empty($_FILES['cover_img']['name'])) {

        $uploadDir = __DIR__ . '/../upload-images/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['cover_img']['name'], PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid image format. Only JPG, PNG, WEBP allowed.";
        } else {

            $coverImageName = "sim_" . time() . "." . $ext;

            move_uploaded_file(
                $_FILES['cover_img']['tmp_name'],
                $uploadDir . $coverImageName
            );
        }
    }



    /* 
    SAVE SIMULATION
    */

    if (empty($errors)) {

        $stmt = $conn->prepare("
            SELECT lg_id
            FROM mg5_digisim_category
            WHERE lg_team_pkid = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $teamId);
        $stmt->execute();
        $stmt->bind_result($categoryId);
        $stmt->fetch();
        $stmt->close();


        $json = json_encode([
            "company_name" => $companyName,
            "title" => $simulationTitle,
            "introduction" => $simulationContext
        ], JSON_UNESCAPED_UNICODE);


        $createdDate = date("Y-m-d H:i:s");


        /* 
        UPDATE EXISTING
        */

        if ($digisimId > 0) {

            $stmt = $conn->prepare("
                UPDATE mg5_digisim 
                SET 
                    di_name = ?,
                    di_description = ?,
                    di_casestudy = ?,
                    di_coverimg = ?,
                    di_createddate = ?
                WHERE di_id = ?
            ");

            $stmt->bind_param(
                "sssssi",
                $simulationTitle,
                $simulationDescription,
                $json,
                $coverImageName,
                $createdDate,
                $digisimId
            );

            $stmt->execute();
        }


        /* 
        INSERT NEW
        */ else {

            $stmt = $conn->prepare("
                INSERT INTO mg5_digisim
                (
                    di_digisim_category_pkid,
                    di_name,
                    di_description,
                    di_casestudy,
                    di_coverimg,
                    di_createddate,
                    di_status
                )
                VALUES (?,?,?,?,?,?,1)
            ");

            $stmt->bind_param(
                "isssss",
                $categoryId,
                $simulationTitle,
                $simulationDescription,
                $json,
                $coverImageName,
                $createdDate
            );

            $stmt->execute();

            $digisimId = $conn->insert_id;
        }



        /* 
        REDIRECT
        */

        if (isset($_POST['action']) && $_POST['action'] === 'draft') {

            header("Location: manual_page_container.php?step=1&digisim_id=" . $digisimId);
        } else {

            header("Location: manual_page_container.php?step=2&digisim_id=" . $digisimId);
        }

        exit;
    }
}

?>



<form method="POST" enctype="multipart/form-data" id="sim-form" onsubmit="return submitEditor()">

    <div class="page-container">
        <?php include 'stepper.php'; ?>
        <div class="sim-shell">

            <div style="margin-bottom: 20px;">
                <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Simulation Context</h2>
                <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">Define the basic details and cover image for your simulation</p>
            </div>

            <!-- MAIN: Split Screen -->
            <div class="sim-main">

                <!-- LEFT SIDEBAR -->
                <aside class="sim-left">
                    <!-- Cover Image Upload -->
                    <div>
                        <p class="sim-section-label">Cover Image</p>

                        <div class="sim-upload-box" onclick="document.getElementById('cover_img').click()">

                            <?php if (!empty($coverImg)): ?>

                                <img src="../upload-images/<?= htmlspecialchars($coverImg) ?>"
                                    style="width:100%;height:120px;object-fit:cover;border-radius:6px;">

                            <?php else: ?>

                                <span class="material-symbols-outlined upload-icon">add_a_photo</span>
                                <span class="upload-label">Upload Simulation Banner</span>

                            <?php endif; ?>

                        </div>

                        <input
                            type="file"
                            name="cover_img"
                            id="cover_img"
                            accept="image/*"
                            style="display:none">

                    </div>

                    <!-- Input Fields -->
                    <div style="display:flex;flex-direction:column;gap:20px;">

                        <div class="sim-input-group">
                            <label for="company_name">Organization Name</label>
                            <input
                                type="text"
                                id="company_name"
                                name="company_name"
                                placeholder="Enter organization name"
                                value="<?= htmlspecialchars($companyName) ?>">
                        </div>

                        <div class="sim-input-group">
                            <label for="simulation_title">Simulation Title</label>
                            <input
                                type="text"
                                id="simulation_title"
                                name="simulation_title"
                                placeholder="Enter simulation title"
                                value="<?= htmlspecialchars($simulationTitle) ?>">
                        </div>

                        <div class="sim-input-group">
                            <label for="simulation_description">Description</label>
                            <textarea
                                id="simulation_description"
                                name="simulation_description"
                                rows="3"
                                placeholder="Enter short description for this simulation"
                            ><?=htmlspecialchars($simulationDescription)?></textarea>
                        </div>

                        <?php foreach ($errors as $e): ?>
                            <div class="sim-error-alert">
                                <span class="material-symbols-outlined sim-error-icon">error</span>
                                <span><?= htmlspecialchars($e) ?></span>
                            </div>
                        <?php endforeach; ?>

                    </div>

                </aside>

                <!-- RIGHT PANEL: Rich Text Editor -->
                <section class="sim-right">

                    <div class="sim-editor-card">

                        <!-- Toolbar -->
                        <div class="sim-toolbar">

                            <button type="button" onclick="formatText('bold')" id="btn-bold" title="Bold">
                                <span class="material-symbols-outlined">format_bold</span>
                            </button>
                            <button type="button" onclick="formatText('italic')" id="btn-italic" title="Italic">
                                <span class="material-symbols-outlined">format_italic</span>
                            </button>
                            <button type="button" onclick="formatText('underline')" id="btn-underline" title="Underline">
                                <span class="material-symbols-outlined">format_underlined</span>
                            </button>

                            <div class="sim-toolbar-divider"></div>

                            <button type="button" onclick="formatText('insertUnorderedList')" id="btn-ul" title="Bullet List">
                                <span class="material-symbols-outlined">format_list_bulleted</span>
                            </button>
                            <button type="button" onclick="formatText('insertOrderedList')" id="btn-ol" title="Numbered List">
                                <span class="material-symbols-outlined">format_list_numbered</span>
                            </button>

                            <div class="sim-toolbar-divider"></div>

                            <button type="button" onclick="formatText('createLink', prompt('Enter URL:'))" title="Insert Link">
                                <span class="material-symbols-outlined">link</span>
                            </button>



                        </div>

                        <!-- Editor Body -->
                        <div class="sim-editor-body">

                            <h2>Simulation Context</h2>

                            <div
                                id="editor"
                                class="sim-rich-editor"
                                contenteditable="true"><?= htmlspecialchars_decode($simulationContext) ?></div>

                            <input type="hidden" name="simulation_context" id="simulation_context">

                        </div>

                    </div>

                </section>

            </div>



        </div>

</form>



<script>
    /* ── Formatting ───────────────────── */
    function formatText(cmd, val = null) {
        document.execCommand(cmd, false, val);
        updateToolbar();
        scheduleAutosave();
    }

    function updateToolbar() {
        const states = {
            'btn-bold': document.queryCommandState('bold'),
            'btn-italic': document.queryCommandState('italic'),
            'btn-underline': document.queryCommandState('underline'),
            'btn-ul': document.queryCommandState('insertUnorderedList'),
            'btn-ol': document.queryCommandState('insertOrderedList'),
        };
        for (const [id, state] of Object.entries(states)) {
            const el = document.getElementById(id);
            if (el) el.classList.toggle('active', state);
        }
    }

    const editor = document.getElementById('editor');
    editor.addEventListener('keyup', updateToolbar);
    editor.addEventListener('mouseup', updateToolbar);

    /* ── Submit ───────────────────────── */
    function submitEditor() {
        document.getElementById('simulation_context').value =
            editor.innerHTML;
        return true;
    }
</script>