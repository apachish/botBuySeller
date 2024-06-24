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
        Schema::create('transfer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('user_telegram');
            $table->string("type");
            $table->integer("number");
            $table->float("price",22,2);
            $table->enum("status",\App\Models\Transfer::getStatus());
            $table->string("message");
            $table->string("description");
            $table->integer("message_id");
            $table->dateTime("date");
            $table->text("message_request");
            $table->text("message_request_me");
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer');
    }
};
