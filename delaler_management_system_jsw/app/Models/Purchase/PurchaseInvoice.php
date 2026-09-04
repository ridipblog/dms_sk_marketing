<?php

namespace App\Models\Purchase;

use App\Models\Company;
use App\Models\UserRoleCompany;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    protected $table = 'purchase_invoices';

    protected $fillable = [
        'invoice_no',
        'supplier_id',
        'company_id',
        'purchase_date',
        'due_date',
        'total_quantity',
        'total_amount',
        'total_gst_amount',
        'total_cgst_amount',
        'total_sgst_amount',
        'chargeable_amount',
        'no_of_goods',
        'status',
        'created_by'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'due_date' => 'date',
        'total_quantity' => 'decimal:3',
        'total_amount' => 'decimal:2',
        'total_gst_amount' => 'decimal:2',
        'total_cgst_amount' => 'decimal:2',
        'total_sgst_amount' => 'decimal:2',
        'chargeable_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function purchaseInvoiceDetails()
    {
        return $this->hasMany(PurchaseInvoiceDetail::class, 'purchase_invoice_id');
    }

    public function purchaseInvoicePayment()
    {
        return $this->hasOne(PurchaseInvoicePayment::class, 'purchase_invoice_id');
    }

    public function purchasePaymentTracks()
    {
        return $this->hasMany(PurchasePaymentTrack::class, 'purchase_invoice_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(UserRoleCompany::class, 'created_by');
    }
}
