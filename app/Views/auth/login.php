<?= $this->extend('layouts/auth_layout') ?>
<?= $this->section('content') ?>

    <div class="flex items-center justify-center m-auto w-full h-screen px-6 py-12 bg-[#FAF9F6]">
        <div class="flex w-full max-w-6xl h-[80vh] rounded-2xl overflow-hidden shadow-lg">
            <!-- Left: Login Form Panel -->
                <div class="w-2/3 flex items-center justify-center bg-white px-12 lg:px-24">
                    <div class="w-full max-w-md">
                        <!-- Header -->
                        <div class="mb-10">
                            <div class="text-gray-400 font-bold text-lg mb-1">Accounting System</div>
                            <h1 class="text-4xl font-bold text-gray-900 mb-3">Welcome Back</h1>
                            <p class="text-gray-500 text-lg">
                                Enter your email and password to access your account.
                            </p>
                        </div>

                        <!-- Error Message -->
                        <?php if (session('error')): ?>
                            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                                <?= esc(session('error')) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Form -->
                        <form action="<?= url_to('login') ?>" method="post" class="space-y-6">
                            <?= csrf_field() ?>

                            <!-- Email / Username -->
                            <div>
                                <input 
                                    type="text" 
                                    name="email" 
                                    value="<?= old('email') ?>" 
                                    placeholder="Email or username"
                                    class="w-full px-5 py-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent text-gray-800 placeholder-gray-400"
                                    required
                                >
                            </div>

                            <!-- Password -->
                            <div class="relative">
                                <input type="password" id="password" name="password" placeholder="Password" class="w-full px-5 py-4 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent text-gray-800 placeholder-gray-400" required>
                                <button type="button" id="toggle-password" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                                <!-- Eye Slash Icon (Password Hidden - Default State) -->
                                    <svg id="eye-closed" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                                    </svg>
                                    <!-- Eye Icon (Password Visible) -->
                                    <svg id="eye-open" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Remember + Forgot -->
                            <div class="flex items-center justify-between text-sm">
                                <label class="flex items-center gap-3 text-gray-600 cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        name="remember" 
                                        class="h-5 w-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                    >
                                    <span>Stay logged in?</span>
                                </label>

                                <a href="#" class="text-purple-600 hover:text-purple-800 hover:underline font-medium">
                                    Forgot password?
                                </a>
                            </div>

                            <!-- Login Button -->
                            <button 
                                type="submit"
                                class="w-full bg-black text-white py-4 rounded-full font-semibold text-lg hover:bg-gray-900 transition duration-200 shadow-md"
                            >
                                Login
                            </button>
                        </form>

                        <!-- Footer text -->
                        <p class="mt-8 text-center text-gray-500 text-sm">
                            By continuing, you agree to our 
                            <span class="font-semibold text-gray-700">Terms</span> and 
                            <span class="font-semibold text-gray-700">Privacy Policy</span>
                        </p>

                        <p class="mt-6 text-center text-gray-400 text-xs">
                            Copyright © 2026 ONESYS Primesolutions Co
                        </p>
                    </div>
            </div>     
            <div class="hidden lg:block w-1/3 flex-shrink-0 bg-cover bg-center" 
                style="background-image: url('<?= base_url('images/background/building.png') ?>');">
            </div>
        </div>
    </div>

<script src="<?= base_url('js/components/password-toggle.js') ?>"></script>

<?= $this->endSection() ?>