<style>

.modal-overlay {

    position: fixed;

    inset: 0;

    z-index: 9999;

    display: none;

    align-items: center;

    justify-content: center;

    overflow-y: auto;

    padding: 24px;
}

/* SHOW MODAL */
.modal-overlay.show {

    display: flex;
}

/* DARK BLURRED BACKDROP */
.modal-overlay::before {

    content: '';

    position: absolute;

    inset: 0;

    background: rgba(0, 0, 0, 0.6);

    backdrop-filter: blur(6px);

    -webkit-backdrop-filter: blur(6px);
}

/* MODAL BOX */
.default-modal-box {

    background: #fff;

    width: 100%;

    max-width: 600px;

    border-radius: 20px;

    box-shadow:
        0 25px 50px rgba(0,0,0,.25),
        0 0 0 1px rgba(255,255,255,.05);

    position: relative;

    padding: 24px;

    animation: modalPop .18s ease;

    z-index: 1;
}

/* CLOSE BUTTON */
.default-modal-close {

    position: absolute;

    right: 18px;

    top: 14px;

    border: none;

    background: transparent;

    font-size: 28px;

    line-height: 1;

    cursor: pointer;

    color: #8a8a8a;

    transition: .18s ease;
}

.default-modal-close:hover {

    color: #111;

    transform: scale(1.08);
}

/* TITLE */
.default-modal-title {

    font-size: 22px;

    font-weight: 700;

    color: #1f2937;

    margin-bottom: 22px;

    padding-right: 40px;
}

/* ANIMATION */
@keyframes modalPop {

    from {

        opacity: 0;

        transform: scale(.96) translateY(8px);
    }

    to {

        opacity: 1;

        transform: scale(1) translateY(0);
    }
}

</style>


<!-- MODAL -->
<div id="<?= esc($id) ?>" class="modal-overlay">

    <!-- MODAL BOX -->
    <div class="default-modal-box"
         style="max-width: <?= esc($maxWidth ?? '950px') ?>;">

        <!-- CLOSE BUTTON -->
        <button
            type="button"
            class="default-modal-close modal-close">

            ✕

        </button>

        <!-- TITLE -->
        <?php if (isset($title)): ?>

            <h2 class="default-modal-title">

                <?= esc($title) ?>

            </h2>

        <?php endif; ?>

        <!-- CONTENT -->
        <?= $content ?>

    </div>

</div>