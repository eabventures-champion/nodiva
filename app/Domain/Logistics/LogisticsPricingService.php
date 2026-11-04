<?php

declare(strict_types=1);

namespace App\Domain\Logistics;

use App\Models\Organization;

/**
 * Calculates courier logistics dispatch charges based on facility proximity,
 * regional service zones, and laboratory transport infrastructure.
 */
class LogisticsPricingService
{
    public const BASE_INTRA_REGION_FEE = 35.00;
    public const INTER_REGION_FEE = 75.00;

    /**
     * Calculate courier dispatch charge between a referring hospital and a laboratory.
     */
    public function calculateDispatchCharge(?Organization $hospital, Organization $laboratory): float
    {
        // 1. Check if laboratory has custom negotiated courier rate in its profile/settings
        if (! empty($laboratory->settings['dispatch_fee'])) {
            return (float) $laboratory->settings['dispatch_fee'];
        }

        if (! $hospital) {
            return self::BASE_INTRA_REGION_FEE;
        }

        $hospitalRegionId = $hospital->service_region_id;
        $labRegionId = $laboratory->service_region_id;

        // 2. Intra-regional / Same-city courier dispatch
        if ($hospitalRegionId && $labRegionId && $hospitalRegionId === $labRegionId) {
            $baseFee = (float) ($hospital->settings['default_dispatch_fee'] ?? self::BASE_INTRA_REGION_FEE);
            return round($baseFee, 2);
        }

        // 3. Laboratory service regions explicitly cover the hospital's region
        if ($hospitalRegionId && $laboratory->serviceRegions->contains('id', $hospitalRegionId)) {
            $baseFee = (float) ($hospital->settings['default_dispatch_fee'] ?? self::BASE_INTRA_REGION_FEE);
            return round($baseFee, 2);
        }

        // 4. Inter-regional cold-chain transit (Cross-zone surcharge)
        return self::INTER_REGION_FEE;
    }
}
