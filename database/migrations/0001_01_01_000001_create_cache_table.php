<?php

declare(strict_types = 1);

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
        Schema::create(config('cache.stores.database.table'), function (Blueprint $table) {
            $table->defaultCharset();
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create(config('cache.stores.database.lock_table'), function (Blueprint $table) {
            $table->defaultCharset();
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('cache.stores.database.table'));
        Schema::dropIfExists(config('cache.stores.database.lock_table'));
    }
};
