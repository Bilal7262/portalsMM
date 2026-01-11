<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CompanyDid;
use App\Models\CompanyDidInvoice;
use Carbon\Carbon;

class GenerateInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly invoices for active company DIDs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting invoice generation...');

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Get all active Company DIDs
        // Also consider DIDs that were active this month but might be inactive now? 
        // For simplicity, let's take all DIDs that are active OR (inactive but end_date >= start of this month)
        $activeDids = CompanyDid::where('status', 'active')
            ->orWhere(function ($query) use ($startOfMonth) {
                 $query->where('status', 'inactive')
                       ->where('end_date', '>=', $startOfMonth);
            })
            ->get();

        foreach ($activeDids as $companyDid) {
            // Check if invoice already exists for this period
            // We assume one invoice per month per DID
            $exists = CompanyDidInvoice::where('company_did_id', $companyDid->id)
                ->where('effective_from', $startOfMonth->toDateString())
                ->exists();

            if (!$exists) {
                CompanyDidInvoice::create([
                    'company_did_id' => $companyDid->id,
                    'effective_from' => $startOfMonth,
                    'effective_to' => $endOfMonth, // Or smaller if DID end_date is sooner
                    'total_minutes_consumption' => 0,
                    'billed_amount' => 0,
                    'status' => 'Draft',
                ]);
                $this->info("Generated invoice for Company DID: {$companyDid->id}");
            }
        }

        $this->info('Invoice generation completed.');
    }
}
