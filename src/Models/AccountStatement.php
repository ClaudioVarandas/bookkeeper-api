<?php

namespace Bookkeeper\Models;

use Illuminate\Database\Eloquent\Model;

class AccountStatement extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'due_date',
        'value',
        'currency',
        'payment_type',
        'isExpense',
    ];

}
