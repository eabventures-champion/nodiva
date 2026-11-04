<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Institutional Access Request Received — NODIVA</title>
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
                                Institutional Diagnostic Network
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
                    Institutional Access Request Received
                </h1>
                <p style="font-size: 13px; line-height: 1.6; color: #61727D; margin-top: 0; margin-bottom: 20px;">
                    Dear {{ $applicant->name }},<br><br>
                    Thank you for applying to connect <strong>{{ $organization->name }}</strong> to the NODIVA Diagnostic Coordination Network. Your institutional onboarding application has been successfully submitted and is currently under review by our Platform Operations team.
                </p>

                <!-- Application Dossier -->
                <table width="100%" cellpadding="8" cellspacing="0" style="background-color: #F5F3ED; border: 1px solid #D5DEDF; font-size: 12px; margin-bottom: 20px;">
                    <tr>
                        <td width="38%" style="color: #61727D; font-weight: 600; border-bottom: 1px solid #D5DEDF;">Institution Legal Name:</td>
                        <td style="color: #102A36; font-weight: 700; border-bottom: 1px solid #D5DEDF;">{{ $organization->name }}</td>
                    </tr>
                    <tr>
                        <td style="color: #61727D; font-weight: 600; border-bottom: 1px solid #D5DEDF;">Account Category:</td>
                        <td style="color: #167C78; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid #D5DEDF;">{{ $organization->type->value }}</td>
                    </tr>
                    <tr>
                        <td style="color: #61727D; font-weight: 600; border-bottom: 1px solid #D5DEDF;">Application Reference:</td>
                        <td style="font-family: monospace; font-weight: 700; color: #102A36; border-bottom: 1px solid #D5DEDF;">{{ $organization->public_id ?? ('REF-' . $organization->id) }}</td>
                    </tr>
                    <tr>
                        <td style="color: #61727D; font-weight: 600; border-bottom: 1px solid #D5DEDF;">Operating City & Region:</td>
                        <td style="color: #102A36; border-bottom: 1px solid #D5DEDF;">{{ $organization->city }}{{ $organization->region ? ', ' . $organization->region->name : '' }} (Ghana)</td>
                    </tr>
                    <tr>
                        <td style="color: #61727D; font-weight: 600; border-bottom: 1px solid #D5DEDF;">Designated Lead:</td>
                        <td style="color: #102A36; font-weight: 700; border-bottom: 1px solid #D5DEDF;">{{ $applicant->name }} ({{ $applicant->job_title }})</td>
                    </tr>
                    <tr>
                        <td style="color: #61727D; font-weight: 600;">Authorized Sign-in Email:</td>
                        <td style="font-family: monospace; font-weight: 700; color: #167C78;">{{ $applicant->email }}</td>
                    </tr>
                </table>

                <!-- Next Steps Protocol Notice -->
                <div style="background-color: #E8F0EF; border-left: 4px solid #167C78; padding: 14px 16px; margin-bottom: 24px; font-size: 12px; line-height: 1.6; color: #102A36;">
                    <strong>Verification & Next Steps:</strong>
                    <p style="margin: 6px 0 0 0; color: #172A35;">
                        Under the Ghana Health Service (GHS) and Health Institutions & Facilities Act (HeFRA) compliance framework, our Central Operations Console verifies institutional licensing and scopes within 1-2 business days. Once verified, you will receive an official activation email with your secure portal sign-in link.
                    </p>
                </div>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #F5F3ED; padding: 16px 24px; border-top: 1px solid #D5DEDF; font-size: 11px; color: #61727D; text-align: center; line-height: 1.5;">
                Ghana Data Protection Act 2012 (Act 843) • NODIVA Institutional Diagnostic Network
            </td>
        </tr>
    </table>
</body>
</html>
