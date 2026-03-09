<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Order Status
            $table->enum('status', [
                'pending',
                'processing', 
                'shipped', 
                'delivered', 
                'cancelled'
            ])->default('pending');
            
            // Payment
            $table->enum('payment_method', [
                'cash', 
                'credit_card', 
                'gcash'
            ])->default('cash');
            
            $table->enum('payment_status', [
                'unpaid', 
                'paid', 
                'refunded'
            ])->default('unpaid');

            // Order Details
            $table->decimal('total_amount', 10, 2);
            $table->text('shipping_address');
            $table->text('notes')->nullable();
            $table->string('tracking_number')->nullable();

            // Timestamps
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};