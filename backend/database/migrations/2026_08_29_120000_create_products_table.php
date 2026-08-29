<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('price', 10, 2)->nullable();
            $table->text('image_url')->nullable();
            // The product page the row was scraped from. Unique so the scraper can
            // updateOrCreate against it instead of inserting duplicates.
            $table->string('source_url')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
