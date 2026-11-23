<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= esc($title) ?></title>
</head>

<body style="margin:0; padding:0; background:#FAF9F6; font-family: Arial, Helvetica, sans-serif; line-height: 1.6;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FAF9F6; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:480px; background:#ffffff; border-radius:16px; overflow:hidden;" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:40px 40px 32px;">
                            <h2 style="margin:0 0 16px; color:#111827; font-size:22px; font-weight:700;">
                                <?= esc($title) ?>
                            </h2>

                            <p style="margin:0 0 8px; color:#4b5563; font-size:15px;">
                                Hi <?= esc($name) ?>,
                            </p>
                            <p style="margin:0 0 24px; color:#4b5563; font-size:15px;">
                                <?= esc($body) ?>
                            </p>

                            <p style="margin:0 0 28px;">
                                <a href="<?= esc($link) ?>"
                                    style="display:inline-block; padding:13px 28px; background:#111111; color:#ffffff; font-weight:600; font-size:15px; text-decoration:none; border-radius:9999px;">
                                    Open the portal
                                </a>
                            </p>

                            <p style="margin:0; color:#6b7280; font-size:13px;">
                                Sent at <?= esc($sentAt) ?>. If you have already done this, you can ignore this message.
                                You can change these reminders in your profile.
                            </p>

                            <hr style="margin:28px 0; border:none; border-top:1px solid #e5e7eb;">

                            <p style="margin:0; color:#9ca3af; font-size:12px;">
                                Onesys Prime Solutions Co. | Ecovis International | BGE
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
