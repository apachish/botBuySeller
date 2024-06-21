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
        Schema::create('request_transfer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->foreign('request_id')->references('id')->on('user_telegram');
            $table->string("transfer_id");
            $table->unsignedBigInteger('transfer_id');
            $table->foreign('transfer_id')->references('id')->on('transfer');
            $table->integer("number");
            $table->float("price");
            $table->string("status");
            $table->string("type");
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_transfer');
    }
};
