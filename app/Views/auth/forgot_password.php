<?= $this->extend(setting('Auth.views')['layout'] ?? 'auth_layout') ?>

<?= $this->section('main') ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card">
                <div class="card-body">

                    <h2 class="mb-3">Forgot Password?</h2>

                    <p class="text-muted">
                        Enter the email address associated with your AIS account.
                        If an account exists, we'll send you a password reset link.
                    </p>

                    <?php if (session()->getFlashdata('message')): ?>
                        <div class="alert alert-info">
                            <?= esc(session()->getFlashdata('message')) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger">
                            <?= esc(session()->getFlashdata('error')) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('errors')): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                                    <li><?= esc($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form
                        action="<?= site_url('forgot-password') ?>"
                        method="post">

                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Email Address
                            </label>

                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control"
                                value="<?= old('email') ?>"
                                required
                                autocomplete="email">
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100">
                            Send Password Reset Link
                        </button>

                    </form>

                    <div class="mt-3 text-center">
                        <a href="<?= site_url('login') ?>">
                            Back to Login
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?= $this->endSection() ?>