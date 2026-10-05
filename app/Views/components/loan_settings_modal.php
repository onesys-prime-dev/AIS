<style>
/* OVERLAY */
.loan-settings-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;

    display: none;
    align-items: center;
    justify-content: center;

    background: rgba(0, 0, 0, .45);
}

.loan-settings-overlay.show {
    display: flex;
}

/* BOX */
.loan-settings-box {
    background: #fff;

    width: 100%;
    max-width: 620px;
    max-height: 90vh;

    border-radius: 18px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, .25);

    position: relative;
    padding: 24px;

    display: flex;
    flex-direction: column;

    animation: loanSettingsPop .18s ease;
}

/* CLOSE BUTTON */
.loan-settings-close {
    position: absolute;
    right: 16px;
    top: 16px;

    border: none;
    background: none;

    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.loan-settings-close:hover {
    color: #000;
}

/* TITLE */
.loan-settings-title {
    font-size: 22px;
    font-weight: 700;
    margin: 0 0 20px 0;
    flex-shrink: 0;
}

/* SCROLLABLE FORM AREA */
.loan-settings-body {
    overflow-y: auto;
    flex: 1;
    min-height: 0;

    /* hide scrollbar */
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.loan-settings-body::-webkit-scrollbar {
    display: none;
}

@keyframes loanSettingsPop {
    from { opacity: 0; transform: scale(.95); }
    to   { opacity: 1; transform: scale(1);   }
}
</style>

<div id="<?= $id ?>" class="loan-settings-overlay modal-overlay">

    <div class="loan-settings-box">

        <button
            type="button"
            class="loan-settings-close modal-close">
            ✕
        </button>

        <?php if (isset($title)): ?>
            <h2 class="loan-settings-title">
                <?= esc($title) ?>
            </h2>
        <?php endif; ?>

        <div class="loan-settings-body">
            <?= $content ?>
        </div>

    </div>

</div>