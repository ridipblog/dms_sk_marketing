<?php

namespace App\Modules;

use App\Models\Accounts\Invoice;
use App\Models\Dealers\Dealer;
use App\Models\Dealers\DealerCompany;
use App\Models\Inventory\Category;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductPricing;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Role;

class ReuseModule
{
    /**
     * Get a query builder for invoices owned by the current active map ID.
     * Optionally filter by a specific invoice ID.
     *
     * @param mixed|null $invoiceId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getOwnedInvoiceQuery($invoiceId = null, $activeMapId = null)
    {
        $activeMapId = $activeMapId ?? session('active_map_id');
        $query = Invoice::where('created_by', $activeMapId);

        if ($invoiceId) {
            $query->where('id', $invoiceId);
        }

        return $query;
    }

    /**
     * Get a query builder for categories owned by the current active company.
     * Optionally filter by a specific category ID.
     *
     * @param mixed|null $categoryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getOwnedCategoryQuery($categoryId = null)
    {
        $companyId = session('active_company_id');
        $query = Category::where('company_id', $companyId);

        if ($categoryId) {
            $query->where('id', $categoryId);
        }

        return $query;
    }



    /**
     * Get a query builder for products owned by the current active company (via their category).
     * Optionally filter by a specific product ID.
     *
     * @param mixed|null $productId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getOwnedProductQuery($productId = null, $companyId = null)
    {
        $companyId = $companyId ?? session('active_company_id');
        $query = Product::whereHas('category', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        });

        if ($productId) {
            $query->where('id', $productId);
        }

        return $query;
    }

    /**
     * Get a query builder for product pricings owned by the current active company (via product.category).
     * Optionally filter by a specific pricing ID.
     *
     * @param mixed|null $pricingId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getOwnedProductPricingQuery($pricingId = null)
    {
        $companyId = session('active_company_id');
        $query = ProductPricing::whereHas('product.category', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        });

        if ($pricingId) {
            $query->where('id', $pricingId);
        }

        return $query;
    }

    /**
     * Get a query builder for dealer companies owned by the current active company.
     * Optionally filter by a specific dealer company ID.
     *
     * @param mixed|null $dealerCompanyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getOwnedDealerCompanyQuery($dealerCompanyId = null)
    {
        $companyId = session('active_company_id');
        $query = DealerCompany::where('company_id', $companyId);

        if ($dealerCompanyId) {
            $query->where('id', $dealerCompanyId);
        }

        return $query;
    }

    /**
     * Get a query builder for dealers owned by the current active company.
     * Optionally filter by a specific dealer ID.
     *
     * @param mixed|null $dealerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getOwnedDealerQuery($dealerId = null, $companyId = null)
    {
        $companyId = $companyId ?? session('active_company_id');
        $query = Dealer::whereHas('dealerCompany', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        });

        if ($dealerId) {
            $query->where('id', $dealerId);
        }

        return $query;
    }

    /**
     * Get the active Cash Discount Slab based on company ID and number of days.
     *
     * @param int $companyId
     * @param int|float $days
     * @return \App\Models\Accounts\CashDiscountSlab|null
     */
    public static function getCashDiscountSlab($companyId, $days)
    {
        return \App\Models\Accounts\CashDiscountSlab::where('company_id', $companyId)
            ->where('minimum_days', '<=', $days)
            ->where('maximum_days', '>=', $days)
            ->where('status', 1)
            ->first();
    }

    /**
     * Generate the next unique invoice number.
     * Must be run within a DB transaction to ensure lockForUpdate consistency.
     *
     * @return string
     */
    public static function generateInvoiceNumber()
    {
        $latestInvoice = Invoice::withTrashed()->lockForUpdate()->orderBy('id', 'desc')->first();
        $nextId = $latestInvoice ? $latestInvoice->id + 1 : 1;

        $invoiceNo = 'INV-' . date('Ym') . '-' . str_pad($nextId, 7, '0', STR_PAD_LEFT);

        while (Invoice::withTrashed()->where('invoice_no', $invoiceNo)->exists()) {
            $nextId++;
            $invoiceNo = 'INV-' . date('Ym') . '-' . str_pad($nextId, 7, '0', STR_PAD_LEFT);
        }

        return $invoiceNo;
    }

    /**
     * Generate the next unique purchase invoice number.
     * Must be run within a DB transaction to ensure lockForUpdate consistency.
     *
     * @return string
     */
    public static function generatePurchaseInvoiceNumber()
    {
        $latestInvoice = PurchaseInvoice::lockForUpdate()->orderBy('id', 'desc')->first();
        $nextId = $latestInvoice ? $latestInvoice->id + 1 : 1;
        return 'PUR-' . date('Ym') . '-' . str_pad($nextId, 7, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate financial amounts for an invoice item (including GST split and rounding).
     *
     * @param float $rate
     * @param float $quantity
     * @param float $gstPercent
     * @return array
     */
    public static function calculateItemAmounts($rate, $quantity, $gstPercent = 18.00, $taxType = 'intra')
    {
        $totalAmount = $rate * $quantity;
        $gstAmount = $totalAmount * ($gstPercent / 100);

        if ($taxType === 'inter' || $taxType === 'igst') {
            $cgstAmount = 0;
            $sgstAmount = 0;
            $igstAmount = $gstAmount;
        } else {
            $cgstAmount = $gstAmount / 2;
            $sgstAmount = $gstAmount / 2;
            $igstAmount = 0;
        }

        $chargeableAmount = $totalAmount + $gstAmount;

        return [
            'total_amount' => round($totalAmount, 2),
            'gst_amount' => round($gstAmount, 2),
            'cgst_amount' => round($cgstAmount, 2),
            'sgst_amount' => round($sgstAmount, 2),
            'igst_amount' => round($igstAmount, 2),
            'chargeable_amount' => round($chargeableAmount, 2),
        ];
    }

    /**
     * Get the active role priority of the logged-in user.
     *
     * @return int
     */
    public static function getActiveRolePriority($role = null)
    {
        $activeRoleId = $role ?? session('active_role_id');
        $activeRole = Role::find($activeRoleId);
        return $activeRole ? $activeRole->priority : 0;
    }
}
