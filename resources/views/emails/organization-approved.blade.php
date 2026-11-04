<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Institutional Access Activated — NODIVA</title>
</head>
<body style="margin: 0; padding: 24px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #F5F3ED; color: #172A35;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #D5DEDF;">
        <tr>
            <td style="background-color: #102A36; padding: 20px 24px; border-bottom: 3px solid #167C78;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <span style="font-size: 18px; font-weight: 700; color: #ffffff; letter-spacing: 1px;">NODIVA</span>
                            <span style="display: block; font-size: 11px; color: #D5DEDF; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px;">
                                Institutional Diagnostic Network
                            </span>
                        </td>
                        <td align="right">
                            <span style="background-color: #247451; color: #ffffff; font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 4px 8px; border-radius: 2px;">
                                Active & Verified
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="padding: 24px;">
                <h1 style="font-size: 18px; font-weight: 700; color: #102A36; margin-top: 0; margin-bottom: 8px;">
                    Institutional Workspace Confirmed & Activated
                </h1>
                <p style="font-size: 13px; line-height: 1.6; color: #61727D; margin-top: 0; margin-bottom: 20px;">
                    Dear {{ $recipient->name }},<br><br>
                    The NODIVA Platform Operations Console has reviewed and verified the credentials for <strong>{{ $organization->name }}</strong>. Your institutional workspace has been fully activated.
                </p>

                <div style="background-color: #E8F0EF; border: 1px solid #167C78; padding: 16px; margin-bottom: 24px; font-size: 12px; line-height: 1.6; color: #102A36;">
                    <strong>Activated Capabilities:</strong>
                    <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                        <li>Direct access to the <strong>{{ ucfirst($organization->type->value) }} Portal</strong></li>
                        <li>Diagnostic order placement, catalog querying, and specimen handover tracking</li>
                        <li>Automated laboratory capacity matching and encrypted clinical result retrieval</li>
                    </ul>
                </div>

                <div style="text-align: center; margin: 28px 0 16px 0;">
                    <a href="{{ $dashboardUrl }}" style="background-color: #167C78; color: #ffffff; padding: 12px 28px; text-decoration: none; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; border-radius: 2px;">
                        Enter Your Institutional Workspace &rarr;
                    </a>
                </div>
            </td>
        </tr>

        <tr>
            <td style="background-color: #F5F3ED; padding: 16px 24px; border-top: 1px solid #D5DEDF; font-size: 11px; color: #61727D; text-align: center; line-height: 1.5;">
                Ghana Data Protection Act 2012 (Act 843) • NODIVA Institutional Diagnostic Network
            </td>
        </tr>
    </table>
</body>
</html>
