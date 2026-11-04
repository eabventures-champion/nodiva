<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation to Join NODIVA Operations Network</title>
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
                                {{ $invitation->organization->name ?? 'Institutional Diagnostic Network' }}
                            </span>
                        </td>
                        <td align="right">
                            <span style="background-color: #167C78; color: #ffffff; font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 4px 8px; border-radius: 2px;">
                                Team Access Invite
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Body -->
        <tr>
            <td style="padding: 24px;">
                <h1 style="font-size: 18px; font-weight: 700; color: #102A36; margin-top: 0; margin-bottom: 8px;">
                    You have been invited to join {{ $invitation->organization->name ?? 'NODIVA Network' }}
                </h1>
                <p style="font-size: 13px; line-height: 1.6; color: #61727D; margin-top: 0; margin-bottom: 20px;">
                    Hello{{ $invitation->name ? ' ' . $invitation->name : '' }},<br>
                    <strong>{{ $inviter->name ?? 'The Institutional Administrator' }}</strong> ({{ $inviter->job_title ?? 'Administrator' }}) has invited you to join <strong>{{ $invitation->organization->name ?? 'NODIVA' }}</strong> with delegated institutional permissions.
                </p>

                <!-- Invitation Dossier -->
                <table width="100%" cellpadding="8" cellspacing="0" style="background-color: #F5F3ED; border: 1px solid #D5DEDF; font-size: 12px; margin-bottom: 20px;">
                    <tr>
                        <td width="35%" style="color: #61727D; font-weight: 600; border-bottom: 1px solid #D5DEDF;">Authorized Email:</td>
                        <td style="color: #102A36; font-weight: 700; font-family: monospace; border-bottom: 1px solid #D5DEDF;">{{ $invitation->email }}</td>
                    </tr>
                    <tr>
                        <td style="color: #61727D; font-weight: 600; border-bottom: 1px solid #D5DEDF;">Assigned Role:</td>
                        <td style="color: #167C78; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid #D5DEDF;">{{ $role->name ?? 'Operations Officer' }}</td>
                    </tr>
                    @if ($invitation->job_title)
                    <tr>
                        <td style="color: #61727D; font-weight: 600; border-bottom: 1px solid #D5DEDF;">Designation:</td>
                        <td style="color: #102A36; border-bottom: 1px solid #D5DEDF;">{{ $invitation->job_title }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="color: #61727D; font-weight: 600; border-bottom: 1px solid #D5DEDF;">Invited By:</td>
                        <td style="color: #102A36; border-bottom: 1px solid #D5DEDF;">{{ $inviter->name }} ({{ $inviter->job_title ?? 'Operations' }})</td>
                    </tr>
                    <tr>
                        <td style="color: #61727D; font-weight: 600;">Invitation Expiry:</td>
                        <td style="color: #94611A; font-weight: 600;">{{ $invitation->expires_at->format('M d, Y H:i') }} (7 days)</td>
                    </tr>
                </table>

                <!-- Permissions Info -->
                <div style="background-color: #E8F0EF; border-left: 3px solid #167C78; padding: 12px 16px; margin-bottom: 24px; font-size: 12px; line-height: 1.5; color: #172A35;">
                    <strong>Sidebar & Module Access Granted:</strong><br>
                    Your account has been provisioned with specific access permissions to view and operate delegated menus within the NODIVA Network Console.
                </div>

                <!-- Call to action button -->
                <div style="text-align: center; margin: 28px 0 20px 0;">
                    <a href="{{ $acceptUrl }}" style="background-color: #167C78; color: #ffffff; padding: 12px 28px; text-decoration: none; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; border-radius: 2px;">
                        Accept Invitation & Set Up Password &rarr;
                    </a>
                </div>

                <p style="font-size: 11px; color: #61727D; text-align: center; margin-bottom: 0;">
                    If button does not work, copy and paste this link in your browser:<br>
                    <a href="{{ $acceptUrl }}" style="color: #167C78; word-break: break-all;">{{ $acceptUrl }}</a>
                </p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #F5F3ED; padding: 16px 24px; border-top: 1px solid #D5DEDF; font-size: 11px; color: #61727D; text-align: center; line-height: 1.5;">
                Ghana Data Protection Act 2012 (Act 843) • Confidential Healthcare Operational Access Notice.
            </td>
        </tr>
    </table>
</body>
</html>
