<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Accounts\DebitNoteTrack;
use App\Modules\ReuseModule;
use App\Models\Dealers\Dealer;
use App\Models\Dealers\DealerCompany;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class DebitNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $companyId = session('active_company_id');
            $dealerCompanies = ReuseModule::getOwnedDealerCompanyQuery()->with('dealer')
                ->where('status', 1)->get();

            return view('accounts.debit_notes.index', compact('dealerCompanies'));
        } catch (\Throwable $e) {
            Log::error('Debit Note Index Error: ' . $e->getMessage());
            return view('accounts.debit_notes.index', [
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
            $activeMapId = session('active_map_id');

            $dealerId = null;
            if ($request->filled('dealer_id')) {
                try {
                    $dealerId = Crypt::decryptString($request->dealer_id);
                } catch (DecryptException $e) {
                    $dealerId = $request->dealer_id;
                }
            }

            $query = DebitNoteTrack::with(['paymentTrack.invoice.buyer.dealer'])
                ->whereHas('paymentTrack.invoice', function ($q) use ($activeMapId, $dealerId) {
                    $q->where('created_by', $activeMapId);
                    if ($dealerId) {
                        $q->where('buyer_id', $dealerId);
                    }
                });

            // Handle Date Range Filter on paymentTrack.transaction_date
            if ($request->filled('start_date')) {
                $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
                $query->whereHas('paymentTrack', function ($q) use ($startDate) {
                    $q->where('transaction_date', '>=', $startDate);
                });
            }

            if ($request->filled('end_date')) {
                $endDate = \Carbon\Carbon::parse($request->end_date)->endOfDay();
                $query->whereHas('paymentTrack', function ($q) use ($endDate) {
                    $q->where('transaction_date', '<=', $endDate);
                });
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('reason', 'like', "%{$search}%")
                        ->orWhereHas('paymentTrack.invoice', function ($sq) use ($search) {
                            $sq->where('invoice_no', 'like', "%{$search}%")
                                ->orWhere('user_invoice_no', 'like', "%{$search}%");
                        });
                });
            }

            // Total sums for filtered debit notes
            $totalsQuery = clone $query;
            $totalAmount = (float)$totalsQuery->sum('amount');
            $totalBaseAmount = (float)$totalsQuery->sum('base_amount');
            $totalGstAmount = (float)$totalsQuery->sum('gst_amount');
            $totalCount = (int)$totalsQuery->count();

            $debitNotes = $query->orderBy('id', 'desc')->paginate(10);

            $html = view('accounts.debit_notes.partials.list', compact(
                'debitNotes',
                'totalAmount',
                'totalBaseAmount',
                'totalGstAmount',
                'totalCount'
            ))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('Debit Note List Fetch Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading data.',
                'html' => '<div class="alert alert-danger text-center">Failed to load data. Please try again.</div>'
            ], 500);
        }
    }
}
