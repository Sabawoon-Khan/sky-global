<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\Archive\ArchivedDocumentController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Equipment\EquipmentCatalogController;
use App\Http\Controllers\Equipment\PersonnelEquipmentIssueController;
use App\Http\Controllers\Equipment\PersonnelTrainingController;
use App\Http\Controllers\Equipment\ProjectEquipmentIssueController;
use App\Http\Controllers\Equipment\TrainingSessionController;
use App\Http\Controllers\Finance\FinanceCategoryController;
use App\Http\Controllers\Finance\FinanceLedgerController;
use App\Http\Controllers\Finance\ExpenseFundController;
use App\Http\Controllers\Finance\GeneralExpenseController;
use App\Http\Controllers\Finance\GeneralIncomeController;
use App\Http\Controllers\Finance\InvoiceController;
use App\Http\Controllers\Finance\ProjectExpenseController;
use App\Http\Controllers\Finance\ProjectIncomeController;
use App\Http\Controllers\Finance\QuotationController;
use App\Http\Controllers\Finance\TaxController;
use App\Http\Controllers\Forms\AttachmentTypeController;
use App\Http\Controllers\Forms\FormTemplateController;
use App\Http\Controllers\Forms\PersonnelAttachmentController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\Hr\ContractorController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\PayrollRunController;
use App\Http\Controllers\Hr\PersonnelAttendanceController;
use App\Http\Controllers\Hr\PersonnelPayrollAdjustmentController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\Project\ProjectActivityController;
use App\Http\Controllers\Project\ProjectController;
use App\Http\Controllers\Project\ProjectDeploymentController;
use App\Http\Controllers\Project\ProjectIssueController;
use App\Http\Controllers\Project\ProjectShareholderController;
use App\Http\Controllers\Project\ProjectSiteController;
use App\Http\Controllers\Settings\ActivityLogController;
use App\Http\Controllers\Settings\AuthenticationLogController;
use App\Http\Controllers\Settings\CurrencySettingsController;
use App\Http\Controllers\Settings\OrganizationTypeController;
use App\Http\Controllers\Settings\PayrollSettingController;
use App\Http\Controllers\Settings\RoleManagementController;
use App\Http\Controllers\Settings\StorageBackupController;
use App\Http\Controllers\Settings\TranslationController;
use App\Http\Controllers\Settings\UserManagementController;
use App\Http\Controllers\Training\TrainingCertificateController;
use App\Http\Controllers\Training\TrainingFieldController;
use App\Http\Controllers\Training\TrainingFieldGuardController;
use App\Http\Controllers\Training\TrainingFieldReportController;
use App\Http\Controllers\Training\TrainingGuardController;
use App\Http\Controllers\Training\TrainingGuardConversionController;
use App\Http\Controllers\Training\TrainingMinistryPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('search', GlobalSearchController::class)->name('search');

    Route::prefix('assignments')->name('assignments.')->whereNumber('assignment')->group(function () {
        Route::get('/', [AssignmentController::class, 'index'])->name('index');
        Route::post('/', [AssignmentController::class, 'store'])->name('store');
        Route::get('{assignment}', [AssignmentController::class, 'show'])->name('show');
        Route::put('{assignment}', [AssignmentController::class, 'update'])->name('update');
        Route::delete('{assignment}', [AssignmentController::class, 'destroy'])->name('destroy');
        Route::post('{assignment}/replies', [AssignmentController::class, 'storeReply'])->name('replies.store');
    });

    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::get('organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::get('organizations/{organization}/edit', [OrganizationController::class, 'edit'])->name('organizations.edit');
    Route::put('organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::delete('organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');

    Route::redirect('bidding', '/mis/projects');
    Route::redirect('bidding/opportunities', '/mis/projects');
    Route::redirect('bidding/bids', '/mis/projects');

    Route::prefix('mis/projects')->name('projects.')->whereNumber('project')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('create', [ProjectController::class, 'create'])->name('create');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::get('{project}', [ProjectController::class, 'show'])->name('show');
        Route::put('{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('{project}', [ProjectController::class, 'destroy'])->name('destroy');
        Route::post('{project}/status', [ProjectController::class, 'updateStatus'])->name('status');
        Route::post('{project}/archive', [ProjectController::class, 'archive'])->name('archive');
        Route::put('{project}/details', [ProjectController::class, 'updateDetails'])->name('details.update');

        Route::post('{project}/competitors', [ProjectController::class, 'storeCompetitorBid'])->name('competitors.store');
        Route::delete('{project}/competitors/{competitorBid}', [ProjectController::class, 'destroyCompetitorBid'])->name('competitors.destroy');

        Route::post('{project}/incomes', [ProjectController::class, 'storeIncome'])->name('incomes.store');
        Route::post('{project}/expenses', [ProjectController::class, 'storeExpense'])->name('expenses.store');

        Route::post('{project}/activities', [ProjectActivityController::class, 'store'])->name('activities.store');
        Route::get('{project}/issues', [ProjectIssueController::class, 'index'])->name('issues.index');
        Route::post('{project}/issues', [ProjectIssueController::class, 'store'])->name('issues.store');
        Route::put('{project}/issues/{issue}', [ProjectIssueController::class, 'update'])->name('issues.update');
        Route::delete('{project}/issues/{issue}', [ProjectIssueController::class, 'destroy'])->name('issues.destroy');

        Route::post('{project}/sites', [ProjectSiteController::class, 'store'])->name('sites.store');
        Route::put('{project}/sites/{site}', [ProjectSiteController::class, 'update'])->name('sites.update');
        Route::delete('{project}/sites/{site}', [ProjectSiteController::class, 'destroy'])->name('sites.destroy');

        Route::post('{project}/deployments', [ProjectDeploymentController::class, 'store'])->name('deployments.store');
        Route::put('{project}/deployments/{deployment}', [ProjectDeploymentController::class, 'update'])->name('deployments.update');
        Route::delete('{project}/deployments/{deployment}', [ProjectDeploymentController::class, 'destroy'])->name('deployments.destroy');

        Route::post('{project}/equipment-issues', [ProjectEquipmentIssueController::class, 'store'])->name('equipment-issues.store');
        Route::post('{project}/equipment-issues/{issue}/return', [ProjectEquipmentIssueController::class, 'returnItems'])->name('equipment-issues.return');

        Route::post('{project}/shareholders', [ProjectShareholderController::class, 'store'])->name('shareholders.store');
        Route::put('{project}/shareholders/{shareholder}', [ProjectShareholderController::class, 'update'])->name('shareholders.update');
        Route::delete('{project}/shareholders/{shareholder}', [ProjectShareholderController::class, 'destroy'])->name('shareholders.destroy');
        Route::post('{project}/shareholders/{shareholder}/contribute', [ProjectShareholderController::class, 'contribute'])->name('shareholders.contribute');
        Route::post('{project}/shareholders/{shareholder}/distribute', [ProjectShareholderController::class, 'distribute'])->name('shareholders.distribute');
    });

    Route::prefix('archive')->name('archive.')->group(function () {
        Route::get('/', [ArchivedDocumentController::class, 'index'])->name('index');
        Route::get('create', [ArchivedDocumentController::class, 'create'])->name('create');
        Route::post('/', [ArchivedDocumentController::class, 'store'])->name('store');
        Route::get('{archivedDocument}/download', [ArchivedDocumentController::class, 'download'])->name('download');
        Route::get('{archivedDocument}', [ArchivedDocumentController::class, 'show'])->name('show');
        Route::put('{archivedDocument}', [ArchivedDocumentController::class, 'update'])->name('update');
        Route::delete('{archivedDocument}', [ArchivedDocumentController::class, 'destroy'])->name('destroy');
        Route::post('{archivedDocument}/archive', [ArchivedDocumentController::class, 'archive'])->name('archive');
    });

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('ledger', [FinanceLedgerController::class, 'index'])->name('ledger');
        Route::get('ledger/print', [FinanceLedgerController::class, 'print'])->name('ledger.print');
        Route::post('ledger/lines', [FinanceLedgerController::class, 'storeLine'])->name('ledger.lines.store');
        Route::get('tax', [TaxController::class, 'index'])->name('tax');
        Route::get('tax/print', [TaxController::class, 'print'])->name('tax.print');
        Route::post('tax/payments', [TaxController::class, 'storePayment'])->name('tax.payments.store');
        Route::get('income', [ProjectIncomeController::class, 'index'])->name('income');
        Route::get('expenses', [ProjectExpenseController::class, 'index'])->name('expenses');
        Route::get('general-income', [GeneralIncomeController::class, 'index'])->name('general-income');
        Route::get('general-expenses', [GeneralExpenseController::class, 'index'])->name('general-expenses');
        Route::post('categories', [FinanceCategoryController::class, 'store'])->name('categories.store');
        Route::delete('categories/{financeCategory}', [FinanceCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::post('incomes', [ProjectIncomeController::class, 'store'])->name('incomes.store');
        Route::put('incomes/{income}', [ProjectIncomeController::class, 'update'])->name('incomes.update');
        Route::delete('incomes/{income}', [ProjectIncomeController::class, 'destroy'])->name('incomes.destroy');

        Route::post('expenses', [ProjectExpenseController::class, 'store'])->name('expenses.store');
        Route::put('expenses/{expense}', [ProjectExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('expenses/{expense}', [ProjectExpenseController::class, 'destroy'])->name('expenses.destroy');

        Route::post('expense-funds', [ExpenseFundController::class, 'store'])->name('expense-funds.store');
        Route::put('expense-funds/{expenseFund}', [ExpenseFundController::class, 'update'])->name('expense-funds.update');
        Route::delete('expense-funds/{expenseFund}', [ExpenseFundController::class, 'destroy'])->name('expense-funds.destroy');

        Route::post('general-expenses', [GeneralExpenseController::class, 'store'])->name('general-expenses.store');
        Route::put('general-expenses/{generalExpense}', [GeneralExpenseController::class, 'update'])->name('general-expenses.update');
        Route::delete('general-expenses/{generalExpense}', [GeneralExpenseController::class, 'destroy'])->name('general-expenses.destroy');

        Route::post('general-incomes', [GeneralIncomeController::class, 'store'])->name('general-incomes.store');
        Route::put('general-incomes/{generalIncome}', [GeneralIncomeController::class, 'update'])->name('general-incomes.update');
        Route::delete('general-incomes/{generalIncome}', [GeneralIncomeController::class, 'destroy'])->name('general-incomes.destroy');

        Route::get('invoices', [InvoiceController::class, 'invoices'])->name('invoices');
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');

        Route::get('quotations', [QuotationController::class, 'index'])->name('quotations');
        Route::get('quotations/{quotation}/print', [QuotationController::class, 'print'])->name('quotations.print');
        Route::post('quotations', [QuotationController::class, 'store'])->name('quotations.store');
        Route::delete('quotations/{quotation}', [QuotationController::class, 'destroy'])->name('quotations.destroy');
    });

    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('employees/{employee}/history', [EmployeeController::class, 'printHistory'])->name('employees.history');
        Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');

        Route::get('contractors', [ContractorController::class, 'index'])->name('contractors.index');
        Route::get('contractors/create', [ContractorController::class, 'create'])->name('contractors.create');
        Route::post('contractors', [ContractorController::class, 'store'])->name('contractors.store');
        Route::get('contractors/{contractor}', [ContractorController::class, 'show'])->name('contractors.show');
        Route::get('contractors/{contractor}/edit', [ContractorController::class, 'edit'])->name('contractors.edit');
        Route::put('contractors/{contractor}', [ContractorController::class, 'update'])->name('contractors.update');

        Route::get('attendance', [PersonnelAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('attendance/print', [PersonnelAttendanceController::class, 'print'])->name('attendance.print');
        Route::post('attendance/sheets/{sheet}/approve', [PersonnelAttendanceController::class, 'approveSheet'])->name('attendance.sheets.approve');
        Route::delete('attendance/sheets/{sheet}', [PersonnelAttendanceController::class, 'destroySheet'])->name('attendance.sheets.destroy');
        Route::get('attendance/create', [PersonnelAttendanceController::class, 'create'])->name('attendance.create');
        Route::post('attendance', [PersonnelAttendanceController::class, 'store'])->name('attendance.store');
        Route::post('attendance/bulk', [PersonnelAttendanceController::class, 'storeBulk'])->name('attendance.bulk');
        Route::put('attendance/{attendance}', [PersonnelAttendanceController::class, 'update'])->name('attendance.update');
        Route::post('attendance/{attendance}/approve', [PersonnelAttendanceController::class, 'approve'])->name('attendance.approve');

        Route::get('payroll-adjustments', [PersonnelPayrollAdjustmentController::class, 'index'])->name('payroll-adjustments.index');
        Route::post('payroll-adjustments', [PersonnelPayrollAdjustmentController::class, 'store'])->name('payroll-adjustments.store');
        Route::post('payroll-adjustments/bulk', [PersonnelPayrollAdjustmentController::class, 'storeBulk'])->name('payroll-adjustments.bulk');
        Route::delete('payroll-adjustments/{payrollAdjustment}', [PersonnelPayrollAdjustmentController::class, 'destroy'])->name('payroll-adjustments.destroy');

        Route::get('payroll', [PayrollRunController::class, 'index'])->name('payroll.index');
        Route::post('payroll', [PayrollRunController::class, 'store'])->name('payroll.store');
        Route::get('payroll/{payrollRun}', [PayrollRunController::class, 'show'])->name('payroll.show');
        Route::get('payroll/{payrollRun}/print', [PayrollRunController::class, 'print'])->name('payroll.print');
        Route::delete('payroll/{payrollRun}', [PayrollRunController::class, 'destroy'])->name('payroll.destroy');
        Route::post('payroll/{payrollRun}/process', [PayrollRunController::class, 'process'])->name('payroll.process');
        Route::put('payroll/{payrollRun}/items/{payrollItem}', [PayrollRunController::class, 'updateItem'])->name('payroll.items.update');
    });

    Route::prefix('training')->name('training.')->group(function () {
        Route::redirect('/', '/training/guards');
        Route::get('guards', [TrainingGuardController::class, 'index'])->name('guards.index');
        Route::get('guards/create', [TrainingGuardController::class, 'create'])->name('guards.create');
        Route::post('guards', [TrainingGuardController::class, 'store'])->name('guards.store');

        Route::get('company', [TrainingGuardController::class, 'company'])->name('company.index');
        Route::post('company', [TrainingGuardController::class, 'assignCompanyBulk'])->name('company.store');

        Route::get('payments', [TrainingMinistryPaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/create', [TrainingMinistryPaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [TrainingMinistryPaymentController::class, 'store'])->name('payments.store');
        Route::get('payments/{trainingMinistryPayment}/receipt', [TrainingMinistryPaymentController::class, 'downloadReceipt'])
            ->whereNumber('trainingMinistryPayment')
            ->name('payments.receipt');
        Route::get('payments/{trainingMinistryPayment}', [TrainingMinistryPaymentController::class, 'show'])
            ->whereNumber('trainingMinistryPayment')
            ->name('payments.show');
        Route::delete('payments/{trainingMinistryPayment}', [TrainingMinistryPaymentController::class, 'destroy'])
            ->whereNumber('trainingMinistryPayment')
            ->name('payments.destroy');

        Route::get('certificates', [TrainingCertificateController::class, 'index'])->name('certificates.index');

        Route::redirect('field', '/training/field/reports');
        Route::get('field/reports', [TrainingFieldReportController::class, 'index'])->name('field.reports.index');
        Route::get('field/reports/create', [TrainingFieldReportController::class, 'create'])->name('field.reports.create');
        Route::post('field/reports', [TrainingFieldReportController::class, 'store'])->name('field.reports.store');
        Route::get('field/reports/{trainingFieldReport}', [TrainingFieldReportController::class, 'show'])
            ->whereNumber('trainingFieldReport')
            ->name('field.reports.show');
        Route::delete('field/reports/{trainingFieldReport}', [TrainingFieldReportController::class, 'destroy'])
            ->whereNumber('trainingFieldReport')
            ->name('field.reports.destroy');
        Route::get('field/reports/{trainingFieldReport}/attachment', [TrainingFieldReportController::class, 'downloadAttachment'])
            ->whereNumber('trainingFieldReport')
            ->name('field.reports.attachment');
        Route::get('field/roster', [TrainingFieldController::class, 'roster'])->name('field.roster.index');
        Route::post('field/roster', [TrainingFieldGuardController::class, 'store'])->name('field.roster.store');
        Route::post('guards/{trainingGuard}/employee', [TrainingGuardConversionController::class, 'toEmployee'])
            ->whereNumber('trainingGuard')
            ->name('guards.to-employee');
        Route::post('guards/{trainingGuard}/contractor', [TrainingGuardConversionController::class, 'toContractor'])
            ->whereNumber('trainingGuard')
            ->name('guards.to-contractor');
        Route::delete('field/roster/{trainingFieldGuard}', [TrainingFieldGuardController::class, 'destroy'])
            ->whereNumber('trainingFieldGuard')
            ->name('field.roster.destroy');

        Route::get('guards/{trainingGuard}/certificate/print', [TrainingGuardController::class, 'printCertificate'])
            ->whereNumber('trainingGuard')
            ->name('guards.certificate.print');
        Route::get('guards/{trainingGuard}/certificate/download', [TrainingGuardController::class, 'downloadCertificate'])
            ->whereNumber('trainingGuard')
            ->name('guards.certificate.download');
        Route::post('guards/{trainingGuard}/company', [TrainingGuardController::class, 'assignCompany'])
            ->whereNumber('trainingGuard')
            ->name('guards.company');
        Route::post('guards/{trainingGuard}/complete', [TrainingGuardController::class, 'complete'])
            ->whereNumber('trainingGuard')
            ->name('guards.complete');
        Route::post('guards/{trainingGuard}/certificate', [TrainingGuardController::class, 'issueCertificate'])
            ->whereNumber('trainingGuard')
            ->name('guards.certificate');
        Route::get('guards/{trainingGuard}/edit', [TrainingGuardController::class, 'edit'])
            ->whereNumber('trainingGuard')
            ->name('guards.edit');
        Route::get('guards/{trainingGuard}', [TrainingGuardController::class, 'show'])
            ->whereNumber('trainingGuard')
            ->name('guards.show');
        Route::put('guards/{trainingGuard}', [TrainingGuardController::class, 'update'])
            ->whereNumber('trainingGuard')
            ->name('guards.update');
        Route::delete('guards/{trainingGuard}', [TrainingGuardController::class, 'destroy'])
            ->whereNumber('trainingGuard')
            ->name('guards.destroy');
    });

    Route::prefix('forms')->name('forms.')->group(function () {
        Route::get('templates', [FormTemplateController::class, 'index'])->name('templates.index');
        Route::post('templates', [FormTemplateController::class, 'store'])->name('templates.store');
        Route::put('templates/{formTemplate}', [FormTemplateController::class, 'update'])->name('templates.update');

        Route::get('attachment-types', [AttachmentTypeController::class, 'index'])->name('attachment-types.index');
        Route::post('attachment-types', [AttachmentTypeController::class, 'store'])->name('attachment-types.store');
        Route::put('attachment-types/{attachmentType}', [AttachmentTypeController::class, 'update'])->name('attachment-types.update');
        Route::delete('attachment-types/{attachmentType}', [AttachmentTypeController::class, 'destroy'])->name('attachment-types.destroy');

        Route::get('personnel-attachments/{personnelAttachment}/download', [PersonnelAttachmentController::class, 'download'])->name('personnel-attachments.download');
        Route::post('personnel-attachments', [PersonnelAttachmentController::class, 'store'])->name('personnel-attachments.store');
        Route::delete('personnel-attachments/{personnelAttachment}', [PersonnelAttachmentController::class, 'destroy'])->name('personnel-attachments.destroy');
    });

    Route::prefix('equipment')->name('equipment.')->group(function () {
        Route::get('/', [EquipmentCatalogController::class, 'index'])->name('index');
        Route::post('/', [EquipmentCatalogController::class, 'store'])->name('store');
        Route::put('{equipmentCatalog}', [EquipmentCatalogController::class, 'update'])->name('update');
        Route::delete('{equipmentCatalog}', [EquipmentCatalogController::class, 'destroy'])->name('destroy');
        Route::post('{equipmentCatalog}/adjust-stock', [EquipmentCatalogController::class, 'adjustStock'])->name('adjust-stock');

        Route::post('issues', [PersonnelEquipmentIssueController::class, 'store'])->name('issues.store');
        Route::get('training', [TrainingSessionController::class, 'index'])->name('training.index');
        Route::post('training', [TrainingSessionController::class, 'store'])->name('training.store');
        Route::post('personnel-training', [PersonnelTrainingController::class, 'store'])->name('personnel-training.store');
    });

    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('bidding', [AnalyticsController::class, 'bidding'])->name('bidding');
        Route::get('finance', [AnalyticsController::class, 'finance'])->name('finance');
        Route::get('finance/print', [AnalyticsController::class, 'financePrint'])->name('finance.print');
        Route::get('reports', [AnalyticsController::class, 'reports'])->name('reports');
        Route::get('reports/print', [AnalyticsController::class, 'reportsPrint'])->name('reports.print');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

        Route::get('login-logs', [AuthenticationLogController::class, 'index'])->name('login-logs.index');
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

        Route::get('roles', [RoleManagementController::class, 'index'])->name('roles.index');
        Route::get('roles/create', [RoleManagementController::class, 'create'])->name('roles.create');
        Route::post('roles', [RoleManagementController::class, 'store'])->name('roles.store');
        Route::put('roles/{role}', [RoleManagementController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleManagementController::class, 'destroy'])->name('roles.destroy');

        Route::get('payroll-rules', [PayrollSettingController::class, 'edit'])->name('payroll-rules.edit');
        Route::put('payroll-rules', [PayrollSettingController::class, 'update'])->name('payroll-rules.update');

        Route::get('organization-types', [OrganizationTypeController::class, 'index'])->name('organization-types.index');
        Route::get('organization-types/create', [OrganizationTypeController::class, 'create'])->name('organization-types.create');
        Route::post('organization-types', [OrganizationTypeController::class, 'store'])->name('organization-types.store');
        Route::put('organization-types/{organizationType}', [OrganizationTypeController::class, 'update'])->name('organization-types.update');
        Route::delete('organization-types/{organizationType}', [OrganizationTypeController::class, 'destroy'])->name('organization-types.destroy');

        Route::get('form-types', [AttachmentTypeController::class, 'index'])->name('form-types.index');
        Route::get('form-types/create', [AttachmentTypeController::class, 'create'])->name('form-types.create');
        Route::post('form-types', [AttachmentTypeController::class, 'store'])->name('form-types.store');
        Route::put('form-types/{attachmentType}', [AttachmentTypeController::class, 'update'])->name('form-types.update');
        Route::delete('form-types/{attachmentType}', [AttachmentTypeController::class, 'destroy'])->name('form-types.destroy');

        Route::get('currencies', [CurrencySettingsController::class, 'index'])->name('currencies.index');
        Route::get('currencies/create', [CurrencySettingsController::class, 'createCurrency'])->name('currencies.create');
        Route::post('currencies', [CurrencySettingsController::class, 'storeCurrency'])->name('currencies.store');
        Route::put('currencies/{currency}', [CurrencySettingsController::class, 'updateCurrency'])->name('currencies.update');
        Route::delete('currencies/{currency}', [CurrencySettingsController::class, 'destroyCurrency'])->name('currencies.destroy');

        Route::get('exchange-rates/create', [CurrencySettingsController::class, 'createExchangeRate'])->name('exchange-rates.create');
        Route::post('exchange-rates', [CurrencySettingsController::class, 'storeExchangeRate'])->name('exchange-rates.store');
        Route::put('exchange-rates/{exchangeRate}', [CurrencySettingsController::class, 'updateExchangeRate'])->name('exchange-rates.update');
        Route::delete('exchange-rates/{exchangeRate}', [CurrencySettingsController::class, 'destroyExchangeRate'])->name('exchange-rates.destroy');

        Route::get('backups', [StorageBackupController::class, 'index'])->name('backups.index');
        Route::post('backups', [StorageBackupController::class, 'store'])->name('backups.store');
        Route::get('backups/{storageBackup}/download', [StorageBackupController::class, 'download'])->name('backups.download');
        Route::delete('backups/{storageBackup}', [StorageBackupController::class, 'destroy'])->name('backups.destroy');

        Route::get('translations', [TranslationController::class, 'index'])->name('translations.index');
        Route::put('translations', [TranslationController::class, 'update'])->name('translations.update');
    });
});
