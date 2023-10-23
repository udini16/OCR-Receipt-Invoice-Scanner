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
        Schema::create('line_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_name'); // Change the column name to item_name
            $table->integer('quantity')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->unsignedBigInteger('receipts_id'); // This will be a foreign key
            $table->foreign('receipts_id')->references('id')->on('receipts');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_items');
    }
};
