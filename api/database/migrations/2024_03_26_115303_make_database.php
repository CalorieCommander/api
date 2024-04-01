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
        Schema::create('histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('data');
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
        });
        Schema::create('dates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('user_weight');
            $table->date('date');
            $table->rememberToken();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users');
        });
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('calories_per_km');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('dates_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('date_id');
            $table->unsignedBigInteger('activity_id');
            $table->decimal('kilometers');
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('date_id')->references('id')->on('dates');
            $table->foreign('activity_id')->references('id')->on('activities');
        });

        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand');
            $table->integer('calories_per_gram');
            $table->string('nutrition_grade');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('nutrients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meal_id');
            $table->decimal('energy');
            $table->decimal('calories');
            $table->decimal('carbohydrates');
            $table->decimal('fats');
            $table->decimal('sugar');
            $table->decimal('fibres');
            $table->decimal('protein');
            $table->decimal('salt');
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('meal_id')->references('id')->on('meals');
        });
        Schema::create('dates_meals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('date_id');
            $table->unsignedBigInteger('meal_id');
            $table->decimal('amount');
            $table->integer('grams');
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('date_id')->references('id')->on('dates');
            $table->foreign('meal_id')->references('id')->on('meals');
        });
        Schema::create('meals_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('date_id');
            $table->unsignedBigInteger('activity_id');
            $table->decimal('kilometers');
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('date_id')->references('id')->on('dates');
            $table->foreign('activity_id')->references('id')->on('activities');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('histories', function (Blueprint $table) {
            $table->dropForeign('histories_user_id_foreign');
        });
        Schema::table('dates_activities', function (Blueprint $table) {
            $table->dropForeign('dates_activities_date_id_foreign');
            $table->dropForeign('dates_activities_activity_id_foreign');
        });
        Schema::table('dates_meals', function (Blueprint $table) {
            $table->dropForeign('dates_meals_date_id_foreign');
            $table->dropForeign('dates_meals_meal_id_foreign');
        });
        Schema::table('dates', function (Blueprint $table) {
            $table->dropForeign('dates_user_id_foreign');
        });
        Schema::table('nutrients', function (Blueprint $table) {
            $table->dropForeign('nutrients_meal_id_foreign');
        });
        Schema::dropIfExists('histories');
        Schema::dropIfExists('dates_activities');
        Schema::dropIfExists('dates_meals');
        Schema::dropIfExists('nutrients');
        Schema::dropIfExists('meals');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('dates');
    }
};
