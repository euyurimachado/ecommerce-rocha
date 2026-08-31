<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('payment_provider')->nullable()->after('payment_method');
            $table->string('payment_status')->nullable()->after('payment_provider');
            $table->uuid('payment_idempotency_key')->nullable()->unique()->after('payment_status');
            $table->longText('pix_qr_code')->nullable()->after('mercado_pago_status_detail');
            $table->longText('pix_qr_code_base64')->nullable()->after('pix_qr_code');
            $table->timestamp('pix_expires_at')->nullable()->after('pix_qr_code_base64');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['payment_idempotency_key']);
            $table->dropColumn(['payment_provider', 'payment_status', 'payment_idempotency_key', 'pix_qr_code', 'pix_qr_code_base64', 'pix_expires_at']);
        });
    }
};
