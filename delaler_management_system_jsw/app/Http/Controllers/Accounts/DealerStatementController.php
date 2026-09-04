<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Dealers\Dealer;
use App\Models\Accounts\PaymentTrack;
use App\Models\Accounts\VoucherType;
use App\Models\Company;
use App\Models\Dealers\DealerCompany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DealerStatementController extends Controller
{
    /**
     * Display Dealer Statement Filter Page
     */
    public function dealerStatementIndex()
    {
        try {
            $companyId = session('active_company_id');
            $dealers = Dealer::whereHas('dealerCompany', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->where('status', 'active')->get();

            return view('accounts.dealer_statement.index', compact('dealers'));
        } catch (\Throwable $e) {
            Log::error('Dealer Statement Index Error: ' . $e->getMessage());
            return view('accounts.dealer_statement.index', [
                'errorMessage' => 'Something went wrong while loading the page.'
            ]);
        }
    }

    public function ledger(Request $request, $encrypted_dealer_id = null)
    {
        try {
            if ($request->isMethod('post')) {
                $dealerId = Crypt::decryptString($request->dealer_id);
                $isCustomDate = true;

                if ($request->financial_year && $request->financial_year !== 'custom') {
                    $parts = explode('-', $request->financial_year);
                    $startDate = $parts[0] . '-04-01';
                    $endDate = $parts[1] . '-03-31';
                } else {
                    $startDate = $request->start_date;
                    $endDate = $request->end_date;
                }
            } else {
                $dealerId = Crypt::decryptString($encrypted_dealer_id);
                $isCustomDate = false;
            }

            $companyId = session('active_company_id');

            $dealerCompany = DealerCompany::where('dealer_id', $dealerId)
                ->where('company_id', $companyId)
                ->firstOrFail();

            $dealerCompanyId = $dealerCompany->id;

            $dealer = Dealer::findOrFail($dealerId);

            if (!$isCustomDate) {
                // Compute current financial year (April to March)
                $currentMonth = date('n');
                $currentYear = date('Y');

                if ($currentMonth >= 4) {
                    $startDate = $currentYear . '-04-01';
                    $endDate = ($currentYear + 1) . '-03-31';
                } else {
                    $startDate = ($currentYear - 1) . '-04-01';
                    $endDate = $currentYear . '-03-31';
                }
            }

            $paymentTracks = PaymentTrack::whereHas('invoice', function ($q) use ($dealerCompanyId) {
                $q->where('buyer_id', $dealerCompanyId);
            })
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->with(['voucherTypeModel', 'invoice'])
                ->orderBy(DB::raw('DATE(transaction_date)'), 'asc')
                ->orderBy('id', 'asc')
                ->get();

            // Calculate historical opening balance
            $debitVoucherTypes = VoucherType::whereIn(DB::raw('UPPER(name)'), ['SALES', 'DEBIT NOTE'])->pluck('id')->toArray();
            $creditVoucherTypes = VoucherType::whereIn(DB::raw('UPPER(name)'), ['RECEIPT', 'CREDIT NOTE', 'JOURNAL'])->pluck('id')->toArray();

            $historicalDebits = PaymentTrack::whereHas('invoice', function ($q) use ($dealerCompanyId) {
                $q->where('buyer_id', $dealerCompanyId);
            })->where('transaction_date', '<', $startDate)
                ->whereIn('voucher_type_id', $debitVoucherTypes)
                ->sum('amount');

            $historicalCredits = PaymentTrack::whereHas('invoice', function ($q) use ($dealerCompanyId) {
                $q->where('buyer_id', $dealerCompanyId);
            })->where('transaction_date', '<', $startDate)
                ->whereIn('voucher_type_id', $creditVoucherTypes)
                ->sum('amount');

            // Process and Group Data by Financial Year
            $ledgerData = [];
            $runningBalance = $historicalDebits - $historicalCredits; // Positive = Debit Balance, Negative = Credit Balance

            if ($paymentTracks->isEmpty()) {
                $date = Carbon::parse($startDate);
                $month = $date->month;
                $year = $date->year;

                if ($month >= 4) {
                    $fyStart = $year;
                } else {
                    $fyStart = $year - 1;
                }
                $fyString = $fyStart . '-' . ($fyStart + 1);

                $ledgerData[$fyString] = [
                    'fy' => $fyString,
                    'start_date' => $fyStart . '-04-01',
                    'end_date' => ($fyStart + 1) . '-03-31',
                    'opening_balance' => $runningBalance,
                    'transactions' => [],
                    'total_debit' => 0,
                    'total_credit' => 0,
                    'closing_balance' => $runningBalance,
                ];
            } else {
                foreach ($paymentTracks as $track) {
                    $date = Carbon::parse($track->transaction_date);
                    $month = $date->month;
                    $year = $date->year;

                    if ($month >= 4) {
                        $fyStart = $year;
                    } else {
                        $fyStart = $year - 1;
                    }
                    $fyString = $fyStart . '-' . ($fyStart + 1);

                    if (!isset($ledgerData[$fyString])) {
                        $ledgerData[$fyString] = [
                            'fy' => $fyString,
                            'start_date' => $fyStart . '-04-01',
                            'end_date' => ($fyStart + 1) . '-03-31',
                            'opening_balance' => $runningBalance,
                            'transactions' => [],
                            'total_debit' => 0,
                            'total_credit' => 0,
                            'closing_balance' => 0,
                        ];
                    }

                    $voucherName = $track->voucherTypeModel ? strtoupper($track->voucherTypeModel->name) : '';
                    $isDebit = in_array($voucherName, ['SALES', 'DEBIT NOTE']);
                    $isCredit = in_array($voucherName, ['RECEIPT', 'CREDIT NOTE', 'JOURNAL']);

                    // Map Particulars
                    if ($voucherName == 'SALES') $particulars = 'To <strong>GST Sale 18%</strong>';
                    elseif ($voucherName == 'CREDIT NOTE') $particulars = 'By <strong>Discount & Scheme</strong>';
                    elseif ($voucherName == 'RECEIPT') $particulars = 'By State Bank Of India (OCC), A.T.Road';
                    elseif ($voucherName == 'DEBIT NOTE') $particulars = 'To <strong>Interest on Delay Payment</strong>';
                    else $particulars = ($isDebit ? 'To ' : 'By ') . $voucherName;

                    // Map Voucher No
                    if ($voucherName == 'SALES' && $track->invoice) {
                        $voucherNo = $track->invoice->invoice_no;
                    } else {
                        $voucherNo = $track->transaction_id ?? $track->id;
                    }

                    $debitAmount = $isDebit ? $track->amount : 0;
                    $creditAmount = $isCredit ? $track->amount : 0;

                    $ledgerData[$fyString]['transactions'][] = [
                        'date' => $date->format('d-M-y'),
                        'particulars' => $particulars,
                        'vch_type' => $track->voucherTypeModel ? $track->voucherTypeModel->name : '',
                        'vch_no' => $voucherNo,
                        'debit' => $debitAmount,
                        'credit' => $creditAmount,
                    ];

                    $ledgerData[$fyString]['total_debit'] += $debitAmount;
                    $ledgerData[$fyString]['total_credit'] += $creditAmount;

                    $runningBalance += ($debitAmount - $creditAmount);
                    $ledgerData[$fyString]['closing_balance'] = $runningBalance;
                }
            }

            $company = Company::find($companyId);

            return view('accounts.dealer_statement.ledger', compact('dealer', 'company', 'ledgerData', 'startDate', 'endDate'));
        } catch (DecryptException $e) {
            Log::error('Ledger View Error: ' . $e->getMessage());
            return view('accounts.dealer_statement.ledger', [
                'errorMessage' => 'Invalid Dealer ID.',
                'dealer' => null
            ]);
        } catch (\Exception $e) {
            Log::error('Ledger View Error: ' . $e->getMessage());
            return view('accounts.dealer_statement.ledger', [
                'errorMessage' => 'Dealer not found or unauthorized access.',
                'dealer' => null
            ]);
        }
    }
}
