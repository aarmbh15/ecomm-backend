<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('address_id')->constrained('user_addresses')->onDelete('restrict');
            $table->string('order_number')->unique();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_charge', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->enum('status', ['pending','confirmed','processing','shipped','delivered','cancelled','returned'])->default('pending');
            $table->enum('payment_status', ['pending','paid','failed','refunded'])->default('pending');
            $table->string('payment_gateway'); // razorpay, stripe, etc.
            $table->string('payment_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};