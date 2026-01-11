<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Indexes for calls table (most queried)
        Schema::table('calls', function (Blueprint $table) {
            $table->index('company_did_invoice_id');
            $table->index('created_at');
            $table->index(['company_did_invoice_id', 'created_at']);
        });

        // Indexes for company_did_invoices
        Schema::table('company_did_invoices', function (Blueprint $table) {
            $table->index('company_did_id');
            $table->index('status');
            $table->index(['company_did_id', 'status']);
            $table->index('effective_from');
        });

        // Indexes for company_dids
        Schema::table('company_dids', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('did_id');
            $table->index('status');
        });

        // Indexes for dids
        Schema::table('dids', function (Blueprint $table) {
            $table->index('status');
            $table->index('did_number');
        });

        // Indexes for company_users
        Schema::table('company_users', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('status');
        });

        // Indexes for company_activity_logs
        Schema::table('company_activity_logs', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('created_at');
            $table->index(['company_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex(['company_did_invoice_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['company_did_invoice_id', 'created_at']);
        });

        Schema::table('company_did_invoices', function (Blueprint $table) {
            $table->dropIndex(['company_did_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['company_did_id', 'status']);
            $table->dropIndex(['effective_from']);
        });

        Schema::table('company_dids', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['did_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('dids', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['did_number']);
        });

        Schema::table('company_users', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('company_activity_logs', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['company_id', 'created_at']);
        });
    }
};
