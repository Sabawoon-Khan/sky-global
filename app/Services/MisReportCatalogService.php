<?php

namespace App\Services;

use App\Models\User;
use App\Support\CompanyDocument;
use Illuminate\Support\Carbon;

class MisReportCatalogService
{
    /** @return list<string> */
    public function catalogPermissions(): array
    {
        $permissions = [];

        foreach (config('mis_reports.groups', []) as $group) {
            foreach ($group['items'] ?? [] as $item) {
                $permission = $item['permission'] ?? null;

                if (is_string($permission) && $permission !== '') {
                    $permissions[] = $permission;
                }
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * @return array{
     *     groups: list<array{
     *         key: string,
     *         module_label_key: string,
     *         items: list<array{
     *             key: string,
     *             title_key: string,
     *             description_key: string,
     *             screen_href: string,
     *             print_href: string|null,
     *             print_note_key: string|null
     *         }>
     *     }>,
     *     generated_on: string,
     *     company: array{name: string}
     * }
     */
    public function catalogForUser(?User $user): array
    {
        $groups = [];

        foreach (config('mis_reports.groups', []) as $group) {
            $items = [];

            foreach ($group['items'] ?? [] as $item) {
                $permission = $item['permission'] ?? null;

                if (! is_string($permission) || $permission === '') {
                    continue;
                }

                if ($user !== null && ! $user->can($permission)) {
                    continue;
                }

                if ($user === null) {
                    continue;
                }

                $items[] = [
                    'key' => (string) $item['key'],
                    'title_key' => (string) $item['title_key'],
                    'description_key' => (string) $item['description_key'],
                    'screen_href' => (string) $item['screen_href'],
                    'print_href' => isset($item['print_href']) && is_string($item['print_href'])
                        ? $item['print_href']
                        : null,
                    'print_note_key' => isset($item['print_note_key']) && is_string($item['print_note_key'])
                        ? $item['print_note_key']
                        : null,
                ];
            }

            if ($items === []) {
                continue;
            }

            $groups[] = [
                'key' => (string) $group['key'],
                'module_label_key' => (string) $group['module_label_key'],
                'items' => $items,
            ];
        }

        $company = CompanyDocument::profile();

        return [
            'groups' => $groups,
            'generated_on' => Carbon::now()->translatedFormat('M j, Y'),
            'company' => [
                'name' => $company['name'],
            ],
        ];
    }
}
