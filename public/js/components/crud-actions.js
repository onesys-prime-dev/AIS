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
            }

        });

        if (updateUrl && id) {
            form.action = `${updateUrl}/${id}`;
        }

        document.getElementById(modalId).classList.remove("hidden");

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