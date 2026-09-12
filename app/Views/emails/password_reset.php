<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Your AIS Password</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6;">

    <h2>Reset Your AIS Password</h2>

    <p>
        We received a request to reset the password for your AIS account.
    </p>

    <p>
        Click the button below to create a new password:
    </p>

    <p>
        <a
            href="<?= esc($resetUrl) ?>"
            style="
                display:inline-block;
                padding:12px 20px;
                background:#0d6efd;
                color:#ffffff;
                text-decoration:none;
                border-radius:5px;
            ">
            Reset Password
        </a>
    </p>

    <p>
        This link will expire in
        <?= esc((string) $expiresInMinutes) ?> minutes.
    </p>

    <p>
        If you did not request a password reset, you can safely ignore this
        email.
    </p>

    <hr>

    <p style="font-size:12px;color:#666;">
        AIS Human Resources Information System
    </p>

</body>

</html>