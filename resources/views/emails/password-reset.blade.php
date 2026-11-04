<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password — NODIVA Network</title>
</head>
<body style="margin: 0; padding: 24px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #F5F3ED; color: #172A35;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #D5DEDF;">
        <!-- Header -->
        <tr>
            <td style="background-color: #102A36; padding: 20px 24px; border-bottom: 3px solid #167C78;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <span style="font-size: 18px; font-weight: 700; color: #ffffff; letter-spacing: 1px;">NODIVA</span>
                            <span style="display: block; font-size: 11px; color: #D5DEDF; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px;">
                                Institutional Diagnostic Infrastructure
                            </span>
                        </td>
                        <td align="right">
                            <span style="background-color: #167C78; color: #ffffff; font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 4px 8px; border-radius: 2px;">
                                Password Reset
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Body -->
        <tr>
            <td style="padding: 28px 24px;">
                <h1 style="font-size: 18px; font-weight: 700; color: #102A36; margin-top: 0; margin-bottom: 8px;">
                    Password Reset Verification
                </h1>
                <p style="font-size: 13px; line-height: 1.6; color: #61727D; margin-top: 0; margin-bottom: 20px;">
                    Hello <strong>{{ $user->name }}</strong>,<br>
                    We received a request to reset the password for your NODIVA institutional account (<strong>{{ $user->email }}</strong>).
                </p>

                <p style="font-size: 13px; line-height: 1.6; color: #172A35; margin-bottom: 24px;">
                    Click the button below to choose a new password. This single-use security link is valid for <strong>60 minutes</strong>.
                </p>

                <!-- CTA Button -->
                <table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                    <tr>
                        <td align="center" style="background-color: #167C78; border-radius: 2px;">
                            <a href="{{ $resetUrl }}" target="_blank" 
                               style="display: inline-block; padding: 12px 28px; color: #ffffff; font-size: 12px; font-weight: 700; text-decoration: none; text-transform: uppercase; letter-spacing: 1px;">
                                Reset Your Password &rarr;
                            </a>
                        </td>
                    </tr>
                </table>

                <div style="background-color: #F5F3ED; border: 1px solid #D5DEDF; padding: 14px 16px; font-size: 11px; line-height: 1.6; color: #61727D; margin-bottom: 24px;">
                    <strong style="color: #102A36;">Security Notice:</strong>
                    If you did not request a password reset, no further action is required. Your account remains completely secure, and your existing credentials are unchanged.
                </div>

                <p style="font-size: 11px; color: #61727D; margin-bottom: 4px;">
                    If the button above does not work, copy and paste this link into your browser:
                </p>
                <p style="font-size: 11px; font-family: monospace; word-break: break-all; color: #167C78; margin-top: 0;">
                    <a href="{{ $resetUrl }}" style="color: #167C78;">{{ $resetUrl }}</a>
                </p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #F5F3ED; padding: 16px 24px; border-top: 1px solid #D5DEDF; font-size: 11px; color: #61727D; line-height: 1.5;">
                <p style="margin: 0 0 4px 0;">
                    This security notification was sent automatically by NODIVA Technologies Ltd.
                </p>
                <p style="margin: 0; font-size: 10px;">
                    Compliant with Data Protection Act, 2012 (Act 843) standards &bull; Republic of Ghana
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
