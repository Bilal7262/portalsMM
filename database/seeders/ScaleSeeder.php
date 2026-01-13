<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Did;
use App\Models\CompanyAgent;
use App\Models\CompanyAgentInvoice;
use App\Models\CompanyAgentInvoiceItem;
use App\Models\Call;
use App\Models\AdminVoice;
use App\Models\CallMessage;

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
        CallMessage::truncate();
        Call::truncate();
        CompanyAgentInvoiceItem::truncate();
        CompanyAgentInvoice::truncate();
        CompanyAgent::truncate();
        Did::truncate();
        CompanyUser::truncate();
        Company::truncate();
        AdminVoice::truncate();

        $password = Hash::make('password');
        $now = Carbon::now();

        // Create default admin voice
        $this->command->info("Creating default admin voice...");
        $adminVoice = AdminVoice::create([
            'name' => 'Default Voice',
            'transcript' => 'Professional AI assistant voice',
            'scene_prompt' => 'You are a helpful customer service representative',
            'ref_audio' => null,
            'ref_audio_in_system_message' => false,
            'chunk_method' => 'default',
            'chunk_max_word_num' => 100,
            'chunk_max_num_turns' => 10,
            'generation_chunk_buffer_size' => 512,
            'temperature' => 0.70,
            'top_k' => 50,
            'top_p' => 0.95,
            'ras_win_len' => 10,
            'ras_win_max_num_repeat' => 3,
            'seed' => null,
            'status' => 'active',
        ]);

        $this->command->info("Creating specialized admin voice...");
        $specializedVoice = AdminVoice::updateOrCreate(
            ['name' => 'Specialized Voice'],
            [
                'transcript' => 'text',
                'ref_audio' => null, // Assuming ref_audio is a variable not available here, setting to null or specific path if known. User provided "ref_audio" as string literal in prompt but likely meant a variable or specific string. I will use null for now as no file path was given.',
                'chunk_max_word_num' => 200,
                'ras_win_len' => 7,
                'ras_win_max_num_repeat' => 2,
                'ref_audio_in_system_message' => true,
                'scene_prompt' => "add some natural human like filers and pauses",
                'temperature' => 1.0,
                'top_k' => 50,
                'top_p' => 0.95,
                'chunk_method' => 'default', // Using default as None implies default or null, database default is 'default'
                'chunk_max_num_turns' => 1,
                'generation_chunk_buffer_size' => 512, // Using default if null
                'seed' => 42, // Using a fixed seed integer as requested
                'status' => 'active',
            ]
        );

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

            // 3. Create 6 AI Agents (previously DIDs) for this company
            for ($d = 1; $d <= 6; $d++) {
                $didNumber = "+1800" . str_pad(($c - 1) * 6 + $d, 7, '0', STR_PAD_LEFT);
                $did = Did::create([
                    'did_number' => $didNumber,
                    'status' => 'assigned',
                ]);

                $agent = CompanyAgent::create([
                    'company_id' => $company->id,
                    'did_id' => $did->id,
                    'admin_voice_id' => $adminVoice->id,
                    'name' => "Agent $d for Company $c",
                    'script' => "Hello! This is Agent $d. How can I assist you today?",
                    'quantity' => 0, // unlimited
                    'price_per_min' => 0.05,
                    'start_date' => $now->copy()->subMonths(12)->startOfMonth(),
                    'status' => 'active',
                ]);

                // 4. Create 12 months of invoices
                for ($m = 0; $m < 12; $m++) {
                    $monthStart = $now->copy()->subMonths(12 - $m)->startOfMonth();
                    $monthEnd = $monthStart->copy()->endOfMonth();

                    // Create or get invoice for this month (one invoice per company per month)
                    $invoiceNumber = 'INV-' . $company->id . '-' . $monthStart->format('Ym') . '-' . $d;

                    $invoice = CompanyAgentInvoice::updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'effective_from' => $monthStart,
                            'effective_to' => $monthEnd,
                        ],
                        [
                            'invoice_number' => $invoiceNumber,
                            'total_amount' => 0,
                            'status' => 'Finalized',
                        ]
                    );

                    // Create invoice item for this agent
                    $invoiceItem = CompanyAgentInvoiceItem::updateOrCreate(
                        [
                            'company_agent_invoice_id' => $invoice->id,
                            'company_agent_id' => $agent->id,
                        ],
                        [
                            'total_minutes' => 0,
                            'rate_per_min' => 0.05,
                            'subtotal' => 0,
                        ]
                    );

                    // 5. Create 10 calls for this month for this agent
                    $totalMinutes = 0;
                    for ($callCount = 1; $callCount <= 10; $callCount++) {
                        $duration = rand(60, 300);
                        $minutes = ceil($duration / 60);
                        $totalMinutes += $minutes;

                        $call = Call::create([
                            'company_agent_invoice_item_id' => $invoiceItem->id,
                            'session_id' => "sess_{$c}_{$d}_{$m}_{$callCount}",
                            'user_phone' => "+1555" . rand(1000000, 9999999),
                            'call_audio_url' => "http://example.com/audio/call_{$c}_{$d}_{$m}_{$callCount}.wav",
                            'call_transcription' => $this->generateTranscript(),
                            'duration' => $duration,
                            'disposition' => 'SALE',
                            'ai_rating' => rand(3, 5),
                            'created_at' => $monthStart->copy()->addDays(rand(1, 28))->addHours(rand(0, 23)),
                            'updated_at' => $monthStart,
                        ]);

                        // Create call messages (conversation history)
                        $messages = [
                            ['type' => 'bot', 'text' => 'Hello, thank you for calling. How can I help you today?'],
                            ['type' => 'user', 'text' => "I'm interested in learning more about your services."],
                            ['type' => 'bot', 'text' => 'Certainly! We offer a wide range of AI-driven portal solutions.'],
                            ['type' => 'user', 'text' => 'That sounds interesting. Do you have a pricing sheet?'],
                            ['type' => 'bot', 'text' => "I can send that over right away. What's your business size?"],
                        ];

                        foreach ($messages as $msg) {
                            CallMessage::create([
                                'call_id' => $call->id,
                                'type' => $msg['type'],
                                'audio' => $msg['type'] === 'bot' ? 'https://actions.google.com/sounds/v1/alarms/beep_short.ogg' : null,
                                'text' => $msg['text'],
                            ]);
                        }
                    }

                    // Update invoice item with totals
                    $invoiceItem->update([
                        'total_minutes' => $totalMinutes,
                        'subtotal' => $totalMinutes * 0.05,
                    ]);
                }
            }

            // Update all invoices for this company with total amounts
            $invoices = CompanyAgentInvoice::where('company_id', $company->id)->get();
            foreach ($invoices as $invoice) {
                $totalAmount = $invoice->items()->sum('subtotal');
                $invoice->update(['total_amount' => $totalAmount]);
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
