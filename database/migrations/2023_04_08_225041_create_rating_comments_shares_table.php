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
        Schema::create('rating_comments_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('feed_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('rating')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
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
        Schema::dropIfExists('rating_comments_shares');
    }
};
