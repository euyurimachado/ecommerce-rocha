<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('mercado_pago_preference_id')->nullable()->after('payment_method');
            $table->string('mercado_pago_payment_id')->nullable()->after('mercado_pago_preference_id');
            $table->string('mercado_pago_status')->nullable()->after('mercado_pago_payment_id');
            $table->string('mercado_pago_status_detail')->nullable()->after('mercado_pago_status');
            $table->text('mercado_pago_init_point')->nullable()->after('mercado_pago_status_detail');
            $table->text('mercado_pago_sandbox_init_point')->nullable()->after('mercado_pago_init_point');
            $table->timestamp('payment_approved_at')->nullable()->after('privacy_accepted_at');

            $table->index('mercado_pago_preference_id');
            $table->index('mercado_pago_payment_id');
            $table->index('mercado_pago_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['mercado_pago_preference_id']);
            $table->dropIndex(['mercado_pago_payment_id']);
            $table->dropIndex(['mercado_pago_status']);
            $table->dropColumn([
                'mercado_pago_preference_id',
                'mercado_pago_payment_id',
                'mercado_pago_status',
                'mercado_pago_status_detail',
                'mercado_pago_init_point',
                'mercado_pago_sandbox_init_point',
                'payment_approved_at',
            ]);
        });
    }
};
