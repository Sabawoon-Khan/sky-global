<?php

namespace App\Enums;

enum ProjectActivityType: string
{
    case StatusChange = 'status_change';
    case DocumentAdded = 'document_added';
    case ExpenseAdded = 'expense_added';
    case IncomeReceived = 'income_received';
    case DeploymentChanged = 'deployment_changed';
    case IssueOpened = 'issue_opened';
    case IssueResolved = 'issue_resolved';
    case NoteAdded = 'note_added';
    case MemberAdded = 'member_added';
    case ProjectCreated = 'project_created';
}
