<?php

use App\Models\OrderStatus;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('status');
            $table->foreignId('client_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->foreignId('worker_id')->nullable();
            $table->foreignId('address_id')->nullable();
            $table->foreignId('card_id')->nullable();
            
            $table->string('phone')->nullable();
            $table->json('takeout_date')->nullable();
            $table->json('delivery_date')->nullable();
            $table->decimal('price');

            $table->json('order_info')->nullable();
            $table->json('payment_info')->nullable();
            $table->json('services')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
