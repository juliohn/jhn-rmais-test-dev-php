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
        Schema::create('supplier_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id')->comment('Suppliers ID references');
            $table->tinyInteger('is_main')->comment('main address?');
            $table->string('cep')->comment('CEP');
            $table->string('street')->comment('Street');
            $table->string('number')->comment('Number address');
            $table->string('complement')->nullable()->comment('Complement address');
            $table->string('city')->comment('City');
            $table->string('state')->comment('State');
            $table->string('neighborhood')->comment('Neighborhood');
            $table->text('reference')->nullable()->comment('Reference of address');
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
        Schema::dropIfExists('supplier_addresses');
    }
};
