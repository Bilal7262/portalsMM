<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_did_invoice_id')->nullable()->constrained('company_did_invoices')->onDelete('set null');
            $table->string('session_id')->nullable()->index();
            $table->string('user_phone')->nullable()->index(); // Consider privacy
            $table->string('call_audio_url')->nullable();
            $table->text('call_transcription')->nullable();
            $table->integer('duration')->default(0); // in seconds
            $table->string('disposition')->nullable();
            // CALLBK → Call Back (scheduled callback)
            // CBHOLD → Call Back Hold (callback waiting to be triggered)
            // SALE → Sale / Transfer / Success
            // DEC → Declined Sale / Not Interested
            // DNC → Do Not Call (manual entry)
            // NI → Not Interested
            // NP → No Pitch / No Presentation
            // WRONG → Wrong Number
            // NC → Not Company / Not a decision maker
            // SVYEXT → Survey Extension (for survey campaigns)
            // TIME → Time Zone / Outside calling hours
            // FAX → Fax machine
            $table->timestamp('scheduled_callback')->nullable();
            $table->text('ai_feedback')->nullable();
            $table->decimal('ai_rating', 3, 1)->nullable();
            $table->text('company_feedback')->nullable();
            $table->decimal('company_rating', 3, 1)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
