<div id="<?= $id ?>" 
    class="fixed inset-0 bg-black/40 flex items-center justify-center z-[9999] modal-overlay"
    style="display: none;">

        <div class="bg-white rounded-xl shadow-xl w-full max-w-[950px] p-6 relative modal-card">

        <button type="button" class="absolute right-4 top-4 text-gray-500 hover:text-black modal-close">
            ✕
        </button>

        <?php if(isset($title)): ?>
            <h2 class="text-xl font-bold mb-5"><?= esc($title) ?></h2>
        <?php endif; ?>

        <?= $content ?>

    </div>

</div>

<!-- Modal is not scrollable; inner table container will handle scrolling -->