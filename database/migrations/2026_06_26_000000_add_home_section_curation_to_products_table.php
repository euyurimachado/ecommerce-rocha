<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('show_in_weight_loss')->default(false)->after('is_offer');
            $table->unsignedSmallInteger('weight_loss_sort_order')->nullable()->after('show_in_weight_loss');
            $table->boolean('show_in_energy')->default(false)->after('weight_loss_sort_order');
            $table->unsignedSmallInteger('energy_sort_order')->nullable()->after('show_in_energy');
            $table->boolean('show_in_mass_gain')->default(false)->after('energy_sort_order');
            $table->unsignedSmallInteger('mass_gain_sort_order')->nullable()->after('show_in_mass_gain');
            $table->boolean('show_in_whey_festival')->default(false)->after('mass_gain_sort_order');
            $table->unsignedSmallInteger('whey_festival_sort_order')->nullable()->after('show_in_whey_festival');
            $table->boolean('show_in_creatine_house')->default(false)->after('whey_festival_sort_order');
            $table->unsignedSmallInteger('creatine_house_sort_order')->nullable()->after('show_in_creatine_house');

            $table->index(['is_active', 'show_in_weight_loss', 'weight_loss_sort_order'], 'products_home_weight_loss_index');
            $table->index(['is_active', 'show_in_energy', 'energy_sort_order'], 'products_home_energy_index');
            $table->index(['is_active', 'show_in_mass_gain', 'mass_gain_sort_order'], 'products_home_mass_gain_index');
            $table->index(['is_active', 'show_in_whey_festival', 'whey_festival_sort_order'], 'products_home_whey_festival_index');
            $table->index(['is_active', 'show_in_creatine_house', 'creatine_house_sort_order'], 'products_home_creatine_house_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_home_weight_loss_index');
            $table->dropIndex('products_home_energy_index');
            $table->dropIndex('products_home_mass_gain_index');
            $table->dropIndex('products_home_whey_festival_index');
            $table->dropIndex('products_home_creatine_house_index');

            $table->dropColumn([
                'show_in_weight_loss',
                'weight_loss_sort_order',
                'show_in_energy',
                'energy_sort_order',
                'show_in_mass_gain',
                'mass_gain_sort_order',
                'show_in_whey_festival',
                'whey_festival_sort_order',
                'show_in_creatine_house',
                'creatine_house_sort_order',
            ]);
        });
    }
};
