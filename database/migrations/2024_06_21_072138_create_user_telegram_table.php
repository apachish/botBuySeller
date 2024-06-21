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
        Schema::create('user_telegram', function (Blueprint $table) {
            $table->id();
            $table->string("telegram_id");
            $table->string("first_name");
            $table->string("last_name");
            $table->string("fullName");
            $table->string("mobile");
            $table->string("username");
            $table->string("language_code");
            $table->boolean("status");
            $table->dateTime("verify_two");
            $table->unsignedBigInteger('agent_id');
            $table->foreign('agent_id')->references('id')->on('user_telegram');
            $table->enum("role",["customer","colleague"])->nullable();
            $table->boolean("change_menu");
            $table->dateTime("accept_rule");
            $table->boolean("is_bot")->nullable();
            $table->boolean("is_premium")->nullable();
            $table->boolean("can_join_groups")->nullable();
            $table->boolean("can_read_all_group_messages")->nullable();
            $table->string("supports_inline_queries")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_telegram');
    }
};
