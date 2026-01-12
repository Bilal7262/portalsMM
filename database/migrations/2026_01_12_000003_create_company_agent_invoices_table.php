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
        Schema::create('company_agent_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('invoice_number')->unique(); // Auto-generated invoice number
            $table->date('effective_from');
            $table->date('effective_to');
            $table->decimal('total_amount', 10, 2)->default(0.00); // Sum of all items
            $table->string('status')->default('Draft'); // Draft, Finalized, Paid
            $table->softDeletes();
            $table->timestamps();

            // Indexes for better query performance
            $table->index('company_id');
            $table->index('status');
            $table->index(['company_id', 'status']);
            $table->index(['effective_from', 'effective_to']);
            $table->index('effective_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_agent_invoices');
    }
};
