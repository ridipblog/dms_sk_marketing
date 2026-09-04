<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Dealers\DealerCompany;
use App\Models\Accounts\OrderDetail;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderTrackingController extends Controller
{
    public function index(Request $request)
    {
        // Load companies for the dropdown
        $dealerCompanies = DealerCompany::with(['dealer', 'company'])->get();

        $dealerCompanyId = $request->input('dealer_company_id');
        $orderId = $request->input('order_id');

        $orders = null;
        $totalPurchase = 0;
        $totalReceived = 0;
        $totalOutstanding = 0;
        $selectedCompany = null;
        $dummyPaymentTracks = [];

        if ($dealerCompanyId) {
            $selectedCompany = DealerCompany::find($dealerCompanyId);
            
            // Base query for orders belonging to this dealer, eager load payment tracks
            $query = OrderDetail::with('paymentTracks')->where('dealer_company_id', $dealerCompanyId);

            if ($orderId) {
                $query->where('id', $orderId);
            }

            // Order by purchase date descending
            $query->orderBy('purchase_date', 'desc');

            // Paginate the orders
            $orders = $query->paginate(5)->appends($request->all());

            $allDealerOrders = OrderDetail::with('paymentTracks')->where('dealer_company_id', $dealerCompanyId)->get();
            
            $totalPurchase = $allDealerOrders->sum('total_purchase_amount');
            $totalOutstanding = $allDealerOrders->sum('outstanding_amount');
            
            // Calculate total received across all payment tracks for this dealer
            $totalReceived = 0;
            foreach ($allDealerOrders as $ord) {
                $totalReceived += $ord->paymentTracks->sum('received_amount');
            }
            // Add dummy data for table view matching PDF outputs
            if ($orderId) {
                // Order-wise dummy
                $dummyPaymentTracks = [
                    [
                        'transaction_date' => '2024-06-01 10:00:00',
                        'transaction_id' => 'INV-2001',
                        'payment_mode' => 'sale',
                        'voucher_type' => 'SALE',
                        'received_amount' => null,
                        'debit' => 2000000.00,
                        'credit_amount' => null,
                        'balance' => 2000000.00,
                    ],
                    [
                        'transaction_date' => '2024-06-05 14:30:00',
                        'transaction_id' => 'TXN-98712',
                        'payment_mode' => 'bank_transfer',
                        'voucher_type' => 'RECEIPT',
                        'received_amount' => 1500000.00,
                        'debit' => null,
                        'credit_amount' => 1500000.00,
                        'balance' => 500000.00,
                    ],
                    [
                        'transaction_date' => '2024-06-26 11:15:00',
                        'transaction_id' => 'DN-001',
                        'payment_mode' => 'adjustment',
                        'voucher_type' => 'DEBIT_NOTE / INTEREST',
                        'received_amount' => null,
                        'debit' => 2700.00,
                        'credit_amount' => null,
                        'balance' => 502700.00,
                    ],
                    [
                        'transaction_date' => '2024-06-27 16:45:00',
                        'transaction_id' => 'CN-001',
                        'payment_mode' => 'adjustment',
                        'voucher_type' => 'CREDIT_NOTE',
                        'received_amount' => null,
                        'debit' => null,
                        'credit_amount' => 700.00,
                        'balance' => 502000.00,
                    ],
                    [
                        'transaction_date' => '2024-06-28 09:20:00',
                        'transaction_id' => 'TXN-98713',
                        'payment_mode' => 'upi',
                        'voucher_type' => 'RECEIPT',
                        'received_amount' => 502000.00,
                        'debit' => null,
                        'credit_amount' => 502000.00,
                        'balance' => 0.00,
                    ],
                ];
            } else {
                // Dealer-wise dummy
                $dummyPaymentTracks = [
                    [
                        'order_no' => 'ORD-2024-001',
                        'transaction_date' => '2024-06-01 10:00:00',
                        'transaction_id' => 'INV-2001',
                        'payment_mode' => 'sale',
                        'voucher_type' => 'SALE',
                        'received_amount' => null,
                        'debit' => 2000000.00,
                        'credit_amount' => null,
                        'balance' => 2000000.00,
                    ],
                    [
                        'order_no' => 'ORD-2024-001',
                        'transaction_date' => '2024-06-05 14:30:00',
                        'transaction_id' => 'TXN-98712',
                        'payment_mode' => 'bank_transfer',
                        'voucher_type' => 'RECEIPT',
                        'received_amount' => 1500000.00,
                        'debit' => null,
                        'credit_amount' => 1500000.00,
                        'balance' => 500000.00,
                    ],
                    [
                        'order_no' => 'ORD-2024-001',
                        'transaction_date' => '2024-06-26 11:15:00',
                        'transaction_id' => 'DN-001',
                        'payment_mode' => 'adjustment',
                        'voucher_type' => 'DEBIT_NOTE / INTEREST',
                        'received_amount' => null,
                        'debit' => 2700.00,
                        'credit_amount' => null,
                        'balance' => 502700.00,
                    ],
                    [
                        'order_no' => 'ORD-2024-001',
                        'transaction_date' => '2024-06-27 16:45:00',
                        'transaction_id' => 'CN-001',
                        'payment_mode' => 'adjustment',
                        'voucher_type' => 'CREDIT_NOTE',
                        'received_amount' => null,
                        'debit' => null,
                        'credit_amount' => 700.00,
                        'balance' => 502000.00,
                    ],
                    [
                        'order_no' => 'ORD-2024-001',
                        'transaction_date' => '2024-06-28 09:20:00',
                        'transaction_id' => 'TXN-98713',
                        'payment_mode' => 'upi',
                        'voucher_type' => 'RECEIPT',
                        'received_amount' => 502000.00,
                        'debit' => null,
                        'credit_amount' => 502000.00,
                        'balance' => 0.00,
                    ],
                ];
            }
        }

        return view('accounts.order_tracking.index', compact(
            'dealerCompanies', 
            'dealerCompanyId', 
            'orderId', 
            'orders', 
            'totalPurchase', 
            'totalReceived', 
            'totalOutstanding',
            'selectedCompany',
            'dummyPaymentTracks'
        ));
    }

    public function getOrders($dealerCompanyId)
    {
        // Fetch orders related to the selected dealer company for the secondary dropdown
        $orders = OrderDetail::where('dealer_company_id', $dealerCompanyId)
            ->select('id', 'order_no', 'purchase_date')
            ->orderBy('purchase_date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    public function generateDealerPdf(Request $request)
    {
        // "used dummay code do not implement backend logic just used backnedfor necesary"
        // Dummy data structured like the payment track model for all orders
        $dealerCompanyId = $request->input('dealer_company_id');
        
        $companyName = "Dummy Dealer Company Ltd.";
        $openingBalance = 0.00;
        
        $dummyPaymentTracks = [
            [
                'order_no' => 'ORD-2024-001',
                'transaction_date' => '2024-06-01 10:00:00',
                'transaction_id' => 'INV-2001',
                'payment_mode' => 'sale',
                'voucher_type' => 'SALE',
                'received_amount' => null,
                'debit' => 2000000.00,
                'credit_amount' => null,
                'balance' => 2000000.00,
            ],
            [
                'order_no' => 'ORD-2024-001',
                'transaction_date' => '2024-06-05 14:30:00',
                'transaction_id' => 'TXN-98712',
                'payment_mode' => 'bank_transfer',
                'voucher_type' => 'RECEIPT',
                'received_amount' => 1500000.00,
                'debit' => null,
                'credit_amount' => 1500000.00,
                'balance' => 500000.00,
            ],
            [
                'order_no' => 'ORD-2024-001',
                'transaction_date' => '2024-06-26 11:15:00',
                'transaction_id' => 'DN-001',
                'payment_mode' => 'adjustment',
                'voucher_type' => 'DEBIT_NOTE / INTEREST',
                'received_amount' => null,
                'debit' => 2700.00,
                'credit_amount' => null,
                'balance' => 502700.00,
            ],
            [
                'order_no' => 'ORD-2024-001',
                'transaction_date' => '2024-06-27 16:45:00',
                'transaction_id' => 'CN-001',
                'payment_mode' => 'adjustment',
                'voucher_type' => 'CREDIT_NOTE',
                'received_amount' => null,
                'debit' => null,
                'credit_amount' => 700.00,
                'balance' => 502000.00,
            ],
            [
                'order_no' => 'ORD-2024-001',
                'transaction_date' => '2024-06-28 09:20:00',
                'transaction_id' => 'TXN-98713',
                'payment_mode' => 'upi',
                'voucher_type' => 'RECEIPT',
                'received_amount' => 502000.00,
                'debit' => null,
                'credit_amount' => 502000.00,
                'balance' => 0.00,
            ],
        ];

        // "closing balance and this balnce sum of all order outstading blance"
        $sumOfOutstanding = 0.00; // Dummy sum matching final balance
        $closingBalance = $sumOfOutstanding;

        $pdf = Pdf::loadView('accounts.order_tracking.pdf_dealer', compact(
            'companyName', 'openingBalance', 'dummyPaymentTracks', 'closingBalance'
        ));

        return $pdf->stream('dealer_wise_report.pdf');
    }

    public function generateOrderPdf(Request $request)
    {
        $orderId = $request->input('order_id');
        
        $orderNo = "ORD-101 (Dummy)";
        
        $dummyPaymentTracks = [
            [
                'transaction_date' => '2024-06-01 10:00:00',
                'transaction_id' => 'INV-2001',
                'payment_mode' => 'sale',
                'voucher_type' => 'SALE',
                'received_amount' => null,
                'debit' => 2000000.00,
                'credit_amount' => null,
                'balance' => 2000000.00,
            ],
            [
                'transaction_date' => '2024-06-05 14:30:00',
                'transaction_id' => 'TXN-98712',
                'payment_mode' => 'bank_transfer',
                'voucher_type' => 'RECEIPT',
                'received_amount' => 1500000.00,
                'debit' => null,
                'credit_amount' => 1500000.00,
                'balance' => 500000.00,
            ],
            [
                'transaction_date' => '2024-06-26 11:15:00',
                'transaction_id' => 'DN-001',
                'payment_mode' => 'adjustment',
                'voucher_type' => 'DEBIT_NOTE / INTEREST',
                'received_amount' => null,
                'debit' => 2700.00,
                'credit_amount' => null,
                'balance' => 502700.00,
            ],
            [
                'transaction_date' => '2024-06-27 16:45:00',
                'transaction_id' => 'CN-001',
                'payment_mode' => 'adjustment',
                'voucher_type' => 'CREDIT_NOTE',
                'received_amount' => null,
                'debit' => null,
                'credit_amount' => 700.00,
                'balance' => 502000.00,
            ],
            [
                'transaction_date' => '2024-06-28 09:20:00',
                'transaction_id' => 'TXN-98713',
                'payment_mode' => 'upi',
                'voucher_type' => 'RECEIPT',
                'received_amount' => 502000.00,
                'debit' => null,
                'credit_amount' => 502000.00,
                'balance' => 0.00,
            ],
        ];

        $outstandingAmount = 0.00; // Dummy outstanding matching final balance

        $pdf = Pdf::loadView('accounts.order_tracking.pdf_order', compact(
            'orderNo', 'dummyPaymentTracks', 'outstandingAmount'
        ));

        return $pdf->stream('order_wise_receipt.pdf');
    }

    public function orderview()
    {
        return view('accounts.order_tracking.orderview');
    }
}
