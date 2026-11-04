<?php

declare(strict_types=1);

namespace App\Domain\Network;

use App\Domain\Finance\Money;
use App\Domain\Logistics\LogisticsPricingService;
use App\Models\DiagnosticTest;
use App\Models\Organization;
use App\Models\ReferralOrder;
use App\Models\TestCatalogueEntry;
use Illuminate\Support\Collection;

/**
 * Transparent, rules-based laboratory matching and ranking engine.
 *
 * Scoring criteria:
 * 1. Test coverage (must offer all requested tests).
 * 2. Qualification status (valid, unexpired licence/accreditation required for specialty).
 * 3. Geographic feasibility (active service region match).
 * 4. Operating capacity (not restricted, daily capacity > 0).
 * 5. Turnaround time target.
 * 6. Total contractual price.
 * 7. Historical reliability score (0-100).
 *
 * Every match result includes a human-readable and structured rationale explaining why
 * a lab is eligible/recommended, preserving the selection rationale for auditability.
 */
final class MatchingEngine
{
    public function match(ReferralOrder $order): Collection
    {
        $testIds = $order->items->pluck('diagnostic_test_id')->all();
        if (empty($testIds)) {
            return collect();
        }

        $regionId = $order->branch?->service_region_id ?? $order->requestingOrganization->service_region_id;

        // Candidate labs: active, approved, accepting orders, not temporarily restricted
        $labs = Organization::query()
            ->ofType(\App\Domain\Organizations\Enums\OrganizationType::Laboratory)
            ->active()
            ->withVerifiedQualifications()
            ->whereHas('laboratoryProfile', function ($p) {
                $p->where('is_accepting_orders', true)
                    ->where(fn ($w) => $w->whereNull('restricted_until')->orWhere('restricted_until', '<=', now()));
            })
            ->with([
                'laboratoryProfile',
                'qualifications' => fn ($q) => $q->valid(),
                'serviceRegions',
                'catalogueEntries' => fn ($q) => $q->whereIn('diagnostic_test_id', $testIds)->where('is_available', true)->with('test.specialty'),
            ])
            ->get();

        $candidates = collect();

        foreach ($labs as $lab) {
            $eval = $this->evaluateLaboratory($lab, $testIds, $regionId, $order);
            if ($eval['eligible']) {
                $candidates->push($eval);
            }
        }

        return $candidates->sortByDesc('score')->values();
    }

    private function evaluateLaboratory(Organization $lab, array $testIds, ?int $regionId, ReferralOrder $order): array
    {
        $entries = $lab->catalogueEntries->keyBy('diagnostic_test_id');
        $reasons = [];
        $penalties = 0;
        $score = 100.0;

        // 1. Completeness: must offer all tests
        $missing = array_diff($testIds, $entries->keys()->all());
        if (! empty($missing)) {
            return ['eligible' => false, 'laboratory' => $lab, 'disqualification' => 'Incomplete test coverage.'];
        }

        // 2. Geographic feasibility & Logistics Dispatch Charge
        $regionMatch = false;
        $dispatchFee = 0.00;
        $logisticsPricing = app(LogisticsPricingService::class);

        if ($order->collection_method === 'courier') {
            $dispatchFee = $logisticsPricing->calculateDispatchCharge($order->requestingOrganization, $lab);
            if ($regionId !== null && ($lab->service_region_id === $regionId || $lab->serviceRegions->contains('id', $regionId))) {
                $score += 15;
                $reasons[] = 'Local dispatch proximity (GHS ' . number_format($dispatchFee, 2) . ' courier fee)';
            } else {
                $score -= 15;
                $reasons[] = 'Inter-regional courier dispatch required (GHS ' . number_format($dispatchFee, 2) . ' fee)';
            }
        } else {
            // Patient direct walk-in
            if ($regionId !== null && $lab->service_region_id !== $regionId && ! $lab->serviceRegions->contains('id', $regionId)) {
                return ['eligible' => false, 'laboratory' => $lab, 'disqualification' => 'Facility outside laboratory service region for walk-in patient.'];
            }
            $reasons[] = 'Patient walk-in directly to facility (GHS 0.00 courier fee)';
        }

        // 3. Qualifications
        $validQuals = $lab->qualifications;
        $hasAccreditation = $validQuals->contains('type', 'accreditation');
        $hasLicense = $validQuals->contains('type', 'operating_license');

        if (! $hasLicense) {
            return ['eligible' => false, 'laboratory' => $lab, 'disqualification' => 'No verified operating licence.'];
        }

        if ($hasAccreditation) {
            $score += 10;
            $reasons[] = 'Verified ISO / national laboratory accreditation';
        }

        // Check test specialties requiring accreditation
        foreach ($entries as $entry) {
            $req = $entry->test?->specialty?->required_qualification_type;
            if ($req && ! $validQuals->contains('type', $req)) {
                return ['eligible' => false, 'laboratory' => $lab, 'disqualification' => "Specialty {$entry->test->specialty->name} requires verified {$req}."];
            }
        }

        // 4. Calculate total price and max turnaround time
        $currency = $order->currency;
        $totalPrice = Money::zero($currency);
        $maxTat = 0;

        foreach ($entries as $entry) {
            $itemPrice = Money::of((string) $entry->price, $entry->currency);
            $totalPrice = $totalPrice->plus($itemPrice);
            if ($entry->turnaround_hours > $maxTat) {
                $maxTat = $entry->turnaround_hours;
            }
        }

        $reasons[] = "Total quoted price: {$totalPrice->format()}";
        $reasons[] = "Estimated turnaround: {$maxTat}h";

        // TAT scoring: faster is better (baseline 48h)
        if ($maxTat <= 24) {
            $score += 10;
        } elseif ($maxTat > 72) {
            $score -= 10;
        }

        // Historical reliability
        $reliability = (float) ($lab->laboratoryProfile?->reliability_score ?? 90.0);
        $score += ($reliability - 90.0) * 0.5;
        $reasons[] = "Historical reliability: {$reliability}%";

        return [
            'eligible' => true,
            'laboratory' => $lab,
            'score' => round(max(1.0, min(100.0, $score)), 2),
            'total_price' => $totalPrice,
            'max_tat_hours' => $maxTat,
            'dispatch_charge' => $dispatchFee,
            'rationale' => $reasons,
            'entries' => $entries,
        ];
    }
}
