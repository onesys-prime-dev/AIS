<?= $this->extend(setting('Auth.views')['layout'] ?? 'auth_layout') ?>

<?= $this->section('main') ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card">
                <div class="card-body">

                    <h2 class="mb-3">Reset Password</h2>

                    <p class="text-muted">
                        Enter a new password for your AIS account.
                    </p>

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
                        action="<?= site_url('reset-password/' . rawurlencode($token)) ?>"
                        method="post">

                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                New Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control"
                                required
                                minlength="8"
                                autocomplete="new-password">
                        </div>

                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">
                                Confirm New Password
                            </label>

                            <input
                                type="password"
                                name="password_confirm"
                                id="password_confirm"
                                class="form-control"
                                required
                                minlength="8"
                                autocomplete="new-password">
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100">
                            Reset Password
                        </button>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<?= $this->endSection() ?>