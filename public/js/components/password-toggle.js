document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('employee-password') || document.getElementById('password');
    const eyeOpen = document.getElementById('eye-open');
    const eyeClosed = document.getElementById('eye-closed');
    const toggleButton = document.getElementById('toggle-password');

    if (!passwordInput || !eyeOpen || !eyeClosed || !toggleButton) {
        return;
    }

    function togglePassword() {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeOpen.classList.remove('hidden');
            eyeClosed.classList.add('hidden');
        } else {
            passwordInput.type = 'password';
            eyeOpen.classList.add('hidden');
            eyeClosed.classList.remove('hidden');
        }
    }

    toggleButton.addEventListener('click', togglePassword);
});