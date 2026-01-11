<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Did;
use App\Models\CompanyDid;
use App\Models\CompanyDidInvoice;
use App\Models\Call;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LargeScaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $totalCompanies = 100000;
        $chunkSize = 500; // Smaller chunk size as we are inserting related data too
        
        $this->command->info("Starting Large Scale Seeding for $totalCompanies companies with Calls...");
        $this->command->getOutput()->progressStart($totalCompanies);

        // Determine starting IDs to maintain relationships without querying back
        // We assume the DB is relatively clean or these IDs are available.
        // For safety/idempotency in dev, maybe truncating first is better, but user might have data.
        // We will just fetch max IDs.
        
        $nextCompanyId = Company::max('id') + 1;
        $nextUserId = CompanyUser::max('id') + 1;
        $nextDidId = Did::max('id') + 1;
        $nextAssignmentId = CompanyDid::max('id') + 1;
        $nextInvoiceId = CompanyDidInvoice::max('id') + 1;
        $nextCallId = Call::max('id') + 1;

        $password = Hash::make('password'); // Pre-hash
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        for ($i = 0; $i < $totalCompanies / $chunkSize; $i++) {
            $companies = [];
            $users = [];
            $dids = [];
            $assignments = [];
            $invoices = [];
            $calls = [];

            for ($j = 0; $j < $chunkSize; $j++) {
                // IDs for this iteration
                $cId = $nextCompanyId + $j;
                $uId = $nextUserId + $j;
                $dId = $nextDidId + $j;
                $aId = $nextAssignmentId + $j;
                $iId = $nextInvoiceId + $j;
                
                // 1. Company
                $companies[] = [
                    'id' => $cId,
                    'business_name' => "Global Corp $cId",
                    'email' => "corp{$cId}@example.com",
                    'phone' => "+1555" . str_pad($cId, 8, '0', STR_PAD_LEFT),
                    'status' => 'active',
                    'verify_email' => 1,
                    'verify_phone' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // 2. User (Admin)
                $users[] = [
                    'id' => $uId,
                    'company_id' => $cId,
                    'name' => "Admin $cId",
                    'email' => "admin{$cId}@example.com",
                    'password' => $password,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // 3. DID
                $didNumber = '+1800' . str_pad($dId, 7, '0', STR_PAD_LEFT);
                $dids[] = [
                    'id' => $dId,
                    'did_number' => $didNumber,
                    'status' => 'assigned',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // 4. Assignment
                $pricePerMin = 0.05;
                $assignments[] = [
                    'id' => $aId,
                    'company_id' => $cId,
                    'did_id' => $dId,
                    'price_per_min' => $pricePerMin,
                    'start_date' => $startOfMonth,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // 5. Calls & Invoice Calculation
                // Let's generate 3-5 calls per company
                $numCalls = rand(3, 5);
                $totalMinutes = 0;

                for ($k = 0; $k < $numCalls; $k++) {
                    $callDuration = rand(60, 600); // seconds
                    $minutes = ceil($callDuration / 60);
                    $totalMinutes += $minutes;
                    
                    // Increment valid global call ID? No, we need to track it manually or let DB handle?
                    // Batch insert allows us to specify ID if we want, or skip it.
                    // If we skip ID in insert, we can't easily link if we had child tables of calls.
                    // But Calls are leaf nodes here (mostly).
                    // Wait, we need 'company_did_invoice_id' in Call. We have $iId.
                    
                    $calls[] = [
                        // 'id' => ... let auto increment handle or managed manual? 
                        // Manual is safer for bulk logic consistency if needed, but let's skip for simple leaves
                        'company_did_invoice_id' => $iId, // Link to the invoice we are about to create
                        'session_id' => "sess_{$cId}_{$k}",
                        'user_phone' => "+1999" . rand(1000000, 9999999),
                        'call_audio_url' => 'http://example.com/audio.wav',
                        'call_transcription' => 'Hello this is a test call.',
                        'duration' => $callDuration,
                        'disposition' => 'SALE',
                        'ai_rating' => rand(1, 5),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // 6. Invoice (Pre-calculated)
                $billedAmount = $totalMinutes * $pricePerMin;
                $invoices[] = [
                    'id' => $iId,
                    'company_did_id' => $aId,
                    'effective_from' => $startOfMonth,
                    'effective_to' => $endOfMonth,
                    'total_minutes_consumption' => $totalMinutes,
                    'billed_amount' => $billedAmount,
                    'status' => 'Finalized', // Visible to user
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Perform Bulk Inserts in Order
            Company::insert($companies);
            CompanyUser::insert($users);
            Did::insert($dids);
            CompanyDid::insert($assignments);
            CompanyDidInvoice::insert($invoices);
            Call::insert($calls); 

            // Increment ID counters
            $nextCompanyId += $chunkSize;
            $nextUserId += $chunkSize;
            $nextDidId += $chunkSize;
            $nextAssignmentId += $chunkSize;
            $nextInvoiceId += $chunkSize;
            // Calls auto-increment, we didn't force IDs.

            $this->command->getOutput()->progressAdvance($chunkSize);
        }

        $this->command->getOutput()->progressFinish();
    }
}
