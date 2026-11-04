<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Institutional Access Request — NODIVA Operations</title>
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
                                Central Operations Network Console
                            </span>
                        </td>
                        <td align="right">
                            <span style="background-color: #94611A; color: #ffffff; font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 4px 8px; border-radius: 2px;">
                                Pending Review
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
                    New Institutional Access Request Submitted
                </h1>
                <p style="font-size: 13px; line-height: 1.6; color: #61727D; margin-top: 0; margin-bottom: 20px;">
                    A new healthcare institution has completed the institutional onboarding form. Access has been provisioned under <strong>PENDING REVIEW</strong> status and requires verification by platform operations before live diagnostic referral routing is enabled.
                </p>

                <!-- Organization Dossier -->
                <table width="100%" cellpadding="8" cellspacing="0" style="background-color: #F5F3ED; border: 1px solid #D5DEDF; font-size: 12px; margin-bottom: 20px;">
                    <tr>
                        <td width="35%" style="color: #61727D; font-weight: 600; border-bottom: 1px solid #D5DEDF;">Institution Legal Name:</td>
                        <td style="color: #102A36; font-weight: 700; border-bottom: 1px solid #D5DEDF;">{{ $organization->name }}</td>
                    </tr>
                    <tr>
                        <td style="color: #61727D; font-weight: 600; border-bottom: 1px solid #D5DEDF;">Account Category:</td>
                        <td style="color: #167C78; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid #D5DEDF;">{{ $organization->type->value }}</td>
                    </tr>
                    <tr>
                        <td style="color: #61727D; font-weight: 600; border-bottom: 1px solid #D5DEDF;">Licence / Registration No:</td>
                        <td style="font-family: monospace; font-weight: 700; border-bottom: 1px solid #D5DEDF;">{{ $organization->registration_number ?? 'Not specified' }}</td>
                    </tr>
                    <tr>
                        <td style="color: #61727D; font-weight: 600; border-bottom: 1px solid #D5DEDF;">City & Region:</td>
                        <td style="color: #102A36; border-bottom: 1px solid #D5DEDF;">{{ $organization->city }}{{ $organization->region ? ', ' . $organization->region->name : '' }} (Ghana)</td>
                    </tr>
                    <tr>
                        <td style="color: #61727D; font-weight: 600; border-bottom: 1px solid #D5DEDF;">Institutional Email:</td>
                        <td style="color: #102A36; border-bottom: 1px solid #D5DEDF;">{{ $organization->email }}</td>
                    </tr>
                    <tr>
                        <td style="color: #61727D; font-weight: 600;">Telephone:</td>
                        <td style="color: #102A36;">{{ $organization->phone }}</td>
                    </tr>
                </table>

                <!-- Designated Administrator -->
                <h2 style="font-size: 13px; font-weight: 700; color: #102A36; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 0; margin-bottom: 8px;">
                    Designated Clinical / Operations Lead
                </h2>
                <table width="100%" cellpadding="8" cellspacing="0" style="background-color: #ffffff; border: 1px solid #D5DEDF; font-size: 12px; margin-bottom: 24px;">
                    <tr>
                        <td width="35%" style="color: #61727D; font-weight: 600; border-bottom: 1px solid #D5DEDF;">Full Name:</td>
                        <td style="color: #102A36; font-weight: 700; border-bottom: 1px solid #D5DEDF;">{{ $applicant->name }}</td>
                    </tr>
                    <tr>
                        <td style="color: #61727D; font-weight: 600; border-bottom: 1px solid #D5DEDF;">Official Role / Title:</td>
                        <td style="color: #102A36; border-bottom: 1px solid #D5DEDF;">{{ $applicant->job_title }}</td>
                    </tr>
                    <tr>
                        <td style="color: #61727D; font-weight: 600;">Authorized Sign-in Email:</td>
                        <td style="font-family: monospace; font-weight: 700; color: #167C78;">{{ $applicant->email }}</td>
                    </tr>
                </table>

                <!-- Call to action button -->
                <div style="text-align: center; margin: 28px 0 16px 0;">
                    <a href="{{ $reviewUrl }}" style="background-color: #167C78; color: #ffffff; padding: 12px 24px; text-decoration: none; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; border-radius: 2px;">
                        Inspect & Verify in Operations Console &rarr;
                    </a>
                </div>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #F5F3ED; padding: 16px 24px; border-top: 1px solid #D5DEDF; font-size: 11px; color: #61727D; text-align: center; line-height: 1.5;">
                Ghana Data Protection Act 2012 (Act 843) Audit Notice • This is an automated network notification from NODIVA Operations System.
            </td>
        </tr>
    </table>
</body>
</html>
