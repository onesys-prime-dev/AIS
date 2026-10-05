<style>
/*
 * NOTE ON CLASS NAMES: this component keeps "modal-overlay" / "modal-close"
 * on its markup because public/js/components/modal-handler.js's global
 * click-delegate looks for those exact classes to know what to close. But at
 * least 7 different files in this codebase independently define their OWN
 * ".modal-overlay { ... }" rule (dashboard.php, view_notification.php,
 * employee_account.php, components/modal.php, components/add_loan_modal.php,
 * etc.) — since CSS classes are global and unscoped, whichever one of those
 * happens to load LAST on a given page wins the cascade for any shared
 * property, including this component's blurred ::before backdrop leaking
 * onto a completely different modal system that never asked for it.
 * Every selector below is therefore a COMPOUND selector requiring the
 * "civm-" (unique, unused-elsewhere) marker class too, giving it higher
 * specificity than any single-class ".modal-overlay" rule regardless of
 * load order — a real fix, not just a rename that could still lose a race.
 */

.modal-overlay.civm-overlay {

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
.modal-overlay.civm-overlay.show {

    display: flex;
}

/* DARK BLURRED BACKDROP */
.modal-overlay.civm-overlay::before {

    content: '';

    position: absolute;

    inset: 0;

    background: rgba(0, 0, 0, 0.6);

    backdrop-filter: blur(6px);

    -webkit-backdrop-filter: blur(6px);
}

/* MODAL BOX */
.civm-box {

    background: #fff;

    width: 100%;

    max-width: 600px;

    border-radius: 20px;

    box-shadow:
        0 25px 50px rgba(0,0,0,.25),
        0 0 0 1px rgba(255,255,255,.05);

    position: relative;

    padding: 24px;

    animation: civmModalPop .18s ease;

    z-index: 1;

    max-height: calc(100dvh - 48px);

    overflow-y: auto;

    overscroll-behavior: contain;
}

/* CLOSE BUTTON */
.modal-close.civm-close {

    position: absolute;

    right: 18px;

    top: 14px;

    z-index: 10;

    border: none;

    background: transparent;

    font-size: 28px;

    line-height: 1;

    cursor: pointer;

    color: #8a8a8a;

    transition: .18s ease;
}

.modal-close.civm-close:hover {

    color: #111;

    transform: scale(1.08);
}

/* TITLE */
.civm-title {

    font-size: 22px;

    font-weight: 700;

    color: #1f2937;

    margin-bottom: 22px;

    padding-right: 40px;
}

/* This shell was already responsive (width:100% capped by max-width), just
   tightening the padding on very narrow phones so more of the actual
   content width is usable — 24px on the overlay + 24px on the box each
   side was ~96px of pure padding on a 375px screen. */
@media (max-width: 480px) {
    .modal-overlay.civm-overlay {
        padding: 10px;
    }
    .civm-box {
        padding: 16px;
        max-height: calc(100dvh - 20px);
        border-radius: 14px;
    }
    .civm-title {
        font-size: 18px;
        padding-right: 32px;
        margin-bottom: 16px;
    }

    .civm-box input:not([type="checkbox"]):not([type="radio"]),
    .civm-box select,
    .civm-box textarea,
    .civm-box button {
        max-width: 100%;
    }
}

/* ANIMATION */
@keyframes civmModalPop {

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
<div id="<?= esc($id) ?>" class="modal-overlay civm-overlay">

    <!-- MODAL BOX -->
    <div class="civm-box"
         style="max-width: <?= esc($maxWidth ?? '950px') ?>;">

        <!-- CLOSE BUTTON -->
        <button
            type="button"
            class="modal-close civm-close">

            ✕

        </button>

        <!-- TITLE -->
        <?php if (isset($title)): ?>

            <h2 class="civm-title">

                <?= esc($title) ?>

            </h2>

        <?php endif; ?>

        <!-- CONTENT -->
        <?= $content ?>

    </div>

</div>
