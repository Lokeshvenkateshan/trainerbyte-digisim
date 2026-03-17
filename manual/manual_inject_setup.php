<?php

$pageTitle = "Manual Inject Builder";
$pageCSS   = "/manual/css/manual_inject_setup.css";

require_once __DIR__ . '/../include/dataconnect.php';

$digisimId = intval($_GET['digisim_id'] ?? 0);

if ($digisimId <= 0) {
    die("Invalid Digisim ID");
}

/* 
GET OR CREATE INJECT GROUP
 */

$stmt = $conn->prepare("
SELECT di_injects_id
FROM mg5_digisim
WHERE di_id = ?
");

$stmt->bind_param("i", $digisimId);
$stmt->execute();
$stmt->bind_result($injectGroupId);
$stmt->fetch();
$stmt->close();

if (!$injectGroupId) {

    /* GET SIMULATION TITLE */

    $stmt = $conn->prepare("
SELECT di_name
FROM mg5_digisim
WHERE di_id = ?
");

    $stmt->bind_param("i", $digisimId);
    $stmt->execute();
    $stmt->bind_result($simTitle);
    $stmt->fetch();
    $stmt->close();

    /* CREATE INJECT GROUP NAME */

    $injectGroupName = $simTitle . "_injects";

    $date = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("
INSERT INTO mg5_mdm_injectes
(lg_digisim_pkid,lg_name,lg_status,lg_order,createddate)
VALUES (?, ?,1,1,?)
");

    $stmt->bind_param("iss", $digisimId, $injectGroupName, $date);
    $stmt->execute();

    $injectGroupId = $conn->insert_id;

    $stmt = $conn->prepare("
UPDATE mg5_digisim
SET di_injects_id=?
WHERE di_id=?
");

    $stmt->bind_param("ii", $injectGroupId, $digisimId);
    $stmt->execute();
}

/* 
FETCH INJECT TYPES
 */

$injectTypes = [];

$res = $conn->query("
SELECT in_name
FROM mg5_inject_master
WHERE in_status=1
ORDER BY in_id
");

while ($r = $res->fetch_assoc()) {
    $injectTypes[] = $r['in_name'];
}

$currentType = $_GET['type'] ?? $injectTypes[0];

/* 
EDIT MODE
 */

$editId = intval($_GET['edit'] ?? 0);

$subject = "";
$body = "";
$trigger = 1;

if ($editId) {

    $stmt = $conn->prepare("
SELECT m.dm_subject,m.dm_message,m.dm_trigger,c.ch_level
FROM mg5_digisim_message m
JOIN mg5_sub_channels c
ON m.dm_injectes_pkid=c.ch_id
WHERE m.dm_id=? AND m.dm_digisim_pkid=?
");

    $stmt->bind_param("ii", $editId, $digisimId);
    $stmt->execute();

    $res = $stmt->get_result()->fetch_assoc();

    $subject = $res['dm_subject'];
    $body = $res['dm_message'];
    $trigger = $res['dm_trigger'];
    $currentType = $res['ch_level'];
}

/* 
SAVE / UPDATE
 */

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $errors = [];

    $type = $_POST['inject_type'] ?? '';
    $trigger = intval($_POST['trigger'] ?? 1);
    $editId = intval($_POST['edit_id'] ?? 0);

    /* SUBJECT VALIDATION */
    if (empty($_POST['subject'])) {
        $errors['subject'] = 'Subject is required';
    } else {
        $subject = htmlspecialchars(trim($_POST['subject']));
    }

    /* BODY VALIDATION */
    if (empty($_POST['body']) || trim(strip_tags($_POST['body'])) === '') {
        $errors['body'] = 'Body content is required';
    } else {
        $body = $_POST['body']; 
    }

     if (empty($errors)) {
    /* 
IMAGE UPLOAD
 */

    $attachmentName = "";

    if (!empty($_FILES['attachment']['name'])) {

        $uploadDir = __DIR__ . '/../upload-images/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {

            $attachmentName = "inject_" . time() . "." . $ext;

            move_uploaded_file(
                $_FILES['attachment']['tmp_name'],
                $uploadDir . $attachmentName
            );
        }
    }

    /* FIND CHANNEL */

    $stmt = $conn->prepare("
SELECT ch_id
FROM mg5_sub_channels
WHERE ch_level=? AND in_group_pkid=?
");

    $stmt->bind_param("si", $type, $injectGroupId);
    $stmt->execute();
    $stmt->bind_result($channelId);
    $stmt->fetch();
    $stmt->close();

    /* CREATE CHANNEL IF NOT EXIST */

    if (!$channelId) {

        $stmt = $conn->prepare("
INSERT INTO mg5_sub_channels
(ch_level,ch_status,in_group_pkid,ch_sequence)
VALUES (?,1,?,1)
");

        $stmt->bind_param("si", $type, $injectGroupId);
        $stmt->execute();

        $channelId = $conn->insert_id;
    }

    /* UPDATE */

    if ($editId) {

        $stmt = $conn->prepare("
UPDATE mg5_digisim_message
SET dm_subject=?,dm_message=?,dm_attachment=?,dm_trigger=?
WHERE dm_id=?
");

        $stmt->bind_param("sssii", $subject, $body, $attachmentName, $trigger, $editId);
        $stmt->execute();
    } else {

        $stmt = $conn->prepare("
INSERT INTO mg5_digisim_message
(dm_digisim_pkid,dm_injectes_pkid,dm_subject,dm_message,dm_attachment,dm_trigger,dm_event)
VALUES (?,?,?,?, ?,?,0)
");

        $stmt->bind_param("iisssi", $digisimId, $channelId, $subject, $body, $attachmentName, $trigger);
        $stmt->execute();
    }

    header("Location: manual_page_container.php?step=2&digisim_id=$digisimId&type=$type");
    exit;
}
}

/* 
FETCH EXISTING INJECTS
 */

$stmt = $conn->prepare("
SELECT m.dm_id,m.dm_subject,c.ch_level,m.dm_trigger
FROM mg5_digisim_message m
JOIN mg5_sub_channels c
ON m.dm_injectes_pkid=c.ch_id
WHERE m.dm_digisim_pkid=?
ORDER BY m.dm_id DESC
");

$stmt->bind_param("i", $digisimId);
$stmt->execute();

$existing = $stmt->get_result();
?>

<?php
/* ----------------------------
   ICON MAP FOR CHANNEL TYPES
---------------------------- */
$channelIcons = [
    'Email'  => 'mail',
    'SMS'    => 'chat_bubble',
    'TV'     => 'tv',
    'News'   => 'newspaper',
    'Social' => 'share_reviews',
    'Phone'  => 'call',
];

function channelIcon(string $type, array $map): string
{
    foreach ($map as $key => $icon) {
        if (stripos($type, $key) !== false) return $icon;
    }
    return 'hub';
}

/* Count injects per type */
$countStmt = $conn->prepare("
    SELECT c.ch_level, COUNT(m.dm_id) as cnt
    FROM mg5_sub_channels c
    LEFT JOIN mg5_digisim_message m ON m.dm_injectes_pkid = c.ch_id AND m.dm_digisim_pkid = ?
    WHERE c.in_group_pkid = ?
    GROUP BY c.ch_level
");
$countStmt->bind_param("ii", $digisimId, $injectGroupId);
$countStmt->execute();
$countRes = $countStmt->get_result();
$injectCounts = [];
while ($cr = $countRes->fetch_assoc()) {
    $injectCounts[$cr['ch_level']] = $cr['cnt'];
}
?>



<div class="page-container">
    <?php include 'stepper.php'; ?>
    <div class="inj-shell">

        <!-- MAIN -->
        <div class="inj-main">

            <!-- Compact Sub-header row -->
            <div class="inj-subheader">
                <div>
                    <h2>Configure Injects</h2>
                    <p>Define the content and timing for simulation events.</p>
                </div>
                <!-- Channel Tabs inside header -->
                <div class="inj-channel-tabs">
                    <?php foreach ($injectTypes as $t):
                        $icon  = channelIcon($t, $channelIcons);
                        $count = $injectCounts[$t] ?? 0;
                        $isActive = ($t === $currentType);
                    ?>
                        <a class="inj-channel-tab <?= $isActive ? 'active' : '' ?>"
                            href="manual_page_container.php?step=2&digisim_id=<?= $digisimId ?>&type=<?= urlencode($t) ?>">
                            <span class="material-symbols-outlined"><?= $icon ?></span>
                            <span><?= htmlspecialchars($t) ?><?= $count ? " ($count)" : '' ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Body: Editor + Sidebar -->
            <div class="inj-body">

                <!-- Editor Card -->
                <div class="lg:col-span-2 inj-editor-wrap">
                    <div class="inj-editor-card">

                        <div class="inj-editor-card-title">
                            <span class="material-symbols-outlined">edit_note</span>
                            <?= $editId ? "Edit" : "New" ?> <?= htmlspecialchars($currentType) ?> Inject
                        </div>

                        <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
                            <input type="hidden" name="inject_type" value="<?= $currentType ?>">
                            <input type="hidden" name="edit_id" value="<?= $editId ?>">

                            <!-- Subject / Name -->
                            <div class="inj-field">
                                <label for="inj-subject">Subject Line</label>
                                <input type="text" id="inj-subject" name="subject"
                                    placeholder="e.g., Security Breach Alert: Internal Action Required"
                                    value="<?= htmlspecialchars($subject) ?>">

                                <?php if (!empty($errors['subject'])): ?>
                                    <p class="field-error"><?= $errors['subject'] ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- Body with toolbar -->
                            <div class="inj-field" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
                                <label>Body Content</label>
                                <div class="inj-body-editor-wrap">
                                    <div class="inj-body-toolbar">
                                        <button type="button" id="inj-btn-bold" onclick="execFmt('bold')" title="Bold"><span class="material-symbols-outlined">format_bold</span></button>
                                        <button type="button" id="inj-btn-italic" onclick="execFmt('italic')" title="Italic"><span class="material-symbols-outlined">format_italic</span></button>
                                        <button type="button" id="inj-btn-underline" onclick="execFmt('underline')" title="Underline"><span class="material-symbols-outlined">format_underlined</span></button>
                                        <button type="button" id="inj-btn-ul" onclick="execFmt('insertUnorderedList')" title="Bullet List"><span class="material-symbols-outlined">format_list_bulleted</span></button>
                                        <div class="inj-toolbar-div"></div>
                                        <button type="button" onclick="execFmt('createLink', prompt('URL:'))" title="Link"><span class="material-symbols-outlined">link</span></button>
                                    </div>
                                    <div id="inj-body"
                                        class="inj-body-textarea"
                                        contenteditable="true"
                                        data-placeholder="Compose your inject content here..."><?= htmlspecialchars_decode($body) ?>
                                    </div>
                                    <input type="hidden" name="body" id="inj-body-hidden">

                                </div>
                                <?php if (!empty($errors['body'])): ?>
                                    <p class="field-error"><?= $errors['body'] ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- Image Upload + Trigger row -->
                            <div class="inj-attach-trigger-row">
                                <div class="inj-attach-wrap">
                                    <label for="inj-attach">Attach Image</label>
                                    <div class="inj-attach-upload" onclick="document.getElementById('inj-attach').click()">
                                        <span class="material-symbols-outlined">add_photo_alternate</span>
                                        <span class="inj-attach-label">Attach Image</span>
                                        <span class="inj-attach-sub">Click to upload</span>
                                    </div>

                                    <input type="file"
                                        id="inj-attach"
                                        name="attachment"
                                        accept="image/*"
                                        style="display:none">
                                </div>

                                <div class="inj-trigger-wrap">
                                    <label for="inj-trigger">Trigger Type</label>
                                    <select id="inj-trigger" name="trigger">
                                        <option value="1" <?= $trigger == 1 ? 'selected' : '' ?>>Start</option>
                                        <option value="2" <?= $trigger == 2 ? 'selected' : '' ?>>Task</option>
                                        <option value="3" <?= $trigger == 3 ? 'selected' : '' ?>>Progressive</option>
                                    </select>
                                </div>
                            </div>


                            <!-- Save button -->
                            <div class="inj-card-actions">
                                <button type="submit" class="inj-btn-save">
                                    <span class="material-symbols-outlined">add</span>
                                    <?= $editId ? "Update Inject" : "Save &amp; Add Another Inject" ?>
                                </button>
                            </div>

                        </form>

                    </div>
                </div>

                <!-- Existing Injects Sidebar -->
                <div class="inj-sidebar-wrap">
                    <div class="inj-sidebar-title">
                        <span class="material-symbols-outlined">list_alt</span>
                        Existing <?= htmlspecialchars($currentType) ?> Injects
                    </div>

                    <div class="inj-cards-list">
                        <?php
                        $existing->data_seek(0);
                        $cardCount = 0;
                        while ($row = $existing->fetch_assoc()):
                            $cardCount++;
                            $trigLabels = [1 => 'Start', 2 => 'Task', 3 => 'Progressive'];
                            $trig = $trigLabels[$row['dm_trigger']] ?? '';
                            $isActiveCard = ($row['dm_id'] == $editId);
                        ?>
                            <a class="inj-inject-card <?= $isActiveCard ? 'active-card' : '' ?>"
                                href="manual_page_container.php?step=2&digisim_id=<?= $digisimId ?>&type=<?= urlencode($row['ch_level'] ?? '') ?>&edit=<?= $row['dm_id'] ?>">
                                <div class="inj-card-meta">
                                    <span class="inj-card-type <?= $isActiveCard ? 'active-card-type' : '' ?>"><?= htmlspecialchars($row['ch_level']) ?></span>
                                    <span class="inj-card-trigger"><?= $trig ?></span>
                                </div>
                                <h4><?= htmlspecialchars($row['dm_subject']) ?></h4>
                            </a>
                        <?php endwhile; ?>

                        <?php if ($cardCount === 0): ?>
                            <p class="inj-no-injects">No injects yet. Create one using the form.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>



    </div>

    <script>
        const injEditor = document.getElementById('inj-body');

        function execFmt(cmd, val) {
            injEditor.focus();
            val = (val !== undefined && val !== null) ? val : null;
            document.execCommand(cmd, false, val);
        }

        // Toolbar active state
        function updateInjToolbar() {
            const cmds = {
                'inj-btn-bold': 'bold',
                'inj-btn-italic': 'italic',
                'inj-btn-underline': 'underline',
                'inj-btn-ul': 'insertUnorderedList',
            };
            for (const [id, cmd] of Object.entries(cmds)) {
                const el = document.getElementById(id);
                if (el) el.classList.toggle('active', document.queryCommandState(cmd));
            }
        }
        injEditor.addEventListener('keyup', updateInjToolbar);
        injEditor.addEventListener('mouseup', updateInjToolbar);

        // Placeholder behaviour
        function checkPlaceholder() {
            injEditor.classList.toggle('empty', injEditor.innerHTML.trim() === '' || injEditor.innerHTML === '<br>');
        }
        injEditor.addEventListener('input', checkPlaceholder);
        checkPlaceholder();

        // Sync hidden input on submit
        const injForm = injEditor.closest('form');
        injForm.addEventListener('submit', function() {
            document.getElementById('inj-body-hidden').value = injEditor.innerHTML;
        });
    </script>