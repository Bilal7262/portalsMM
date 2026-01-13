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
        Schema::create('voice_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voice_id')->constrained('admin_voices')->onDelete('cascade');
            $table->foreignId('company_agent_id')->nullable()->constrained('company_agents')->onDelete('cascade');
            $table->string('cache_key')->nullable();
            $table->text('message')->nullable();
            $table->boolean('hit')->default(false);
            $table->integer('hit_count')->default(0);
            $table->softDeletes();
            $table->timestamps();

            // Index for faster lookups
            $table->index(['voice_id', 'company_agent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_cache');
    }
};
