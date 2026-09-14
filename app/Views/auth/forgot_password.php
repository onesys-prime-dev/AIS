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
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Forgot your password?</h1>
                    <p class="text-gray-500 text-base">
                        Enter the email address linked to your AIS account and we'll send you a link to reset your password.
                    </p>
                </div>

                <?php if (session()->getFlashdata('message')): ?>
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg text-sm">
                        <?= esc(session()->getFlashdata('message')) ?>
                    </div>
                <?php endif; ?>

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

                <form action="<?= site_url('forgot-password') ?>" method="post" class="space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="<?= old('email') ?>"
                            placeholder="Email address"
                            class="w-full px-5 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent text-gray-800 placeholder-gray-400"
                            required
                            autocomplete="email"
                        >
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-black text-white py-3 rounded-full font-semibold text-base hover:bg-gray-900 transition duration-200 shadow-md"
                    >
                        Send Reset Link
                    </button>
                </form>

                <p class="mt-6 text-center text-sm">
                    <a
                        href="<?= site_url('login') ?>"
                        class="text-purple-600 hover:text-purple-800 hover:underline font-medium">
                        Back to Login
                    </a>
                </p>

                <p class="mt-6 text-center text-gray-500 text-sm">
                    By continuing, you agree to our
                    <span class="font-semibold text-gray-700">Terms</span> and
                    <span class="font-semibold text-gray-700">Privacy Policy</span>
                </p>

                <p class="mt-3 text-center text-gray-400 text-xs">
                    Copyright © 2026 ONESYS Primesolutions Co
                </p>
            </div>
        </div>
        <div class="hidden lg:block w-1/3 flex-shrink-0 bg-cover bg-center"
            style="background-image: url('<?= base_url('images/background/building.png') ?>');">
        </div>
    </div>
</div>

<?= $this->endSection() ?>