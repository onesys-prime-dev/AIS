<style>
.edit-employee-overlay {
    position: fixed;
    inset: 0;

    background: rgba(0,0,0,.45);

    display: none;
    align-items: center;
    justify-content: center;

    z-index: 99999;
}

.edit-employee-overlay.show {
    display: flex;
}

.edit-employee-box {
    background: #fff;

    width: 100%;
    max-width: 600px;

    /* Same fix as edit_loan_modal.php's identical rule (both define this
       class independently and render on the same page, Employee Management):
       the form inside can run longer than the viewport, and without a height
       cap the box just grows past the screen with no way to scroll to its
       own Save button at the bottom — zooming out was the only way to reach
       it. The ✕ close button (position:absolute) stays pinned at the corner
       since it isn't affected by the box's own scroll. */
    max-height: 90vh;
    overflow-y: auto;

    border-radius: 18px;

    padding: 24px;

    position: relative;

    box-shadow:
        0 25px 50px rgba(0,0,0,.25);

    animation: modalPop .18s ease;
}

.edit-employee-close {
    position: absolute;

    right: 16px;
    top: 16px;

    border: none;
    background: none;

    font-size: 24px;

    cursor: pointer;
}

.edit-employee-title {
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

<div id="<?= $id ?>" class="edit-employee-overlay modal-overlay">

    <div class="edit-employee-box">

        <button
            type="button"
            class="edit-employee-close modal-close">
            ✕
        </button>

        <?php if(isset($title)): ?>
            <h2 class="edit-employee-title">
                <?= esc($title) ?>
            </h2>
        <?php endif; ?>

        <?= $content ?>

    </div>

</div>