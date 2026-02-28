<?php

namespace App\Services;

use App\Models\PackageConfig;

class PackageCatalogService
{
    public function defaults(): array
    {
        $configured = config('plans.packages', []);

        $fallback = [
            'Free' => ['monthly_price' => 0, 'yearly_price' => 0, 'invoice_limit' => 10],
            'Basic' => ['monthly_price' => 49000, 'yearly_price' => 38000, 'invoice_limit' => 10],
            'Pro' => ['monthly_price' => 139000, 'yearly_price' => 106000, 'invoice_limit' => 500],
            'Enterprise' => ['monthly_price' => 199000, 'yearly_price' => 151000, 'invoice_limit' => null],
        ];

        if (! is_array($configured) || $configured === []) {
            return $fallback;
        }

        $normalized = [];
        foreach ($configured as $plan => $row) {
            $normalized[(string) $plan] = [
                'monthly_price' => max(0, (int) ($row['monthly_price'] ?? 0)),
                'yearly_price' => max(0, (int) ($row['yearly_price'] ?? 0)),
                'invoice_limit' => array_key_exists('invoice_limit', (array) $row) && $row['invoice_limit'] !== null
                    ? max(1, (int) $row['invoice_limit'])
                    : null,
            ];
        }

        return $normalized;
    }

    public function all(): array
    {
        $catalog = $this->defaults();
        $overrides = PackageConfig::query()->get()->keyBy('plan_name');

        foreach ($catalog as $plan => $defaults) {
            $override = $overrides->get($plan);
            if (! $override) {
                continue;
            }

            $catalog[$plan] = [
                'monthly_price' => max(0, (int) $override->monthly_price),
                'yearly_price' => max(0, (int) $override->yearly_price),
                'invoice_limit' => $override->invoice_limit !== null ? max(1, (int) $override->invoice_limit) : null,
            ];
        }

        return $catalog;
    }

    public function for(string $planName): array
    {
        $catalog = $this->all();

        return $catalog[$planName] ?? ($catalog['Free'] ?? [
            'monthly_price' => 0,
            'yearly_price' => 0,
            'invoice_limit' => 10,
        ]);
    }

    public function invoiceLimit(string $planName): ?int
    {
        return $this->for($planName)['invoice_limit'];
    }

    public function checkoutPriceMap(): array
    {
        $map = [];
        foreach ($this->all() as $plan => $config) {
            $monthly = max(0, (int) ($config['monthly_price'] ?? 0));
            $yearlyMonthlyEquivalent = max(0, (int) ($config['yearly_price'] ?? 0));

            $map[$plan] = [
                'monthly' => $monthly,
                'yearly' => $yearlyMonthlyEquivalent * 12,
            ];
        }

        return $map;
    }

    public function toRows(): array
    {
        $rows = [];

        foreach ($this->all() as $plan => $config) {
            $rows[] = [
                'plan_name' => $plan,
                'monthly_price' => (int) ($config['monthly_price'] ?? 0),
                'yearly_price' => (int) ($config['yearly_price'] ?? 0),
                'invoice_limit' => $config['invoice_limit'] !== null ? (int) $config['invoice_limit'] : null,
            ];
        }

        return $rows;
    }

    public function upsertRows(array $rows): void
    {
        foreach ($rows as $row) {
            $planName = (string) ($row['plan_name'] ?? '');
            if ($planName === '') {
                continue;
            }

            PackageConfig::query()->updateOrCreate(
                ['plan_name' => $planName],
                [
                    'monthly_price' => max(0, (int) ($row['monthly_price'] ?? 0)),
                    'yearly_price' => max(0, (int) ($row['yearly_price'] ?? 0)),
                    'invoice_limit' => array_key_exists('invoice_limit', $row) && $row['invoice_limit'] !== null
                        ? max(1, (int) $row['invoice_limit'])
                        : null,
                ]
            );
        }
    }
}

