<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Accounts\CreditNoteTrack;
use App\Models\Accounts\VoucherType;
use App\Models\Accounts\PaymentTrack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Accounts\InvoicePayment;
use App\Models\Accounts\Invoice;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Carbon;
use App\Modules\ReuseModule;
use App\Models\Accounts\DebitNoteTrack;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Company;
use App\Models\Dealers\Dealer;
use App\Models\Dealers\DealerCompany;

class PaymentTrackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $voucherTypes = VoucherType::where('status', 1)->get();
            $companyId = session('active_company_id');
            $dealerCompanies = ReuseModule::getOwnedDealerCompanyQuery()->with('dealer')
                ->where('status', 1)->get();

            $selectedDealerCompanyId = null;
            if (request()->has('dealer_company_id')) {
                try {
                    $selectedDealerCompanyId = Crypt::decryptString(request('dealer_company_id'));
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    $selectedDealerCompanyId = request('dealer_company_id');
                }
            } elseif (request()->has('dealer_id')) {
                try {
                    $dealerId = Crypt::decryptString(request('dealer_id'));
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    $dealerId = request('dealer_id');
                }
                $dealerComp = ReuseModule::getOwnedDealerCompanyQuery()->where('dealer_id', $dealerId)->first();
                if ($dealerComp) {
                    $selectedDealerCompanyId = $dealerComp->id;
                }
            }

            return view('accounts.payment_tracks.index', compact('voucherTypes', 'dealerCompanies', 'selectedDealerCompanyId'));
        } catch (\Throwable $e) {
            Log::error('Payment Track Index Error: ' . $e->getMessage());
            return view('accounts.payment_tracks.index', [
                'errorMessage' => 'Something went wrong while loading the page.'
            ]);
        }
    }



    /**
     * List resources for AJAX.
     */
    public function list(Request $request)
    {
        try {
            $query = ReuseModule::getOwnedInvoiceQuery()
                ->withCount('paymentTracks')
                ->with(['buyer', 'invoicePayment'])
                ->where(function ($q) {
                    $q->where('invoice_status', 1)
                        ->orWhere('manual_amount_update', 1);
                });

            // Handle Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                        ->orWhere('user_invoice_no', 'like', "%{$search}%")
                        ->orWhereHas('buyer.dealer', function ($sq) use ($search) {
                            $sq->where('dealer_name', 'like', "%{$search}%");
                        });
                });
            }

            // Handle Dealer Filter
            if ($request->has('dealer_id') && !empty($request->dealer_id)) {
                try {
                    $dealerId = Crypt::decryptString($request->dealer_id);
                    $query->where('buyer_id', $dealerId);
                } catch (DecryptException $e) {
                    // Ignore invalid dealer id
                }
            }

            $invoices = $query->orderBy('id', 'desc')->paginate(10);

            $html = view('accounts.payment_tracks.partials.list', compact('invoices'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('Payment Track List Fetch Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading data.',
                'html' => '<div class="alert alert-danger text-center">Failed to load data. Please try again.</div>'
            ], 500);
        }
    }

    public function voucher($id)
    {
        try {
            $invoiceId = Crypt::decryptString($id);
            $invoice = ReuseModule::getOwnedInvoiceQuery($invoiceId)
                ->with(['buyer', 'invoicePayment'])
                ->firstOrFail();
            $voucherTypes = VoucherType::where('status', 1)->where('name', '!=', 'SALES')->get(); // exclude sales

            return view('accounts.payment_tracks.voucher', compact('invoice', 'voucherTypes'));
        } catch (DecryptException $e) {
            return view('accounts.payment_tracks.voucher', [
                'errorMessage' => 'Invalid Invoice ID.'
            ]);
        } catch (\Exception $e) {
            Log::error('Voucher View Error: ' . $e->getMessage());
            return view('accounts.payment_tracks.voucher', [
                'errorMessage' => 'Something went wrong.'
            ]);
        }
    }



    public function voucherList(Request $request)
    {
        try {
            $invoiceId = Crypt::decryptString($request->invoice_id);

            // Verify ownership using the reusable module
            $invoice = ReuseModule::getOwnedInvoiceQuery($invoiceId)->with('invoicePayment')->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized or invalid invoice.',
                    'html' => '<div class="alert alert-danger text-center">Unauthorized access.</div>'
                ], 403);
            }

            $query = PaymentTrack::with('voucherTypeModel')
                ->where('invoice_id', $invoice->id)
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc');

            // Handle Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_id', 'like', "%{$search}%")
                        ->orWhere('payment_mode', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhereHas('voucherTypeModel', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $vouchers = $query->get();

            $html = view('accounts.payment_tracks.partials.voucher_list', compact('vouchers', 'invoice'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('Voucher List Fetch Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading data.',
                'html' => '<div class="alert alert-danger text-center">Failed to load data. Please try again.</div>'
            ], 500);
        }
    }

    public function storeVoucher(Request $request)
    {
        try {
            $request->merge([
                'invoice_id' => Crypt::decryptString($request->invoice_id)
            ]);

            $request->validate([
                'invoice_id' => 'required|exists:invoices,id',
                'amount' => 'required|numeric|min:0',
                'voucher_type_id' => 'required|exists:voucher_types,id',
                'payment_mode' => 'required|string',
                'transaction_date' => 'required|date',
                'number_of_days' => 'nullable|integer|min:0'
            ]);

            $invoiceId = $request->invoice_id;

            // Verify invoice belongs to the active map id
            $invoice = ReuseModule::getOwnedInvoiceQuery($invoiceId)->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or unauthorized invoice.'
                ], 403);
            }

            DB::transaction(function () use ($request, $invoiceId, $invoice) {
                $voucherType = VoucherType::find($request->voucher_type_id);
                $voucherTypeName = strtolower($voucherType->name ?? '');

                // Automatically add 18% GST to Debit Note voucher amounts
                $baseAmount = $request->amount;
                $gstAmount = 0;
                $amount = $baseAmount;
                if (str_contains($voucherTypeName, 'debit note')) {
                    $gstAmount = round($baseAmount * 0.18, 2);
                    $amount = round($baseAmount + $gstAmount, 2);
                }

                $transactionId = 'TXN-' . strtoupper(uniqid());

                // Combine selected transaction date with current time timestamp (HH:MM:SS)
                $txDate = \Carbon\Carbon::parse($request->transaction_date ?? now())->setTimeFrom(now());

                $invoicePayment = InvoicePayment::where('invoice_id', $invoiceId)->lockForUpdate()->firstOrFail();

                // Calculate the exact current running balance
                $currentBalance = $invoicePayment->outstanding_amount;

                // Determine new balance after this voucher is applied
                $newBalance = $currentBalance;
                $paymentForMt = 0;

                if (str_contains($voucherTypeName, 'receipt') || str_contains($voucherTypeName, 'credit note') || str_contains($voucherTypeName, 'journal')) {
                    $newBalance -= $amount;
                    if (str_contains($voucherTypeName, 'receipt')) {
                        $chargeableAmount = $invoice->chargeable_amount > 0 ? $invoice->chargeable_amount : 1;
                        $paymentForMt = round(($invoice->total_quantity / $chargeableAmount) * $amount, 3);
                    }
                } elseif (str_contains($voucherTypeName, 'debit note') || str_contains($voucherTypeName, 'sale')) {
                    $newBalance += $amount;
                }

                $paymentTrack = PaymentTrack::create([
                    'invoice_id' => $invoiceId,
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                    'balance_amount' => $newBalance,
                    'payment_for_mt' => $paymentForMt,
                    'payment_mode' => $request->payment_mode,
                    'voucher_type_id' => $request->voucher_type_id,
                    'transaction_date' => $txDate,
                    'remarks' => $request->remarks,
                ]);

                if (str_contains($voucherTypeName, 'receipt')) {
                    $invoicePayment->paid_amount += $amount;
                    $invoicePayment->outstanding_amount -= $amount;

                    // Calculate Cash Discount (Credit Note) if applicable
                    if ($invoice->invoice_generate_date && ($invoice->manual_amount_update ?? 0) != 1) {
                        $generateDate = \Carbon\Carbon::parse($invoice->invoice_generate_date)->startOfDay();
                        $transactionDate = \Carbon\Carbon::parse($request->transaction_date)->startOfDay();
                        $daysSinceGenerate = $generateDate->diffInDays($transactionDate, false);

                        $companyId = session('active_company_id') ?? 1;

                        // Query the slab dynamically via ReuseModule
                        $slab = ReuseModule::getCashDiscountSlab($companyId, $daysSinceGenerate);

                        if ($slab && $paymentForMt > 0) {
                            $discountPerMt = $slab->discount_percent;
                            $discountAmount = round($paymentForMt * $discountPerMt, 2);

                            if ($discountAmount > 0) {
                                $creditVoucherType = VoucherType::where('name', 'like', '%credit note%')->first();

                                if ($creditVoucherType) {
                                    $creditPaymentTrack = PaymentTrack::create([
                                        'invoice_id' => $invoiceId,
                                        'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                                        'amount' => $discountAmount,
                                        'balance_amount' => $newBalance - $discountAmount,
                                        'payment_for_mt' => 0,
                                        'payment_mode' => $request->payment_mode,
                                        'voucher_type_id' => $creditVoucherType->id,
                                        'transaction_date' => $txDate,
                                        'remarks' => "Auto-generated Cash Discount at Rs {$discountPerMt}/MT ({$slab->slab_name})",
                                    ]);

                                    CreditNoteTrack::create([
                                        'payment_track_id' => $creditPaymentTrack->id,
                                        'parent_payment_track_id' => $paymentTrack->id,
                                        'cash_discount_slab_id' => $slab->id,
                                        'nos' => $daysSinceGenerate,
                                        'amount' => $discountAmount,
                                    ]);

                                    $newBalance -= $discountAmount;
                                    $invoicePayment->credit_note_amount += $discountAmount;
                                    $invoicePayment->outstanding_amount -= $discountAmount;
                                }
                            }
                        }
                    }

                    // Calculate late fine if applicable (including 18% GST)
                    if ($invoice->due_date) {
                        $transactionDate = \Carbon\Carbon::parse($request->transaction_date)->startOfDay();
                        $dueDate = \Carbon\Carbon::parse($invoice->due_date)->startOfDay();

                        if ($dueDate->lt($transactionDate)) {
                            $daysOverdue = $dueDate->diffInDays($transactionDate);
                            $baseFineAmount = round(($amount * 0.15 / 365) * $daysOverdue, 2);
                            $fineGstAmount = round($baseFineAmount * 0.18, 2);
                            $fineAmount = round($baseFineAmount + $fineGstAmount, 2); // Includes 18% GST

                            if ($fineAmount > 0) {
                                $debitVoucherType = VoucherType::where('name', 'like', '%debit note%')->first();

                                if ($debitVoucherType) {
                                    $finePaymentTrack = PaymentTrack::create([
                                        'invoice_id' => $invoiceId,
                                        'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                                        'amount' => $fineAmount,
                                        'balance_amount' => $newBalance + $fineAmount,
                                        'payment_for_mt' => 0,
                                        'payment_mode' => $request->payment_mode,
                                        'voucher_type_id' => $debitVoucherType->id,
                                        'transaction_date' => $request->transaction_date,
                                        'remarks' => "Auto-generated fine (15% p.a. + 18% GST) for {$daysOverdue} days overdue",
                                    ]);

                                    DebitNoteTrack::create([
                                        'payment_track_id' => $finePaymentTrack->id,
                                        'parent_payment_track_id' => $paymentTrack->id,
                                        'nos' => $daysOverdue,
                                        'amount' => $fineAmount,
                                        'base_amount' => $baseFineAmount,
                                        'gst_amount' => $fineGstAmount,
                                        'reason' => "Auto-generated fine (15% p.a. + 18% GST) for {$daysOverdue} days overdue",
                                    ]);

                                    $invoicePayment->debit_note_amount += $fineAmount;
                                    $invoicePayment->outstanding_amount += $fineAmount;
                                }
                            }
                        }
                    }
                } elseif (str_contains($voucherTypeName, 'debit note')) {
                    $invoicePayment->debit_note_amount += $amount;
                    $invoicePayment->outstanding_amount += $amount;
                    DebitNoteTrack::create([
                        'payment_track_id' => $paymentTrack->id,
                        'nos' => $request->number_of_days ?? 0,
                        'amount' => $amount,
                        'base_amount' => $baseAmount,
                        'gst_amount' => $gstAmount,
                        'reason' => $request->remarks ?? 'Manual Debit Note',
                    ]);
                } elseif (str_contains($voucherTypeName, 'credit note') || str_contains($voucherTypeName, 'journal')) {
                    $invoicePayment->credit_note_amount += $amount;
                    $invoicePayment->outstanding_amount -= $amount;

                    if (str_contains($voucherTypeName, 'credit note')) {
                        $companyId = session('active_company_id') ?? 1;
                        $slab = null;
                        if ($request->filled('number_of_days')) {
                            $slab = ReuseModule::getCashDiscountSlab($companyId, $request->number_of_days);
                        }

                        CreditNoteTrack::create([
                            'payment_track_id' => $paymentTrack->id,
                            'nos' => $request->number_of_days,
                            'amount' => $amount,
                            'cash_discount_slab_id' => $slab ? $slab->id : null,
                        ]);
                    }
                }

                if ($invoicePayment->outstanding_amount <= 0) {
                    $invoicePayment->clear_status = 'clear payment';
                } else {
                    $invoicePayment->clear_status = 'pending payment';
                }

                $invoicePayment->save();
            });

            return response()->json([
                'success' => true,
                'message' => 'Voucher added successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Store Voucher Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add voucher: ' . $e->getMessage()
            ], 500);
        }
    }

    public function editVoucher($id)
    {
        try {
            $trackId = Crypt::decryptString($id);
            $track = PaymentTrack::with('invoice', 'voucherTypeModel')->findOrFail($trackId);

            // Restrict direct edit of auto-generated child credit/debit notes
            $isLinked = false;
            $voucherTypeName = strtolower($track->voucherTypeModel->name ?? '');

            if (str_contains($voucherTypeName, 'credit note')) {
                $isLinked = CreditNoteTrack::where('payment_track_id', $track->id)
                    ->whereNotNull('parent_payment_track_id')
                    ->exists();
            } elseif (str_contains($voucherTypeName, 'debit note')) {
                $isLinked = DebitNoteTrack::where('payment_track_id', $track->id)
                    ->whereNotNull('parent_payment_track_id')
                    ->exists();
            }

            if ($isLinked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Auto-generated credit/debit notes linked to a receipt voucher cannot be modified directly. Please edit or delete the parent receipt voucher instead.'
                ], 403);
            }

            $nos = null;
            $debitNote = DebitNoteTrack::where('payment_track_id', $trackId)->first();
            if ($debitNote) {
                $nos = $debitNote->nos;
            } else {
                $creditNote = CreditNoteTrack::where('payment_track_id', $trackId)->first();
                if ($creditNote) {
                    $nos = $creditNote->nos;
                }
            }

            return response()->json([
                'success' => true,
                'voucher' => [
                    'id' => Crypt::encryptString($track->id),
                    'amount' => $track->amount,
                    'voucher_type_id' => $track->voucher_type_id,
                    'voucher_type_name' => $track->voucherTypeModel ? $track->voucherTypeModel->name : '',
                    'payment_mode' => $track->payment_mode,
                    'transaction_date' => $track->transaction_date ? Carbon::parse($track->transaction_date)->format('Y-m-d') : '',
                    'remarks' => $track->remarks,
                    'number_of_days' => $nos
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch voucher: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateVoucher(Request $request, $id)
    {
        try {
            $trackId = Crypt::decryptString($id);
            $track = PaymentTrack::with('voucherTypeModel')->findOrFail($trackId);

            // Restrict direct update of auto-generated child credit/debit notes
            $isLinked = false;
            $voucherTypeName = strtolower($track->voucherTypeModel->name ?? '');

            if (str_contains($voucherTypeName, 'credit note')) {
                $isLinked = CreditNoteTrack::where('payment_track_id', $track->id)
                    ->whereNotNull('parent_payment_track_id')
                    ->exists();
            } elseif (str_contains($voucherTypeName, 'debit note')) {
                $isLinked = DebitNoteTrack::where('payment_track_id', $track->id)
                    ->whereNotNull('parent_payment_track_id')
                    ->exists();
            }

            if ($isLinked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Auto-generated credit/debit notes linked to a receipt voucher cannot be modified directly. Please edit or delete the parent receipt voucher instead.'
                ], 403);
            }

            if ($track->voucherTypeModel && strtoupper($track->voucherTypeModel->name) == 'SALES') {
                return response()->json([
                    'success' => false,
                    'message' => 'SALES vouchers cannot be modified.'
                ], 403);
            }

            $request->validate([
                'amount' => 'required|numeric|min:0',
                'payment_mode' => 'required|string',
                'transaction_date' => 'required|date',
                'number_of_days' => 'nullable|integer|min:0'
            ]);

            DB::transaction(function () use ($request, $track) {
                $voucherTypeName = strtolower($track->voucherTypeModel->name ?? '');

                // Automatically add 18% GST to Debit Note voucher amounts
                $baseAmount = $request->amount;
                $gstAmount = 0;
                $updatedAmount = $baseAmount;
                if (str_contains($voucherTypeName, 'debit note')) {
                    $gstAmount = round($baseAmount * 0.18, 2);
                    $updatedAmount = round($baseAmount + $gstAmount, 2);
                }

                $track->update([
                    'amount' => $updatedAmount,
                    'payment_mode' => $request->payment_mode,
                    'transaction_date' => $request->transaction_date,
                    'remarks' => $request->remarks,
                    'modified_by' => session('active_map_id'),
                ]);

                if (str_contains($voucherTypeName, 'debit note')) {
                    DebitNoteTrack::updateOrCreate(
                        ['payment_track_id' => $track->id],
                        [
                            'nos' => $request->number_of_days ?? 0,
                            'amount' => $updatedAmount,
                            'base_amount' => $baseAmount,
                            'gst_amount' => $gstAmount,
                            'reason' => $request->remarks ?? 'Manual Debit Note',
                        ]
                    );
                } elseif (str_contains($voucherTypeName, 'credit note')) {
                    $companyId = session('active_company_id') ?? 1;
                    $slab = null;
                    if ($request->filled('number_of_days')) {
                        $slab = ReuseModule::getCashDiscountSlab($companyId, $request->number_of_days);
                    }

                    CreditNoteTrack::updateOrCreate(
                        ['payment_track_id' => $track->id],
                        [
                            'nos' => $request->number_of_days,
                            'amount' => $request->amount,
                            'cash_discount_slab_id' => $slab ? $slab->id : null,
                        ]
                    );
                } elseif (str_contains($voucherTypeName, 'receipt')) {
                    // 1. Delete existing auto-generated child credit/debit notes
                    $childCreditNotes = CreditNoteTrack::where('parent_payment_track_id', $track->id)->get();
                    $childCNPaymentTrackIds = $childCreditNotes->pluck('payment_track_id')->filter();

                    if ($childCNPaymentTrackIds->isNotEmpty()) {
                        PaymentTrack::whereIn('id', $childCNPaymentTrackIds)->update(['modified_by' => session('active_map_id')]);
                        PaymentTrack::whereIn('id', $childCNPaymentTrackIds)->delete();
                    }
                    CreditNoteTrack::where('parent_payment_track_id', $track->id)->delete();

                    $childDebitNotes = DebitNoteTrack::where('parent_payment_track_id', $track->id)->get();
                    $childDNPaymentTrackIds = $childDebitNotes->pluck('payment_track_id')->filter();

                    if ($childDNPaymentTrackIds->isNotEmpty()) {
                        PaymentTrack::whereIn('id', $childDNPaymentTrackIds)->update(['modified_by' => session('active_map_id')]);
                        PaymentTrack::whereIn('id', $childDNPaymentTrackIds)->delete();
                    }
                    DebitNoteTrack::where('parent_payment_track_id', $track->id)->delete();

                    // Calculate correct payment_for_mt based on new amount
                    $invoice = $track->invoice;
                    if ($invoice) {
                        $amount = $request->amount;
                        $chargeableAmount = $invoice->chargeable_amount > 0 ? $invoice->chargeable_amount : 1;
                        $paymentForMt = round(($invoice->total_quantity / $chargeableAmount) * $amount, 3);

                        $track->update([
                            'payment_for_mt' => $paymentForMt,
                        ]);

                        // Run recalculateLedger first to get the correct balance_amount for this receipt voucher
                        self::recalculateLedger($track->invoice_id);
                        $track->refresh();
                        $newBalance = $track->balance_amount;

                        // Calculate Cash Discount (Credit Note) if applicable
                        if ($invoice->invoice_generate_date && ($invoice->manual_amount_update ?? 0) != 1) {
                            $generateDate = \Carbon\Carbon::parse($invoice->invoice_generate_date)->startOfDay();
                            $transactionDate = \Carbon\Carbon::parse($request->transaction_date)->startOfDay();
                            $daysSinceGenerate = $generateDate->diffInDays($transactionDate, false);

                            $companyId = session('active_company_id') ?? 1;
                            $slab = ReuseModule::getCashDiscountSlab($companyId, $daysSinceGenerate);

                            if ($slab && $paymentForMt > 0) {
                                $discountPerMt = $slab->discount_percent;
                                $discountAmount = round($paymentForMt * $discountPerMt, 2);

                                if ($discountAmount > 0) {
                                    $creditVoucherType = VoucherType::where('name', 'like', '%credit note%')->first();

                                    if ($creditVoucherType) {
                                        $creditPaymentTrack = PaymentTrack::create([
                                            'invoice_id' => $track->invoice_id,
                                            'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                                            'amount' => $discountAmount,
                                            'balance_amount' => $newBalance - $discountAmount,
                                            'payment_for_mt' => 0,
                                            'payment_mode' => $request->payment_mode,
                                            'voucher_type_id' => $creditVoucherType->id,
                                            'transaction_date' => $request->transaction_date,
                                            'remarks' => "Auto-generated Cash Discount at Rs {$discountPerMt}/MT ({$slab->slab_name})",
                                        ]);

                                        CreditNoteTrack::create([
                                            'payment_track_id' => $creditPaymentTrack->id,
                                            'parent_payment_track_id' => $track->id,
                                            'cash_discount_slab_id' => $slab->id,
                                            'nos' => $daysSinceGenerate,
                                            'amount' => $discountAmount,
                                        ]);

                                        $newBalance -= $discountAmount;
                                    }
                                }
                            }
                        }

                        // Calculate late fine if applicable (including 18% GST)
                        if ($invoice->due_date) {
                            $transactionDate = \Carbon\Carbon::parse($request->transaction_date)->startOfDay();
                            $dueDate = \Carbon\Carbon::parse($invoice->due_date)->startOfDay();

                            if ($dueDate->lt($transactionDate)) {
                                $daysOverdue = $dueDate->diffInDays($transactionDate);
                                $baseFineAmount = round(($amount * 0.15 / 365) * $daysOverdue, 2);
                                $fineGstAmount = round($baseFineAmount * 0.18, 2);
                                $fineAmount = round($baseFineAmount + $fineGstAmount, 2); // Includes 18% GST

                                if ($fineAmount > 0) {
                                    $debitVoucherType = VoucherType::where('name', 'like', '%debit note%')->first();

                                    if ($debitVoucherType) {
                                        $finePaymentTrack = PaymentTrack::create([
                                            'invoice_id' => $track->invoice_id,
                                            'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                                            'amount' => $fineAmount,
                                            'balance_amount' => $newBalance + $fineAmount,
                                            'payment_for_mt' => 0,
                                            'payment_mode' => $request->payment_mode,
                                            'voucher_type_id' => $debitVoucherType->id,
                                            'transaction_date' => $request->transaction_date,
                                            'remarks' => "Auto-generated fine (15% p.a. + 18% GST) for {$daysOverdue} days overdue",
                                        ]);

                                        DebitNoteTrack::create([
                                            'payment_track_id' => $finePaymentTrack->id,
                                            'parent_payment_track_id' => $track->id,
                                            'nos' => $daysOverdue,
                                            'amount' => $fineAmount,
                                            'base_amount' => $baseFineAmount,
                                            'gst_amount' => $fineGstAmount,
                                            'reason' => "Auto-generated fine (15% p.a. + 18% GST) for {$daysOverdue} days overdue",
                                        ]);

                                        $newBalance += $fineAmount;
                                    }
                                }
                            }
                        }
                    }
                }

                self::recalculateLedger($track->invoice_id);
            });

            return response()->json([
                'success' => true,
                'message' => 'Voucher updated successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update voucher: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteVoucher($id)
    {
        try {
            $trackId = Crypt::decryptString($id);
            $track = PaymentTrack::with('voucherTypeModel')->findOrFail($trackId);

            if ($track->voucherTypeModel && strtoupper($track->voucherTypeModel->name) == 'SALES') {
                return response()->json([
                    'success' => false,
                    'message' => 'SALES vouchers cannot be deleted.'
                ], 403);
            }

            $invoiceId = $track->invoice_id;

            DB::transaction(function () use ($track, $invoiceId) {
                $voucherTypeName = strtolower($track->voucherTypeModel->name ?? '');

                if (str_contains($voucherTypeName, 'debit note')) {
                    // Delete only this specific debit note track
                    DebitNoteTrack::where('payment_track_id', $track->id)->delete();
                } elseif (str_contains($voucherTypeName, 'credit note')) {
                    // Restore scheme amount back to dealer if this credit note was generated from a scheme discount
                    if (($track->scheme_amount ?? 0) > 0) {
                        $invoice = Invoice::find($invoiceId);
                        if ($invoice && $invoice->buyer_id) {
                            $dealerCompany = DealerCompany::find($invoice->buyer_id);
                            if ($dealerCompany) {
                                $dealerCompany->total_scheme_amount = ($dealerCompany->total_scheme_amount ?? 0) + (float)$track->scheme_amount;
                                $dealerCompany->save();
                            }
                        }
                    }
                    CreditNoteTrack::where('payment_track_id', $track->id)->delete();
                } elseif (str_contains($voucherTypeName, 'receipt')) {
                    // Deleting a parent receipt voucher also deletes its auto-generated child credit & debit notes
                    $childCreditNotes = CreditNoteTrack::where('parent_payment_track_id', $track->id)->get();
                    $childCNPaymentTrackIds = $childCreditNotes->pluck('payment_track_id')->filter();

                    if ($childCNPaymentTrackIds->isNotEmpty()) {
                        $cnPaymentTracks = PaymentTrack::whereIn('id', $childCNPaymentTrackIds)->get();
                        foreach ($cnPaymentTracks as $cnTrack) {
                            if (($cnTrack->scheme_amount ?? 0) > 0) {
                                $invoice = Invoice::find($invoiceId);
                                if ($invoice && $invoice->buyer_id) {
                                    $dealerCompany = DealerCompany::find($invoice->buyer_id);
                                    if ($dealerCompany) {
                                        $dealerCompany->total_scheme_amount = ($dealerCompany->total_scheme_amount ?? 0) + (float)$cnTrack->scheme_amount;
                                        $dealerCompany->save();
                                    }
                                }
                            }
                        }
                        PaymentTrack::whereIn('id', $childCNPaymentTrackIds)->update(['modified_by' => session('active_map_id')]);
                        PaymentTrack::whereIn('id', $childCNPaymentTrackIds)->delete();
                    }
                    CreditNoteTrack::where('parent_payment_track_id', $track->id)->delete();

                    $childDebitNotes = DebitNoteTrack::where('parent_payment_track_id', $track->id)->get();
                    $childDNPaymentTrackIds = $childDebitNotes->pluck('payment_track_id')->filter();

                    if ($childDNPaymentTrackIds->isNotEmpty()) {
                        PaymentTrack::whereIn('id', $childDNPaymentTrackIds)->update(['modified_by' => session('active_map_id')]);
                        PaymentTrack::whereIn('id', $childDNPaymentTrackIds)->delete();
                    }
                    DebitNoteTrack::where('parent_payment_track_id', $track->id)->delete();
                }

                $track->modified_by = session('active_map_id');
                $track->save();
                $track->delete();

                self::recalculateLedger($invoiceId);
            });

            return response()->json([
                'success' => true,
                'message' => 'Voucher deleted successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Voucher Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete voucher: ' . $e->getMessage()
            ], 500);
        }
    }

    public static function recalculateLedger($invoiceId)
    {
        $invoicePayment = InvoicePayment::where('invoice_id', $invoiceId)->first();
        if (!$invoicePayment) return;

        $tracks = PaymentTrack::where('invoice_id', $invoiceId)
            ->with('voucherTypeModel')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = 0;
        $totalPaid = 0;
        $totalDebitNote = 0;
        $totalCreditNote = 0;

        foreach ($tracks as $track) {
            $voucherTypeName = strtolower($track->voucherTypeModel->name ?? '');

            if (str_contains($voucherTypeName, 'receipt') || str_contains($voucherTypeName, 'credit note') || str_contains($voucherTypeName, 'journal') || str_contains($voucherTypeName, 'advance')) {
                $runningBalance -= $track->amount;
                if (str_contains($voucherTypeName, 'receipt') || str_contains($voucherTypeName, 'advance')) {
                    $totalPaid += $track->amount;
                } elseif (str_contains($voucherTypeName, 'credit note') || str_contains($voucherTypeName, 'journal')) {
                    $totalCreditNote += $track->amount;
                }
            } elseif (str_contains($voucherTypeName, 'debit note') || str_contains($voucherTypeName, 'sale')) {
                $runningBalance += $track->amount;
                if (str_contains($voucherTypeName, 'debit note')) {
                    $totalDebitNote += $track->amount;
                }
            }

            $track->update(['balance_amount' => $runningBalance]);
        }

        $invoicePayment->update([
            'paid_amount' => $totalPaid,
            'debit_note_amount' => $totalDebitNote,
            'credit_note_amount' => $totalCreditNote,
            'outstanding_amount' => $runningBalance,
            'clear_status' => $runningBalance <= 0 ? 'clear payment' : 'pending payment',
        ]);
    }

    /**
     * Export Voucher History to Excel/CSV.
     */
    public function exportVouchersExcel(Request $request, $id)
    {
        try {
            $invoiceId = Crypt::decryptString($id);
            $invoice = ReuseModule::getOwnedInvoiceQuery($invoiceId)->with(['buyer.dealer', 'invoicePayment'])->firstOrFail();

            $query = PaymentTrack::with('voucherTypeModel')
                ->where('invoice_id', $invoice->id)
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc');

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_id', 'like', "%{$search}%")
                        ->orWhere('payment_mode', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhereHas('voucherTypeModel', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $vouchers = $query->cursor();

            $fileName = 'vouchers_invoice_' . $invoice->invoice_no . '_' . date('Ymd_His') . '.csv';

            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=" . $fileName,
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $columns = [
                '#',
                'Transaction ID',
                'Voucher Type',
                'Payment Mode',
                'Transaction Date',
                'Amount (₹)',
                'Running Balance (₹)',
                'Remarks'
            ];

            $callback = function () use ($vouchers, $columns) {
                $file = fopen('php://output', 'w');
                // Output UTF-8 BOM so Microsoft Excel renders Rupee symbol (₹) correctly
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($file, $columns);

                foreach ($vouchers as $index => $voucher) {
                    fputcsv($file, [
                        $index + 1,
                        $voucher->transaction_id ?? 'N/A',
                        $voucher->voucherTypeModel->name ?? 'Sales',
                        ucwords(str_replace('_', ' ', $voucher->payment_mode ?? '-')),
                        $voucher->transaction_date ? \Carbon\Carbon::parse($voucher->transaction_date)->format('d-M-Y h:i A') : '-',
                        number_format($voucher->amount, 2, '.', ''),
                        number_format($voucher->balance_amount, 2, '.', ''),
                        $voucher->remarks ?? '-'
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            Log::error('Export Vouchers Excel Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to export vouchers.');
        }
    }

    /**
     * Export Voucher History to PDF.
     */
    public function exportVouchersPdf(Request $request, $id)
    {
        try {
            $invoiceId = Crypt::decryptString($id);
            $invoice = ReuseModule::getOwnedInvoiceQuery($invoiceId)->with(['buyer.dealer', 'invoicePayment', 'createdBy.company'])->firstOrFail();

            $query = PaymentTrack::with('voucherTypeModel')
                ->where('invoice_id', $invoice->id)
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc');

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_id', 'like', "%{$search}%")
                        ->orWhere('payment_mode', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhereHas('voucherTypeModel', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $vouchers = $query->get();

            $pdf = Pdf::loadView('accounts.payment_tracks.pdf.voucher_history', compact('invoice', 'vouchers'));
            return $pdf->stream('vouchers_invoice_' . $invoice->invoice_no . '.pdf');
        } catch (\Throwable $e) {
            Log::error('Export Vouchers PDF Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to export vouchers PDF.');
        }
    }
}
