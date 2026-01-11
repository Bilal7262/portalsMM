<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Did;
use App\Models\Company;
use App\Models\CompanyDid;
use Carbon\Carbon;

class DidSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Available DIDs
        $dids = [];
        for ($i = 0; $i < 10; $i++) {
            $dids[] = Did::create([
                'did_number' => '+180055501' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'status' => 'available',
            ]);
        }

        // 2. Assign some to a company if exists
        $company = Company::first();
        if ($company) {
            // Assign 2 dIDs
            $didToAssign = $dids[0];
            $didToAssign->update(['status' => 'assigned']);
            
            CompanyDid::create([
                'company_id' => $company->id,
                'did_id' => $didToAssign->id,
                'price_per_min' => 0.05,
                'start_date' => Carbon::now()->subMonths(1),
                'status' => 'active'
            ]);

            $didToAssign2 = $dids[1];
            $didToAssign2->update(['status' => 'assigned']);

            CompanyDid::create([
                'company_id' => $company->id,
                'did_id' => $didToAssign2->id,
                'price_per_min' => 0.10,
                'start_date' => Carbon::now()->subMonths(2),
                'status' => 'active'
            ]);
        }
    }
}
