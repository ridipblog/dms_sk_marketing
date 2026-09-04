<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dealers\DealerCompany;
use App\Models\User;
use App\Models\UserRoleCompany;
use App\Models\Accounts\Invoice;
use App\Models\Accounts\InvoicePayment;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('preview_role') ?? session('active_role_name');

        if (!$role && auth()->check()) {
            $activeMapping = UserRoleCompany::with('role')
                ->where('user_id', auth()->id())
                ->first();
            if ($activeMapping && $activeMapping->role) {
                $role = $activeMapping->role->role_name;
                session([
                    'active_map_id' => $activeMapping->id,
                    'active_company_id' => $activeMapping->company_id,
                    'active_role_id' => $activeMapping->role_id,
                    'active_role_name' => $role,
                ]);
            }
        }

        $role = $role ?? 'Admin';
        $normalizedRole = $this->getNormalizedRole($role);

        $companyId = session('active_company_id') ?? 1;
        $userId = auth()->id();

        $dmsList = [];
        $asmsList = [];
        $asosList = [];
        $dealersList = [];

        if ($normalizedRole === 'Admin') {
            $dmsList = User::whereHas('userRoleCompanies', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->whereHas('role', fn($r) => $r->where('role_name', 'like', '%Branch Manager%'));
            })->select('id', 'name')->get()->toArray();
        } elseif ($normalizedRole === 'DM') {
            $asmsList = User::whereHas('userRoleCompanies', function ($q) use ($companyId, $userId) {
                $q->where('company_id', $companyId)
                    ->where('parent_id', $userId)
                    ->whereHas('role', fn($r) => $r->where('role_name', 'like', '%ASM%'));
            })->select('id', 'name')->get()->toArray();
        } elseif ($normalizedRole === 'ASM') {
            $asosList = User::whereHas('userRoleCompanies', function ($q) use ($companyId, $userId) {
                $q->where('company_id', $companyId)
                    ->where('parent_id', $userId)
                    ->whereHas('role', fn($r) => $r->where('role_name', 'like', '%ASO%'));
            })->select('id', 'name')->get()->toArray();
        } elseif ($normalizedRole === 'ASO') {
            $dealersList = DealerCompany::where('dealer_companies.company_id', $companyId)
                ->where('dealer_companies.aso_id', $userId)
                ->join('dealers', 'dealer_companies.dealer_id', '=', 'dealers.id')
                ->select('dealer_companies.id as id', 'dealers.dealer_name as name')
                ->get()
                ->toArray();
        }

        // Calculate initial total outstanding amount using single-query SQL JOIN
        $dealerCompanyIds = $this->getScopedDealerCompanyIds($normalizedRole, $companyId, $userId);
        $outstanding = InvoicePayment::join('invoices', 'invoice_payments.invoice_id', '=', 'invoices.id')
            ->whereIn('invoices.buyer_id', $dealerCompanyIds)
            ->whereNull('invoices.deleted_at')
            ->sum('invoice_payments.outstanding_amount');

        return view('dashboard.dashboard-new', [
            'roleType' => $normalizedRole,
            'activeRole' => $role,
            'dms' => $dmsList,
            'asms' => $asmsList,
            'asos' => $asosList,
            'dealers' => $dealersList,
            'metrics' => [
                'outstanding' => $outstanding
            ]
        ]);
    }

    public function getFilterOptions(Request $request)
    {
        $companyId = session('active_company_id') ?? 1;
        $parentRole = $request->input('parent_role');
        $parentId = $request->input('parent_id');

        $dmId = $request->input('dm_id');
        $asmId = $request->input('asm_id');
        $asoId = $request->input('aso_id');
        $dealerId = $request->input('dealer_id');
        $date = $request->input('date');

        // 1. Fetch child options
        $options = [];
        if ($parentRole === 'DM' && $parentId) {
            $options = User::whereHas('userRoleCompanies', function ($q) use ($companyId, $parentId) {
                $q->where('company_id', $companyId)
                    ->where('parent_id', $parentId)
                    ->whereHas('role', fn($r) => $r->where('role_name', 'like', '%ASM%'));
            })->select('id', 'name')->get()->toArray();
        } elseif ($parentRole === 'ASM' && $parentId) {
            $options = User::whereHas('userRoleCompanies', function ($q) use ($companyId, $parentId) {
                $q->where('company_id', $companyId)
                    ->where('parent_id', $parentId)
                    ->whereHas('role', fn($r) => $r->where('role_name', 'like', '%ASO%'));
            })->select('id', 'name')->get()->toArray();
        } elseif ($parentRole === 'ASO' && $parentId) {
            $options = DealerCompany::where('dealer_companies.company_id', $companyId)
                ->where('dealer_companies.aso_id', $parentId)
                ->join('dealers', 'dealer_companies.dealer_id', '=', 'dealers.id')
                ->select('dealer_companies.id as id', 'dealers.dealer_name as name')
                ->get()
                ->toArray();

            if (empty($options)) {
                $options = UserRoleCompany::where('user_role_companies.company_id', $companyId)
                    ->where('user_role_companies.parent_id', $parentId)
                    ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%Dealer%'))
                    ->join('users', 'user_role_companies.user_id', '=', 'users.id')
                    ->select('user_role_companies.id as id', 'users.name as name')
                    ->get()
                    ->toArray();
            }
        }

        // 2. Compute Total Outstanding for selected filter state
        $dealerCompanyIds = [];
        if (!empty($dealerId)) {
            $dealerCompanyIds = DealerCompany::where('company_id', $companyId)
                ->where(function ($q) use ($dealerId) {
                    $q->where('id', $dealerId)
                        ->orWhere('dealer_id', $dealerId);
                })
                ->pluck('id')
                ->toArray();

            if (empty($dealerCompanyIds)) {
                $dealerCompanyIds = [$dealerId];
            }
        } elseif (!empty($asoId)) {
            $dealerCompanyIds = DealerCompany::where('company_id', $companyId)
                ->where('aso_id', $asoId)
                ->pluck('id')
                ->toArray();
        } elseif (!empty($asmId)) {
            $asoUserIds = UserRoleCompany::where('company_id', $companyId)
                ->where('parent_id', $asmId)
                ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASO%'))
                ->pluck('user_id')
                ->toArray();

            $dealerCompanyIds = DealerCompany::where('company_id', $companyId)
                ->whereIn('aso_id', $asoUserIds)
                ->pluck('id')
                ->toArray();
        } elseif (!empty($dmId)) {
            $asmUserIds = UserRoleCompany::where('company_id', $companyId)
                ->where('parent_id', $dmId)
                ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASM%'))
                ->pluck('user_id')
                ->toArray();

            $asoUserIds = UserRoleCompany::where('company_id', $companyId)
                ->whereIn('parent_id', $asmUserIds)
                ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASO%'))
                ->pluck('user_id')
                ->toArray();

            $dealerCompanyIds = DealerCompany::where('company_id', $companyId)
                ->whereIn('aso_id', $asoUserIds)
                ->pluck('id')
                ->toArray();
        } else {
            // Fallback when all dropdowns are "All": scope by active logged-in user role using reusable method
            $role = session('active_role_name');
            if (!$role && auth()->check()) {
                $activeMapping = UserRoleCompany::with('role')
                    ->where('user_id', auth()->id())
                    ->first();
                if ($activeMapping && $activeMapping->role) {
                    $role = $activeMapping->role->role_name;
                }
            }
            $role = $role ?? 'Admin';
            $normalizedRole = $this->getNormalizedRole($role);
            $dealerCompanyIds = $this->getScopedDealerCompanyIds($normalizedRole, $companyId, auth()->id());
        }

        $outstanding = InvoicePayment::join('invoices', 'invoice_payments.invoice_id', '=', 'invoices.id')
            ->whereIn('invoices.buyer_id', $dealerCompanyIds)
            ->whereNull('invoices.deleted_at')
            ->when($date, fn($q) => $q->whereDate('invoices.invoice_generate_date', '<=', $date))
            ->sum('invoice_payments.outstanding_amount');

        return response()->json([
            'success' => true,
            'options' => $options,
            'outstanding' => $outstanding,
            'outstanding_formatted' => '₹ ' . number_format($outstanding / 100000, 2) . ' L'
        ]);
    }

    public function restricted()
    {
        return view('errors.restricted');
    }

    /**
     * Reusable Helper: Normalize Role String
     */
    private function getNormalizedRole($role)
    {
        if (str_contains($role, 'Branch Manager') || $role === 'BM' || $role === 'DM') {
            return 'DM';
        }
        if (str_contains($role, 'Area Sales Manager') || $role === 'ASM') {
            return 'ASM';
        }
        if (str_contains($role, 'Assistant Section Officer') || $role === 'ASO') {
            return 'ASO';
        }
        if (str_contains($role, 'Dealer')) {
            return 'Dealer';
        }
        return 'Admin';
    }

    /**
     * Reusable Helper: Fetch Scoped Dealer Company IDs by Role
     */
    private function getScopedDealerCompanyIds($normalizedRole, $companyId, $userId)
    {
        if ($normalizedRole === 'DM') {
            $asmUserIds = UserRoleCompany::where('company_id', $companyId)
                ->where('parent_id', $userId)
                ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASM%'))
                ->pluck('user_id')
                ->toArray();

            $asoUserIds = UserRoleCompany::where('company_id', $companyId)
                ->whereIn('parent_id', $asmUserIds)
                ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASO%'))
                ->pluck('user_id')
                ->toArray();

            return DealerCompany::where('company_id', $companyId)
                ->whereIn('aso_id', $asoUserIds)
                ->pluck('id')
                ->toArray();
        }

        if ($normalizedRole === 'ASM') {
            $asoUserIds = UserRoleCompany::where('company_id', $companyId)
                ->where('parent_id', $userId)
                ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASO%'))
                ->pluck('user_id')
                ->toArray();

            return DealerCompany::where('company_id', $companyId)
                ->whereIn('aso_id', $asoUserIds)
                ->pluck('id')
                ->toArray();
        }

        if ($normalizedRole === 'ASO') {
            return DealerCompany::where('company_id', $companyId)
                ->where('aso_id', $userId)
                ->pluck('id')
                ->toArray();
        }

        return DealerCompany::where('company_id', $companyId)->pluck('id')->toArray();
    }
}
