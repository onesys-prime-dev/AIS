<div class="p-4">
    <form id="importTimesheetForm" enctype="multipart/form-data">
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">CSV Timesheet File</label>
            <input type="file" name="timesheet_file" accept=".csv" required class="border rounded p-2 w-full">
            <p class="text-xs text-gray-500 mt-1">Expected columns: employee_number, date, time_in, time_out, break_duration</p>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">Upload</button>
        </div>
    </form>
</div>

<script>
    document.getElementById('importTimesheetForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const response = await fetch('<?= site_url('client_portal/payroll/importTimesheet') ?>', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            const skippedCount = result.skipped || 0;
            if (skippedCount > 0) {
                console.warn('Timesheet import — skipped rows:', result.errors);
                showToast(
                    `${result.imported} row(s) imported, ${skippedCount} skipped — see browser console for details.`,
                    'warning'
                );
            } else {
                showToast(`${result.imported} row(s) imported successfully.`, 'success');
            }
            // Close modal (if using a modal)
            const modal = document.getElementById('importTimesheetModal');
            if (modal) modal.style.display = 'none';
        } else {
            showToast('Import failed: ' + (result.error || 'Unknown error'), 'error');
        }
    });
</script>