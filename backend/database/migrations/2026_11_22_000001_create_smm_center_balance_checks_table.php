<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smm_center_balance_checks', function (Blueprint $table): void {
            $table->id();
            $table->decimal('amount', 18, 8)->nullable();
            $table->char('currency', 3)->nullable();
            $table->string('status', 16);
            $table->string('error_message')->nullable();
            $table->timestamp('checked_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smm_center_balance_checks');
    }
};
