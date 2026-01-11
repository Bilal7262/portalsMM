<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Did;
use App\Models\CompanyDid;
use App\Models\CompanyDidInvoice;
use App\Models\Call;

class ScaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info("Starting Custom Scaling Seeder (3 Companies)...");

        // Disable foreign key checks
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } else {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        // Truncate tables
        $this->command->info("Truncating relevant tables...");
        Call::truncate();
        CompanyDidInvoice::truncate();
        CompanyDid::truncate();
        Did::truncate();
        CompanyUser::truncate();
        Company::truncate();

        $password = Hash::make('password');
        $now = Carbon::now();

        for ($c = 1; $c <= 3; $c++) {
            $this->command->info("Seeding Company $c...");

            // 1. Create Company
            $company = Company::create([
                'business_name' => "Company $c Corp",
                'email' => "company$c@example.com",
                'phone' => "+122200000$c",
                'status' => 'active',
                'verify_email' => 1,
                'verify_phone' => 1,
            ]);

            // 2. Create Admin User
            CompanyUser::create([
                'company_id' => $company->id,
                'name' => "Admin $c",
                'email' => "admin$c@example.com",
                'password' => $password,
                'status' => 'active',
            ]);

            // 3. Create 6 DIDs for this company
            for ($d = 1; $d <= 6; $d++) {
                $didNumber = "+1800" . str_pad(($c - 1) * 6 + $d, 7, '0', STR_PAD_LEFT);
                $did = Did::create([
                    'did_number' => $didNumber,
                    'status' => 'assigned',
                ]);

                $assignment = CompanyDid::create([
                    'company_id' => $company->id,
                    'did_id' => $did->id,
                    'price_per_min' => 0.05,
                    'start_date' => $now->copy()->subMonths(12)->startOfMonth(),
                    'status' => 'active',
                ]);

                // 4. Create 12 months of invoices
                for ($m = 0; $m < 12; $m++) {
                    $monthStart = $now->copy()->subMonths(12 - $m)->startOfMonth();
                    $monthEnd = $monthStart->copy()->endOfMonth();

                    $invoice = CompanyDidInvoice::create([
                        'company_did_id' => $assignment->id,
                        'effective_from' => $monthStart,
                        'effective_to' => $monthEnd,
                        'total_minutes_consumption' => 0, // Updated below
                        'billed_amount' => 0,
                        'status' => 'Finalized',
                    ]);

                    // 5. Create 10 calls for this month
                    $totalMinutes = 0;
                    $calls = [];
                    for ($callCount = 1; $callCount <= 10; $callCount++) {
                        $duration = rand(60, 300);
                        $minutes = ceil($duration / 60);
                        $totalMinutes += $minutes;

                        $calls[] = [
                            'company_did_invoice_id' => $invoice->id,
                            'session_id' => "sess_{$c}_{$d}_{$m}_{$callCount}",
                            'user_phone' => "+1555" . rand(1000000, 9999999),
                            'call_audio_url' => "http://example.com/audio/call_{$c}_{$d}_{$m}_{$callCount}.wav",
                            'call_transcription' => $this->generateTranscript(),
                            'duration' => $duration,
                            'disposition' => 'SALE',
                            'ai_rating' => rand(3, 5),
                            'created_at' => $monthStart->copy()->addDays(rand(1, 28))->addHours(rand(0, 23)),
                            'updated_at' => $monthStart,
                        ];
                    }
                    Call::insert($calls);

                    // Update invoice with totals
                    $invoice->update([
                        'total_minutes_consumption' => $totalMinutes,
                        'billed_amount' => $totalMinutes * 0.05,
                    ]);
                }
            }
        }

        // Re-enable foreign key checks
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->command->info("Seeding complete!");
    }

    /**
     * Generates a 10-turn dialogue transcript.
     */
    private function generateTranscript(): string
    {
        $dialouges = [
            "Agent: Hello, thank you for calling. How can I help you today?",
            "User: I'm interested in learning more about your services.",
            "Agent: Certainly! We offer a wide range of AI-driven portal solutions.",
            "User: That sounds interesting. Do you have a pricing sheet?",
            "Agent: I can send that over right away. What's your business size?",
            "User: We are a medium enterprise with about 100 employees.",
            "Agent: Great, our Enterprise plan would be perfect for you.",
            "User: What does that include exactly?",
            "Agent: It includes 24/7 support, dedicated DIDs, and advanced analytics.",
            "User: Sounds good. Let's schedule a follow-up call."
        ];
        return implode("\n", $dialouges);
    }
}
