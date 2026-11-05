<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Organizations\Enums\OrganizationStatus;
use App\Domain\Organizations\Enums\OrganizationType;
use App\Domain\Referrals\Enums\OrderStatus;
use App\Models\Invoice;
use App\Models\LaboratoryQualification;
use App\Models\LaboratorySettlement;
use App\Models\Organization;
use App\Models\ReferralOrder;
use App\Models\ScreeningProgram;
use App\Models\User;

class DashboardNotificationService
{
    /**
     * Retrieve contextual dashboard notifications for the active organization workspace.
     *
     * @return array<int, array{
     *     id: string,
     *     type: string,
     *     badge: string,
     *     badge_class: string,
     *     title: string,
     *     message: string,
     *     time: string,
     *     url: string,
     *     icon: string,
     * }>
     */
    public function getNotifications(?Organization $organization, ?User $user): array
    {
        if (! $organization && $user) {
            $organization = $user->currentMembership?->organization ?? $user->activeMemberships()->first()?->organization;
        }

        if (! $organization) {
            return [];
        }

        $items = [];

        try {
            if ($organization->isPlatform()) {
                // 1. Pending Access Requests for Operations Console
                $pendingOrgs = Organization::where('status', OrganizationStatus::Pending)
                    ->where('type', '!=', OrganizationType::Platform)
                    ->latest()
                    ->take(5)
                    ->get();

                foreach ($pendingOrgs as $org) {
                    $items[] = [
                        'id' => 'org_pending_' . $org->id,
                        'type' => 'access_request',
                        'badge' => 'PENDING REVIEW',
                        'badge_class' => 'bg-amber-50 text-amber-800 border border-amber-200/80',
                        'title' => 'Access Request: ' . $org->name,
                        'message' => 'New ' . $org->type->label() . ' registered from ' . $org->city . '. Verification required.',
                        'time' => $org->created_at?->diffForHumans() ?? 'Recently',
                        'url' => route('ops.partners.index') . '?status=pending',
                        'icon' => 'user-check',
                    ];
                }

                // 2. Unverified / Expiring Laboratory Qualifications
                $qualifications = LaboratoryQualification::with('organization')
                    ->where('status', '!=', 'verified')
                    ->latest()
                    ->take(3)
                    ->get();

                foreach ($qualifications as $qual) {
                    $labName = $qual->organization?->name ?? 'Laboratory';
                    $items[] = [
                        'id' => 'qual_pending_' . $qual->id,
                        'type' => 'qualification',
                        'badge' => 'AUDIT REQUIRED',
                        'badge_class' => 'bg-blue-50 text-blue-800 border border-blue-200/80',
                        'title' => 'Licence Audit: ' . $labName,
                        'message' => $qual->typeLabel() . ' (' . ($qual->reference_number ?? 'N/A') . ') pending review.',
                        'time' => $qual->created_at?->diffForHumans() ?? 'Recently',
                        'url' => route('ops.qualifications.index'),
                        'icon' => 'shield-alert',
                    ];
                }

                // 3. Recent referral orders across network
                $orders = ReferralOrder::with(['requestingOrganization', 'laboratory'])
                    ->latest()
                    ->take(4)
                    ->get();

                foreach ($orders as $order) {
                    $statusVal = $order->status->value ?? (string) $order->status;
                    $statusLabel = strtoupper(str_replace('_', ' ', $statusVal));

                    $badgeClass = match (true) {
                        $statusVal === 'completed' => 'bg-emerald-50 text-emerald-800 border border-emerald-200/80',
                        str_contains($statusVal, 'transit') || str_contains($statusVal, 'collection') || str_contains($statusVal, 'received') => 'bg-blue-50 text-blue-800 border border-blue-200/80',
                        str_contains($statusVal, 'awaiting') || str_contains($statusVal, 'matching') => 'bg-amber-50 text-amber-800 border border-amber-200/80',
                        default => 'bg-[#167C78]/10 text-[#167C78] border border-[#167C78]/25',
                    };

                    $fromFacility = $order->requestingOrganization?->name ?? 'Hospital';
                    $toFacility = $order->laboratory?->name ?? 'Open Network Broadcast';

                    $items[] = [
                        'id' => 'ops_order_' . $order->id,
                        'type' => 'order',
                        'badge' => $statusLabel,
                        'badge_class' => $badgeClass,
                        'title' => 'Referral ' . $order->reference,
                        'order_reference' => $order->reference,
                        'from_facility' => $fromFacility,
                        'to_facility' => $toFacility,
                        'message' => $fromFacility . ' → ' . $toFacility,
                        'time' => $order->created_at?->diffForHumans() ?? 'Recently',
                        'url' => route('ops.orders.show', $order),
                        'icon' => 'order',
                    ];
                }

                // 4. Partner Communications & Replies
                $commThreads = \App\Models\CommunicationThread::with(['messages.user', 'messages.organization'])
                    ->where('status', 'open')
                    ->orderByDesc('last_message_at')
                    ->take(5)
                    ->get();

                foreach ($commThreads as $thread) {
                    $lastMsg = $thread->messages->last();
                    if ($lastMsg && (! $lastMsg->organization || ! $lastMsg->organization->isPlatform())) {
                        $orgName = $lastMsg->organization?->name ?? 'Partner Institution';
                        $items[] = [
                            'id' => 'comm_' . $thread->id . '_' . $lastMsg->id,
                            'type' => 'communication',
                            'badge' => $thread->priority === 'urgent' ? 'URGENT MSG' : 'PARTNER REPLY',
                            'badge_class' => $thread->priorityBadgeClass(),
                            'title' => 'Message: ' . $thread->subject,
                            'message' => $orgName . ': ' . \Illuminate\Support\Str::limit($lastMsg->message, 70),
                            'time' => $lastMsg->created_at?->diffForHumans() ?? 'Recently',
                            'url' => route('ops.communications.show', $thread),
                            'icon' => 'chat',
                        ];
                    }
                }
            } elseif ($organization->isHospital()) {
                // 1. Completed diagnostic results ready for inspection & download
                $completed = ReferralOrder::with('laboratory')
                    ->where('requesting_organization_id', $organization->id)
                    ->where('status', 'completed')
                    ->latest('updated_at')
                    ->take(4)
                    ->get();

                foreach ($completed as $order) {
                    $items[] = [
                        'id' => 'hosp_result_' . $order->id,
                        'type' => 'result',
                        'badge' => 'RESULT READY',
                        'badge_class' => 'bg-emerald-50 text-emerald-800 border border-emerald-200/80',
                        'title' => 'Result Released: ' . $order->reference,
                        'order_reference' => $order->reference,
                        'from_facility' => $organization->name,
                        'to_facility' => $order->laboratory?->name ?? 'Diagnostic Lab',
                        'message' => 'Pathologist verification complete. Encrypted clinical report is ready.',
                        'time' => $order->result_released_at?->diffForHumans() ?? $order->updated_at?->diffForHumans() ?? 'Recently',
                        'url' => route('hospital.orders.show', $order->uuid),
                        'icon' => 'file-check',
                    ];
                }

                // 2. Active specimens in transit or processing
                $inTransit = ReferralOrder::with('laboratory')
                    ->where('requesting_organization_id', $organization->id)
                    ->whereIn('status', ['matched', 'specimen_in_transit', 'received_at_lab'])
                    ->latest('updated_at')
                    ->take(3)
                    ->get();

                foreach ($inTransit as $order) {
                    $statusText = $order->status === 'received_at_lab' ? 'AT LABORATORY' : 'IN TRANSIT';
                    $items[] = [
                        'id' => 'hosp_transit_' . $order->id,
                        'type' => 'transit',
                        'badge' => $statusText,
                        'badge_class' => 'bg-blue-50 text-blue-800 border border-blue-200/80',
                        'title' => 'Specimen Track: ' . $order->reference,
                        'order_reference' => $order->reference,
                        'from_facility' => $organization->name,
                        'to_facility' => $order->laboratory?->name ?? 'Processing Lab',
                        'message' => 'Chain of custody monitored en route to diagnostic facility.',
                        'time' => $order->updated_at?->diffForHumans() ?? 'Recently',
                        'url' => route('hospital.specimens.index'),
                        'icon' => 'truck',
                    ];
                }

                // 3. Invoices
                $invoices = Invoice::where('organization_id', $organization->id)
                    ->latest()
                    ->take(2)
                    ->get();

                foreach ($invoices as $inv) {
                    $items[] = [
                        'id' => 'hosp_inv_' . $inv->id,
                        'type' => 'invoice',
                        'badge' => 'STATEMENT',
                        'badge_class' => 'bg-blue-50 text-blue-800 border border-blue-200/80',
                        'title' => 'Monthly Statement: ' . $inv->number,
                        'message' => 'Total ' . $inv->currency . ' ' . number_format((float) $inv->total, 2) . ' due on ' . ($inv->due_on?->format('M d, Y') ?? 'Due date pending'),
                        'time' => $inv->created_at?->diffForHumans() ?? 'Recently',
                        'url' => route('hospital.invoices.index'),
                        'icon' => 'receipt',
                    ];
                }
            } elseif ($organization->isLaboratory()) {
                // 0. Alert for Missing Laboratory Accreditations / Licensing
                if ($organization->hasMissingQualifications()) {
                    $items[] = [
                        'id' => 'lab_qual_missing_' . $organization->id,
                        'type' => 'qualification',
                        'badge' => 'ACTION REQUIRED',
                        'badge_class' => 'bg-rose-50 text-rose-800 border border-rose-200/80',
                        'title' => 'Mandatory Accreditations Missing',
                        'message' => 'Regulatory licensing documents have not been uploaded. Upload immediately to unlock referral fulfillment.',
                        'time' => 'Action required',
                        'url' => route('lab.qualifications.index'),
                        'icon' => 'shield-alert',
                    ];
                }

                // 1. Pending offers awaiting lab acceptance (Direct assignment OR Open broadcast)
                $pendingOffers = ReferralOrder::where(function ($q) use ($organization) {
                        $q->where('laboratory_id', $organization->id)
                          ->where('status', OrderStatus::AwaitingLabAcceptance);
                    })
                    ->orWhere(function ($q) use ($organization) {
                        $q->whereNull('laboratory_id')
                          ->where('status', OrderStatus::Matching)
                          ->whereHas('assignments', function ($a) use ($organization) {
                              $a->where('laboratory_id', $organization->id)->where('status', 'offered');
                          });
                    })
                    ->with(['requestingOrganization', 'items.test'])
                    ->latest()
                    ->take(5)
                    ->get();

                foreach ($pendingOffers as $order) {
                    $isBroadcast = is_null($order->laboratory_id);
                    $items[] = [
                        'id' => 'lab_offer_' . $order->id,
                        'type' => 'order',
                        'badge' => $isBroadcast ? 'OPEN BROADCAST' : 'AWAITING ACCEPTANCE',
                        'badge_class' => $isBroadcast ? 'bg-blue-50 text-blue-800 border border-blue-200/80' : 'bg-amber-50 text-amber-800 border border-amber-200/80',
                        'title' => ($isBroadcast ? 'Open Referral: ' : 'New Referral: ') . $order->reference,
                        'order_reference' => $order->reference,
                        'from_facility' => $order->requestingOrganization?->name ?? 'Hospital',
                        'to_facility' => $organization->name,
                        'message' => ($order->requestingOrganization?->name ?? 'Hospital') . ' requested ' . ($order->items->pluck('test.name')->join(', ') ?: 'diagnostic test') . ($isBroadcast ? ' (First to claim).' : '.'),
                        'time' => $order->created_at?->diffForHumans() ?? 'Recently',
                        'url' => route('lab.reception.index'),
                        'icon' => 'order',
                    ];
                }

                // 2. Inbound referrals in transit
                $inbound = ReferralOrder::with('requestingOrganization')
                    ->where('laboratory_id', $organization->id)
                    ->whereIn('status', [OrderStatus::CollectionScheduled, OrderStatus::SpecimenCollected, OrderStatus::InTransit])
                    ->latest()
                    ->take(4)
                    ->get();

                foreach ($inbound as $order) {
                    $items[] = [
                        'id' => 'lab_inbound_' . $order->id,
                        'type' => 'order',
                        'badge' => 'INBOUND SPECIMEN',
                        'badge_class' => 'bg-blue-50 text-blue-800 border border-blue-200/80',
                        'title' => 'Inbound Referral: ' . $order->reference,
                        'order_reference' => $order->reference,
                        'from_facility' => $order->requestingOrganization?->name ?? 'Hospital',
                        'to_facility' => $organization->name,
                        'message' => 'Specimen dispatched from ' . ($order->requestingOrganization?->name ?? 'Hospital') . '. Awaiting reception.',
                        'time' => $order->created_at?->diffForHumans() ?? 'Recently',
                        'url' => route('lab.reception.index'),
                        'icon' => 'truck',
                    ];
                }

                // 3. Pending analytical entry or signoff
                $pendingResults = ReferralOrder::where('laboratory_id', $organization->id)
                    ->whereIn('status', [OrderStatus::SpecimenReceived, OrderStatus::Processing, OrderStatus::AwaitingResultReview])
                    ->latest('updated_at')
                    ->take(3)
                    ->get();

                foreach ($pendingResults as $order) {
                    $items[] = [
                        'id' => 'lab_results_' . $order->id,
                        'type' => 'processing',
                        'badge' => 'NEEDS SIGNOFF',
                        'badge_class' => 'bg-purple-50 text-purple-800 border border-purple-200/80',
                        'title' => 'Analytical Signoff: ' . $order->reference,
                        'order_reference' => $order->reference,
                        'message' => 'Specimen received. Pending dual-control pathologist review & release.',
                        'time' => $order->updated_at?->diffForHumans() ?? 'Recently',
                        'url' => route('lab.results.index'),
                        'icon' => 'flask',
                    ];
                }

                // 4. Laboratory settlements
                $settlements = LaboratorySettlement::where('laboratory_id', $organization->id)
                    ->latest()
                    ->take(2)
                    ->get();

                foreach ($settlements as $settlement) {
                    $items[] = [
                        'id' => 'lab_settle_' . $settlement->id,
                        'type' => 'settlement',
                        'badge' => 'SETTLEMENT',
                        'badge_class' => 'bg-emerald-50 text-emerald-800 border border-emerald-200/80',
                        'title' => 'Settlement Statement',
                        'message' => 'Net payable GHS ' . number_format((float) $settlement->net_payable, 2) . ' for recent period.',
                        'time' => $settlement->created_at?->diffForHumans() ?? 'Recently',
                        'url' => route('lab.settlements.index'),
                        'icon' => 'banknotes',
                    ];
                }
            } elseif ($organization->isCorporate()) {
                // 1. Screening Programs
                $programs = ScreeningProgram::where('organization_id', $organization->id)
                    ->latest()
                    ->take(3)
                    ->get();

                foreach ($programs as $prog) {
                    $items[] = [
                        'id' => 'corp_prog_' . $prog->id,
                        'type' => 'screening',
                        'badge' => strtoupper($prog->status ?? 'ACTIVE'),
                        'badge_class' => 'bg-emerald-50 text-emerald-800 border border-emerald-200/80',
                        'title' => 'Screening Campaign: ' . $prog->name,
                        'message' => 'Program ref ' . $prog->reference . ' • ' . str_replace('_', ' ', $prog->purpose),
                        'time' => $prog->created_at?->diffForHumans() ?? 'Recently',
                        'url' => route('corporate.sessions.index'),
                        'icon' => 'users',
                    ];
                }

                // 2. Corporate Invoices
                $invoices = Invoice::where('organization_id', $organization->id)
                    ->latest()
                    ->take(2)
                    ->get();

                foreach ($invoices as $inv) {
                    $items[] = [
                        'id' => 'corp_inv_' . $inv->id,
                        'type' => 'invoice',
                        'badge' => 'STATEMENT',
                        'badge_class' => 'bg-blue-50 text-blue-800 border border-blue-200/80',
                        'title' => 'Screening Invoice: ' . $inv->number,
                        'message' => 'Total ' . $inv->currency . ' ' . number_format((float) $inv->total, 2) . ' for workforce diagnostic batch.',
                        'time' => $inv->created_at?->diffForHumans() ?? 'Recently',
                        'url' => route('corporate.invoices.index'),
                        'icon' => 'receipt',
                    ];
                }
            }

            // Active Communications from Platform Ops for Non-Platform Organizations
            if (! $organization->isPlatform()) {
                $partnerThreads = \App\Models\CommunicationThread::with(['messages.user', 'messages.organization'])
                    ->where('status', 'open')
                    ->where(function ($q) use ($organization) {
                        $q->where('recipient_organization_id', $organization->id)
                          ->orWhere('sender_organization_id', $organization->id)
                          ->orWhere('recipient_type', 'all_partners');
                        if ($organization->isHospital()) {
                            $q->orWhere('recipient_type', 'all_hospitals');
                        } elseif ($organization->isLaboratory()) {
                            $q->orWhere('recipient_type', 'all_laboratories');
                        } elseif ($organization->isCorporate()) {
                            $q->orWhere('recipient_type', 'all_corporates');
                        }
                    })
                    ->orderByDesc('last_message_at')
                    ->take(4)
                    ->get();

                $routePrefix = match(true) {
                    $organization->isHospital() => 'hospital',
                    $organization->isLaboratory() => 'lab',
                    $organization->isCorporate() => 'corporate',
                    default => 'hospital',
                };

                foreach ($partnerThreads as $thread) {
                    $lastMsg = $thread->messages->last();
                    if ($lastMsg && $lastMsg->organization_id !== $organization->id) {
                        $senderOrg = $lastMsg->organization;
                        $isFromPlatform = $senderOrg ? $senderOrg->isPlatform() : true;

                        $badge = match(true) {
                            $thread->priority === 'urgent' => 'URGENT MSG',
                            $thread->isBroadcast() => 'NETWORK NOTICE',
                            $isFromPlatform => 'PLATFORM NOTICE',
                            $senderOrg?->isHospital() => 'HOSPITAL MESSAGE',
                            $senderOrg?->isLaboratory() => 'LAB MESSAGE',
                            $senderOrg?->isCorporate() => 'CORPORATE MESSAGE',
                            default => 'PARTNER MESSAGE',
                        };

                        $title = ($senderOrg ? $senderOrg->name : 'NODIVA Ops') . ': ' . $thread->subject;

                        $items[] = [
                            'id' => 'comm_' . $thread->id . '_' . $lastMsg->id,
                            'type' => 'communication',
                            'badge' => $badge,
                            'badge_class' => $thread->priorityBadgeClass(),
                            'title' => $title,
                            'message' => ($lastMsg->user?->name ? $lastMsg->user->name . ': ' : '') . \Illuminate\Support\Str::limit($lastMsg->message, 70),
                            'time' => $lastMsg->created_at?->diffForHumans() ?? 'Recently',
                            'url' => route($routePrefix . '.communications.show', $thread),
                            'icon' => 'chat',
                        ];
                    }
                }
            }
        } catch (\Throwable) {
            // Gracefully catch any query issues so notification dropdown never breaks page rendering
        }

        return $items;
    }
}
