<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFinancialRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('financial_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('isExpense');
            $table->dateTime('due_date');
            $table->decimal('value');
            $table->string('currency',3)->default('EUR'); // ISO 4217 Currency Code
            $table->string('payment_type')->default('bank_account');
            $table->boolean('recursive');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->boolean('isActive')->default(1);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('financial_records');
    }
}
