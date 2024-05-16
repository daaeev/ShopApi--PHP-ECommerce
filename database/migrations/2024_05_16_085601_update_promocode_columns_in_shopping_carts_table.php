<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shopping_carts', function (Blueprint $table) {
            $table->string('promocode')->nullable()->after('currency');
            $table->tinyInteger('promocode_discount_percent', unsigned: true)->nullable()->after('promocode_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shopping_carts', function (Blueprint $table) {
            $table->dropColumn('promocode');
            $table->dropColumn('promocode_discount_percent');
        });
    }
};
