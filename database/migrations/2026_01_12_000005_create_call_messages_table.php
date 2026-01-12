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
        Schema::create('call_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained('calls')->onDelete('cascade');
            $table->enum('type', ['user', 'bot']); // Message type
            $table->string('audio')->nullable(); // Audio file path
            $table->text('text'); // Message text/transcription
            $table->timestamps();

            // Indexes for better query performance
            $table->index('call_id');
            $table->index(['call_id', 'created_at']); // For chronological message retrieval
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_messages');
    }
};
