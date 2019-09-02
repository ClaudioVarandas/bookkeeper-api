<?php

namespace Bookkeeper\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialRecord extends Model
{
    protected $casts = [
        'due_date' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'due_date',
        'value',
        'currency',
        'payment_type',
        'recursive',
        'isExpense',
        'started_at',
        'ended_at'
    ];

    public function accountStatement()
    {
        return $this->hasMany(AccountStatement::class);
    }
}
