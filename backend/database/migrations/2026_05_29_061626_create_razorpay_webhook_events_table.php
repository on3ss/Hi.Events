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
        Schema::create('razorpay_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('event_type');
            $table->string('entity_id');
            $table->string('status')->default('received');
            $table->unsignedInteger('retry_count')->default(0);

            $table->json('payload');
            $table->json('headers')
                ->nullable()
                ->after('payload');

            $table->string('signature')
                ->nullable()
                ->after('headers');

            $table->unsignedInteger('processing_time_ms')
                ->nullable()
                ->after('processed_at');
            $table->text('exception')->nullable();

            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('event_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('razorpay_webhook_events');
    }
};
