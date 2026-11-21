<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('category')->nullable()->after('platform');
            $table->string('provider')->nullable()->after('category');
            $table->string('provider_service_id')->nullable()->unique()->after('provider');
            $table->string('provider_type')->nullable()->after('provider_service_id');
            $table->decimal('provider_rate', 16, 6)->nullable()->after('provider_type');
            $table->string('provider_currency', 3)->nullable()->after('provider_rate');
            $table->boolean('provider_refill')->nullable()->after('provider_currency');
            $table->boolean('provider_cancel')->nullable()->after('provider_refill');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique(['provider_service_id']);
            $table->dropColumn([
                'category', 'provider', 'provider_service_id', 'provider_type',
                'provider_rate', 'provider_currency', 'provider_refill', 'provider_cancel',
            ]);
        });
    }
};
