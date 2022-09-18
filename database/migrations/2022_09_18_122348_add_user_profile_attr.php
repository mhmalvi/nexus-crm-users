<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserProfileAttr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_profile', function (Blueprint $table) {
            //
            $table->string('first_name')->after('user_id')->nullable();
            $table->string('last_name')->after('first_name')->nullable();
            $table->text('address')->after('last_name')->nullable();
            $table->string('region')->after('address')->nullable();
            $table->string('postcode')->after('region')->nullable();
            $table->text('work_experiences')->after('postcode')->nullable();
            $table->string('location')->after('work_experiences')->nullable();
            $table->string('profession')->after('location')->nullable();
            $table->string('secondary_contact')->after('profession')->nullable();
            $table->string('date_of_birth')->after('secondary_contact')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_profile', function (Blueprint $table) {
            //
        });
    }
}
