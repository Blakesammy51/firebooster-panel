<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('provider_order_id')->nullable()->unique()->after('notes');
            $table->string('provider_status')->nullable()->after('provider_order_id');
            $table->decimal('provider_charge', 16, 6)->nullable()->after('provider_status');
            $table->string('provider_currency', 3)->nullable()->after('provider_charge');
            $table->unsignedBigInteger('provider_start_count')->nullable()->after('provider_currency');
            $table->unsignedBigInteger('provider_remains')->nullable()->after('provider_start_count');
            $table->timestamp('provider_submitted_at')->nullable()->after('provider_remains');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['provider_order_id']);
            $table->dropColumn([
                'provider_order_id', 'provider_status', 'provider_charge',
                'provider_currency', 'provider_start_count', 'provider_remains',
                'provider_submitted_at',
            ]);
        });
    }
};
