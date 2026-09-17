<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('requires_shipping')->default(true)->after('weight');
            $table->decimal('weight_kg', 8, 3)->nullable()->after('requires_shipping');
            $table->decimal('width_cm', 8, 2)->nullable()->after('weight_kg');
            $table->decimal('height_cm', 8, 2)->nullable()->after('width_cm');
            $table->decimal('length_cm', 8, 2)->nullable()->after('height_cm');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('customer_tax_id', 14)->nullable()->after('customer_phone');
            $table->string('shipping_provider')->nullable()->after('shipping_cents');
            $table->string('shipping_service_id')->nullable()->after('shipping_provider');
            $table->string('shipping_service_name')->nullable()->after('shipping_service_id');
            $table->string('shipping_carrier')->nullable()->after('shipping_service_name');
            $table->unsignedInteger('shipping_price_cents')->nullable()->after('shipping_carrier');
            $table->unsignedSmallInteger('shipping_estimated_days')->nullable()->after('shipping_price_cents');
            $table->string('shipping_external_id')->nullable()->after('shipping_estimated_days');
            $table->string('shipping_invoice_key', 44)->nullable()->after('shipping_external_id');
            $table->string('tracking_code')->nullable()->after('shipping_external_id');
            $table->string('shipping_status')->default('pending')->after('tracking_code');
            $table->string('shipping_external_status')->nullable()->after('shipping_status');
            $table->text('shipping_label_url')->nullable()->after('shipping_external_status');
            $table->json('shipping_quote_snapshot')->nullable()->after('shipping_label_url');
            $table->timestamp('shipping_posted_at')->nullable()->after('shipping_quote_snapshot');
            $table->timestamp('shipping_delivered_at')->nullable()->after('shipping_posted_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'customer_tax_id',
                'shipping_provider', 'shipping_service_id', 'shipping_service_name', 'shipping_carrier',
                'shipping_price_cents', 'shipping_estimated_days', 'shipping_external_id', 'shipping_invoice_key', 'tracking_code',
                'shipping_status', 'shipping_external_status', 'shipping_label_url', 'shipping_quote_snapshot',
                'shipping_posted_at', 'shipping_delivered_at',
            ]);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['requires_shipping', 'weight_kg', 'width_cm', 'height_cm', 'length_cm']);
        });
    }
};
