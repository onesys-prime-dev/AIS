<div id="<?= $id ?>" 
    class="fixed inset-0 bg-black/40 flex items-center justify-center modal-overlay"
    style="display: none;">

    <div class="bg-white rounded-xl shadow-xl w-full max-w-[600px] h-[550px] p-6 relative flex flex-col">

        <button type="button" class="absolute right-4 top-4 text-gray-500 hover:text-black modal-close">
            ✕
        </button>

        <?php if(isset($title)): ?>
            <h2 class="text-xl font-bold mb-5 shrink-0"><?= esc($title) ?></h2>
        <?php endif; ?>

        <div class="flex-1 overflow-y-auto">
            <?= $content ?>
        </div>

    </div>

</div>