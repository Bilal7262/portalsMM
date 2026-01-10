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
        Schema::create('company_did_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_did_id')->constrained('company_dids')->onDelete('cascade');
            $table->date('effective_from');
            $table->date('effective_to');
            $table->integer('total_minutes_consumption')->default(0);
            $table->decimal('billed_amount', 10, 2);
            $table->string('status')->default('Draft'); // Draft, Finalized, Paid
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_did_invoices');
    }
};
