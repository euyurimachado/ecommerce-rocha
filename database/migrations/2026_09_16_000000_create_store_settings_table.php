<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Minha loja');
            $table->string('legal_name')->nullable();
            $table->string('short_name')->nullable();
            $table->string('slogan')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('logo_dark_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('pwa_icon_path')->nullable();
            $table->string('primary_color', 7)->default('#0098D7');
            $table->string('primary_dark_color', 7)->default('#005D8F');
            $table->string('secondary_color', 7)->default('#A7A9AC');
            $table->string('accent_color', 7)->default('#F59E0B');
            $table->string('background_color', 7)->default('#F8FAFC');
            $table->string('font_family')->default('Ubuntu');
            $table->string('tax_id')->nullable();
            $table->string('state_registration')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('street')->nullable();
            $table->string('number')->nullable();
            $table->string('complement')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('country', 2)->default('BR');
            $table->timestamp('installed_at')->nullable();
            $table->string('installation_version')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
