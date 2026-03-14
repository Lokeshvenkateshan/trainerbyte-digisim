<?php
$pageTitle = "Manual Response Builder";

require_once __DIR__ . '/../include/dataconnect.php';

$digisimId = isset($_GET['digisim_id']) ? intval($_GET['digisim_id']) : 0;

if ($digisimId <= 0) {
    die("Invalid Digisim ID");
}

/* -------------------------------
GET SELECTED SCALE
-------------------------------- */

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


/* -------------------------------
FETCH SCORE TYPES
-------------------------------- */

$scoreTypes = [];

$res = $conn->query("
SELECT st_id, st_name
FROM mg5_scoretype
");

while($row = $res->fetch_assoc()){
    $scoreTypes[] = $row;
}


/* -------------------------------
FETCH SCALE COMPONENTS
-------------------------------- */

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


/* -------------------------------
FETCH SAVED RESPONSES
-------------------------------- */

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


/* -------------------------------
SAVE STATEMENTS
-------------------------------- */

if($_SERVER['REQUEST_METHOD']=="POST"){

    $scaleId = intval($_POST['score_scale']);

    $stmt = $conn->prepare("
    UPDATE mg5_digisim
    SET di_scoretype_id=?
    WHERE di_id=?
    ");

    $stmt->bind_param("ii",$scaleId,$digisimId);
    $stmt->execute();

    $conn->query("
    DELETE FROM mg5_digisim_response
    WHERE dr_digisim_pkid=".$digisimId
    );

    if(isset($_POST['statement'])){
        foreach($_POST['statement'] as $scoreId=>$statements){
            $order=1;
            foreach($statements as $s){
                $s = trim($s);
                if($s=="") continue;

                $stmt = $conn->prepare("
                INSERT INTO mg5_digisim_response
                (dr_digisim_pkid,dr_response_pkid,dr_order,dr_tasks,dr_score_pkid,dr_benchmark_pkid)
                VALUES (?,?,?,?,?,0)
                ");

                $stmt->bind_param(
                "iiisi",
                $digisimId,
                $digisimId,
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
?>

<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

<script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#3b82f6",
                    "background-light": "#f8fafc",
                    "background-dark": "#0f172a",
                },
                fontFamily: {
                    "display": ["Public Sans"]
                },
                borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
            },
        },
    }
</script>

<style>
    /* Custom Scrollbar for the statement lists */
    .statements-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .statements-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
    .statements-scroll::-webkit-scrollbar-thumb {
        background-color: #cbd5e1; /* slate-300 */
        border-radius: 20px;
    }
    .statements-scroll::-webkit-scrollbar-thumb:hover {
        background-color: #94a3b8; /* slate-400 */
    }
    
    /* Hide global scrollbars inside this tool */
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<div class="bg-slate-50 text-slate-900 flex flex-col w-full overflow-hidden" style="height: calc(100vh - 70px); font-family: 'Public Sans', sans-serif;">

    <form method="POST" id="responseForm" class="flex flex-col flex-1 h-full w-full min-h-0 overflow-hidden">
        
        <div class="shrink-0 max-w-6xl mx-auto w-full px-8 pt-8 pb-5 flex flex-col gap-3">
            <div class="flex justify-between items-end">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Response Configuration</h1>
                    <p class="text-sm text-slate-500 mt-1">Configure grading scales and mapped statements</p>
                </div>
                <div class="text-right">
                    <p class="text-slate-900 text-sm font-semibold">Step 3: Statements</p>
                    <p class="text-slate-500 text-xs">70% Complete</p>
                </div>
            </div>
            <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full bg-primary w-[70%] rounded-full transition-all duration-500"></div>
            </div>
        </div>

        <main class="flex-1 min-h-0 w-full max-w-6xl mx-auto px-8 pb-6 flex gap-8">
            
            <div class="w-1/3 flex flex-col min-h-0 h-full">
                <h3 class="shrink-0 text-sm font-bold uppercase tracking-wider text-slate-500 mb-4">Choose Scale</h3>
                
                <div class="flex-1 min-h-0 overflow-y-auto statements-scroll pr-3 flex flex-col gap-3 pb-4">
                    <?php foreach($scoreTypes as $st): ?>
                    <label class="relative cursor-pointer group block shrink-0">
                        <input 
                            class="peer sr-only scale-input-radio" 
                            type="radio" 
                            name="score_scale" 
                            value="<?=$st['st_id']?>" 
                            data-name="<?=htmlspecialchars($st['st_name'])?>" 
                            <?=$selectedScaleId==$st['st_id']?'checked':''?>
                        />
                        <div class="p-4 rounded-xl border-2 border-slate-200 bg-white peer-checked:border-primary peer-checked:bg-primary/5 hover:border-slate-300 transition-all flex flex-col shadow-sm">
                            <div class="flex justify-between items-center mb-1">
                                <span class="block text-[15px] font-bold text-slate-900">
                                    <?=htmlspecialchars($st['st_name'])?>
                                </span>
                                <span class="material-symbols-outlined text-primary opacity-0 peer-checked:opacity-100 transition-opacity">check_circle</span>
                            </div>
                            <span class="block text-xs text-slate-500 font-medium">Click to configure</span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="w-2/3 flex flex-col min-h-0 h-full bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                
                <div class="shrink-0 px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white z-10">
                    <h3 class="text-[13px] font-bold uppercase tracking-wider text-slate-500">Configure Statements</h3>
                    <div id="activeScaleBadge" class="bg-white text-primary px-3 py-1 rounded-full text-xs font-bold border border-slate-200 shadow-sm">
                        Scale: None
                    </div>
                </div>

                <div id="response-container" class="flex-1 min-h-0 overflow-y-auto statements-scroll p-6 flex flex-col gap-5 bg-slate-50/30">
                    </div>

            </div>

        </main>

        <footer class="shrink-0 border-t border-slate-200 bg-white px-8 py-4 shadow-[0_-4px_12px_rgba(0,0,0,0.03)] z-20 w-full">
            <div class="max-w-6xl mx-auto flex justify-between items-center w-full">
                <a href="manual_page_container.php?step=2&digisim_id=<?=$digisimId?>" class="flex items-center gap-2 px-6 py-2.5 text-slate-600 font-semibold text-sm hover:bg-slate-100 rounded-lg transition-colors">
                    <span class="material-symbols-outlined text-lg">arrow_back</span>
                    Back
                </a>
                
                <div class="flex gap-4">
                    <button type="submit" name="action" value="draft" class="px-6 py-2.5 border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 rounded-lg transition-colors bg-white">
                        Save Progress
                    </button>
                    <button type="submit" name="action" value="next" class="flex items-center gap-2 px-8 py-2.5 bg-primary text-white font-bold text-sm rounded-lg hover:bg-primary/90 transition-all shadow-lg shadow-primary/25">
                        Next Step
                        <span class="material-symbols-outlined text-lg">arrow_forward</span>
                    </button>
                </div>
            </div>
        </footer>

    </form>
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
            block.className = "bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden shrink-0";

            block.innerHTML = `
                <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-400 text-[20px] font-light" style="font-variation-settings: 'FILL' 0;">label</span>
                    <h3 class="font-bold text-slate-900 text-[15px]">${comp.stv_name}</h3>
                </div>
                <div class="p-4 flex flex-col gap-4">
                    <div class="resp-inputs-list flex flex-col gap-3 w-full"></div>
                    <button type="button" class="resp-btn-add flex items-center gap-1.5 w-fit text-sm font-semibold text-primary hover:text-primary/80 transition-colors mt-1">
                        <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 0;">add_circle</span>
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
        row.className = "flex items-center gap-3 w-full group shrink-0";

        const input = document.createElement("input");
        input.type = "text";
        input.className = "flex-1 rounded-lg border border-slate-300 bg-white text-sm px-4 py-2 text-slate-900 focus:ring-1 focus:ring-primary focus:border-primary shadow-sm placeholder:text-slate-400";
        input.name = `statement[${scoreId}][]`;
        input.value = val || "";

        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "text-slate-400 hover:text-slate-600 transition-colors p-1.5 rounded-md hover:bg-slate-100 flex items-center justify-center shrink-0";
        btn.innerHTML = '<span class="material-symbols-outlined text-[20px]" style="font-variation-settings: \'FILL\' 0;">delete</span>';
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
</script>