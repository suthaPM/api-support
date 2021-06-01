<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CareerSupportModelsOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('career_support_models_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('participant_id');
            $table->integer('webinar_id');
            $table->string('token')->nullable();
            $table->string('order_id')->nullable();
            $table->string('status')->default('order');
            // $table->timestamps();
            $table->bigInteger("creator_id")->nullable(); //
            $table->bigInteger("modifier_id")->nullable(); //
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('created')->useCurrent();
            $table->timestamp('modified')->nullable()->useCurrentOnUpdate();

            $table->foreign('webinar_id')->references('id')->on('career_support_models_webinarnormal');
            $table->foreign('participant_id')->references('id')->on('career_support_models_normal_studentparticipants');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('career_support_models_orders');
    }
}