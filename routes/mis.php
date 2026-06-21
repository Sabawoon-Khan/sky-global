<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Archive\ArchivedDocumentController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Equipment\EquipmentCatalogController;
use App\Http\Controllers\Equipment\PersonnelEquipmentIssueController;
use App\Http\Controllers\Equipment\PersonnelTrainingController;
use App\Http\Controllers\Equipment\TrainingSessionController;
use App\Http\Controllers\Finance\GeneralExpenseController;
use App\Http\Controllers\Finance\InvoiceController;
use App\Http\Controllers\Finance\ProjectExpenseController;
use App\Http\Controllers\Finance\ProjectIncomeController;
use App\Http\Controllers\Forms\AttachmentTypeController;
use App\Http\Controllers\Forms\FormTemplateController;
use App\Http\Controllers\Forms\PersonnelAttachmentController;
use App\Http\Controllers\Hr\ContractorController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\PayrollRunController;
use App\Http\Controllers\Hr\PersonnelAttendanceController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\Project\ProjectActivityController;
use App\Http\Controllers\Project\ProjectController;
use App\Http\Controllers\Project\ProjectDeploymentController;
use App\Http\Controllers\Project\ProjectIssueController;
use App\Http\Controllers\Project\ProjectSiteController;
use App\Http\Controllers\Settings\OrganizationTypeController;
use App\Http\Controllers\Settings\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::get('organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::get('organizations/{organization}/edit', [OrganizationController::class, 'edit'])->name('organizations.edit');
    Route::put('organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::delete('organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');

    Route::redirect('bidding', '/projects');
    Route::redirect('bidding/opportunities', '/projects');
    Route::redirect('bidding/bids', '/projects');

    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('create', [ProjectController::class, 'create'])->name('create');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::get('{project}', [ProjectController::class, 'show'])->name('show');
        Route::put('{project}', [ProjectController::class, 'update'])->name('update');
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

        Route::post('{project}/sites', [ProjectSiteController::class, 'store'])->name('sites.store');
        Route::put('{project}/sites/{site}', [ProjectSiteController::class, 'update'])->name('sites.update');
        Route::delete('{project}/sites/{site}', [ProjectSiteController::class, 'destroy'])->name('sites.destroy');

        Route::post('{project}/deployments', [ProjectDeploymentController::class, 'store'])->name('deployments.store');
        Route::put('{project}/deployments/{deployment}', [ProjectDeploymentController::class, 'update'])->name('deployments.update');
        Route::delete('{project}/deployments/{deployment}', [ProjectDeploymentController::class, 'destroy'])->name('deployments.destroy');
    });

    Route::prefix('archive')->name('archive.')->group(function () {
        Route::get('/', [ArchivedDocumentController::class, 'index'])->name('index');
        Route::post('/', [ArchivedDocumentController::class, 'store'])->name('store');
        Route::get('{archivedDocument}', [ArchivedDocumentController::class, 'show'])->name('show');
        Route::put('{archivedDocument}', [ArchivedDocumentController::class, 'update'])->name('update');
        Route::post('{archivedDocument}/archive', [ArchivedDocumentController::class, 'archive'])->name('archive');
    });

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [ProjectIncomeController::class, 'index'])->name('index');
        Route::post('incomes', [ProjectIncomeController::class, 'store'])->name('incomes.store');
        Route::put('incomes/{income}', [ProjectIncomeController::class, 'update'])->name('incomes.update');
        Route::delete('incomes/{income}', [ProjectIncomeController::class, 'destroy'])->name('incomes.destroy');

        Route::post('expenses', [ProjectExpenseController::class, 'store'])->name('expenses.store');
        Route::put('expenses/{expense}', [ProjectExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('expenses/{expense}', [ProjectExpenseController::class, 'destroy'])->name('expenses.destroy');

        Route::post('general-expenses', [GeneralExpenseController::class, 'store'])->name('general-expenses.store');
        Route::put('general-expenses/{generalExpense}', [GeneralExpenseController::class, 'update'])->name('general-expenses.update');
        Route::delete('general-expenses/{generalExpense}', [GeneralExpenseController::class, 'destroy'])->name('general-expenses.destroy');

        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    });

    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');

        Route::get('contractors', [ContractorController::class, 'index'])->name('contractors.index');
        Route::get('contractors/create', [ContractorController::class, 'create'])->name('contractors.create');
        Route::post('contractors', [ContractorController::class, 'store'])->name('contractors.store');
        Route::get('contractors/{contractor}', [ContractorController::class, 'show'])->name('contractors.show');
        Route::get('contractors/{contractor}/edit', [ContractorController::class, 'edit'])->name('contractors.edit');
        Route::put('contractors/{contractor}', [ContractorController::class, 'update'])->name('contractors.update');

        Route::get('attendance', [PersonnelAttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance', [PersonnelAttendanceController::class, 'store'])->name('attendance.store');
        Route::put('attendance/{attendance}', [PersonnelAttendanceController::class, 'update'])->name('attendance.update');
        Route::post('attendance/{attendance}/approve', [PersonnelAttendanceController::class, 'approve'])->name('attendance.approve');

        Route::get('payroll', [PayrollRunController::class, 'index'])->name('payroll.index');
        Route::post('payroll', [PayrollRunController::class, 'store'])->name('payroll.store');
        Route::get('payroll/{payrollRun}', [PayrollRunController::class, 'show'])->name('payroll.show');
        Route::post('payroll/{payrollRun}/process', [PayrollRunController::class, 'process'])->name('payroll.process');
    });

    Route::prefix('forms')->name('forms.')->group(function () {
        Route::get('templates', [FormTemplateController::class, 'index'])->name('templates.index');
        Route::post('templates', [FormTemplateController::class, 'store'])->name('templates.store');
        Route::put('templates/{formTemplate}', [FormTemplateController::class, 'update'])->name('templates.update');

        Route::get('attachment-types', [AttachmentTypeController::class, 'index'])->name('attachment-types.index');
        Route::post('attachment-types', [AttachmentTypeController::class, 'store'])->name('attachment-types.store');
        Route::put('attachment-types/{attachmentType}', [AttachmentTypeController::class, 'update'])->name('attachment-types.update');

        Route::post('personnel-attachments', [PersonnelAttachmentController::class, 'store'])->name('personnel-attachments.store');
        Route::delete('personnel-attachments/{personnelAttachment}', [PersonnelAttachmentController::class, 'destroy'])->name('personnel-attachments.destroy');
    });

    Route::prefix('equipment')->name('equipment.')->group(function () {
        Route::get('/', [EquipmentCatalogController::class, 'index'])->name('index');
        Route::post('/', [EquipmentCatalogController::class, 'store'])->name('store');
        Route::put('{equipmentCatalog}', [EquipmentCatalogController::class, 'update'])->name('update');

        Route::post('issues', [PersonnelEquipmentIssueController::class, 'store'])->name('issues.store');
        Route::get('training', [TrainingSessionController::class, 'index'])->name('training.index');
        Route::post('training', [TrainingSessionController::class, 'store'])->name('training.store');
        Route::post('personnel-training', [PersonnelTrainingController::class, 'store'])->name('personnel-training.store');
    });

    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('bidding', [AnalyticsController::class, 'bidding'])->name('bidding');
        Route::get('finance', [AnalyticsController::class, 'finance'])->name('finance');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
        Route::put('users/{user}', [UserManagementController::class, 'update'])->name('users.update');

        Route::get('organization-types', [OrganizationTypeController::class, 'index'])->name('organization-types.index');
        Route::post('organization-types', [OrganizationTypeController::class, 'store'])->name('organization-types.store');
        Route::put('organization-types/{organizationType}', [OrganizationTypeController::class, 'update'])->name('organization-types.update');
        Route::delete('organization-types/{organizationType}', [OrganizationTypeController::class, 'destroy'])->name('organization-types.destroy');
    });
});
