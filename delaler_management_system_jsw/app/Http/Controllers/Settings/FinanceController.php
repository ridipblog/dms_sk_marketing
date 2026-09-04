<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyBankDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FinanceController extends Controller
{
    /**
     * Display Company Finance & Bank Details settings form and list.
     */
    public function index()
    {
        try {
            $companyId = session('active_company_id');
            $company = Company::find($companyId);
            
            $bankDetailsList = CompanyBankDetail::where('company_id', $companyId)
                ->orderByRaw("status = 'active' DESC")
                ->latest('id')
                ->get();

            $activeBankDetail = $bankDetailsList->where('status', 'active')->first();

            if (!$activeBankDetail && $company) {
                $activeBankDetail = new CompanyBankDetail([
                    'company_id' => $companyId,
                    'account_holder_name' => $company->company_name ?? '',
                    'status' => 'active',
                ]);
            }

            return view('settings.finance.index', compact('bankDetailsList', 'activeBankDetail', 'company'));
        } catch (\Throwable $e) {
            Log::error('Settings Finance Index Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading finance settings.');
        }
    }

    /**
     * Store new Company Bank Details (automatically deactivates old bank details).
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'account_holder_name' => 'required|string|max:255',
                'bank_name' => 'required|string|max:255',
                'account_no' => 'required|string|max:50',
                'ifsc_code' => 'required|string|max:20',
                'branch_name' => 'nullable|string|max:255',
                'swift_code' => 'nullable|string|max:20',
                'upi_id' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ]);
            }

            $companyId = session('active_company_id');

            if (!$companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Active company not found in session.'
                ]);
            }

            $bankDetail = DB::transaction(function () use ($companyId, $request) {
                // Deactivate all previous bank details for this company
                CompanyBankDetail::where('company_id', $companyId)->update(['status' => 'inactive']);

                // Create and activate the new bank detail
                return CompanyBankDetail::create([
                    'company_id' => $companyId,
                    'account_holder_name' => trim($request->account_holder_name),
                    'bank_name' => trim($request->bank_name),
                    'account_no' => trim($request->account_no),
                    'ifsc_code' => strtoupper(trim($request->ifsc_code)),
                    'branch_name' => $request->filled('branch_name') ? trim($request->branch_name) : null,
                    'swift_code' => $request->filled('swift_code') ? strtoupper(trim($request->swift_code)) : null,
                    'upi_id' => $request->filled('upi_id') ? trim($request->upi_id) : null,
                    'status' => 'active',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'New bank details added successfully and set as active.',
                'bank_detail' => $bankDetail
            ]);
        } catch (\Throwable $e) {
            Log::error('Settings Finance Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving company bank details.'
            ]);
        }
    }

    /**
     * Backward-compatible update route pointing to store.
     */
    public function update(Request $request)
    {
        return $this->store($request);
    }

    /**
     * Set a specific bank detail as active and deactivate all others for the company.
     */
    public function setActive(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'bank_detail_id' => 'required|integer|exists:company_bank_details,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid bank detail specified.'
                ]);
            }

            $companyId = session('active_company_id');

            if (!$companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Active company not found in session.'
                ]);
            }

            DB::transaction(function () use ($companyId, $request) {
                // Deactivate all bank details for this company
                CompanyBankDetail::where('company_id', $companyId)->update(['status' => 'inactive']);

                // Activate selected bank detail
                CompanyBankDetail::where('company_id', $companyId)
                    ->where('id', $request->bank_detail_id)
                    ->update(['status' => 'active']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Bank details set as active successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Settings Finance Set Active Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating active bank details.'
            ]);
        }
    }
}
