<style>
    .progress-wrapper {
        width: 100%;
        margin: 20px 0 30px;
    }

    .progress-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-family: 'Outfit', sans-serif;
    }

    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        position: relative;
    }

    .step span {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: 2px solid #d1d5db;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 600;
        color: #6b7280;
        background: #fff;
    }

    .step p {
        margin-top: 6px;
        font-size: 13px;
        color: #9ca3af;
    }

    .step.active span {
        background: #2c4152;
        border-color: #2c4152;
        color: #fff;
    }

    .step.active p {
        color: #111827;
        font-weight: 500;
    }

    .step::after {
        content: "";
        position: absolute;
        top: 17px;
        left: 50%;
        width: 100%;
        height: 2px;
        background: #e5e7eb;
        z-index: -1;
    }

    .step:last-child::after {
        display: none;
    }

    .step.active::after {
        background: #2c4152;
    }

    @media(max-width:768px) {

        .progress-bar {
            flex-wrap: wrap;
            gap: 15px;
        }

        .step {
            width: 45%;
        }

        .step::after {
            display: none;
        }

    }
</style>

<div class="progress-wrapper">

    <div class="progress-bar">

        <div class="step <?= $step >= 1 ? 'active' : '' ?>">
            <span><?= $step > 1 ? '✓' : '1' ?></span>
            <p>Simulation Context</p>
        </div>

        <div class="step <?= $step >= 2 ? 'active' : '' ?>">
            <span><?= $step > 2 ? '✓' : '2' ?></span>
            <p>Configure Injects</p>
        </div>

        <div class="step <?= $step >= 3 ? 'active' : '' ?>">
            <span><?= $step > 3 ? '✓' : '3' ?></span>
            <p>Response Scale</p>
        </div>

        <div class="step <?= $step >= 4 ? 'active' : '' ?>">
            <span><?= $step > 4 ? '✓' : '4' ?></span>
            <p>Processing Settings</p>
        </div>

        <div class="step <?= $step >= 5 ? 'active' : '' ?>">
            <span><?= $step > 5 ? '✓' : '5' ?></span>
            <p>Review & Summary</p>
        </div>

    </div>

</div>