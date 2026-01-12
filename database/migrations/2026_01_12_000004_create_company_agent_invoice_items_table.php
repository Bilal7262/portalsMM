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
        Schema::create('company_agent_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_agent_invoice_id')->constrained('company_agent_invoices')->onDelete('cascade');
            $table->foreignId('company_agent_id')->constrained('company_agents')->onDelete('cascade');
            $table->integer('total_minutes')->default(0); // Minutes consumed by this agent
            $table->decimal('rate_per_min', 8, 4); // Rate at time of invoice (allows for partial cents)
            $table->decimal('subtotal', 10, 2)->default(0.00); // total_minutes * rate_per_min
            $table->softDeletes();
            $table->timestamps();

            // Indexes for better query performance
            $table->index('company_agent_invoice_id');
            $table->index('company_agent_id');
            $table->unique(['company_agent_invoice_id', 'company_agent_id'], 'unique_invoice_agent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_agent_invoice_items');
    }
};
