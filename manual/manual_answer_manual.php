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

$answerKey = $_POST['answer_key'] ?? "";
$manualContent = $_POST['moderator_manual'] ?? "";

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

?>

<!-- Material Symbols font -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">

<div class="ans-shell">

    <div class="ans-main">

        <!-- Progress -->
        <div class="ans-progress-wrap">
            <div class="ans-progress-header">
                <div class="ans-progress-title">
                    <h1>De-briefing Content</h1>
                    <p>Provide the answer key and moderator instructions for this simulation.</p>
                </div>
                <div class="ans-progress-step">
                    <p>Step 5: Completion</p>
                    <span>95% Complete</span>
                </div>
            </div>
            <div class="ans-progress-bar-bg">
                <div class="ans-progress-bar-fill" style="width: 95%;"></div>
            </div>
        </div>

        <form method="POST" id="ansForm">

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


            <!-- Card 2: Moderator Manual -->
            <div class="ans-editor-card">
                <div class="ans-editor-header">
                    <span class="material-symbols-outlined">admin_panel_settings</span>
                    <h3>Moderator Manual</h3>
                </div>
                <div class="ans-editor-toolbar">
                    <div class="ans-toolbar-left">
                        <button type="button" class="ans-btn-tool" onclick="execFmt('manual','bold')" title="Bold"><span class="material-symbols-outlined">format_bold</span></button>
                        <button type="button" class="ans-btn-tool" onclick="execFmt('manual','italic')" title="Italic"><span class="material-symbols-outlined">format_italic</span></button>
                        <button type="button" class="ans-btn-tool" onclick="execFmt('manual','underline')" title="Underline"><span class="material-symbols-outlined">format_underlined</span></button>
                        <div class="ans-toolbar-divider"></div>
                        <button type="button" class="ans-btn-tool" onclick="execFmt('manual','insertUnorderedList')" title="Bullet List"><span class="material-symbols-outlined">format_list_bulleted</span></button>
                        <button type="button" class="ans-btn-tool" onclick="execFmt('manual','insertOrderedList')" title="Numbered List"><span class="material-symbols-outlined">format_list_numbered</span></button>
                        <div class="ans-toolbar-divider"></div>
                        <button type="button" class="ans-btn-tool" onclick="execFmt('manual','createLink', prompt('URL:'))" title="Link"><span class="material-symbols-outlined">link</span></button>
                        <button type="button" class="ans-btn-tool" onclick="alert('Image upload coming soon')" title="Image"><span class="material-symbols-outlined">image</span></button>
                    </div>
                    <div class="ans-toolbar-right">
                        <button type="button" class="ans-btn-tool ans-btn-tool-danger" onclick="execFmt('manual','removeFormat')" title="Clear Formatting"><span class="material-symbols-outlined">format_clear</span></button>
                    </div>
                </div>
                <div id="editor-manual" class="ans-editor-content" contenteditable="true" data-placeholder="Start writing the moderator instructions here..."><?= htmlspecialchars_decode($manualContent ?? '') ?></div>
                <input type="hidden" name="moderator_manual" id="hidden-manual">
            </div>

        </form>

    </div><!-- /.ans-main -->

    <footer class="ans-footer">
        <div class="ans-footer-inner">
            <a href="manual_page_container.php?step=4&digisim_id=<?=$digisimId?>" class="ans-btn-back">
                <span class="material-symbols-outlined">arrow_back</span>
                Back
            </a>
            <div class="ans-footer-right">
                <button type="submit" form="ansForm" name="action" value="draft" class="ans-btn-save-progress">
                    Save Progress
                </button>
                <button type="submit" form="ansForm" name="action" value="next" class="ans-btn-next">
                    Finish Simulation
                    <span class="material-symbols-outlined">check_circle</span>
                </button>
            </div>
        </div>
    </footer>

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
const edMan = document.getElementById('editor-manual');

edAns.addEventListener('input', () => checkPlaceholder(edAns));
edMan.addEventListener('input', () => checkPlaceholder(edMan));

// Initial check
checkPlaceholder(edAns);
checkPlaceholder(edMan);

// Sync to hidden inputs before submit
document.getElementById('ansForm').addEventListener('submit', function() {
    document.getElementById('hidden-answer').value = edAns.innerHTML;
    document.getElementById('hidden-manual').value = edMan.innerHTML;
});
</script>