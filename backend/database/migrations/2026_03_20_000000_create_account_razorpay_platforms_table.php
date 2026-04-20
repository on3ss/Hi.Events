<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_razorpay_platforms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('razorpay_account_id')->unique();
            $table->timestamp('razorpay_setup_completed_at')->nullable();
            $table->jsonb('razorpay_account_details')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->index(['account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_razorpay_platforms');
    }
};
