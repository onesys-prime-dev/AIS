<style>
.add-employee-overlay {
    position: fixed;
    inset: 0;

    background: rgba(0,0,0,.45);

    display: none;
    align-items: center;
    justify-content: center;

    z-index: 99999;
}

.add-employee-overlay.show {
    display: flex;
}

.add-employee-box {
    background: #fff;

    width: 100%;
    max-width: 700px;

    height: 650px;

    border-radius: 18px;

    position: relative;

    display: flex;
    flex-direction: column;

    padding: 24px;

    box-shadow:
        0 25px 50px rgba(0,0,0,.25);

    animation: modalPop .18s ease;
}

.add-employee-close {
    position: absolute;

    right: 16px;
    top: 16px;

    border: none;
    background: none;

    font-size: 24px;

    cursor: pointer;
}

.add-employee-title {
    font-size: 22px;
    font-weight: 700;

    margin-bottom: 20px;

    flex-shrink: 0;
}

.add-employee-content {
    flex: 1;
    overflow-y: auto;
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

<div id="<?= $id ?>" class="add-employee-overlay modal-overlay">

    <div class="add-employee-box">

        <button
            type="button"
            class="add-employee-close modal-close">
            ✕
        </button>

        <?php if(isset($title)): ?>
            <h2 class="add-employee-title">
                <?= esc($title) ?>
            </h2>
        <?php endif; ?>

        <div class="add-employee-content">
            <?= $content ?>
        </div>

    </div>

</div>