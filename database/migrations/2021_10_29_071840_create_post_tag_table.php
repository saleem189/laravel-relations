<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostTagTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_tag', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('post_id');
            $table->bigInteger('tag_id');
            $table->string('status');
            /**
             * we dont use Timestamp coloum thats why we coomet it 
             * we will use it onward to update Pivot table
             * $table->timestamps();
             */
            
             /**
              * now we will update table and un-commet
              * $table->timestamps();
              * and add additional coloums init
              * we will rollback the last migration in the terminal
              * by php artisan migrate:rollback
              * and the php artisan migrate
              * to enable timestamps we will define it in Post Model
              * withTimestamps()
              */
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
        Schema::dropIfExists('post_tag');
    }
}
