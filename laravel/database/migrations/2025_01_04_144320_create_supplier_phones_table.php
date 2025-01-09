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
        Schema::create('supplier_phones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id')->comment('Supplier ID reference');
            $table->string('number')->comment('Phone number');
            $table->tinyInteger('is_main')->default(0)->comment('Main phone number');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('supplier_id')->references('id')->on('suppliers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_phones');
    }
}; 