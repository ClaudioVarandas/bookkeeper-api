<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountStatementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_statements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('financial_record_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('isExpense');
            $table->dateTime('due_date');
            $table->decimal('value');
            $table->string('currency',3)->default('EUR'); // ISO 4217 Currency Code
            $table->string('payment_type')->default('bank_account');
            $table->string('comment')->nullable();
            $table->enum('payment_status', ['paid', 'pending', 'overdue'])->default('pending');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('financial_record_id')->references('id')->on('financial_records')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_statements');
    }
}
