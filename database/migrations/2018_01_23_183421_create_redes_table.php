<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRedesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('redes', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('nome');
            $table->ipAddress('iprede');
            $table->ipAddress('gateway');
            $table->string('dns');
            $table->string('ntp')->nullable();
            $table->string('netbios')->nullable();
            $table->integer('cidr');
            $table->integer('vlan');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('equipamentos');
        Schema::dropIfExists('redes');
    }
}
