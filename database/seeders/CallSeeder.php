<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CompanyDid;
use App\Models\Call;
use App\Models\CompanyDidInvoice;
use Carbon\Carbon;

class CallSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activeAssignments = CompanyDid::where('status', 'active')->get();

        foreach ($activeAssignments as $assignment) {
            // Ensure an invoice exists for this month
            $startOfMonth = Carbon::now()->startOfMonth();
            $invoice = CompanyDidInvoice::firstOrCreate(
                [
                    'company_did_id' => $assignment->id,
                    'effective_from' => $startOfMonth,
                ],
                [
                    'effective_to' => Carbon::now()->endOfMonth(),
                    'total_minutes_consumption' => 0,
                    'billed_amount' => 0,
                    'status' => 'Draft'
                ]
            );

            // Create some calls
            for ($i = 0; $i < 5; $i++) {
                $duration = rand(60, 600); // 1 to 10 minutes
                Call::create([
                    'company_did_invoice_id' => $invoice->id,
                    'session_id' => 'sess_' . uniqid(),
                    'user_phone' => '+199999900' . $i,
                    'duration' => $duration,
                    'disposition' => ['SALE', 'NI', 'DNC'][rand(0, 2)],
                    'call_audio_url' => 'https://example.com/audio.mp3',
                ]);
                // Observer should handle calculations
            }
        }
    }
}
