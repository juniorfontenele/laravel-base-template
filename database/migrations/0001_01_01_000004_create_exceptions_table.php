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
        Schema::create('exceptions', function (Blueprint $table) {
            $table->defaultCharset();
            $table->id();
            $table->string('exception_class')->index();
            $table->longText('message')->nullable();
            $table->longText('user_message')->nullable();
            $table->text('file')->nullable();
            $table->unsignedBigInteger('line')->nullable()->index();
            $table->unsignedBigInteger('code')->default(0)->index();
            $table->unsignedBigInteger('status_code')->nullable()->index();
            $table->string('error_id')->nullable()->index();
            $table->string('correlation_id')->nullable()->index();
            $table->string('request_id')->nullable()->index();
            $table->string('app_version')->nullable()->index();
            $table->string('app_commit')->nullable();
            $table->string('app_build_date')->nullable();
            $table->string('app_role')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->boolean('is_retryable')->index();
            $table->longText('stack_trace')->nullable();
            $table->json('context')->nullable();
            $table->string('previous_exception_class')->nullable()->index();
            $table->longText('previous_message')->nullable();
            $table->text('previous_file')->nullable();
            $table->unsignedBigInteger('previous_line')->nullable()->index();
            $table->unsignedBigInteger('previous_code')->nullable()->default(0)->index();
            $table->longText('previous_stack_trace')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exceptions');
    }
};
