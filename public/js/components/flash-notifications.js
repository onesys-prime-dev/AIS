document.addEventListener("DOMContentLoaded", () => {
    const toasts = [
        document.getElementById("toast-success"),
        document.getElementById("toast-error")
    ];

    toasts.forEach(toast => {
        if (!toast) return;
        // show toast
        setTimeout(() => {
            toast.classList.remove("opacity-0", "translate-y-[-10px]");
        }, 100);
        // hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.add("opacity-0", "translate-y-[-10px]");
        }, 3000);
        // remove from DOM
        setTimeout(() => {
            toast.remove();
        }, 3500);

    });

});