<div 
    class="bg-white rounded-lg shadow-lg p-5 h-full border border-gray-400 relative flex flex-col min-h-0"
    data-crud-table="<?= esc($tableId) ?>">

    <!-- TABLE -->
    <div class="flex flex-col flex-1 min-h-0">
        <!-- HEAD -->
        <div class="grid <?= esc($grid) ?> bg-[#DCDCDC] text-[#737373] border border-[#9E9E9E] rounded-lg overflow-hidden text-sm font-medium">
            <?php foreach ($columns as $col): ?>
            <div class="p-4 <?= $col === 'Action' ? 'text-center' : '' ?>">
                <?= esc($col) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- BODY -->
        <div class="crud-body scroll-bar text-gray-700 flex-1 min-h-0 overflow-y-auto"></div>
    </div>

</div>