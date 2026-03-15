<?php
/**
 * Reusable Stepper Component
 * Modern SaaS-style horizontal stepper for simulation workflow.
 */

// Load Material Symbols if not already loaded
?>
<!-- Material Symbols font -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$steps = [
    ['label' => 'Simulation Context', 'file' => 'manual_simulation_setup.php'],
    ['label' => 'Configure Injects', 'file' => 'manual_inject_setup.php'],
    ['label' => 'Response Scale', 'file' => 'manual_response_setup.php'],
    ['label' => 'Processing Settings', 'file' => 'manual_processing_configuration.php'],
    ['label' => 'Review & Summary', 'file' => 'manual_answer_manual.php'],
    ['label' => 'Success', 'file' => 'manual_success.php'],
];

// 1. Detect current step
// If included via manual_page_container.php, use the $step variable.
if (isset($step) && is_numeric($step)) {
    $currentStepIndex = (int)$step - 1;
} else {
    // Fallback: Detect by filename if accessed directly
    $currentPage = basename($_SERVER['PHP_SELF']);
    $currentStepIndex = 0;
    foreach ($steps as $index => $s) {
        if ($s['file'] === $currentPage) {
            $currentStepIndex = $index;
            break;
        }
    }
}
?>

<style>
    .stepper-container {
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        padding: 32px 24px 16px 24px;
        font-family: 'Public Sans', sans-serif;
    }

    .stepper-flex {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        position: relative;
    }

    /* Step Item */
    .step-item {
        display: flex;
        align-items: center;
        position: relative;
        z-index: 1;
        flex: 1;
    }
    
    .step-item:last-child {
        flex: none;
    }

    /* Circle Styling */
    .step-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 2px solid #d1d5db;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 600;
        color: #94a3b8;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }

    /* Label Styling */
    .step-label {
        font-size: 14px;
        margin-left: 12px;
        color: #94a3b8;
        white-space: nowrap;
        transition: all 0.3s ease;
    }

    /* Connector Line */
    .step-connector {
        flex: 1;
        height: 2px;
        background: #d1d5db;
        margin: 0 16px;
        transition: background 0.3s ease;
    }

    /* --- Completed State --- */
    .step-completed .step-circle {
        border-color: #3b82f6;
        color: #3b82f6;
    }
    .step-completed .step-label {
        color: #3b82f6;
    }
    .step-completed + .step-connector {
        background: #3b82f6;
    }
    .step-completed .step-circle .material-symbols-outlined {
        font-size: 20px;
        font-weight: 700;
    }

    /* --- Current State --- */
    .step-current .step-circle {
        border-color: #3b82f6;
        color: #3b82f6;
        background: #fff;
    }
    .step-current .step-label {
        color: #3b82f6;
        font-weight: 700;
    }

    /* --- Upcoming State --- (handled by defaults) */

</style>

<div class="stepper-container">
    <div class="stepper-flex">
        <?php foreach ($steps as $index => $step): ?>
            <?php 
                $statusClass = '';
                if ($index < $currentStepIndex) {
                    $statusClass = 'step-completed';
                } elseif ($index === $currentStepIndex) {
                    $statusClass = 'step-current';
                } else {
                    $statusClass = 'step-upcoming';
                }
            ?>
            
            <div class="step-item <?=$statusClass?>">
                <div class="step-circle">
                    <?php if ($index < $currentStepIndex): ?>
                        <span class="material-symbols-outlined">check</span>
                    <?php else: ?>
                        <?=($index + 1)?>
                    <?php endif; ?>
                </div>
                <div class="step-label"><?=htmlspecialchars($step['label'])?></div>
            </div>

            <?php if ($index < count($steps) - 1): ?>
                <div class="step-connector <?= ($index < $currentStepIndex) ? 'step-completed' : '' ?>"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
