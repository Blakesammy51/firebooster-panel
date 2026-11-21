<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('provider_fx_rate', 16, 4)->nullable()->after('provider_currency');
            $table->string('provider_fx_source', 160)->nullable()->after('provider_fx_rate');
            $table->timestamp('provider_fx_captured_at')->nullable()->after('provider_fx_source');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'provider_fx_rate',
                'provider_fx_source',
                'provider_fx_captured_at',
            ]);
        });
    }
};
