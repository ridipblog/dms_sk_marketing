<?php

namespace App\Models\Accounts;

use App\Models\Dealers\Dealer;
use App\Models\UserRoleCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'invoice_no',
        'user_invoice_no',
        'buyer_id',
        'ship_to',
        'tax_type',
        'created_by',
        'company_bank_detail_id',
        'total_quantity',
        'total_amount',
        'chargeable_amount',
        'total_gst_amount',
        'total_cgst_amount',
        'total_sgst_amount',
        'total_igst_amount',
        'cgst',
        'sgst',
        'igst',
        'gst',
        'round_of',
        'invoice_generate_date',
        'due_date',
        'vehicle_no',
        'delivery_note',
        'destination',
        'no_of_goods',
        'invoice_status',
        'manual_amount_update',
        'whatsapp_reminder_stage'
    ];

    protected $casts = [
        'round_of' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'chargeable_amount' => 'decimal:2',
    ];

    public function buyer()
    {
        return $this->belongsTo(\App\Models\Dealers\DealerCompany::class, 'buyer_id');
    }

    public function shipTo()
    {
        return $this->belongsTo(\App\Models\Dealers\DealerCompany::class, 'ship_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(UserRoleCompany::class, 'created_by');
    }

    public function bankDetail()
    {
        return $this->belongsTo(\App\Models\CompanyBankDetail::class, 'company_bank_detail_id');
    }

    public function invoiceDetails()
    {
        return $this->hasMany(InvoiceDetail::class, 'invoice_id');
    }

    public function paymentTracks()
    {
        return $this->hasMany(PaymentTrack::class, 'invoice_id');
    }

    public function invoicePayment()
    {
        return $this->hasOne(InvoicePayment::class, 'invoice_id');
    }
}
