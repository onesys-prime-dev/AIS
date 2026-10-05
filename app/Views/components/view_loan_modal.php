<style>
.view-modal-overlay {
    position: fixed;
    inset: 0;

    background: rgba(0,0,0,.45);

    display: none;
    align-items: center;
    justify-content: center;

    z-index: 99999;
}

.view-modal-overlay.show {
    display: flex;
}

.view-modal-box {
    background: #fff;

    width: 100%;
    max-width: 700px;

    border-radius: 18px;

    position: relative;

    overflow-x: hidden;

    margin: 16px;

    padding: 24px;

    box-shadow:
        0 25px 50px rgba(0,0,0,.25);

    animation: modalPop .18s ease;
}

.view-modal-close {
    position: absolute;

    right: 16px;
    top: 16px;

    border: none;
    background: none;

    font-size: 24px;

    cursor: pointer;
}

.view-modal-title {
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

<div id="<?= $id ?>" class="view-modal-overlay modal-overlay">

    <div class="view-modal-box">

        <button
            type="button"
            class="view-modal-close modal-close">
            ✕
        </button>

        <?php if(isset($title)): ?>
            <h2 class="view-modal-title">
                <?= esc($title) ?>
            </h2>
        <?php endif; ?>

        <?= $content ?>

    </div>

</div>