document.addEventListener("DOMContentLoaded", () => {

    const openButtons = document.querySelectorAll("[data-modal-open]");
    const closeButtons = document.querySelectorAll(".modal-close");
    const overlays = document.querySelectorAll(".modal-overlay");

    // OPEN MODAL
    openButtons.forEach(btn => {
        btn.addEventListener("click", () => {

            const id = btn.dataset.modalOpen;
            const modal = document.getElementById(id);

            modal.classList.remove("hidden");

            // RESET FORM TO STEP 1
            resetSteps();

        });
    });

    // CLOSE MODAL BUTTON
    closeButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            btn.closest(".modal-overlay").classList.add("hidden");
        });
    });

    // CLICK OUTSIDE MODAL
    overlays.forEach(overlay => {
        overlay.addEventListener("click", (e) => {

            if(e.target === overlay)
                overlay.classList.add("hidden");

        });
    });

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
    const steps = ["step1","step2","step3"];

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
    const steps = [1,2,3];

    steps.forEach(i => {

        const progress = document.getElementById("progress"+i);

        if(!progress) return;

        if(i <= step)
        {
            progress.classList.remove("bg-gray-300","text-gray-700");
            progress.classList.add("bg-purple-500","text-white");
        }
        else
        {
            progress.classList.remove("bg-purple-500","text-white");
            progress.classList.add("bg-gray-300","text-gray-700");
        }

    });
}