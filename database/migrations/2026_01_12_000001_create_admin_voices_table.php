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
        Schema::create('admin_voices', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Voice name/identifier
            $table->text('transcript')->nullable();
            $table->text('scene_prompt')->nullable();
            $table->string('ref_audio')->nullable(); // Reference audio file path
            $table->boolean('ref_audio_in_system_message')->default(false);
            $table->string('chunk_method')->default('default'); // Chunking method
            $table->integer('chunk_max_word_num')->default(100);
            $table->integer('chunk_max_num_turns')->default(10);
            $table->integer('generation_chunk_buffer_size')->default(512);
            $table->decimal('temperature', 3, 2)->default(0.70); // 0.00 to 9.99
            $table->integer('top_k')->default(50);
            $table->decimal('top_p', 3, 2)->default(0.95); // 0.00 to 1.00
            $table->integer('ras_win_len')->default(10);
            $table->integer('ras_win_max_num_repeat')->default(3);
            $table->integer('seed')->nullable(); // Random seed for reproducibility
            $table->string('status')->default('active'); // active, inactive
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_voices');
    }
};
