<style>
/* Renamed from .edit-employee-* to .ee-modal-* — edit_loan_modal.php used
   to define an identically-named .edit-employee-box with different needs
   (its form has no inner scroll cap, so IT still needs the box itself to
   scroll), and since both components render on the same page (Employee
   Management), the two same-named rules silently fought over which one's
   overflow/max-height won. Unique names per component, no more collision. */
.ee-modal-overlay {
    position: fixed;
    inset: 0;

    background: rgba(0,0,0,.45);

    display: none;
    align-items: center;
    justify-content: center;

    z-index: 99999;
}

.ee-modal-overlay.show {
    display: flex;
}

.ee-modal-box {
    background: #fff;

    width: 100%;
    max-width: 600px;

    /* The form inside (#editEmployeeForm) already caps itself at 75vh with
       its own overflow-y:auto (scrollbar hidden via .hide-scroll-bar, but
       still scrollable) so its own Save button stays reachable. This box
       must NOT also scroll — two independent scroll containers nested
       inside each other fought over wheel input and left a dead blank gap
       below the form once it was tall enough to trigger both. max-height
       stays only as a hard ceiling (title + capped form always fits under
       it); overflow is visible, not auto — the form is the one true
       scroll area. */
    max-height: 90vh;

    border-radius: 18px;

    padding: 24px;

    position: relative;

    box-shadow:
        0 25px 50px rgba(0,0,0,.25);

    animation: modalPop .18s ease;
}

.ee-modal-close {
    position: absolute;

    right: 16px;
    top: 16px;

    border: none;
    background: none;

    font-size: 24px;

    cursor: pointer;
}

.ee-modal-title {
    font-size: 22px;
    font-weight: 700;

    margin-bottom: 20px;
}

@keyframes modalPop {
    from {
        opacity: 0;
        transform: scale(.95);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>

<div id="<?= $id ?>" class="ee-modal-overlay modal-overlay">

    <div class="ee-modal-box">

        <button
            type="button"
            class="ee-modal-close modal-close">
            ✕
        </button>

        <?php if(isset($title)): ?>
            <h2 class="ee-modal-title">
                <?= esc($title) ?>
            </h2>
        <?php endif; ?>

        <?= $content ?>

    </div>

</div>
