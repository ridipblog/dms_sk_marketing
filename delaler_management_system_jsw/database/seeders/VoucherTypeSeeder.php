<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Accounts\VoucherType;

class VoucherTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Receipt', 'description' => 'Payment received from dealer'],
            ['name' => 'Journal', 'description' => 'Adjustment entry'],
            // ['name' => 'GST Credit Note', 'description' => 'Discount/return/rate reduction with GST'],
            ['name' => 'Credit Note', 'description' => 'Amount reduced from dealer account'],
            ['name' => 'Debit Note', 'description' => 'Extra amount added with GST'],
            ['name' => 'SALES', 'description' => 'Sales invoice entry'],
            ['name' => 'PURCHASE', 'description' => 'Purchase entry for external supplier'],
            ['name' => 'PAYMENT', 'description' => 'Payment entry to external supplier'],
        ];

        foreach ($types as $type) {
            VoucherType::updateOrCreate(
                ['name' => $type['name']],
                ['description' => $type['description'], 'status' => 1]
            );
        }
    }
}
