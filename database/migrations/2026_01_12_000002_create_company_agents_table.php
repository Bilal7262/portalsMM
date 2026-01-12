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
        Schema::create('company_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('did_id')->nullable()->constrained('dids')->onDelete('set null');
            $table->foreignId('admin_voice_id')->nullable()->constrained('admin_voices')->onDelete('set null');
            $table->string('name');
            $table->text('script')->nullable();
            $table->integer('quantity')->default(0)->comment('0 means unlimited');
            $table->decimal('price_per_min', 8, 4); // Allows for partial cents like 0.0045
            $table->enum('status', ['request', 'training', 'active', 'maintenance', 'inactive'])->default('request');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('last_use')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Indexes for better performance
            $table->index('company_id');
            $table->index('status');
            $table->index(['company_id', 'status']);
            $table->index('did_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_agents');
    }
};
