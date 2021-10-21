<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_users', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('token_password')->nullable();
            $table->string('extra_email')->nullable();
            $table->string('referral_code')->nullable();
            $table->string('country');
            $table->string('categories_id')->nullable();
            $table->enum('is_active', ['0', '1'])->default('0');
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
        Schema::dropIfExists('bank_users');
    }
}
