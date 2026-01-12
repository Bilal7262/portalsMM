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
        Schema::create('company_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('company_user_id')->nullable()->constrained('company_users')->onDelete('set null');
            $table->string('activity_type');
            $table->text('activity_details')->nullable();
            $table->timestamp('activity_date')->useCurrent();
            $table->softDeletes();
            $table->timestamps();

            // Indexes for better performance
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
        Schema::dropIfExists('company_activity_logs');
    }
};
