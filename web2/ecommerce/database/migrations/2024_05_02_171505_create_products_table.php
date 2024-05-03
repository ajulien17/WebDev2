<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->integer('section_id');
            $table->integer('category_id');
            $table->integer('brand_id');
            $table->integer('vendor_id'); 
            $table->integer('admin_id'); 
            $table->string('admin_type'); 
            $table->string('product_name');
            $table->string('product_code');
            $table->string('product_color');
            $table->float('product_price');
            $table->float('product_discount');
            $table->integer('product_weight');
            $table->string('product_image')->nullable();
            $table->string('product_video')->nullable();
            $table->string('group_code')->nullable(); 
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable(); 
            $table->string('meta_keywords')->nullable();  
            $table->string('meta_description')->nullable(); 
            $table->enum('is_featured', ['No', 'Yes']); 
            $table->enum('is_bestseller', ['No', 'Yes']);
            $table->tinyInteger('status');

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
        Schema::dropIfExists('products');
    }
};
