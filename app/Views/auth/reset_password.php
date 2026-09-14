<?= $this->extend('layouts/auth_layout') ?>
<?= $this->section('content') ?>

<div class="relative flex min-h-screen items-center justify-center m-auto w-full px-6 py-6 bg-[#FAF9F6]">
    <div class="flex w-full max-w-6xl rounded-2xl overflow-hidden shadow-lg">
        <div class="w-2/3 flex items-center justify-center bg-white px-12 lg:px-24 py-8">
            <div class="w-full max-w-md">
                <div class="mb-6">
                    <div class="flex items-center justify-center mb-5">
                        <?php if (! empty($companyLogoUrl)): ?>
                            <img src="<?= esc($companyLogoUrl) ?>" alt="Company Logo" class="h-20 w-20" loading="eager" decoding="async">
                        <?php endif; ?>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Reset your password</h1>
                    <p class="text-gray-500 text-base">
                        Choose a new password for your account. It must be at least 8 characters long.
                    </p>
                </div>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                        <?= esc(session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('errors')): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                        <ul class="list-disc pl-5 space-y-1">
                            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('reset-password/' . rawurlencode($token)) ?>" method="post" class="space-y-4">
                    <?= csrf_field() ?>

                    <div class="relative">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="New password"
                            class="w-full px-5 py-3 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent text-gray-800 placeholder-gray-400"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >
                        <button type="button" class="toggle-password-btn absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none" data-target="password" aria-label="Show or hide password">
                            <svg class="eye-closed h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                            </svg>
                            <svg class="eye-open h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>

                    <div class="relative">
                        <input
                            type="password"
                            name="password_confirm"
                            id="password_confirm"
                            placeholder="Confirm new password"
                            class="w-full px-5 py-3 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent text-gray-800 placeholder-gray-400"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >
                        <button type="button" class="toggle-password-btn absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none" data-target="password_confirm" aria-label="Show or hide password">
                            <svg class="eye-closed h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                            </svg>
                            <svg class="eye-open h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-black text-white py-3 rounded-full font-semibold text-base hover:bg-gray-900 transition duration-200 shadow-md"
                    >
                        Reset Password
                    </button>
                </form>

                <p class="mt-6 text-center text-sm">
                    <a
                        href="<?= site_url('login') ?>"
                        class="text-purple-600 hover:text-purple-800 hover:underline font-medium">
                        Back to Login
                    </a>
                </p>
            </div>
        </div>
        <div class="hidden lg:block w-1/3 flex-shrink-0 bg-cover bg-center"
            style="background-image: url('<?= base_url('images/background/building.png') ?>');">
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.toggle-password-btn').forEach(function (button) {
        var input = document.getElementById(button.dataset.target);
        var eyeClosed = button.querySelector('.eye-closed');
        var eyeOpen = button.querySelector('.eye-open');

        if (!input || !eyeClosed || !eyeOpen) {
            return;
        }

        button.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            eyeOpen.classList.toggle('hidden', !show);
            eyeClosed.classList.toggle('hidden', show);
        });
    });
</script>

<?= $this->endSection() ?>