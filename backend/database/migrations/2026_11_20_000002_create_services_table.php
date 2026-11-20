<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('platform', 40);
            $table->text('description');
            $table->decimal('price_per_1000', 12, 2);
            $table->unsignedInteger('minimum_quantity')->default(1);
            $table->unsignedInteger('maximum_quantity')->default(10000);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
