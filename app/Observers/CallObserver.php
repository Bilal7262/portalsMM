<?php

namespace App\Observers;

use App\Models\Call;

class CallObserver
{
    /**
     * Handle the Call "saved" event (created or updated).
     */
    public function saved(Call $call): void
    {
        if ($call->company_did_invoice_id) {
            $invoice = $call->invoice; 
            
            if ($invoice) {
                // improved logic: Recalculate from all calls to ensure consistency
                // Assuming duration is in Seconds.
                $totalSeconds = $invoice->calls()->sum('duration');
                $totalMinutes = ceil($totalSeconds / 60);

                $pricePerMin = $invoice->companyDid->price_per_min;
                
                $invoice->update([
                    'total_minutes_consumption' => $totalMinutes,
                    'billed_amount' => $totalMinutes * $pricePerMin
                ]);
            }
        }
    }

    /**
     * Handle the Call "deleted" event.
     */
    public function deleted(Call $call): void
    {
        if ($call->company_did_invoice_id) {
             // Re-trigger update on the invoice
            $this->saved($call);
        }
    }
}
