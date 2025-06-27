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
        Schema::create('custom_fields', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('product_id'); // ✅ Add this
    $table->string('label');
    $table->string('type'); // text, select, checkbox, etc.
    $table->text('options')->nullable();
    $table->unsignedBigInteger('parent_id')->nullable();
    $table->string('dependency_value')->nullable();
    $table->timestamps();

    // Foreign Keys
    $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
    $table->foreign('parent_id')->references('id')->on('custom_fields')->onDelete('cascade');
});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
