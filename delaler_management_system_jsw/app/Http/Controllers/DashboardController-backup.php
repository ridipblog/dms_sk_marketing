<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dealers\DealerCompany;
use App\Models\Inventory\Product;
use App\Models\Inventory\Category;
use App\Models\UploadTrack;
use App\Models\User;
use App\Models\Role;
use App\Models\UserRoleCompany;
use App\Models\Accounts\Invoice;
use App\Models\Accounts\InvoicePayment;
use App\Models\Accounts\PaymentTrack;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Support preview switcher or fall back to active session role key, or active user mapping
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

        $role = $role ?? 'Super Admin';

        // Normalize roles for simplicity in blade matching
        $normalizedRole = $role;
        if (str_contains($role, 'Super Admin') || str_contains($role, 'Admin')) {
            $normalizedRole = 'Admin';
        } elseif (str_contains($role, 'Branch Manager') || $role === 'BM') {
            $normalizedRole = 'BM';
        } elseif (str_contains($role, 'Area Sales Manager') || $role === 'ASM') {
            $normalizedRole = 'ASM';
        } elseif (str_contains($role, 'Assistant Section Officer') || $role === 'ASO') {
            $normalizedRole = 'ASO';
        } elseif (str_contains($role, 'Dealer') || $role === 'Dealer') {
            $normalizedRole = 'Dealer';
        }

        // Fetch data based on the role
        $roleData = [];
        switch ($normalizedRole) {
            case 'Admin':
                $roleData = $this->getAdminData();
                break;
            case 'BM':
                $roleData = $this->getBranchManagerData();
                break;
            case 'ASM':
                $roleData = $this->getAreaSalesManagerData();
                break;
            case 'ASO':
                $roleData = $this->getAsoData();
                break;
            case 'Dealer':
                $roleData = $this->getDealerData();
                break;
            default:
                // Default render Admin dashboard page first
                $roleData = $this->getAdminData();
                $normalizedRole = 'Admin';
                break;
        }

        return view('dashboard.index', array_merge([
            'activeRole' => $role,
            'roleType' => $normalizedRole,
        ], $roleData));
    }

    private function getDealersUnderUser($companyId, $userId, $roleName)
    {
        $normalizedRole = $roleName;
        if (str_contains($roleName, 'Branch Manager')) {
            $normalizedRole = 'BM';
        } elseif (str_contains($roleName, 'Area Sales Manager')) {
            $normalizedRole = 'ASM';
        } elseif (str_contains($roleName, 'Assistant Section Officer')) {
            $normalizedRole = 'ASO';
        } elseif (str_contains($roleName, 'Dealer')) {
            $normalizedRole = 'Dealer';
        }

        $baseQuery = UserRoleCompany::where('company_id', $companyId)->with(['user', 'role', 'parent']);

        if ($normalizedRole === 'Dealer') {
            return (clone $baseQuery)->where('user_id', $userId)
                ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%Dealer%'))
                ->get();
        }

        if ($normalizedRole === 'ASO') {
            return (clone $baseQuery)->where('parent_id', $userId)
                ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%Dealer%'))
                ->get();
        }

        if ($normalizedRole === 'ASM') {
            $asoUserIds = UserRoleCompany::where('company_id', $companyId)
                ->where('parent_id', $userId)
                ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASO%'))
                ->pluck('user_id');

            $dealers = (clone $baseQuery)->whereIn('parent_id', $asoUserIds)
                ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%Dealer%'))
                ->get();

            if ($dealers->isEmpty()) {
                $dealers = (clone $baseQuery)->whereHas('role', fn($q) => $q->where('role_name', 'like', '%Dealer%'))
                    ->get();
            }
            return $dealers;
        }

        if ($normalizedRole === 'BM') {
            $asmUserIds = UserRoleCompany::where('company_id', $companyId)
                ->where('parent_id', $userId)
                ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASM%'))
                ->pluck('user_id');

            $asoUserIds = UserRoleCompany::where('company_id', $companyId)
                ->whereIn('parent_id', $asmUserIds)
                ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASO%'))
                ->pluck('user_id');

            $dealers = (clone $baseQuery)->whereIn('parent_id', $asoUserIds)
                ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%Dealer%'))
                ->get();

            if ($dealers->isEmpty()) {
                $dealers = (clone $baseQuery)->whereHas('role', fn($q) => $q->where('role_name', 'like', '%Dealer%'))
                    ->get();
            }
            return $dealers;
        }

        return (clone $baseQuery)->whereHas('role', fn($q) => $q->where('role_name', 'like', '%Dealer%'))
            ->get();
    }

    private function getAdminData()
    {
        $companyId = session('active_company_id') ?? 1;

        // Fetch ONLY DMs (Branch Managers / District Managers) name and id for initial select box
        $dmsList = User::whereHas('userRoleCompanies', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
                ->whereHas('role', fn($r) => $r->where('role_name', 'like', '%Branch Manager%'));
        })->select('id', 'name')->get()->toArray();

        // Consolidated metrics for Admin executive view using dealer_companies.id (buyer_id)
        $dealerCompanies = DealerCompany::where('company_id', $companyId)->get();
        $dealerCompanyIds = $dealerCompanies->pluck('id');

        $sales = Invoice::whereIn('buyer_id', $dealerCompanyIds)->sum('chargeable_amount');
        $collections = InvoicePayment::whereIn('invoice_id', Invoice::whereIn('buyer_id', $dealerCompanyIds)->pluck('id'))->sum('paid_amount');
        $outstanding = InvoicePayment::whereIn('invoice_id', Invoice::whereIn('buyer_id', $dealerCompanyIds)->pluck('id'))->sum('outstanding_amount');

        $asmMappings = UserRoleCompany::where('company_id', $companyId)
            ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASM%'))
            ->with('user')
            ->get();

        $asoMappings = UserRoleCompany::where('company_id', $companyId)
            ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASO%'))
            ->with('user')
            ->get();

        $asmPerformance = [];
        foreach ($asmMappings as $asmMap) {
            $asmDealers = $this->getDealersUnderUser($companyId, $asmMap->user_id, 'ASM');
            $asmDealerMappingIds = $asmDealers->pluck('id');

            $asmSales = Invoice::whereIn('created_by', $asmDealerMappingIds)->sum('chargeable_amount');
            $asmCollections = InvoicePayment::whereIn('invoice_id', Invoice::whereIn('created_by', $asmDealerMappingIds)->pluck('id'))->sum('paid_amount');
            $asmOutstanding = InvoicePayment::whereIn('invoice_id', Invoice::whereIn('created_by', $asmDealerMappingIds)->pluck('id'))->sum('outstanding_amount');

            $asmPerformance[] = [
                'id' => $asmMap->user_id,
                'name' => $asmMap->user->name ?? 'N/A',
                'sales' => $asmSales,
                'collections' => $asmCollections,
                'outstanding' => $asmOutstanding
            ];
        }

        return [
            'metrics' => [
                'achievement' => $sales,
                'outstanding' => $outstanding,
                'collections' => $collections,
                'active_asms' => $asmMappings->unique('user_id')->count(),
                'active_asos' => $asoMappings->unique('user_id')->count(),
                'total_dealers' => $dealerCompanies->count(),
            ],
            'dms' => $dmsList,
            'asms' => [],      // Sent empty initially; dynamically loaded on selection
            'asos' => [],      // Sent empty initially; dynamically loaded on selection
            'dealers' => [],   // Sent empty initially; dynamically loaded on selection
            'asm_performance' => $asmPerformance
        ];
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

        // 1. Fetch child options if parentRole and parentId are supplied
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

        // 2. Compute Total Outstanding for selected filter state in the same call
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
            $dealerCompanyIds = DealerCompany::where('company_id', $companyId)->pluck('id')->toArray();
        }

        $invoicesQuery = Invoice::whereIn('buyer_id', $dealerCompanyIds);

        if ($date) {
            $invoicesQuery->whereDate('invoice_generate_date', '<=', $date);
        }

        $invoiceIds = $invoicesQuery->pluck('id');
        $outstanding = InvoicePayment::whereIn('invoice_id', $invoiceIds)->sum('outstanding_amount');

        return response()->json([
            'success' => true,
            'options' => $options,
            'outstanding' => $outstanding,
            'outstanding_formatted' => '₹ ' . number_format($outstanding / 100000, 2) . ' L'
        ]);
    }

    private function getBranchManagerData()
    {
        $companyId = session('active_company_id') ?? 1;
        $userId = auth()->id();

        $bmMapping = UserRoleCompany::where('company_id', $companyId)
            ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%Branch Manager%'))
            ->first();
        $bmUserId = $bmMapping ? $bmMapping->user_id : $userId;

        $dealers = $this->getDealersUnderUser($companyId, $bmUserId, 'BM');
        $dealerMappingIds = $dealers->pluck('id');

        $sales = Invoice::whereIn('created_by', $dealerMappingIds)->sum('chargeable_amount');
        $collections = InvoicePayment::whereIn('invoice_id', Invoice::whereIn('created_by', $dealerMappingIds)->pluck('id'))->sum('paid_amount');
        $outstanding = InvoicePayment::whereIn('invoice_id', Invoice::whereIn('created_by', $dealerMappingIds)->pluck('id'))->sum('outstanding_amount');

        $activeAsms = UserRoleCompany::where('company_id', $companyId)
            ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASM%'))
            ->count();
        $activeAsos = UserRoleCompany::where('company_id', $companyId)
            ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASO%'))
            ->count();
        $totalDealersCount = $dealers->count();

        $asmMappings = UserRoleCompany::where('company_id', $companyId)
            ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASM%'))
            ->with('user')
            ->get();

        $asmPerformance = [];
        foreach ($asmMappings as $asmMap) {
            $asmDealers = $this->getDealersUnderUser($companyId, $asmMap->user_id, 'ASM');
            $asmDealerMappingIds = $asmDealers->pluck('id');

            $asmSales = Invoice::whereIn('created_by', $asmDealerMappingIds)->sum('chargeable_amount');
            $asmCollections = InvoicePayment::whereIn('invoice_id', Invoice::whereIn('created_by', $asmDealerMappingIds)->pluck('id'))->sum('paid_amount');
            $asmOutstanding = InvoicePayment::whereIn('invoice_id', Invoice::whereIn('created_by', $asmDealerMappingIds)->pluck('id'))->sum('outstanding_amount');

            $asmPerformance[] = [
                'id' => $asmMap->user_id,
                'name' => $asmMap->user->name ?? 'N/A',
                'sales' => $asmSales,
                'collections' => $asmCollections,
                'outstanding' => $asmOutstanding
            ];
        }

        // Region list fallbacks
        $regions = ['West Zone', 'North Zone', 'East Zone', 'South Zone'];
        $asmsList = [];
        $asmUserIdsSeen = [];
        foreach ($asmMappings as $asmMap) {
            if ($asmMap->user && !in_array($asmMap->user_id, $asmUserIdsSeen)) {
                $asmUserIdsSeen[] = $asmMap->user_id;
                $asmsList[] = [
                    'id' => $asmMap->user_id,
                    'name' => $asmMap->user->name ?? 'N/A',
                    'region' => 'West Zone'
                ];
            }
        }

        $asoMappings = UserRoleCompany::where('company_id', $companyId)
            ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASO%'))
            ->with('user')
            ->get();
        $asosList = [];
        $asoUserIdsSeen = [];
        foreach ($asoMappings as $asoMap) {
            if ($asoMap->user && !in_array($asoMap->user_id, $asoUserIdsSeen)) {
                $asoUserIdsSeen[] = $asoMap->user_id;
                $asosList[] = [
                    'id' => $asoMap->user_id,
                    'name' => $asoMap->user->name ?? 'N/A',
                    'asm_id' => $asoMap->parent_id ?? ($asmsList[0]['id'] ?? 0)
                ];
            }
        }

        $dealersList = [];
        $dealerIdsSeen = [];
        foreach ($dealers as $dl) {
            if (!in_array($dl->id, $dealerIdsSeen)) {
                $dealerIdsSeen[] = $dl->id;
                $dealersList[] = [
                    'id' => $dl->id,
                    'user_id' => $dl->user_id,
                    'name' => $dl->user->name ?? 'N/A',
                    'aso_id' => $dl->parent_id ?? ($asosList[0]['id'] ?? 0)
                ];
            }
        }

        return [
            'metrics' => [
                'achievement' => $sales,
                'outstanding' => $outstanding,
                'collections' => $collections,
                'active_asms' => $activeAsms,
                'active_asos' => $activeAsos,
                'total_dealers' => $totalDealersCount,
            ],
            'regions' => $regions,
            'asms' => $asmsList,
            'asos' => $asosList,
            'dealers' => $dealersList,
            'asm_performance' => $asmPerformance
        ];
    }

    private function getAreaSalesManagerData()
    {
        $companyId = session('active_company_id') ?? 1;
        $userId = auth()->id();

        $asmMapping = UserRoleCompany::where('company_id', $companyId)
            ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%Area Sales Manager%'))
            ->first();
        $asmUserId = $asmMapping ? $asmMapping->user_id : $userId;

        $dealers = $this->getDealersUnderUser($companyId, $asmUserId, 'ASM');
        $dealerMappingIds = $dealers->pluck('id');

        $sales = Invoice::whereIn('created_by', $dealerMappingIds)->sum('chargeable_amount');
        $collections = InvoicePayment::whereIn('invoice_id', Invoice::whereIn('created_by', $dealerMappingIds)->pluck('id'))->sum('paid_amount');
        $outstanding = InvoicePayment::whereIn('invoice_id', Invoice::whereIn('created_by', $dealerMappingIds)->pluck('id'))->sum('outstanding_amount');

        $totalVal = $collections + $outstanding;
        $collectionEfficiency = $totalVal > 0 ? round(($collections / $totalVal) * 100, 1) : 100.0;

        $activeAsos = UserRoleCompany::where('company_id', $companyId)
            ->where('parent_id', $asmUserId)
            ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASO%'))
            ->count();
        if ($activeAsos === 0) {
            $activeAsos = UserRoleCompany::where('company_id', $companyId)
                ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASO%'))
                ->count();
        }

        $asoMappings = UserRoleCompany::where('company_id', $companyId)
            ->where('parent_id', $asmUserId)
            ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASO%'))
            ->with('user')
            ->get();
        if ($asoMappings->isEmpty()) {
            $asoMappings = UserRoleCompany::where('company_id', $companyId)
                ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%ASO%'))
                ->with('user')
                ->get();
        }

        $asoScorecard = [];
        foreach ($asoMappings as $asoMap) {
            $asoDealers = $this->getDealersUnderUser($companyId, $asoMap->user_id, 'ASO');
            $asoDealerMappingIds = $asoDealers->pluck('id');

            $asoSales = Invoice::whereIn('created_by', $asoDealerMappingIds)->sum('chargeable_amount');
            $asoOutstanding = InvoicePayment::whereIn('invoice_id', Invoice::whereIn('created_by', $asoDealerMappingIds)->pluck('id'))->sum('outstanding_amount');
            $asoCollections = InvoicePayment::whereIn('invoice_id', Invoice::whereIn('created_by', $asoDealerMappingIds)->pluck('id'))->sum('paid_amount');

            $asoScorecard[] = [
                'id' => $asoMap->user_id,
                'name' => $asoMap->user->name ?? 'N/A',
                'dealers_count' => $asoDealers->count(),
                'sales' => $asoSales,
                'collections' => $asoCollections,
                'outstanding' => $asoOutstanding,
                'visit_rate' => 92
            ];
        }

        $topDealersList = [];
        foreach ($dealers as $dlMap) {
            $dlSales = Invoice::where('created_by', $dlMap->id)->sum('chargeable_amount');
            $dlOutstanding = InvoicePayment::whereIn('invoice_id', Invoice::where('created_by', $dlMap->id)->pluck('id'))->sum('outstanding_amount');

            $topDealersList[] = [
                'id' => $dlMap->id,
                'name' => $dlMap->user->name ?? 'N/A',
                'sales' => $dlSales,
                'outstanding' => $dlOutstanding,
                'status' => $dlOutstanding > 50000 ? 'Warning' : 'Active'
            ];
        }
        usort($topDealersList, fn($a, $b) => $b['sales'] <=> $a['sales']);
        $topDealersList = array_slice($topDealersList, 0, 5);

        $asosList = [];
        $asoUserIdsSeen = [];
        foreach ($asoMappings as $asoMap) {
            if ($asoMap->user && !in_array($asoMap->user_id, $asoUserIdsSeen)) {
                $asoUserIdsSeen[] = $asoMap->user_id;
                $asosList[] = [
                    'id' => $asoMap->user_id,
                    'name' => $asoMap->user->name ?? 'N/A'
                ];
            }
        }

        $dealersList = [];
        $dealerIdsSeen = [];
        foreach ($dealers as $dl) {
            if (!in_array($dl->id, $dealerIdsSeen)) {
                $dealerIdsSeen[] = $dl->id;
                $dealersList[] = [
                    'id' => $dl->id,
                    'user_id' => $dl->user_id,
                    'name' => $dl->user->name ?? 'N/A',
                    'aso_id' => $dl->parent_id ?? ($asosList[0]['id'] ?? 0)
                ];
            }
        }

        return [
            'metrics' => [
                'area_name' => 'West Zone',
                'achievement' => $sales,
                'outstanding' => $outstanding,
                'collections' => $collections,
                'collection_efficiency' => $collectionEfficiency,
                'active_asos' => $activeAsos,
                'total_dealers' => $dealers->count(),
            ],
            'asos' => $asosList,
            'dealers' => $dealersList,
            'aso_scorecard' => $asoScorecard,
            'top_dealers' => $topDealersList
        ];
    }

    private function getAsoData()
    {
        $companyId = session('active_company_id') ?? 1;
        $userId = auth()->id();

        $asoMapping = UserRoleCompany::where('company_id', $companyId)
            ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%Assistant Section Officer%'))
            ->first();
        $asoUserId = $asoMapping ? $asoMapping->user_id : $userId;

        $dealers = $this->getDealersUnderUser($companyId, $asoUserId, 'ASO');
        $dealerMappingIds = $dealers->pluck('id');

        $sales = Invoice::whereIn('created_by', $dealerMappingIds)->sum('chargeable_amount');
        $collections = InvoicePayment::whereIn('invoice_id', Invoice::whereIn('created_by', $dealerMappingIds)->pluck('id'))->sum('paid_amount');
        $outstanding = InvoicePayment::whereIn('invoice_id', Invoice::whereIn('created_by', $dealerMappingIds)->pluck('id'))->sum('outstanding_amount');

        $dealersList = [];
        $dealerIdsSeen = [];
        foreach ($dealers as $dlMap) {
            if (in_array($dlMap->id, $dealerIdsSeen)) {
                continue;
            }
            $dealerIdsSeen[] = $dlMap->id;

            $dlSales = Invoice::where('created_by', $dlMap->id)->sum('chargeable_amount');
            $dlOutstanding = InvoicePayment::whereIn('invoice_id', Invoice::where('created_by', $dlMap->id)->pluck('id'))->sum('outstanding_amount');

            $lastTrack = PaymentTrack::whereIn('invoice_id', Invoice::where('created_by', $dlMap->id)->pluck('id'))
                ->latest()
                ->first();
            $lastUpdateDate = $lastTrack ? \Carbon\Carbon::parse($lastTrack->transaction_date)->format('d M Y') : 'N/A';

            $dealersList[] = [
                'id' => $dlMap->id,
                'user_id' => $dlMap->user_id,
                'name' => $dlMap->user->name ?? 'N/A',
                'sales' => $dlSales,
                'collections' => InvoicePayment::whereIn('invoice_id', Invoice::where('created_by', $dlMap->id)->pluck('id'))->sum('paid_amount'),
                'outstanding' => $dlOutstanding,
                'status' => $dlOutstanding > 50000 ? 'Warning' : 'Active',
                'last_visit' => $lastUpdateDate
            ];
        }

        $recentInvoices = Invoice::whereIn('created_by', $dealerMappingIds)
            ->with(['buyer.dealer'])
            ->latest()
            ->take(5)
            ->get();

        $recentOrdersList = [];
        foreach ($recentInvoices as $inv) {
            $recentOrdersList[] = [
                'order_no' => $inv->invoice_no,
                'dealer' => $inv->buyer->dealer->dealer_name ?? 'N/A',
                'amount' => $inv->chargeable_amount,
                'status' => $inv->invoice_status ?? 'Approved',
                'date' => \Carbon\Carbon::parse($inv->invoice_generate_date)->format('d M Y')
            ];
        }

        $overdueInvoices = Invoice::whereIn('created_by', $dealerMappingIds)
            ->where('due_date', '<', now())
            ->whereHas('invoicePayment', fn($q) => $q->where('outstanding_amount', '>', 0))
            ->count();

        return [
            'metrics' => [
                'territory' => 'Mumbai Territory',
                'dealers_count' => $dealers->count(),
                'sales' => $sales,
                'collections' => $collections,
                'outstanding' => $outstanding,
                'overdue_invoices' => $overdueInvoices
            ],
            'dealers' => $dealersList,
            'recent_orders' => $recentOrdersList
        ];
    }

    private function getDealerData()
    {
        $companyId = session('active_company_id') ?? 1;
        $userId = auth()->id();

        $dealerMapping = UserRoleCompany::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%Dealer%'))
            ->first();
        if (!$dealerMapping) {
            $dealerMapping = UserRoleCompany::where('company_id', $companyId)
                ->whereHas('role', fn($q) => $q->where('role_name', 'like', '%Dealer%'))
                ->first();
        }

        $dealerMappingId = $dealerMapping ? $dealerMapping->id : 0;

        $sales = Invoice::where('created_by', $dealerMappingId)->sum('chargeable_amount');
        $collections = InvoicePayment::whereIn('invoice_id', Invoice::where('created_by', $dealerMappingId)->pluck('id'))->sum('paid_amount');
        $outstanding = InvoicePayment::whereIn('invoice_id', Invoice::where('created_by', $dealerMappingId)->pluck('id'))->sum('outstanding_amount');

        $invoices = Invoice::where('created_by', $dealerMappingId)
            ->with('invoicePayment')
            ->latest()
            ->take(5)
            ->get();

        $recentInvoicesList = [];
        foreach ($invoices as $inv) {
            $status = 'Paid';
            if (($inv->invoicePayment->outstanding_amount ?? 0) > 0) {
                $status = \Carbon\Carbon::parse($inv->due_date)->isPast() ? 'Overdue' : 'Unpaid';
            }

            $recentInvoicesList[] = [
                'invoice_no' => $inv->invoice_no,
                'date' => \Carbon\Carbon::parse($inv->invoice_generate_date)->format('d M Y'),
                'amount' => $inv->chargeable_amount,
                'due_date' => \Carbon\Carbon::parse($inv->due_date)->format('d M Y'),
                'status' => $status
            ];
        }

        $receiptTracks = PaymentTrack::whereIn('invoice_id', Invoice::where('created_by', $dealerMappingId)->pluck('id'))
            ->whereHas('voucherType', fn($q) => $q->where('name', 'like', '%receipt%'))
            ->latest()
            ->take(5)
            ->get();

        $recentReceiptsList = [];
        foreach ($receiptTracks as $rt) {
            $recentReceiptsList[] = [
                'receipt_no' => $rt->transaction_id,
                'date' => \Carbon\Carbon::parse($rt->transaction_date)->format('d M Y'),
                'amount' => $rt->amount,
                'payment_mode' => $rt->payment_mode ?? 'RTGS',
                'status' => 'Matched'
            ];
        }

        $slabs = \DB::table('cash_discount_slabs')->where('company_id', $companyId)->get();
        $discountSlabsList = [];
        foreach ($slabs as $s) {
            $discountSlabsList[] = [
                'tier' => $s->slab_name,
                'range' => $s->min_days . ' - ' . $s->max_days . ' Days',
                'rate' => $s->discount_percent . '%',
                'status' => 'Active'
            ];
        }
        if (empty($discountSlabsList)) {
            $discountSlabsList = [
                ['tier' => 'Early Payment', 'range' => '0 - 7 Days', 'rate' => '2.0%', 'status' => 'Active'],
                ['tier' => 'Standard Payment', 'range' => '8 - 15 Days', 'rate' => '1.0%', 'status' => 'Active'],
            ];
        }

        $overdueAmount = InvoicePayment::whereIn('invoice_id', Invoice::where('created_by', $dealerMappingId)->where('due_date', '<', now())->pluck('id'))->sum('outstanding_amount');

        return [
            'metrics' => [
                'dealer_name' => $dealerMapping && $dealerMapping->user ? $dealerMapping->user->name : 'My Dealership',
                'sales' => $sales,
                'collections' => $collections,
                'outstanding' => $outstanding,
                'overdue_amount' => $overdueAmount
            ],
            'recent_invoices' => $recentInvoicesList,
            'recent_receipts' => $recentReceiptsList,
            'discount_slabs' => $discountSlabsList
        ];
    }

    /**
     * Show the restricted access page.
     *
     * @return \Illuminate\View\View
     */
    public function restricted()
    {
        return view('errors.restricted');
    }

    public function fetchData(Request $request)
    {
        try {
            $companyId = session('active_company_id');
            if (!$companyId) {
                return response()->json(['success' => false, 'message' => 'No active company selected.']);
            }

            // 1. Top Level Metrics
            $totalDealers = DealerCompany::where('company_id', $companyId)->count();
            $totalProducts = Product::whereHas('category', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->count();
            $totalCategories = Category::where('company_id', $companyId)->count();
            $totalUploads = UploadTrack::where('company_id', $companyId)->count();

            // 2. Chart 1: Dealer Status Breakdown
            $dealerStatuses = DealerCompany::where('company_id', $companyId)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // 3. Chart 2: Top 5 Categories by Product Count
            $topCategories = Category::where('company_id', $companyId)
                ->withCount('products')
                ->orderBy('products_count', 'desc')
                ->take(5)
                ->get(['category_name', 'products_count']);

            // 4. Chart 3: Upload Activity (Last 7 days)
            $last7Days = collect();
            for ($i = 6; $i >= 0; $i--) {
                $last7Days->push(now()->subDays($i)->format('Y-m-d'));
            }

            $uploadActivity = UploadTrack::where('company_id', $companyId)
                ->where('created_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date')
                ->toArray();

            $uploadTrend = $last7Days->map(function ($date) use ($uploadActivity) {
                return [
                    'date' => $date,
                    'count' => $uploadActivity[$date] ?? 0
                ];
            });

            // 5. Recent Activity
            $recentUploads = UploadTrack::where('company_id', $companyId)
                ->with('user:id,name')
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($track) {
                    return [
                        'file_name' => $track->file_name,
                        'type' => ucfirst($track->upload_type),
                        'status' => $track->status,
                        'date' => $track->created_at->diffForHumans(),
                        'user' => $track->user->name ?? 'System'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'metrics' => [
                        'dealers' => $totalDealers,
                        'products' => $totalProducts,
                        'categories' => $totalCategories,
                        'uploads' => $totalUploads,
                    ],
                    'charts' => [
                        'dealer_statuses' => $dealerStatuses,
                        'top_categories' => $topCategories,
                        'upload_trend' => $uploadTrend,
                    ],
                    'recent_activity' => $recentUploads
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Dashboard fetch data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading dashboard data.'
            ], 500);
        }
    }
}
