<?php
/**
 * Reusable Stepper Component — with embedded Back / Next navigation
 * The stepper renders a compact progress bar row with nav buttons on the right.
 */

$steps = [
    ['label' => 'Simulation Context', 'file' => 'manual_simulation_setup.php'],
    ['label' => 'Configure Injects',  'file' => 'manual_inject_setup.php'],
    ['label' => 'Response Scale',     'file' => 'manual_response_setup.php'],
    ['label' => 'Processing Settings','file' => 'manual_processing_configuration.php'],
    ['label' => 'Debriefing',   'file' => 'manual_answer_manual.php'],
    ['label' => 'Success',            'file' => 'manual_success.php'],
];

// Detect current step index (0-based)
if (isset($step) && is_numeric($step)) {
    $currentStepIndex = (int)$step - 1;
} else {
    $currentPage = basename($_SERVER['PHP_SELF']);
    $currentStepIndex = 0;
    foreach ($steps as $index => $s) {
        if ($s['file'] === $currentPage) { $currentStepIndex = $index; break; }
    }
}

$totalSteps  = count($steps);
$digisimId   = isset($digisimId) ? (int)$digisimId : 0;
$prevStepNum = $currentStepIndex;       // 1-based of previous (index is 0-based so prev = index)
$nextStepNum = $currentStepIndex + 2;   // 1-based of next

$backHref = ($currentStepIndex > 0)
    ? "manual_page_container.php?step={$prevStepNum}&digisim_id={$digisimId}"
    : null;

$nextHref = ($nextStepNum <= $totalSteps && $currentStepIndex < $totalSteps - 1)
    ? "manual_page_container.php?step={$nextStepNum}&digisim_id={$digisimId}"
    : null;

// Step 1 submits a form; steps 2-5 just navigate.
// The form id used in step 1 is "sim-form" (we'll set that in the step file).
$nextIsSubmit = ($currentStepIndex === 0);

// Step 5 (index 4) — finish label
$isLastActionStep = ($currentStepIndex === 4);
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">

<style>
/* ── Stepper Shell  */
.stepper-bar {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: 0;
    flex-shrink: 0;
    height: 58px;
    display: flex;
    align-items: stretch;
}

/* Inner wrapper: mirrors the same max-width centering as content shells */
.stepper-inner {
    max-width: 1400px;
    width: 100%;
    margin: 0 auto;
    padding: 0 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

/* ── Progress Track  */
.stepper-track {
    display: flex;
    align-items: center;
    flex: 1;
    min-width: 0;
    gap: 0;
}

.step-item {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.step-circle {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: #94a3b8;
    flex-shrink: 0;
    transition: all 0.25s ease;
}

.step-label {
    font-size: 12px;
    font-weight: 500;
    color: #94a3b8;
    white-space: nowrap;
    transition: all 0.25s ease;
    font-family: 'Public Sans', sans-serif;
}

/* Show label always */
.step-item.step-upcoming .step-label {
    /* visible by default */
}

.step-connector {
    flex: 1;
    min-width: 12px;
    max-width: 48px;
    height: 2px;
    background: #e2e8f0;
    margin: 0 6px;
    transition: background 0.3s ease;
    flex-shrink: 1;
}

/* Completed */
.step-item.step-completed .step-circle {
    border-color: #3b82f6;
    background: #3b82f6;
    color: #fff;
}
.step-item.step-completed .step-label { color: #3b82f6; }
.step-connector.step-done { background: #3b82f6; }

/* Current */
.step-item.step-current .step-circle {
    border-color: #3b82f6;
    color: #3b82f6;
}
.step-item.step-current .step-label {
    color: #0f172a;
    font-weight: 700;
    display: flex !important;
}

/* ── Nav Buttons ───────────────────────── */
.stepper-nav {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}

.stp-btn-back {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    background: transparent;
    padding: 7px 14px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
    font-family: 'Public Sans', sans-serif;
    white-space: nowrap;
}
.stp-btn-back:hover { background: #f1f5f9; border-color: #94a3b8; }
.stp-btn-back.disabled {
    opacity: 0.38;
    pointer-events: none;
}
.stp-btn-back .material-symbols-outlined { font-size: 16px; }

.stp-btn-next {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    background: #3b82f6;
    border: none;
    padding: 7px 18px;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(59,130,246,0.22);
    transition: filter 0.15s, transform 0.1s;
    font-family: 'Public Sans', sans-serif;
    white-space: nowrap;
}
.stp-btn-next:hover { filter: brightness(1.08); }
.stp-btn-next:active { transform: scale(0.97); }
.stp-btn-next .material-symbols-outlined { font-size: 16px; }
</style>

<div class="stepper-bar">
    <div class="stepper-inner">

    <!-- Progress Track -->
    <div class="stepper-track">
        <?php foreach ($steps as $index => $s):
            if ($index < $currentStepIndex) $cls = 'step-completed';
            elseif ($index === $currentStepIndex) $cls = 'step-current';
            else $cls = 'step-upcoming';
        ?>
        <div class="step-item <?= $cls ?>">
            <div class="step-circle">
                <?php if ($index < $currentStepIndex): ?>
                    <span class="material-symbols-outlined" style="font-size:14px;">check</span>
                <?php else: ?>
                    <?= ($index + 1) ?>
                <?php endif; ?>
            </div>
            <div class="step-label"><?= htmlspecialchars($s['label']) ?></div>
        </div>
        <?php if ($index < $totalSteps - 1): ?>
            <div class="step-connector <?= ($index < $currentStepIndex) ? 'step-done' : '' ?>"></div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Navigation Buttons -->
    <div class="stepper-nav">
        <?php if ($currentStepIndex > 0 && $currentStepIndex < $totalSteps - 1): ?>
            <a class="stp-btn-back" href="<?= $backHref ?>">
                <span class="material-symbols-outlined">arrow_back</span>
                Back
            </a>
        <?php endif; ?>

        <?php if ($currentStepIndex < $totalSteps - 1): ?>
            <?php if ($nextIsSubmit): ?>
                <!-- Step 1: submit the page's form -->
                <button class="stp-btn-next" type="submit" form="sim-form" name="action" value="next">
                    <?= $isLastActionStep ? 'Finish Simulation' : 'Next Step' ?>
                    <span class="material-symbols-outlined"><?= $isLastActionStep ? 'check_circle' : 'arrow_forward' ?></span>
                </button>
            <?php else: ?>
                <?php if ($isLastActionStep): ?>
                    <!-- Step 5: submit form -->
                    <button class="stp-btn-next" type="submit" form="ansForm" name="action" value="next">
                        Finish Simulation
                        <span class="material-symbols-outlined">check_circle</span>
                    </button>
                <?php elseif ($currentStepIndex === 3): ?>
                    <!-- Step 4: submit form -->
                    <button class="stp-btn-next" type="submit" form="procForm" name="action" value="next">
                        Next Step
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </button>
                <?php elseif ($currentStepIndex === 2): ?>
                    <!-- Step 3: submit form -->
                    <button class="stp-btn-next" type="submit" form="responseForm" name="action" value="next">
                        Next Step
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </button>
                <?php else: ?>
                    <a class="stp-btn-next" href="<?= $nextHref ?>">
                        Next Step
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    </div><!-- /.stepper-inner -->
</div>

