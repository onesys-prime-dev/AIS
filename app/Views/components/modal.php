<div id="<?= $id ?>" 
     class="fixed inset-0 bg-black/40 hidden flex items-center justify-center z-[9999] modal-overlay">

    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 relative modal-card">

        <!-- Close -->
        <button class="absolute right-4 top-4 text-gray-500 hover:text-black modal-close">
            ✕
        </button>

        <!-- Modal Title -->
        <?php if(isset($title)): ?>
            <h2 class="text-xl font-bold mb-6"><?= esc($title) ?></h2>
        <?php endif; ?>

        <!-- Modal Body -->
        <?= $content ?>

    </div>

</div>