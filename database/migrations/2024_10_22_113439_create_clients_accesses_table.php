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
        Schema::create('clients_accesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable(false);
            $table->string('type')->nullable(false);
            $table->json('credentials')->nullable();
            $table->timestamp('created_at')->nullable(false);

            $table->index('client_id', 'idx-clients_accesses-client_id');
            $table->foreign('client_id', 'fk-clients_accesses-client_id')
                ->references('id')
                ->on('clients')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients_accesses');
    }
};
