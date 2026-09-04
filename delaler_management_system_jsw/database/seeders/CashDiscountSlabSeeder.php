<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Accounts\CashDiscountSlab;

class CashDiscountSlabSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyIds = Company::pluck('id');
        if ($companyIds->isEmpty()) {
            $companyIds = collect([1]);
        }

        $slabs = [
            [
                'slab_name' => 'ON ADVANCE PAYMENT',
                'minimum_days' => -9999,
                'maximum_days' => 0,
                'discount_percent' => 900.00,
                'status' => 1,
            ],
            [
                'slab_name' => '1 TO 4 DAYS',
                'minimum_days' => 1,
                'maximum_days' => 4,
                'discount_percent' => 700.00,
                'status' => 1,
            ],
            [
                'slab_name' => '5 TO 10 DAYS',
                'minimum_days' => 5,
                'maximum_days' => 10,
                'discount_percent' => 500.00,
                'status' => 1,
            ],
            [
                'slab_name' => '11 TO 15 DAYS',
                'minimum_days' => 11,
                'maximum_days' => 15,
                'discount_percent' => 300.00,
                'status' => 1,
            ],
            [
                'slab_name' => '16 TO 20 DAYS',
                'minimum_days' => 16,
                'maximum_days' => 20,
                'discount_percent' => 100.00,
                'status' => 1,
            ]
        ];

        foreach ($companyIds as $companyId) {
            foreach ($slabs as $slab) {
                CashDiscountSlab::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'minimum_days' => $slab['minimum_days'],
                        'maximum_days' => $slab['maximum_days'],
                    ],
                    $slab
                );
            }
        }
    }
}
