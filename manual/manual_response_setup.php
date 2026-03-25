<?php
$pageTitle = "Manual Response Builder";
$pageCSS = "/manual/css/manual_response_setup.css";

require_once __DIR__ . '/../include/dataconnect.php';

$digisimId = isset($_GET['digisim_id']) ? intval($_GET['digisim_id']) : 0;

if ($digisimId <= 0) {
    die("Invalid Digisim ID");
}

/* 
GET SELECTED SCALE
 */

$stmt = $conn->prepare("
SELECT di_scoretype_id
FROM mg5_digisim
WHERE di_id = ?
");

$stmt->bind_param("i",$digisimId);
$stmt->execute();
$stmt->bind_result($selectedScaleId);
$stmt->fetch();
$stmt->close();


/* 
FETCH SCORE TYPES
 */

$scoreTypes = [];

$res = $conn->query("
SELECT st_id, st_name
FROM mg5_scoretype
");

while($row = $res->fetch_assoc()){
    $scoreTypes[] = $row;
}


/* 
FETCH SCALE COMPONENTS
 */

$scaleComponents = [];

$res = $conn->query("
SELECT stv_scoretype_pkid, stv_id, stv_name, stv_color
FROM mg5_scoretype_value
ORDER BY stv_scoretype_pkid, stv_value DESC
");

while($row = $res->fetch_assoc()){

    $key = (int)$row['stv_scoretype_pkid'];

    if(!isset($scaleComponents[$key])){
        $scaleComponents[$key] = [];
    }

    $scaleComponents[$key][] = $row;
}


/* 
FETCH SAVED RESPONSES
 */

$savedStatements = [];

$stmt = $conn->prepare("
SELECT dr_tasks, dr_score_pkid
FROM mg5_digisim_response
WHERE dr_digisim_pkid = ?
ORDER BY dr_order
");

$stmt->bind_param("i",$digisimId);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    $savedStatements[$row['dr_score_pkid']][] = $row['dr_tasks'];
}

$stmt->close();


/* 
SAVE STATEMENTS
 */

if($_SERVER['REQUEST_METHOD']=="POST"){

    $errors = [];

    /* VALIDATE SCALE */
    if (empty($_POST['score_scale'])) {
        $errors[] = "Please select a scale";
    }

    /* VALIDATE STATEMENTS */
    $hasStatement = false;

    if (isset($_POST['statement'])) {
        foreach ($_POST['statement'] as $group) {
            foreach ($group as $s) {
                if (trim($s) !== "") {
                    $hasStatement = true;
                    break 2;
                }
            }
        }
    }

    if (!$hasStatement) {
        $errors[] = "Please add at least one statement";
    }

    // if no errors 
    if (empty($errors)) {
        $scaleId = intval($_POST['score_scale']);

        /* 
        UPDATE SCALE
        */

        $stmt = $conn->prepare("
            UPDATE mg5_digisim
            SET di_scoretype_id=?
            WHERE di_id=?
        ");
        $stmt->bind_param("ii",$scaleId,$digisimId);
        $stmt->execute();

        /* 
        DELETE OLD RESPONSES + GROUP
        */

        $conn->query("DELETE FROM mg5_digisim_response WHERE dr_digisim_pkid=".$digisimId);

        /* OPTIONAL: delete old group */
        $conn->query("DELETE FROM mg5_mdm_response WHERE lg_digisim_pkid=".$digisimId);

        /* 
        STEP 1: CREATE GROUP
            */
        $createdDate = date("Y-m-d H:i:s");

        $groupName = "manual_response_".$digisimId;

        $stmt = $conn->prepare("
            INSERT INTO mg5_mdm_response
            (lg_digisim_pkid, lg_name, lg_description, lg_status, lg_order, createddate)
            VALUES (?, ?, '', 1, 1, ?)
        ");

        $stmt->bind_param("iss", $digisimId, $groupName, $createdDate);
        $stmt->execute();

        $responseGroupId = $conn->insert_id;

        /* 
        UPDATE DIGISIM
        */

        $stmt = $conn->prepare("
            UPDATE mg5_digisim
            SET di_response_id=?
            WHERE di_id=?
        ");
        $stmt->bind_param("ii",$responseGroupId,$digisimId);
        $stmt->execute();

        /* 
        STEP 2: CREATE SUB INDEX
        */

        $subIndexName = "manual_subindex_".$digisimId;

        $stmt = $conn->prepare("
            INSERT INTO mg5_sub_index
            (ln_name, ln_desc, ln_status, ix_group_pkid, ln_image, ln_sequence)
            VALUES (?, '', 1, ?, '', 1)
        ");

        $stmt->bind_param("si",$subIndexName,$responseGroupId);
        $stmt->execute();

        $subIndexId = $conn->insert_id;

        /* 
        STEP 3: INSERT RESPONSES
        */

        if(isset($_POST['statement'])){

            $stmt = $conn->prepare("
                INSERT INTO mg5_digisim_response
                (
                    dr_digisim_pkid,
                    dr_response_pkid,
                    dr_order,
                    dr_tasks,
                    dr_score_pkid,
                    dr_benchmark_pkid
                )
                VALUES (?, ?, ?, ?, ?, 0)
            ");

            $order = 1;

            foreach($_POST['statement'] as $scoreId => $statements){

                foreach($statements as $s){

                    $s = trim($s);
                    if($s == "") continue;

                    $stmt->bind_param(
                        "iiisi",
                        $digisimId,
                        $subIndexId,
                        $order,
                        $s,
                        $scoreId
                    );

                    $stmt->execute();
                    $order++;
                }
            }
        }

        header("Location: manual_page_container.php?step=4&digisim_id=".$digisimId);
        exit;
    }
}
?>



<div class="page-container">
    <?php include 'stepper.php'; ?>

    <!-- if no scale selected -->
    <div class="resp-shell">
        <?php if (!empty($errors)): ?>
        <div style="background:#ffeaea; color:#b91c1c; padding:10px; border-radius:6px; margin-bottom:15px;">
            <?php foreach ($errors as $err): ?>
                <div>⚠️ <?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="responseForm">

        <div class="resp-page-header">
            <div>
                <h2 class="resp-page-title">Response Configuration</h2>
                <p class="resp-page-subtitle">Configure grading scales and mapped statements</p>
            </div>
        </div>

        <main class="resp-main">

            <!-- Choose Scale (Top, Horizontal) -->
            <div class="resp-section">
                <h3 class="resp-section-label">Choose Scale</h3>

                <div class="resp-scale-grid">
                    <?php foreach($scoreTypes as $st): ?>
                    <label class="resp-scale-label 
                    <?= ($selectedScaleId && $selectedScaleId != $st['st_id']) ? 'scale-disabled' : '' ?>">
                        <input
                            class="resp-scale-input scale-input-radio"
                            type="radio"
                            name="score_scale"
                            value="<?=$st['st_id']?>"
                            data-name="<?=htmlspecialchars($st['st_name'])?>"
                            <?=$selectedScaleId==$st['st_id']?'checked':''?>
                            <?= ($selectedScaleId && $selectedScaleId != $st['st_id']) ? 'disabled' : '' ?>
                        >
                        <div class="resp-scale-card">
                            <div class="resp-scale-card-top">
                                <span class="resp-scale-card-name">
                                    <?=htmlspecialchars($st['st_name'])?>
                                </span>
                                <span class="resp-scale-check material-symbols-outlined">check_circle</span>
                            </div>
                            <span class="resp-scale-card-hint">Click to configure</span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Configure Statements (Bottom, Full Width) -->
            <div class="resp-section-scrollable">

                <div class="resp-header-row">
                    <h3 class="resp-stmts-label">Configure Statements</h3>
                    <div id="activeScaleBadge" class="resp-active-scale-badge">
                        Scale: None
                    </div>
                </div>

                <div id="response-container">
                </div>

            </div>

        </main>

    </form>
</div>
</div>

<script>
    const scaleComponents = <?=json_encode($scaleComponents)?>;
    const savedStatements = <?=json_encode($savedStatements)?>;

    const container = document.getElementById("response-container");
    const badge = document.getElementById("activeScaleBadge");

    function renderResponses(scaleInput) {
        const scaleId = parseInt(scaleInput.value);
        const scaleName = scaleInput.getAttribute("data-name");

        badge.textContent = "Scale: " + scaleName;
        container.innerHTML = "";

        const components = scaleComponents[scaleId] || [];

        components.forEach(comp => {
            const block = document.createElement("div");
            block.className = "resp-group";

            block.innerHTML = `
                <div class="resp-group-header">
                    <span class="material-symbols-outlined resp-group-icon" style="font-variation-settings: 'FILL' 0;">label</span>
                    <h3 class="resp-group-title">${comp.stv_name}</h3>
                </div>
                <div class="resp-group-body">
                    <div class="resp-inputs-list"></div>
                    <button type="button" class="resp-btn-add">
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 0;">add_circle</span>
                        Add Statement
                    </button>
                </div>
            `;

            container.appendChild(block);

            const list = block.querySelector(".resp-inputs-list");
            const addBtn = block.querySelector(".resp-btn-add");

            const inputs = savedStatements[comp.stv_id] || [""];

            inputs.forEach(text => {
                list.appendChild(createRow(comp.stv_id, text));
            });

            addBtn.onclick = () => {
                list.appendChild(createRow(comp.stv_id, ""));
                // Auto-scroll the inner container so the user sees the new statement
                container.scrollTop = container.scrollHeight;
            };
        });
    }

    function createRow(scoreId, val) {
        const row = document.createElement("div");
        row.className = "resp-statement-row";

        const input = document.createElement("input");
        input.type = "text";
        input.className = "resp-statement-input";
        input.name = `statement[${scoreId}][]`;
        input.value = val || "";

        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "resp-btn-delete";
        btn.innerHTML = '<span class="material-symbols-outlined" style="font-variation-settings: \'FILL\' 0;">delete</span>';
        btn.onclick = () => row.remove();

        row.appendChild(input);
        row.appendChild(btn);

        return row;
    }

    document.querySelectorAll(".scale-input-radio").forEach(radio => {
        radio.addEventListener("change", function() {
            renderResponses(this);
        });
    });

    const selected = document.querySelector(".scale-input-radio:checked");
    if (selected) {
        renderResponses(selected);
    }
    // for the frontened validation.
    /* document.getElementById("responseForm").addEventListener("submit", function(e) {

    // Check scale
    const selectedScale = document.querySelector(".scale-input-radio:checked");
    if (!selectedScale) {
        alert("Please select a scale");
        e.preventDefault();
        return;
    }

    // Check at least one statement
    const inputs = document.querySelectorAll(".resp-statement-input");

    let hasValue = false;
    inputs.forEach(inp => {
        if (inp.value.trim() !== "") {
            hasValue = true;
        }
    });

    if (!hasValue) {
        alert("Please add at least one statement");
        e.preventDefault();
    }
    }); */
</script>