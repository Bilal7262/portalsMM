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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('cnic_passport_url')->nullable();
            $table->string('business_name');
            $table->text('business_address')->nullable();
            $table->boolean('verify_email')->default(false);
            $table->boolean('verify_phone')->default(false);
            $table->string('status')->default('active'); // active, inactive, pending, suspended
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
