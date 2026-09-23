<?php

return [
    'groups' => [
        [
            'key' => 'analytics',
            'module_label_key' => 'reports.module.analytics',
            'items' => [
                [
                    'key' => 'finance_statement',
                    'title_key' => 'reports.finance_statement.title',
                    'description_key' => 'reports.finance_statement.description',
                    'permission' => 'finance.view',
                    'screen_href' => '/analytics/finance',
                    'print_href' => '/analytics/finance/print?year=all',
                ],
                [
                    'key' => 'bidding_overview',
                    'title_key' => 'reports.bidding_overview.title',
                    'description_key' => 'reports.bidding_overview.description',
                    'permission' => 'bidding.view',
                    'screen_href' => '/analytics/bidding',
                ],
            ],
        ],
        [
            'key' => 'finance',
            'module_label_key' => 'reports.module.finance',
            'items' => [
                [
                    'key' => 'monthly_ledger',
                    'title_key' => 'reports.monthly_ledger.title',
                    'description_key' => 'reports.monthly_ledger.description',
                    'permission' => 'finance.view',
                    'screen_href' => '/finance/ledger',
                    'print_note_key' => 'reports.print_note.date_range',
                ],
                [
                    'key' => 'company_tax',
                    'title_key' => 'reports.company_tax.title',
                    'description_key' => 'reports.company_tax.description',
                    'permission' => 'finance.view',
                    'screen_href' => '/finance/tax',
                    'print_note_key' => 'reports.print_note.tax_period',
                ],
                [
                    'key' => 'invoices',
                    'title_key' => 'reports.invoices.title',
                    'description_key' => 'reports.invoices.description',
                    'permission' => 'finance.view',
                    'screen_href' => '/finance/invoices',
                    'print_note_key' => 'reports.print_note.per_record',
                ],
                [
                    'key' => 'quotations',
                    'title_key' => 'reports.quotations.title',
                    'description_key' => 'reports.quotations.description',
                    'permission' => 'finance.view',
                    'screen_href' => '/finance/quotations',
                    'print_note_key' => 'reports.print_note.per_record',
                ],
            ],
        ],
        [
            'key' => 'hr',
            'module_label_key' => 'reports.module.hr',
            'items' => [
                [
                    'key' => 'attendance',
                    'title_key' => 'reports.attendance.title',
                    'description_key' => 'reports.attendance.description',
                    'permission' => 'hr.view',
                    'screen_href' => '/hr/attendance',
                    'print_note_key' => 'reports.print_note.attendance_sheet',
                ],
                [
                    'key' => 'payroll',
                    'title_key' => 'reports.payroll.title',
                    'description_key' => 'reports.payroll.description',
                    'permission' => 'hr.view',
                    'screen_href' => '/hr/payroll',
                    'print_note_key' => 'reports.print_note.per_record',
                ],
                [
                    'key' => 'employee_history',
                    'title_key' => 'reports.employee_history.title',
                    'description_key' => 'reports.employee_history.description',
                    'permission' => 'hr.view',
                    'screen_href' => '/hr/employees',
                    'print_note_key' => 'reports.print_note.per_employee',
                ],
            ],
        ],
        [
            'key' => 'training',
            'module_label_key' => 'reports.module.training',
            'items' => [
                [
                    'key' => 'guard_certificate',
                    'title_key' => 'reports.guard_certificate.title',
                    'description_key' => 'reports.guard_certificate.description',
                    'permission' => 'training.view',
                    'screen_href' => '/training/guards',
                    'print_note_key' => 'reports.print_note.per_guard',
                ],
            ],
        ],
    ],
];
