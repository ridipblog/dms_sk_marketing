<?php

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoicePayment extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'invoice_id',
        'outstanding_amount',
        'paid_amount',
        'debit_note_amount',
        'credit_note_amount',
        'clear_status'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
