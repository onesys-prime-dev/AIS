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
            <div class="crud-body scroll-bar text-gray-700 flex-1 min-h-0 overflow-y-auto hide-scrollbar"
                 style="max-height: 56vh; -webkit-overflow-scrolling: touch;"></div>
    </div>

</div>

<style>
/* Hide scrollbars but preserve scrolling for targeted containers */
.hide-scrollbar {
    -ms-overflow-style: none; /* IE and Edge */
    scrollbar-width: none; /* Firefox */
}
.hide-scrollbar::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}
</style>