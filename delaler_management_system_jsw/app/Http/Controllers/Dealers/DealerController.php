<?php

namespace App\Http\Controllers\Dealers;

use App\Http\Controllers\Controller;
use App\Models\Dealers\Dealer;
use App\Models\Dealers\DealerCompany;
use App\Models\UserRoleCompany;
use App\Modules\ReuseModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class DealerController extends Controller
{
    /**
     * Display a listing of the dealers.
     */
    public function index()
    {
        $companyId = session('active_company_id');
        $asos = $this->getCompanyAsos($companyId);
        return view('dealers.index', compact('asos'));
    }

    /**
     * Helper to get ASO users for the given company context.
     */
    private function getCompanyAsos($companyId)
    {
        if (!$companyId) {
            return collect();
        }

        return UserRoleCompany::where('company_id', $companyId)
            ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASO%'))
            ->with('user')
            ->get()
            ->map(function ($map) {
                return [
                    'id' => $map->user_id,
                    'name' => $map->user->name ?? 'N/A',
                ];
            });
    }

    /**
     * Fetch dealers list via AJAX POST.
     */
    public function list(Request $request)
    {
        try {
            $companyId = session('active_company_id');
            $hasAsoColumn = Schema::hasColumn('dealer_companies', 'aso_id');

            $query = Dealer::join('dealer_companies', 'dealers.id', '=', 'dealer_companies.dealer_id')
                ->where('dealer_companies.company_id', $companyId);

            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            // Both from_date AND to_date are required for the date range filter
            $hasDateRange = !empty($fromDate) && !empty($toDate);
            $targetDate = $hasDateRange ? $toDate : date('Y-m-d');
            $targetDateQuoted = DB::getPdo()->quote($targetDate);

            if ($hasAsoColumn) {
                $query->leftJoin('users as asos', 'dealer_companies.aso_id', '=', 'asos.id')
                    ->select([
                        'dealers.*',
                        'dealer_companies.opening_balance',
                        'dealer_companies.id as dealer_company_id',
                        'dealer_companies.aso_id',
                        'asos.name as aso_name',
                        DB::raw('(SELECT COALESCE(SUM(i.chargeable_amount), 0) + COALESCE(SUM(ip.debit_note_amount), 0) 
                                  FROM invoices i 
                                  JOIN invoice_payments ip ON i.id = ip.invoice_id 
                                  WHERE i.buyer_id = dealer_companies.id AND i.deleted_at IS NULL AND ip.deleted_at IS NULL) as total_debit'),
                        DB::raw('(SELECT COALESCE(SUM(ip.paid_amount), 0) + COALESCE(SUM(ip.credit_note_amount), 0) 
                                  FROM invoices i 
                                  JOIN invoice_payments ip ON i.id = ip.invoice_id 
                                  WHERE i.buyer_id = dealer_companies.id AND i.deleted_at IS NULL AND ip.deleted_at IS NULL) as total_credit'),
                        DB::raw('(SELECT DATEDIFF(' . $targetDateQuoted . ', MIN(i.invoice_generate_date)) 
                                  FROM invoices i 
                                  JOIN invoice_payments ip ON i.id = ip.invoice_id 
                                  WHERE i.buyer_id = dealer_companies.id 
                                  AND i.deleted_at IS NULL AND ip.deleted_at IS NULL 
                                  AND ip.outstanding_amount > 0) as oldest_outstanding_days'),
                        DB::raw('(SELECT DATEDIFF(' . $targetDateQuoted . ', MAX(pt.transaction_date)) 
                                  FROM payment_tracks pt 
                                  JOIN invoices i ON pt.invoice_id = i.id 
                                  LEFT JOIN voucher_types vt ON pt.voucher_type_id = vt.id 
                                  WHERE i.buyer_id = dealer_companies.id 
                                  AND pt.deleted_at IS NULL 
                                  AND i.deleted_at IS NULL 
                                  AND DATE(pt.transaction_date) <= ' . $targetDateQuoted . '
                                  AND (vt.name LIKE "%receipt%" OR vt.name LIKE "%credit%" OR vt.name LIKE "%payment%" OR UPPER(vt.name) NOT IN ("SALES", "DEBIT NOTE"))) as days_since_last_payment'),
                    ]);
            } else {
                $query->select([
                    'dealers.*',
                    'dealer_companies.opening_balance',
                    'dealer_companies.id as dealer_company_id',
                    DB::raw('NULL as aso_id'),
                    DB::raw('NULL as aso_name'),
                    DB::raw('(SELECT COALESCE(SUM(i.chargeable_amount), 0) + COALESCE(SUM(ip.debit_note_amount), 0) 
                              FROM invoices i 
                              JOIN invoice_payments ip ON i.id = ip.invoice_id 
                              WHERE i.buyer_id = dealer_companies.id AND i.deleted_at IS NULL AND ip.deleted_at IS NULL) as total_debit'),
                    DB::raw('(SELECT COALESCE(SUM(ip.paid_amount), 0) + COALESCE(SUM(ip.credit_note_amount), 0) 
                              FROM invoices i 
                              JOIN invoice_payments ip ON i.id = ip.invoice_id 
                              WHERE i.buyer_id = dealer_companies.id AND i.deleted_at IS NULL AND ip.deleted_at IS NULL) as total_credit'),
                    DB::raw('(SELECT DATEDIFF(' . $targetDateQuoted . ', MIN(i.invoice_generate_date)) 
                              FROM invoices i 
                              JOIN invoice_payments ip ON i.id = ip.invoice_id 
                              WHERE i.buyer_id = dealer_companies.id 
                              AND i.deleted_at IS NULL AND ip.deleted_at IS NULL 
                              AND ip.outstanding_amount > 0) as oldest_outstanding_days'),
                    DB::raw('(SELECT DATEDIFF(' . $targetDateQuoted . ', MAX(pt.transaction_date)) 
                              FROM payment_tracks pt 
                              JOIN invoices i ON pt.invoice_id = i.id 
                              LEFT JOIN voucher_types vt ON pt.voucher_type_id = vt.id 
                              WHERE i.buyer_id = dealer_companies.id 
                              AND pt.deleted_at IS NULL 
                              AND i.deleted_at IS NULL 
                              AND DATE(pt.transaction_date) <= ' . $targetDateQuoted . '
                              AND (vt.name LIKE "%receipt%" OR vt.name LIKE "%credit%" OR vt.name LIKE "%payment%" OR UPPER(vt.name) NOT IN ("SALES", "DEBIT NOTE"))) as days_since_last_payment'),
                ]);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search, $hasAsoColumn) {
                    $q->where('dealers.dealer_name', 'like', "%{$search}%")
                        ->orWhere('dealers.dealer_code', 'like', "%{$search}%")
                        ->orWhere('dealers.email', 'like', "%{$search}%")
                        ->orWhere('dealers.phone', 'like', "%{$search}%");

                    if ($hasAsoColumn) {
                        $q->orWhere('asos.name', 'like', "%{$search}%");
                    }
                });
            }

            if ($request->has('status') && !empty($request->status)) {
                $query->where('dealers.status', $request->status);
            }

            // Date Range Filter Logic: When both from_date and to_date are provided, show dealers who have NOT made any payment (Receipt, Credit Note, Payment) in this date range AND have outstanding invoice payment required
            if ($hasDateRange) {
                $query->whereNotExists(function ($sub) use ($fromDate, $toDate) {
                    $sub->select(DB::raw(1))
                        ->from('payment_tracks as pt')
                        ->join('invoices as inv', 'pt.invoice_id', '=', 'inv.id')
                        ->leftJoin('voucher_types as vt', 'pt.voucher_type_id', '=', 'vt.id')
                        ->whereColumn('inv.buyer_id', 'dealer_companies.id')
                        ->whereNull('pt.deleted_at')
                        ->whereNull('inv.deleted_at')
                        ->whereBetween(DB::raw('DATE(pt.transaction_date)'), [$fromDate, $toDate])
                        ->where(function ($vq) {
                            $vq->where('vt.name', 'like', '%receipt%')
                                ->orWhere('vt.name', 'like', '%credit%')
                                ->orWhere('vt.name', 'like', '%payment%')
                                ->orWhereNotIn(DB::raw('UPPER(vt.name)'), ['SALES', 'DEBIT NOTE']);
                        });
                });

                // Must have outstanding invoice payment required
                $query->where(function ($q) {
                    $q->whereRaw('((SELECT COALESCE(SUM(i.chargeable_amount), 0) + COALESCE(SUM(ip.debit_note_amount), 0) 
                                    FROM invoices i JOIN invoice_payments ip ON i.id = ip.invoice_id 
                                    WHERE i.buyer_id = dealer_companies.id AND i.deleted_at IS NULL AND ip.deleted_at IS NULL) 
                                   - 
                                   (SELECT COALESCE(SUM(ip.paid_amount), 0) + COALESCE(SUM(ip.credit_note_amount), 0) 
                                    FROM invoices i JOIN invoice_payments ip ON i.id = ip.invoice_id 
                                    WHERE i.buyer_id = dealer_companies.id AND i.deleted_at IS NULL AND ip.deleted_at IS NULL) 
                                   + COALESCE(dealer_companies.opening_balance, 0)) > 0')
                        ->orWhereExists(function ($invSub) {
                            $invSub->select(DB::raw(1))
                                ->from('invoices as inv2')
                                ->join('invoice_payments as ip2', 'inv2.id', '=', 'ip2.invoice_id')
                                ->whereColumn('inv2.buyer_id', 'dealer_companies.id')
                                ->whereNull('inv2.deleted_at')
                                ->whereNull('ip2.deleted_at')
                                ->where('ip2.outstanding_amount', '>', 0);
                        });
                });
            }

            // Get the page number from request, defaulting to 1
            $page = $request->input('page', 1);

            $dealers = $query->latest('dealers.created_at')->paginate(10, ['*'], 'page', $page);

            // Compute outstanding balance dynamically for each dealer and calculate days from last transaction date to to_date / today
            $dealers->getCollection()->transform(function ($dealer) use ($targetDate) {
                $opening = (float)($dealer->opening_balance ?? 0);
                $debit = (float)($dealer->total_debit ?? 0);
                $credit = (float)($dealer->total_credit ?? 0);

                $dealer->outstanding_balance = $debit - $credit + $opening;

                if ($dealer->outstanding_balance > 0) {
                    if ($dealer->days_since_last_payment !== null) {
                        $dealer->days = max(0, (int)$dealer->days_since_last_payment);
                    } elseif ($dealer->oldest_outstanding_days !== null) {
                        $dealer->days = max(0, (int)$dealer->oldest_outstanding_days);
                    } elseif (!empty($dealer->created_at)) {
                        $created = \Carbon\Carbon::parse($dealer->created_at)->startOfDay();
                        $target = \Carbon\Carbon::parse($targetDate)->startOfDay();
                        $dealer->days = max(0, $created->diffInDays($target, false));
                    } else {
                        $dealer->days = 0;
                    }
                } else {
                    $dealer->days = 0;
                }

                return $dealer;
            });

            $html = view('dealers.partials.table', compact('dealers'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('Dealer List Fetch Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading dealers.',
                'html' => '<div class="alert alert-danger">Failed to load dealers. Please try again.</div>'
            ], 500);
        }
    }

    /**
     * Store a newly created dealer in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'dealer_name' => 'required|string|max:255',
                'dealer_code' => 'required|string|max:255|unique:dealers,dealer_code',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:10|unique:dealers,phone',
                'pan_number' => 'required|string|max:50|unique:dealers,pan_number',
                'gst_number' => 'required|string|max:50|unique:dealers,gst_number',
                'aso_id' => [
                    'nullable',
                    Rule::exists('user_role_companies', 'user_id')->where(function ($query) {
                        $query->where('company_id', session('active_company_id'));
                    }),
                ],
                'address' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 200);
            }

            // Custom validation: prevent duplicate company-wise for gst, pan
            if (!session()->has('active_company_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Active company context is missing. Please select a company.'
                ], 200);
            }

            $validatedData = $validator->validated();
            unset($validatedData['aso_id']);

            DB::beginTransaction();

            $dealer = Dealer::create($validatedData);

            // Map the newly created dealer to the user's active company context
            if (session()->has('active_company_id')) {
                DealerCompany::create([
                    'dealer_id' => $dealer->id,
                    'company_id' => session('active_company_id'),
                    'aso_id' => $request->aso_id ?: null,
                    'created_by' => Auth::id(),
                    'status' => 'active'
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Dealer created successfully.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Dealer Creation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the dealer.'
            ], 500);
        }
    }

    /**
     * Show the specified dealer with company relations.
     */
    public function show($encrypted_id)
    {
        try {
            $id = Crypt::decryptString($encrypted_id);
            $companyId = session('active_company_id');
            $dealer = ReuseModule::getOwnedDealerQuery($id)->with(['dealerCompany' => function ($query) use ($companyId) {
                $query->where('company_id', $companyId)->with(['company', 'aso']);
            }])->firstOrFail();

            $activeDealerCompany = $dealer->dealerCompany->first();

            if ($activeDealerCompany) {
                $totals = DB::table('invoices')
                    ->join('invoice_payments', 'invoices.id', '=', 'invoice_payments.invoice_id')
                    ->where('invoices.buyer_id', $activeDealerCompany->id)
                    ->whereNull('invoices.deleted_at')
                    ->whereNull('invoice_payments.deleted_at')
                    ->selectRaw('
                        COALESCE(SUM(invoices.chargeable_amount), 0) as total_chargeable,
                        COALESCE(SUM(invoice_payments.debit_note_amount), 0) as total_debit_note,
                        COALESCE(SUM(invoice_payments.paid_amount), 0) as total_paid,
                        COALESCE(SUM(invoice_payments.credit_note_amount), 0) as total_credit_note
                    ')
                    ->first();

                $activeDealerCompany->total_debit_amount = ($totals->total_chargeable ?? 0) + ($totals->total_debit_note ?? 0);
                $activeDealerCompany->total_credit_amount = ($totals->total_paid ?? 0) + ($totals->total_credit_note ?? 0);
            }

            return response()->json([
                'success' => true,
                'dealer' => $dealer
            ]);
        } catch (\Throwable $e) {
            Log::error('Dealer Show Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the dealer details.'
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified dealer.
     */
    public function edit($encrypted_id)
    {
        try {
            $id = Crypt::decryptString($encrypted_id);
            $companyId = session('active_company_id');
            $dealer = ReuseModule::getOwnedDealerQuery($id)->firstOrFail();
            $dealerCompany = DealerCompany::where('dealer_id', $dealer->id)
                ->where('company_id', $companyId)
                ->first();
            $asos = $this->getCompanyAsos($companyId);

            return response()->json([
                'success' => true,
                'dealer' => $dealer,
                'aso_id' => $dealerCompany ? $dealerCompany->aso_id : null,
                'asos' => $asos
            ]);
        } catch (\Throwable $e) {
            Log::error('Dealer Edit Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the dealer.'
            ], 500);
        }
    }

    /**
     * Update the specified dealer in storage.
     */
    public function update(Request $request, $encrypted_id)
    {
        try {
            $id = Crypt::decryptString($encrypted_id);
            $dealer = ReuseModule::getOwnedDealerQuery($id)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'dealer_name' => 'required|string|max:255',
                'dealer_code' => 'required|string|max:100|unique:dealers,dealer_code,' . $dealer->id,
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:10|unique:dealers,phone,' . $dealer->id,
                'pan_number' => 'required|string|max:50|unique:dealers,pan_number,' . $dealer->id,
                'gst_number' => 'required|string|max:50|unique:dealers,gst_number,' . $dealer->id,
                'aso_id' => [
                    'nullable',
                    Rule::exists('user_role_companies', 'user_id')->where(function ($query) {
                        $query->where('company_id', session('active_company_id'));
                    }),
                ],
                'address' => 'nullable|string',
                'status' => 'required|in:active,inactive,blocked'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 200);
            }

            if (!session()->has('active_company_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Active company context is missing. Please select a company.'
                ], 200);
            }

            DB::beginTransaction();

            $dealer->update([
                'dealer_name' => $request->dealer_name,
                'dealer_code' => $request->dealer_code,
                'email' => $request->email,
                'phone' => $request->phone,
                'pan_number' => $request->pan_number,
                'gst_number' => $request->gst_number,
                'address' => $request->address,
                'status' => $request->status,
            ]);

            $existingRecord = DealerCompany::where('dealer_id', $dealer->id)
                ->where('company_id', session('active_company_id'))
                ->first();

            if ($existingRecord) {
                $existingRecord->update([
                    'aso_id' => $request->aso_id ?: null,
                    'status' => $request->status,
                    'updated_by' => Auth::id(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Dealer updated successfully.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Dealer Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the dealer.'
            ], 500);
        }
    }

    /**
     * Export dealers list to Excel/CSV.
     */
    public function export(Request $request)
    {
        try {
            $companyId = session('active_company_id');
            $hasAsoColumn = Schema::hasColumn('dealer_companies', 'aso_id');

            $query = Dealer::join('dealer_companies', 'dealers.id', '=', 'dealer_companies.dealer_id')
                ->where('dealer_companies.company_id', $companyId);

            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $hasDateRange = !empty($fromDate) && !empty($toDate);
            $targetDate = $hasDateRange ? $toDate : date('Y-m-d');
            $targetDateQuoted = DB::getPdo()->quote($targetDate);

            if ($hasAsoColumn) {
                $query->leftJoin('users as asos', 'dealer_companies.aso_id', '=', 'asos.id')
                    ->select([
                        'dealers.*',
                        'dealer_companies.opening_balance',
                        'dealer_companies.id as dealer_company_id',
                        'dealer_companies.aso_id',
                        'asos.name as aso_name',
                        DB::raw('(SELECT COALESCE(SUM(i.chargeable_amount), 0) + COALESCE(SUM(ip.debit_note_amount), 0) 
                                  FROM invoices i 
                                  JOIN invoice_payments ip ON i.id = ip.invoice_id 
                                  WHERE i.buyer_id = dealer_companies.id AND i.deleted_at IS NULL AND ip.deleted_at IS NULL) as total_debit'),
                        DB::raw('(SELECT COALESCE(SUM(ip.paid_amount), 0) + COALESCE(SUM(ip.credit_note_amount), 0) 
                                  FROM invoices i 
                                  JOIN invoice_payments ip ON i.id = ip.invoice_id 
                                  WHERE i.buyer_id = dealer_companies.id AND i.deleted_at IS NULL AND ip.deleted_at IS NULL) as total_credit'),
                        DB::raw('(SELECT DATEDIFF(' . $targetDateQuoted . ', MIN(i.invoice_generate_date)) 
                                  FROM invoices i 
                                  JOIN invoice_payments ip ON i.id = ip.invoice_id 
                                  WHERE i.buyer_id = dealer_companies.id 
                                  AND i.deleted_at IS NULL AND ip.deleted_at IS NULL 
                                  AND ip.outstanding_amount > 0) as oldest_outstanding_days'),
                        DB::raw('(SELECT DATEDIFF(' . $targetDateQuoted . ', MAX(pt.transaction_date)) 
                                  FROM payment_tracks pt 
                                  JOIN invoices i ON pt.invoice_id = i.id 
                                  LEFT JOIN voucher_types vt ON pt.voucher_type_id = vt.id 
                                  WHERE i.buyer_id = dealer_companies.id 
                                  AND pt.deleted_at IS NULL 
                                  AND i.deleted_at IS NULL 
                                  AND DATE(pt.transaction_date) <= ' . $targetDateQuoted . '
                                  AND (vt.name LIKE "%receipt%" OR vt.name LIKE "%credit%" OR vt.name LIKE "%payment%" OR UPPER(vt.name) NOT IN ("SALES", "DEBIT NOTE"))) as days_since_last_payment'),
                    ]);
            } else {
                $query->select([
                    'dealers.*',
                    'dealer_companies.opening_balance',
                    'dealer_companies.id as dealer_company_id',
                    DB::raw('NULL as aso_id'),
                    DB::raw('NULL as aso_name'),
                    DB::raw('(SELECT COALESCE(SUM(i.chargeable_amount), 0) + COALESCE(SUM(ip.debit_note_amount), 0) 
                              FROM invoices i 
                              JOIN invoice_payments ip ON i.id = ip.invoice_id 
                              WHERE i.buyer_id = dealer_companies.id AND i.deleted_at IS NULL AND ip.deleted_at IS NULL) as total_debit'),
                    DB::raw('(SELECT COALESCE(SUM(ip.paid_amount), 0) + COALESCE(SUM(ip.credit_note_amount), 0) 
                              FROM invoices i 
                              JOIN invoice_payments ip ON i.id = ip.invoice_id 
                              WHERE i.buyer_id = dealer_companies.id AND i.deleted_at IS NULL AND ip.deleted_at IS NULL) as total_credit'),
                    DB::raw('(SELECT DATEDIFF(' . $targetDateQuoted . ', MIN(i.invoice_generate_date)) 
                              FROM invoices i 
                              JOIN invoice_payments ip ON i.id = ip.invoice_id 
                              WHERE i.buyer_id = dealer_companies.id 
                              AND i.deleted_at IS NULL AND ip.deleted_at IS NULL 
                              AND ip.outstanding_amount > 0) as oldest_outstanding_days'),
                    DB::raw('(SELECT DATEDIFF(' . $targetDateQuoted . ', MAX(pt.transaction_date)) 
                              FROM payment_tracks pt 
                              JOIN invoices i ON pt.invoice_id = i.id 
                              LEFT JOIN voucher_types vt ON pt.voucher_type_id = vt.id 
                              WHERE i.buyer_id = dealer_companies.id 
                              AND pt.deleted_at IS NULL 
                              AND i.deleted_at IS NULL 
                              AND DATE(pt.transaction_date) <= ' . $targetDateQuoted . '
                              AND (vt.name LIKE "%receipt%" OR vt.name LIKE "%credit%" OR vt.name LIKE "%payment%" OR UPPER(vt.name) NOT IN ("SALES", "DEBIT NOTE"))) as days_since_last_payment'),
                ]);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search, $hasAsoColumn) {
                    $q->where('dealers.dealer_name', 'like', "%{$search}%")
                        ->orWhere('dealers.dealer_code', 'like', "%{$search}%")
                        ->orWhere('dealers.email', 'like', "%{$search}%")
                        ->orWhere('dealers.phone', 'like', "%{$search}%");

                    if ($hasAsoColumn) {
                        $q->orWhere('asos.name', 'like', "%{$search}%");
                    }
                });
            }

            if ($request->has('status') && !empty($request->status)) {
                $query->where('dealers.status', $request->status);
            }

            if ($hasDateRange) {
                $query->whereNotExists(function ($sub) use ($fromDate, $toDate) {
                    $sub->select(DB::raw(1))
                        ->from('payment_tracks as pt')
                        ->join('invoices as inv', 'pt.invoice_id', '=', 'inv.id')
                        ->leftJoin('voucher_types as vt', 'pt.voucher_type_id', '=', 'vt.id')
                        ->whereColumn('inv.buyer_id', 'dealer_companies.id')
                        ->whereNull('pt.deleted_at')
                        ->whereNull('inv.deleted_at')
                        ->whereBetween(DB::raw('DATE(pt.transaction_date)'), [$fromDate, $toDate])
                        ->where(function ($vq) {
                            $vq->where('vt.name', 'like', '%receipt%')
                                ->orWhere('vt.name', 'like', '%credit%')
                                ->orWhere('vt.name', 'like', '%payment%')
                                ->orWhereNotIn(DB::raw('UPPER(vt.name)'), ['SALES', 'DEBIT NOTE']);
                        });
                });

                $query->where(function ($q) {
                    $q->whereRaw('((SELECT COALESCE(SUM(i.chargeable_amount), 0) + COALESCE(SUM(ip.debit_note_amount), 0) 
                                    FROM invoices i JOIN invoice_payments ip ON i.id = ip.invoice_id 
                                    WHERE i.buyer_id = dealer_companies.id AND i.deleted_at IS NULL AND ip.deleted_at IS NULL) 
                                   - 
                                   (SELECT COALESCE(SUM(ip.paid_amount), 0) + COALESCE(SUM(ip.credit_note_amount), 0) 
                                    FROM invoices i JOIN invoice_payments ip ON i.id = ip.invoice_id 
                                    WHERE i.buyer_id = dealer_companies.id AND i.deleted_at IS NULL AND ip.deleted_at IS NULL) 
                                   + COALESCE(dealer_companies.opening_balance, 0)) > 0')
                        ->orWhereExists(function ($invSub) {
                            $invSub->select(DB::raw(1))
                                ->from('invoices as inv2')
                                ->join('invoice_payments as ip2', 'inv2.id', '=', 'ip2.invoice_id')
                                ->whereColumn('inv2.buyer_id', 'dealer_companies.id')
                                ->whereNull('inv2.deleted_at')
                                ->whereNull('ip2.deleted_at')
                                ->where('ip2.outstanding_amount', '>', 0);
                        });
                });
            }

            $dealers = $query->latest('dealers.created_at')->cursor();

            $fileName = 'dealers_list_' . date('Ymd_His') . '.csv';

            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=" . $fileName,
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $columns = [
                'Dealer Code',
                'Dealer Name',
                'Assigned ASO',
                'Email',
                'Phone',
                'Total Debits',
                'Total Credits',
                'Outstanding Balance',
                'Days',
                'Status'
            ];

            $callback = function () use ($dealers, $columns, $targetDate) {
                $file = fopen('php://output', 'w');
                // Output UTF-8 BOM so Microsoft Excel renders Rupee symbol (₹) correctly
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($file, $columns);

                foreach ($dealers as $dealer) {
                    $opening = (float)($dealer->opening_balance ?? 0);
                    $debit = (float)($dealer->total_debit ?? 0);
                    $credit = (float)($dealer->total_credit ?? 0);

                    $outstanding = $debit - $credit + $opening;

                    $days = 0;
                    if ($outstanding > 0) {
                        if ($dealer->days_since_last_payment !== null) {
                            $days = max(0, (int)$dealer->days_since_last_payment);
                        } elseif ($dealer->oldest_outstanding_days !== null) {
                            $days = max(0, (int)$dealer->oldest_outstanding_days);
                        } elseif (!empty($dealer->created_at)) {
                            $created = \Carbon\Carbon::parse($dealer->created_at)->startOfDay();
                            $target = \Carbon\Carbon::parse($targetDate)->startOfDay();
                            $days = max(0, $created->diffInDays($target, false));
                        }
                    }

                    $outstandingType = $outstanding > 0 ? ' (Dr)' : ($outstanding < 0 ? ' (Cr)' : '');
                    $formattedOutstanding = number_format(abs($outstanding), 2, '.', '') . $outstandingType;

                    fputcsv($file, [
                        $dealer->dealer_code ?? 'N/A',
                        $dealer->dealer_name ?? 'N/A',
                        $dealer->aso_name ?? 'Unassigned',
                        $dealer->email ?? 'N/A',
                        $dealer->phone ?? 'N/A',
                        number_format($debit, 2, '.', ''),
                        number_format($credit, 2, '.', ''),
                        $formattedOutstanding,
                        $days . ' Days',
                        ucfirst($dealer->status ?? 'N/A')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            Log::error('Dealer Export Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to export dealers list.');
        }
    }
}
