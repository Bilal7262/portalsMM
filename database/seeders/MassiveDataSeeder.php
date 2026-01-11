<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MassiveDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info("Starting Massive Data Seeding for 10 Companies...");
        
        // Disable foreign key checks (SQLite compatible)
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } else {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }
        
        // Truncate existing data
        $this->command->info("Truncating existing data...");
        DB::table('calls')->delete();
        DB::table('company_did_invoices')->delete();
        DB::table('company_dids')->delete();
        DB::table('dids')->delete();
        DB::table('company_users')->delete();
        DB::table('companies')->delete();
        
        $password = Hash::make('password');
        $now = now();
        
        // Step 1: Create 10 Companies
        $this->command->info("Creating 10 companies...");
        $companies = [];
        for ($i = 1; $i <= 10; $i++) {
            $companies[] = [
                'id' => $i,
                'business_name' => "Enterprise Corp $i",
                'email' => "corp{$i}@example.com",
                'phone' => "+1555000000{$i}",
                'status' => 'active',
                'verify_email' => 1,
                'verify_phone' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('companies')->insert($companies);
        
        // Create admin users for each company
        $this->command->info("Creating admin users...");
        $users = [];
        for ($i = 1; $i <= 10; $i++) {
            $users[] = [
                'id' => $i,
                'company_id' => $i,
                'name' => "Admin $i",
                'email' => "admin{$i}@example.com",
                'password' => $password,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('company_users')->insert($users);
        
        // Step 2: Create 100K DIDs (10K per company)
        $this->command->info("Creating 100,000 DIDs...");
        $this->command->getOutput()->progressStart(100000);
        
        $didId = 1;
        $chunkSize = 5000;
        
        for ($batch = 0; $batch < 20; $batch++) { // 20 batches of 5K
            $dids = [];
            for ($i = 0; $i < $chunkSize; $i++) {
                $dids[] = [
                    'id' => $didId,
                    'did_number' => '+1800' . str_pad($didId, 7, '0', STR_PAD_LEFT),
                    'status' => 'assigned',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $didId++;
            }
            DB::table('dids')->insert($dids);
            $this->command->getOutput()->progressAdvance($chunkSize);
        }
        $this->command->getOutput()->progressFinish();
        
        // Step 3: Assign DIDs to Companies (10K per company)
        $this->command->info("Assigning DIDs to companies...");
        $this->command->getOutput()->progressStart(100000);
        
        $assignmentId = 1;
        $didId = 1;
        
        for ($companyId = 1; $companyId <= 10; $companyId++) {
            for ($batch = 0; $batch < 2; $batch++) { // 2 batches of 5K per company
                $assignments = [];
                for ($i = 0; $i < 5000; $i++) {
                    $assignments[] = [
                        'id' => $assignmentId,
                        'company_id' => $companyId,
                        'did_id' => $didId,
                        'price_per_min' => 0.05,
                        'start_date' => $now->copy()->subYears(5),
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $assignmentId++;
                    $didId++;
                }
                DB::table('company_dids')->insert($assignments);
                $this->command->getOutput()->progressAdvance(5000);
            }
        }
        $this->command->getOutput()->progressFinish();
        
        // Step 4: Create 60 months of invoices for each DID
        $this->command->info("Creating 6,000,000 invoices (60 months × 100K DIDs)...");
        $this->command->getOutput()->progressStart(6000000);
        
        $invoiceId = 1;
        $assignmentId = 1;
        
        for ($companyId = 1; $companyId <= 10; $companyId++) {
            for ($didBatch = 0; $didBatch < 10; $didBatch++) { // Process 1K DIDs at a time
                $invoices = [];
                
                for ($didOffset = 0; $didOffset < 1000; $didOffset++) {
                    // Create 60 monthly invoices per DID
                    for ($month = 0; $month < 60; $month++) {
                        $startDate = $now->copy()->subMonths(60 - $month)->startOfMonth();
                        $endDate = $startDate->copy()->endOfMonth();
                        
                        $invoices[] = [
                            'id' => $invoiceId,
                            'company_did_id' => $assignmentId,
                            'effective_from' => $startDate,
                            'effective_to' => $endDate,
                            'total_minutes_consumption' => 0, // Will be updated by calls
                            'billed_amount' => 0,
                            'status' => $month < 59 ? 'Finalized' : 'Draft', // Last month is draft
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        $invoiceId++;
                        
                        // Insert in chunks of 5000
                        if (count($invoices) >= 5000) {
                            DB::table('company_did_invoices')->insert($invoices);
                            $this->command->getOutput()->progressAdvance(count($invoices));
                            $invoices = [];
                        }
                    }
                    $assignmentId++;
                }
                
                // Insert remaining
                if (count($invoices) > 0) {
                    DB::table('company_did_invoices')->insert($invoices);
                    $this->command->getOutput()->progressAdvance(count($invoices));
                }
            }
        }
        $this->command->getOutput()->progressFinish();
        
        // Step 5: Create Calls (8K per DID = 800 Million total)
        $this->command->info("Creating 800,000,000 calls (8K per DID)...");
        $this->command->warn("WARNING: This will take 2-4 hours. Press Ctrl+C to cancel.");
        sleep(3);
        
        $this->command->getOutput()->progressStart(800000000);
        
        $callId = 1;
        
        // Process each company
        for ($companyId = 1; $companyId <= 10; $companyId++) {
            $this->command->info("Processing Company $companyId...");
            
            // Process each DID (10K per company)
            for ($didIndex = 0; $didIndex < 10000; $didIndex++) {
                $assignmentId = ($companyId - 1) * 10000 + $didIndex + 1;
                
                // Get random invoice for this DID
                $invoiceId = ($assignmentId - 1) * 60 + rand(1, 60);
                
                // Create 8K calls per DID in batches of 2K
                for ($callBatch = 0; $callBatch < 4; $callBatch++) {
                    $calls = [];
                    
                    for ($i = 0; $i < 2000; $i++) {
                        $duration = rand(60, 600);
                        $calls[] = [
                            'company_did_invoice_id' => $invoiceId,
                            'session_id' => "sess_{$callId}",
                            'user_phone' => '+1999' . rand(1000000, 9999999),
                            'call_audio_url' => 'https://example.com/audio.wav',
                            'call_transcription' => 'Sample call transcription',
                            'duration' => $duration,
                            'disposition' => ['SALE', 'NI', 'DNC'][rand(0, 2)],
                            'ai_rating' => rand(1, 5),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        $callId++;
                    }
                    
                    DB::table('calls')->insert($calls);
                    $this->command->getOutput()->progressAdvance(2000);
                }
                
                // Progress update every 100 DIDs
                if (($didIndex + 1) % 100 === 0) {
                    $this->command->info("Company $companyId: Processed " . ($didIndex + 1) . "/10000 DIDs");
                }
            }
        }
        
        $this->command->getOutput()->progressFinish();
        
        // Re-enable foreign key checks
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            DB::statement('PRAGMA foreign_keys = ON;');
        }
        
        $this->command->info("Massive Data Seeding Complete!");
        $this->command->info("Summary:");
        $this->command->info("- Companies: 10");
        $this->command->info("- DIDs: 100,000");
        $this->command->info("- Invoices: 6,000,000");
        $this->command->info("- Calls: 800,000,000");
    }
}
