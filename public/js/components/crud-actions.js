function autoResizeTextarea(textarea) {
    if (!textarea || textarea.tagName !== "TEXTAREA") return;

    if (!textarea.dataset.minHeightPx) {
        textarea.dataset.minHeightPx = String(textarea.offsetHeight || 0);
    }

    const minHeight = Number(textarea.dataset.minHeightPx) || 0;

    textarea.style.height = "auto";
    textarea.style.height = `${Math.max(textarea.scrollHeight, minHeight)}px`;
}

document.addEventListener("input", function (e) {
    const textarea = e.target.closest('textarea[data-autoresize="true"]');
    if (!textarea) return;

    autoResizeTextarea(textarea);
});

document.addEventListener("DOMContentLoaded", function () {
    document
        .querySelectorAll('textarea[data-autoresize="true"]')
        .forEach(autoResizeTextarea);
});

document.addEventListener("click", function (e) {

    const editBtn = e.target.closest('[data-action="edit"]');
    const deleteBtn = e.target.closest('[data-action="delete"]');

    // Edit Action
    if (editBtn) {

        const modalId = editBtn.dataset.modal;
        const updateUrl = editBtn.dataset.updateUrl;
        const id = editBtn.dataset.id;

        const form = document.querySelector(`#${modalId} form`);

        if (!form) return;

        form.reset();

        // auto-fill inputs using dataset
        Object.keys(editBtn.dataset).forEach(key => {

            const input = form.querySelector(`[name="${key}"]`);

            if (!input) return;

            if (input.type === "checkbox") {
                input.checked = Number(editBtn.dataset[key]) === 1;
            } else {
                input.value = editBtn.dataset[key];

                if (input.tagName === "TEXTAREA" && input.dataset.autoresize === "true") {
                    autoResizeTextarea(input);
                }
            }

        });

        if (updateUrl && id) {
            form.action = `${updateUrl}/${id}`;
        }

        if (typeof window.openModalById === "function") {
            window.openModalById(modalId);
        } else {
            const modalEl = document.getElementById(modalId);
            if (modalEl) {
                modalEl.style.display = "flex";
            }
        }

        return;
    }

    // Delete Action
    if (deleteBtn) {

        const deleteUrl = deleteBtn.dataset.deleteUrl;
        const id = deleteBtn.dataset.id;

        if (!deleteUrl || !id) return;

        if (confirm("Are you sure you want to delete this record?")) {

            window.location.href = `${deleteUrl}/${id}`;

        }

    }

});