<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Your AIS Password</title>
</head>

<body style="margin:0; padding:0; background:#FAF9F6; font-family: Arial, Helvetica, sans-serif; line-height: 1.6;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FAF9F6; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:480px; background:#ffffff; border-radius:16px; overflow:hidden;" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:40px 40px 32px;">
                            <h2 style="margin:0 0 16px; color:#111827; font-size:22px; font-weight:700;">
                                Reset your AIS password
                            </h2>

                            <p style="margin:0 0 16px; color:#4b5563; font-size:15px;">
                                We received a request to reset the password for your AIS account.
                                Click the button below to create a new one.
                            </p>

                            <p style="margin:0 0 28px;">
                                <a
                                    href="<?= esc($resetUrl) ?>"
                                    style="
                                        display:inline-block;
                                        padding:13px 28px;
                                        background:#111111;
                                        color:#ffffff;
                                        font-weight:600;
                                        font-size:15px;
                                        text-decoration:none;
                                        border-radius:9999px;
                                    ">
                                    Reset Password
                                </a>
                            </p>

                            <p style="margin:0 0 8px; color:#6b7280; font-size:13px;">
                                This link will expire in <?= esc((string) $expiresInMinutes) ?> minutes.
                            </p>

                            <p style="margin:0; color:#6b7280; font-size:13px;">
                                If you did not request a password reset, you can safely ignore this email.
                            </p>

                            <hr style="margin:28px 0; border:none; border-top:1px solid #e5e7eb;">

                            <p style="margin:0; color:#9ca3af; font-size:12px;">
                                AIS Human Resources Information System
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>