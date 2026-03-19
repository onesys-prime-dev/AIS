<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.register') ?> <?= $this->endSection() ?>

<?= $this->section('main') ?>

    <div class="container d-flex justify-content-center p-5">
        <div class="card col-12 col-md-8 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-5"><?= lang('Auth.register') ?></h5>

                <?php if (session('error') !== null) : ?>
                    <div class="alert alert-danger" role="alert"><?= esc(session('error')) ?></div>
                <?php elseif (session('errors') !== null) : ?>
                    <div class="alert alert-danger" role="alert">
                        <?php if (is_array(session('errors'))) : ?>
                            <?php foreach (session('errors') as $error) : ?>
                                <?= esc($error) ?>
                                <br>
                            <?php endforeach ?>
                        <?php else : ?>
                            <?= esc(session('errors')) ?>
                        <?php endif ?>
                    </div>
                <?php endif ?>

                <form action="<?= site_url('register') ?>" method="post"><?= csrf_field() ?>

                    <!-- Email -->
                    <div class="form-floating mb-2">
                        <input type="email" class="form-control" id="floatingEmailInput" name="email" inputmode="email" autocomplete="email" placeholder="<?= lang('Auth.email') ?>" value="<?= old('email') ?>" required>
                        <label for="floatingEmailInput"><?= lang('Auth.email') ?></label>
                    </div>

                    <!-- Username -->
                    <div class="form-floating mb-4">
                        <input type="text" class="form-control" id="floatingUsernameInput" name="username" inputmode="text" autocomplete="username" placeholder="<?= lang('Auth.username') ?>" value="<?= old('username') ?>" required>
                        <label for="floatingUsernameInput"><?= lang('Auth.username') ?></label>
                    </div>

                    <div class="mb-3">
                        <label for="role">Register As</label>

                        <select name="role" class="form-control" required>
                            <option value="employee">Employee</option>
                            <option value="client_payroll_staff">Client Payroll Staff</option>
                        </select>
                    </div>

                    <hr class="my-3">
                    <h6 class="mb-3">Employee Details</h6>

                    <div class="form-floating mb-2">
                        <input type="text" class="form-control" id="floatingFirstNameInput" name="first_name" inputmode="text" placeholder="First Name" value="<?= old('first_name') ?>" required>
                        <label for="floatingFirstNameInput">First Name</label>
                    </div>

                    <div class="form-floating mb-2">
                        <input type="text" class="form-control" id="floatingMiddleNameInput" name="middle_name" inputmode="text" placeholder="Middle Name" value="<?= old('middle_name') ?>">
                        <label for="floatingMiddleNameInput">Middle Name (Optional)</label>
                    </div>

                    <div class="form-floating mb-2">
                        <input type="text" class="form-control" id="floatingLastNameInput" name="last_name" inputmode="text" placeholder="Last Name" value="<?= old('last_name') ?>" required>
                        <label for="floatingLastNameInput">Last Name</label>
                    </div>

                    <div class="form-floating mb-4">
                        <input type="date" class="form-control" id="floatingBirthdateInput" name="birthdate" value="<?= old('birthdate') ?>" required>
                        <label for="floatingBirthdateInput">Birthdate</label>
                    </div>

                    <h6 class="mb-3">Government IDs</h6>

                    <div class="form-floating mb-2">
                        <input type="text" class="form-control" id="floatingSssInput" name="sss" inputmode="text" placeholder="SSS" value="<?= old('sss') ?>" required>
                        <label for="floatingSssInput">SSS</label>
                    </div>

                    <div class="form-floating mb-2">
                        <input type="text" class="form-control" id="floatingTinInput" name="tin" inputmode="text" placeholder="TIN" value="<?= old('tin') ?>" required>
                        <label for="floatingTinInput">TIN</label>
                    </div>

                    <div class="form-floating mb-2">
                        <input type="text" class="form-control" id="floatingPagibigInput" name="pagibig" inputmode="text" placeholder="Pag-IBIG" value="<?= old('pagibig') ?>" required>
                        <label for="floatingPagibigInput">Pag-IBIG</label>
                    </div>

                    <div class="form-floating mb-4">
                        <input type="text" class="form-control" id="floatingPhilhealthInput" name="philhealth" inputmode="text" placeholder="PhilHealth" value="<?= old('philhealth') ?>" required>
                        <label for="floatingPhilhealthInput">PhilHealth</label>
                    </div>

                    <h6 class="mb-3">Position Details</h6>

                    <div class="form-floating mb-2">
                        <input type="text" class="form-control" id="floatingDepartmentInput" name="department" inputmode="text" placeholder="Department" value="<?= old('department') ?>" required>
                        <label for="floatingDepartmentInput">Department</label>
                    </div>

                    <div class="form-floating mb-2">
                        <input type="text" class="form-control" id="floatingPositionInput" name="position" inputmode="text" placeholder="Position" value="<?= old('position') ?>" required>
                        <label for="floatingPositionInput">Position</label>
                    </div>

                    <div class="mb-4">
                        <label for="employmentType">Employment Type</label>
                        <select id="employmentType" name="employment_type" class="form-control" required>
                            <option value="Regular" <?= old('employment_type') === 'Regular' ? 'selected' : '' ?>>Regular</option>
                            <option value="Probationary" <?= old('employment_type') === 'Probationary' ? 'selected' : '' ?>>Probationary</option>
                            <option value="Contractual" <?= old('employment_type') === 'Contractual' ? 'selected' : '' ?>>Contractual</option>
                            <option value="Part-time" <?= old('employment_type') === 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                        </select>
                    </div>

                    <!-- Password -->
                    <div class="form-floating mb-2">
                        <input type="password" class="form-control" id="floatingPasswordInput" name="password" inputmode="text" autocomplete="new-password" placeholder="<?= lang('Auth.password') ?>" required>
                        <label for="floatingPasswordInput"><?= lang('Auth.password') ?></label>
                    </div>

                    <!-- Password (Again) -->
                    <div class="form-floating mb-5">
                        <input type="password" class="form-control" id="floatingPasswordConfirmInput" name="password_confirm" inputmode="text" autocomplete="new-password" placeholder="<?= lang('Auth.passwordConfirm') ?>" required>
                        <label for="floatingPasswordConfirmInput"><?= lang('Auth.passwordConfirm') ?></label>
                    </div>

                    <div class="d-grid col-12 col-md-8 mx-auto m-3">
                        <button type="submit" class="btn btn-primary btn-block"><?= lang('Auth.register') ?></button>
                    </div>

                    <p class="text-center"><?= lang('Auth.haveAccount') ?> <a href="<?= url_to('login') ?>"><?= lang('Auth.login') ?></a></p>

                </form>
            </div>
        </div>
    </div>

<?= $this->endSection() ?>
