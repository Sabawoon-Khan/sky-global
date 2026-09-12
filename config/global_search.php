<?php

use App\Models\Archive\ArchivedDocument;
use App\Models\Equipment\EquipmentCatalog;
use App\Models\Finance\Invoice;
use App\Models\Hr\Contractor;
use App\Models\Hr\Employee;
use App\Models\Organization;
use App\Models\Project\Project;

/**
 * Resources included in the header global search.
 *
 * @return array<string, array<string, mixed>>
 */
return [
    'projects' => [
        'label' => 'Projects',
        'model' => Project::class,
        'permission' => 'projects',
        'search' => ['code', 'name', 'status', 'location', 'description'],
        'priority' => ['code', 'name'],
        'title' => 'name',
        'subtitle' => ['code', 'status'],
        'show_route' => 'projects.show',
        'index_route' => 'projects.index',
        'route_param' => 'project',
    ],
    'organizations' => [
        'label' => 'Organizations',
        'model' => Organization::class,
        'permission' => 'bidding',
        'search' => ['name', 'phone', 'email', 'province', 'address', 'tax_id'],
        'priority' => ['name', 'phone'],
        'phone' => ['phone'],
        'title' => 'name',
        'subtitle' => ['province', 'phone'],
        'edit_route' => 'organizations.edit',
        'show_route' => 'organizations.show',
        'index_route' => 'organizations.index',
        'route_param' => 'organization',
    ],
    'employees' => [
        'label' => 'Employees',
        'model' => Employee::class,
        'permission' => 'hr',
        'search' => ['first_name', 'last_name', 'father_name', 'phone', 'email', 'tazkira_number'],
        'priority' => ['first_name', 'last_name', 'phone'],
        'phone' => ['phone'],
        'title' => 'name',
        'subtitle' => ['phone', 'email', 'status'],
        'edit_route' => 'hr.employees.edit',
        'show_route' => 'hr.employees.show',
        'index_route' => 'hr.employees.index',
        'route_param' => 'employee',
    ],
    'contractors' => [
        'label' => 'Contractors',
        'model' => Contractor::class,
        'permission' => 'hr',
        'search' => ['first_name', 'last_name', 'father_name', 'phone', 'email', 'tazkira_number'],
        'priority' => ['first_name', 'last_name', 'phone'],
        'phone' => ['phone'],
        'title' => 'name',
        'subtitle' => ['phone', 'email', 'status'],
        'edit_route' => 'hr.contractors.edit',
        'show_route' => 'hr.contractors.show',
        'index_route' => 'hr.contractors.index',
        'route_param' => 'contractor',
    ],
    'archive' => [
        'label' => 'Archive',
        'model' => ArchivedDocument::class,
        'permission' => 'archive',
        'search' => ['reference_number', 'title', 'description'],
        'priority' => ['reference_number', 'title'],
        'title' => 'title',
        'subtitle' => ['reference_number'],
        'show_route' => 'archive.show',
        'index_route' => 'archive.index',
        'route_param' => 'archivedDocument',
    ],
    'invoices' => [
        'label' => 'Invoices',
        'model' => Invoice::class,
        'permission' => 'finance',
        'search' => ['invoice_number', 'status', 'currency'],
        'priority' => ['invoice_number'],
        'relations' => [
            'organization' => ['name'],
            'project' => ['name', 'code'],
        ],
        'title' => 'invoice_number',
        'subtitle' => ['status', 'currency'],
        'index_route' => 'finance.index',
        'route_param' => 'invoice',
        'with' => ['organization:id,name', 'project:id,name,code'],
    ],
    'equipment' => [
        'label' => 'Stock / Inventory',
        'model' => EquipmentCatalog::class,
        'permission' => 'inventory',
        'search' => ['name', 'sku', 'category', 'description'],
        'priority' => ['sku', 'name'],
        'title' => 'name',
        'subtitle' => ['sku', 'category'],
        'index_route' => 'equipment.index',
        'route_param' => 'equipmentCatalog',
    ],
];
