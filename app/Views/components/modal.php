<style>
.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;

    display: none;
    align-items: center;
    justify-content: center;
    
    background: rgba(0,0,0,.45);
}

.modal-overlay.show {
    display: flex;
}

.default-modal-box {
    background: #fff;

    width: 100%;
    max-width: 600px;

    border-radius: 18px;

    box-shadow:
        0 25px 50px rgba(0,0,0,.25);

    position: relative;

    padding: 24px;

    animation: modalPop .18s ease;
}

.default-modal-close {
    position: absolute;

    right: 16px;
    top: 16px;

    border: none;
    background: none;

    font-size: 24px;

    cursor: pointer;

    color: #666;
}

.default-modal-close:hover {
    color: #000;
}

.default-modal-title {
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

<div id="<?= $id ?>" class="modal-overlay">

    <div class="default-modal-box">

        <button
            type="button"
            class="default-modal-close modal-close">
            ✕
        </button>

        <?php if(isset($title)): ?>
            <h2 class="default-modal-title">
                <?= esc($title) ?>
            </h2>
        <?php endif; ?>

        <?= $content ?>

    </div>

</div>