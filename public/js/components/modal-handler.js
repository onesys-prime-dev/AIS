function openModalById(id) {
    const modal = document.getElementById(id);

    if (!modal) {
        console.warn(`Modal not found: ${id}`);
        return;
    }

    modal.style.display = "flex";

    if (typeof resetSteps === "function") {
        resetSteps();
    }
}

function closeModalElement(modal) {
    if (!modal) return;
    modal.style.display = "none";
}

window.openModalById = openModalById;
window.closeModalById = function (id) {
    closeModalElement(document.getElementById(id));
};

document.addEventListener("click", (event) => {
    const openButton = event.target.closest("[data-modal-open]");
    if (openButton) {
        openModalById(openButton.dataset.modalOpen);
        return;
    }

    const closeButton = event.target.closest(".modal-close");
    if (closeButton) {
        closeModalElement(closeButton.closest(".modal-overlay"));
        return;
    }

    const overlay = event.target.closest(".modal-overlay");
    if (overlay && event.target === overlay) {
        closeModalElement(overlay);
    }
});

function nextStep(step)
{
    document.querySelectorAll(".form-step")
        .forEach(el => el.classList.add("hidden"));

    document.getElementById("step"+step)
        .classList.remove("hidden");

    updateProgress(step);
}


function prevStep(step)
{
    document.querySelectorAll(".form-step")
        .forEach(el => el.classList.add("hidden"));

    document.getElementById("step"+step)
        .classList.remove("hidden");

    updateProgress(step);
}

function resetSteps()
{
    const steps = ["step1","step2","step3","step4"];

    steps.forEach((id,index)=>{

        const step = document.getElementById(id);

        if(!step) return;

        if(index === 0)
            step.classList.remove("hidden");
        else
            step.classList.add("hidden");

    });

    updateProgress(1);
}

function updateProgress(step)
{
    const steps = [1,2,3,4];

    steps.forEach(i => {

        const progress = document.getElementById("progress"+i);

        if(!progress) return;

        if(i <= step)
        {
            progress.classList.remove("bg-gray-300","text-gray-700");
            progress.classList.add("bg-[#A891FC]","text-white");
        }
        else
        {
            progress.classList.remove("bg-[#A891FC]","text-white");
            progress.classList.add("bg-gray-300","text-gray-700");
        }
    });
}