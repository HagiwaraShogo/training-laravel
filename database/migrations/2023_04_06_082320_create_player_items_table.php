<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlayerItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('player_items', function (Blueprint $table) {
            $table->unsignedBigInteger('playerId')->comment("プレイヤーID");
            $table->unsignedBigInteger('itemId')->comment("アイテムID");
            $table->integer('count')->comment("アイテムの個数");

            $table->primary(['playerId', 'itemId']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('player_items');
    }
}
