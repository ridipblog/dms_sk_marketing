<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sidebar Menu Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can define the sidebar menu for your application. Each item
    | can have a title, icon, route, and permissions based on roles and companies.
    |
    | Using ['*'] means it is available to all roles or companies.
    | Omitting the 'roles' or 'companies' key defaults to ['*'].
    |
    */

    'menu' => [
        [
            'title' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'route' => 'dashboard',
            'roles' => ['*'],
            'companies' => ['*'],
        ],
        [
            'title' => 'User Management',
            'icon' => 'fas fa-users',
            'roles' => ['Admin Users', 'Super Admin', 'Manager'],
            'submodules' => [
                [
                    'title' => 'All Users',
                    'route' => 'users.index', // Ensure these routes exist, or they will throw an error if route() is called
                    'roles' => ['Admin Users', 'Super Admin', 'Manager'],
                    'companies' => ['2'],
                ],
                [
                    'title' => 'Add User',
                    'route' => 'users.create',
                    'roles' => ['Admin Users', 'Super Admin'],
                    'companies' => ['1'],
                ],
                [
                    'title' => 'Set Role and Company',
                    'route' => 'users.roles.index',
                    'roles' => ['Admin Users', 'Super Admin'],
                    'companies' => ['*'],
                ],
                [
                    'title' => 'Upload Users',
                    'route' => 'users.uploads.index',
                    'api_routes' => ['users.uploads.list', 'users.uploads.import', 'users.uploads.template', 'users.uploads.errors'],
                    'roles' => ['Admin Users', 'Super Admin'],
                    'companies' => ['*'],
                ]
            ]
        ],
        [
            'title' => 'Dealer Management',
            'icon' => 'fas fa-store',
            'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Dealer', 'Assistant Section Officer (ASO)', 'Finance Team'],
            'submodules' => [
                [
                    'title' => 'Dealers List',
                    'route' => 'dealers.index',
                    'api_routes' => ['dealers.list', 'dealers.store', 'dealers.show', 'dealers.edit', 'dealers.update'],
                    // 'submodules' => [
                    //     [
                    //         'route' => 'dealers.store',
                    //         'roles' => ['Admin Users','Super Admin', 'Manager', 'Dealer'],
                    //     ],
                    // ], // for special permission
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ],
                [
                    'title' => 'Upload Dealers',
                    'route' => 'dealers.upload.index',
                    'api_routes' => ['dealers.upload.list', 'dealers.upload.import', 'dealers.upload.template'],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ],
                [
                    'title' => 'Scheme Amount',
                    'route' => 'dealers.scheme_amounts.index',
                    'api_routes' => ['dealers.scheme_amounts.list', 'dealers.scheme_amounts.store', 'dealers.scheme_amounts.delete'],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ],
                [
                    'title' => 'My Dealership',
                    'route' => 'dealers.profile',
                    'roles' => ['Dealer'],
                ]
            ]
        ],
        [
            'title' => 'Inventory Management',
            'icon' => 'fas fa-boxes',
            'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
            'submodules' => [
                [
                    'title' => 'Categories',
                    'route' => 'categories.index',
                    'api_routes' => ['categories.list', 'categories.store', 'categories.edit', 'categories.update'],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ],
                [
                    'title' => 'Products',
                    'route' => 'products.index',
                    'api_routes' => ['products.list', 'products.store', 'products.edit', 'products.update'],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ],
                [
                    'title' => 'Product Pricing',
                    'route' => 'product_pricings.index',
                    'api_routes' => ['product_pricings.list', 'product_pricings.store', 'product_pricings.edit', 'product_pricings.update'],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ],
                [
                    'title' => 'Upload Inventory',
                    'route' => 'inventory.upload.index',
                    'api_routes' => ['inventory.upload.list', 'inventory.upload.import', 'inventory.upload.template'],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ]
            ]
        ],
        [
            'title' => 'Dealer Accounts',
            'icon' => 'fas fa-file-invoice-dollar',
            'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team', 'Dealer'],
            'submodules' => [
                [
                    'title' => 'Generate Invoice',
                    'route' => 'accounts.invoices.index',
                    'view_routes' => ['accounts.invoices.generate', 'accounts.invoices.view'],
                    'api_routes' => ['accounts.invoices.list'],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Dealer', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ],
                [
                    'title' => 'Upload Excel',
                    'route' => 'accounts.upload.index',
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                    'api_routes' => [
                        'accounts.upload.invoices.list',
                        'accounts.upload.vouchers.list',
                        'accounts.upload.invoices.import',
                        'accounts.upload.vouchers.import',
                        'accounts.upload.invoices.template',
                        'accounts.upload.vouchers.template',
                        'accounts.upload.errors.download'
                    ]
                ],
                [
                    'title' => 'Company-Wise Upload',
                    'route' => 'accounts.invoices.company_wise_upload.index',
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                    'api_routes' => [
                        'accounts.invoices.company_wise_upload.list',
                        'accounts.invoices.company_wise_upload.import',
                        'accounts.invoices.company_wise_upload.template',
                    ]
                ],
                [
                    'title' => 'Payment Receipts',
                    'route' => 'accounts.payment_tracks.index',
                    'view_routes' => ['accounts.payment_tracks.voucher'],
                    'api_routes' => [
                        'accounts.payment_tracks.list',
                    ],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team', 'Dealer'],
                ],
                [
                    'title' => 'Debit Notes',
                    'route' => 'accounts.debit_notes.index',
                    'api_routes' => ['accounts.debit_notes.list'],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team', 'Dealer'],
                ],
                [
                    'title' => 'Credit Notes',
                    'route' => 'accounts.credit_notes.index',
                    'api_routes' => ['accounts.credit_notes.list'],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team', 'Dealer'],
                ],
                [
                    'title' => 'Slab Management',
                    'route' => 'accounts.cash_discount_slabs.index',
                    'api_routes' => ['accounts.cash_discount_slabs.list'],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager'],
                ],
                [
                    'title' => 'Dealer Statement',
                    'route' => 'accounts.dealer_statement.index',
                    'api_routes' => [
                        'accounts.payment_tracks.ledger'
                    ],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team', 'Dealer'],
                ],
                [
                    'title' => 'Invoice Ageing',
                    'route' => 'accounts.ageing_report.index',
                    'api_routes' => [
                        'accounts.ageing_report.list',
                        'accounts.ageing_report.export'
                    ],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ],
                [
                    'title' => 'Order Tracking & PDF',
                    'route' => 'accounts.order_tracking.index',
                    'api_routes' => [
                        'accounts.order_tracking.dealer_pdf',
                        'accounts.order_tracking.get_orders',
                        'accounts.order_tracking.order_pdf',
                        'accounts.order_tracking.orderview',
                    ],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team', 'Dealer'],
                ]
            ]
        ],
        [
            'title' => 'Purchase Management',
            'icon' => 'fas fa-shopping-cart',
            'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
            'submodules' => [
                [
                    'title' => 'Suppliers',
                    'route' => 'purchase.suppliers.index',
                    'api_routes' => ['purchase.suppliers.list', 'purchase.suppliers.store', 'purchase.suppliers.edit', 'purchase.suppliers.update'],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ],
                [
                    'title' => 'Purchase Invoices',
                    'route' => 'purchase.invoices.index',
                    'view_routes' => ['purchase.invoices.generate', 'purchase.invoices.view'],
                    'api_routes' => ['purchase.invoices.list', 'purchase.invoices.store', 'purchase.invoices.fetch_items', 'purchase.invoices.store_item', 'purchase.invoices.delete_item', 'purchase.invoices.finalize', 'purchase.invoices.payment'],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ],
//                [
//                    'title' => 'Product Stock',
//                    'route' => 'inventory.stocks.index',
//                    'api_routes' => ['inventory.stocks.adjust'],
//                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
//                ]
            ]
        ],
        [
            'title' => 'Reports',
            'icon' => 'fas fa-chart-line',
            'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
            'submodules' => [
                [
                    'title' => 'Dealer Wise Payment Report',
                    'route' => 'reports.dealer_payments.index',
                    'api_routes' => ['reports.dealer_payments.list', 'reports.dealer_payments.export'],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ],
                [
                    'title' => 'Dealer Wise Sales Report',
                    'route' => 'reports.dealer_sales.index',
                    'api_routes' => ['reports.dealer_sales.list', 'reports.dealer_sales.export'],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ],
                [
                    'title' => 'Daily Stock Report',
                    'route' => 'reports.daily_stock.index',
                    'api_routes' => ['reports.daily_stock.list', 'reports.daily_stock.export'],
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ]
            ]
        ],
        [
            'title' => 'Settings',
            'icon' => 'fas fa-cog',
            'roles' => ['Admin Users', 'Super Admin', 'Assistant Section Officer (ASO)', 'Finance Team'],
            'companies' => ['*'],
            'submodules' => [
                [
                    'title' => 'Change Password',
                    'route' => 'settings.password',
                    'api_routes' => ['settings.password.update'],
                    'roles' => ['Admin Users', 'Super Admin', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ],
                [
                    'title' => 'Finance',
                    'route' => 'settings.finance.index',
                    'api_routes' => ['settings.finance.update'],
                    'roles' => ['Admin Users', 'Super Admin', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ]
            ]
        ]
    ]
];
