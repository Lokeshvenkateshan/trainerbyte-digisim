<?php

$pageTitle = "Answer Key & Moderator Manual";
$pageCSS   = "/manual/css/manual_answer_manual.css";

require_once __DIR__ . '/../include/dataconnect.php';

$digisimId = isset($_GET['digisim_id']) ? intval($_GET['digisim_id']) : 0;

if ($digisimId <= 0) {
    header("Location: manual_page_container.php?step=1");
    exit;
}

$answerKey = "";
$manualContent = "";

/* LOAD EXISTING DATA */

$stmt = $conn->prepare("
SELECT di_answerkey, di_manual
FROM mg5_digisim
WHERE di_id = ?
");

$stmt->bind_param("i",$digisimId);
$stmt->execute();
$result = $stmt->get_result();

if($row = $result->fetch_assoc()){

$answerKey = $row['di_answerkey'];
$manualContent = $row['di_manual'];

}

$stmt->close();


/* SAVE DATA */

if($_SERVER['REQUEST_METHOD']=="POST"){

$errors = [];

$answerKey = $_POST['answer_key'] ?? "";
$manualContent = $_POST['moderator_manual'] ?? "";

/* VALIDATE ANSWER KEY */
if (trim(strip_tags($answerKey)) === "") {
    $errors[] = "Please enter de-briefing content";
}
//if no errors
if (empty($errors)) {
    $stmt = $conn->prepare("
    UPDATE mg5_digisim
    SET
    di_answerkey = ?,
    di_manual = ?
    WHERE di_id = ?
    ");

    $stmt->bind_param("ssi",$answerKey,$manualContent,$digisimId);
    $stmt->execute();

    if(isset($_POST['action']) && $_POST['action'] === 'draft'){
        header("Location: manual_page_container.php?step=5&digisim_id=".$digisimId);
    } else {
        header("Location: manual_page_container.php?step=6&digisim_id=".$digisimId);
    }
    exit;
}
}

?>



<div class="page-container">
    <?php include 'stepper.php'; ?>
<div class="ans-shell">

    <div class="ans-main">



        <div style="margin-bottom: 20px;">
            <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">De-briefing Content</h2>
            <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">Define De-briefing Content</p>
        </div>

        <!-- to show error messages -->
        <?php if (!empty($errors)): ?>
            <div style="background:#ffeaea; color:#b91c1c; padding:10px; border-radius:6px; margin-bottom:15px;">
                <?php foreach ($errors as $err): ?>
                    <div>⚠️ <?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="ansForm" style="padding-bottom: 40px;">

            <!-- Card 1: De-briefing Content -->
            <div class="ans-editor-card">
                <div class="ans-editor-header">
                    <span class="material-symbols-outlined">notes</span>
                    <h3>De-briefing Content</h3>
                </div>
                <div class="ans-editor-toolbar">
                    <div class="ans-toolbar-left">
                        <button type="button" class="ans-btn-tool" onclick="execFmt('answer','bold')" title="Bold"><span class="material-symbols-outlined">format_bold</span></button>
                        <button type="button" class="ans-btn-tool" onclick="execFmt('answer','italic')" title="Italic"><span class="material-symbols-outlined">format_italic</span></button>
                        <button type="button" class="ans-btn-tool" onclick="execFmt('answer','underline')" title="Underline"><span class="material-symbols-outlined">format_underlined</span></button>
                        <div class="ans-toolbar-divider"></div>
                        <button type="button" class="ans-btn-tool" onclick="execFmt('answer','insertUnorderedList')" title="Bullet List"><span class="material-symbols-outlined">format_list_bulleted</span></button>
                        <button type="button" class="ans-btn-tool" onclick="execFmt('answer','insertOrderedList')" title="Numbered List"><span class="material-symbols-outlined">format_list_numbered</span></button>
                        <div class="ans-toolbar-divider"></div>
                        <button type="button" class="ans-btn-tool" onclick="execFmt('answer','createLink', prompt('URL:'))" title="Link"><span class="material-symbols-outlined">link</span></button>
                        <button type="button" class="ans-btn-tool" onclick="alert('Image upload coming soon')" title="Image"><span class="material-symbols-outlined">image</span></button>
                    </div>
                    <div class="ans-toolbar-right">
                        <button type="button" class="ans-btn-tool ans-btn-tool-danger" onclick="execFmt('answer','removeFormat')" title="Clear Formatting"><span class="material-symbols-outlined">format_clear</span></button>
                    </div>
                </div>
                <div id="editor-answer" class="ans-editor-content" contenteditable="true" data-placeholder="Start writing your de-briefing session content here..."><?= htmlspecialchars_decode($answerKey ?? '') ?></div>
                <input type="hidden" name="answer_key" id="hidden-answer">
            </div>

        </form>

    </div><!-- /.ans-main -->



</div>
</div><!-- /.ans-shell -->


<script>
// Handles both editors
function execFmt(editorType, cmd, val) {
    const editor = document.getElementById('editor-' + editorType);
    if (!editor) return;

    editor.focus();
    val = (val !== undefined && val !== null) ? val : null;
    document.execCommand(cmd, false, val);
    checkPlaceholder(editor);
}

// Handle placeholder styling
function checkPlaceholder(editor) {
    editor.classList.toggle('empty', editor.innerHTML.trim() === '' || editor.innerHTML === '<br>');
}

const edAns = document.getElementById('editor-answer');

edAns.addEventListener('input', () => checkPlaceholder(edAns));

// Initial check
checkPlaceholder(edAns);

// Sync to hidden inputs before submit
document.getElementById('ansForm').addEventListener('submit', function() {
    document.getElementById('hidden-answer').value = edAns.innerHTML;
});
</script>