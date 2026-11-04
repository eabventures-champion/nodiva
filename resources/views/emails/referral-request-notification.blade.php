<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Diagnostic Referral Alert — NODIVA</title>
</head>
<body style="margin: 0; padding: 24px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #F5F3ED; color: #172A35;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 620px; margin: 0 auto; background-color: #ffffff; border: 1px solid #D5DEDF; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <!-- Header -->
        <tr>
            <td style="background-color: #102A36; padding: 24px; border-bottom: 3px solid #167C78;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <span style="font-size: 20px; font-weight: 700; color: #ffffff; letter-spacing: 1px;">NODIVA</span>
                            <span style="display: block; font-size: 11px; color: #D5DEDF; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px;">
                                Diagnostic Coordination Network
                            </span>
                        </td>
                        <td align="right">
                            @if ($isBroadcast)
                                <span style="background-color: #1E40AF; color: #ffffff; font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 5px 10px; border-radius: 2px; letter-spacing: 0.5px;">
                                    ● Open Broadcast
                                </span>
                            @else
                                <span style="background-color: #167C78; color: #ffffff; font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 5px 10px; border-radius: 2px; letter-spacing: 0.5px;">
                                    Direct Assignment
                                </span>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Body -->
        <tr>
            <td style="padding: 28px 24px;">
                <div style="font-size: 12px; font-weight: 700; color: #167C78; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                    Inbound Referral Notification • {{ $laboratory->name }}
                </div>

                <h1 style="font-size: 19px; font-weight: 700; color: #102A36; margin-top: 0; margin-bottom: 8px;">
                    @if ($isBroadcast)
                        New Open Referral Request Available to Claim
                    @else
                        New Diagnostic Referral Assigned to Your Facility
                    @endif
                </h1>

                <p style="font-size: 13px; line-height: 1.6; color: #61727D; margin-top: 0; margin-bottom: 22px;">
                    @if ($isBroadcast)
                        <strong>{{ $order->requestingOrganization?->name ?? 'A referring hospital' }}</strong> has broadcast an open diagnostic referral order across verified network laboratories. The first accredited laboratory to accept this order in their intake bay will claim and fulfill it.
                    @else
                        <strong>{{ $order->requestingOrganization?->name ?? 'A referring hospital' }}</strong> has directly routed a diagnostic referral order to your accredited laboratory for clinical processing and result release.
                    @endif
                </p>

                <!-- Key Order Highlights Banner -->
                <table width="100%" cellpadding="12" cellspacing="0" style="background-color: #F8FAFC; border: 1px solid #CBD5E1; margin-bottom: 24px; border-left: 4px solid #167C78;">
                    <tr>
                        <td width="50%" style="vertical-align: top;">
                            <span style="display: block; font-size: 10px; font-weight: 700; color: #64748B; text-transform: uppercase;">Order Reference</span>
                            <span style="font-family: monospace; font-size: 15px; font-weight: 700; color: #0F172A;">{{ $order->reference }}</span>
                        </td>
                        <td width="50%" style="vertical-align: top;">
                            <span style="display: block; font-size: 10px; font-weight: 700; color: #64748B; text-transform: uppercase;">Priority Tier</span>
                            @if (strtoupper($order->priority) === 'URGENT')
                                <span style="display: inline-block; background-color: #FEE2E2; color: #991B1B; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 2px 8px; border-radius: 2px; margin-top: 2px;">
                                    STAT / Urgent Priority
                                </span>
                            @else
                                <span style="display: inline-block; background-color: #E2E8F0; color: #334155; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 2px 8px; border-radius: 2px; margin-top: 2px;">
                                    Routine Turnaround
                                </span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding-top: 8px;">
                            <span style="display: block; font-size: 10px; font-weight: 700; color: #64748B; text-transform: uppercase;">Referring Institution</span>
                            <span style="font-size: 13px; font-weight: 600; color: #0F172A;">{{ $order->requestingOrganization?->name }}</span>
                            <span style="display: block; font-size: 11px; color: #64748B;">{{ $order->requestingOrganization?->city ?? 'Accra' }}, Ghana</span>
                        </td>
                        <td style="vertical-align: top; padding-top: 8px;">
                            <span style="display: block; font-size: 10px; font-weight: 700; color: #64748B; text-transform: uppercase;">Specimen Logistics</span>
                            <span style="font-size: 13px; font-weight: 600; color: #0F172A; text-transform: capitalize;">{{ $order->collection_method === 'courier' ? 'Hospital Phlebotomy (Cold-Chain Courier)' : 'Patient Walk-In' }}</span>
                        </td>
                    </tr>
                </table>

                <!-- Requested Investigations -->
                <h2 style="font-size: 12px; font-weight: 700; color: #102A36; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 0; margin-bottom: 10px;">
                    Requested Diagnostic Investigation(s)
                </h2>
                <table width="100%" cellpadding="10" cellspacing="0" style="background-color: #ffffff; border: 1px solid #D5DEDF; font-size: 12px; margin-bottom: 22px;">
                    <thead style="background-color: #F5F3ED; border-bottom: 1px solid #D5DEDF; color: #61727D; text-transform: uppercase; font-size: 10px;">
                        <tr>
                            <th align="left" style="padding: 8px 10px;">Test Name & Code</th>
                            <th align="left" style="padding: 8px 10px;">Specialty</th>
                            <th align="left" style="padding: 8px 10px;">Specimen Required</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr style="border-bottom: 1px solid #E2E8F0;">
                                <td style="padding: 10px; color: #102A36; font-weight: 700;">
                                    {{ $item->test?->name }}
                                    <div style="font-family: monospace; font-size: 10px; color: #61727D; font-weight: normal;">{{ $item->test?->code }}</div>
                                </td>
                                <td style="padding: 10px; color: #61727D;">
                                    {{ $item->test?->specialty?->name ?? 'General Diagnostic' }}
                                </td>
                                <td style="padding: 10px; color: #167C78; font-weight: 600;">
                                    {{ $item->test?->specimenType?->container ?? 'Standard Tube' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($order->clinical_indication)
                    <div style="background-color: #F1F5F9; border-left: 3px solid #64748B; padding: 10px 14px; font-size: 12px; color: #334155; margin-bottom: 24px;">
                        <strong>Clinical Indication:</strong> <em>{{ $order->clinical_indication }}</em>
                    </div>
                @endif

                <!-- Call to Action -->
                <div style="text-align: center; margin: 30px 0 20px;">
                    <a href="{{ $actionUrl }}" style="display: inline-block; background-color: #167C78; color: #ffffff; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 32px; text-decoration: none; border-radius: 2px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        @if ($isBroadcast)
                            Review & Claim Referral in Intake Bay &rarr;
                        @else
                            Review & Accept Referral &rarr;
                        @endif
                    </a>
                    <p style="font-size: 11px; color: #61727D; margin-top: 10px; margin-bottom: 0;">
                        @if ($isBroadcast)
                            First-to-claim policy: When accepted, this order will be exclusively locked to your laboratory.
                        @else
                            Specimen dispatch tracking and accessioning barcodes will activate upon acceptance.
                        @endif
                    </p>
                </div>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #F5F3ED; padding: 20px 24px; border-top: 1px solid #D5DEDF; font-size: 11px; line-height: 1.6; color: #61727D;">
                <p style="margin: 0 0 8px;">
                    <strong>NODIVA Diagnostic Coordination Network</strong> • Launch Region: Ghana (Accra & Central Operational Zone).
                </p>
                <p style="margin: 0; font-size: 10px; color: #8F9FA3;">
                    This clinical notification contains confidential medical referral metadata governed by the Ghana Data Protection Act 2012 (Act 843) and ISO 15189 / HEFRA quality compliance standards. Zero identifiable patient PII is transmitted in unencrypted transit.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
