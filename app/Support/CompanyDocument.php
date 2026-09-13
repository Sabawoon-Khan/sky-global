<?php

namespace App\Support;

class CompanyDocument
{
    /**
     * @return array{
     *     name: string,
     *     name_fa: string,
     *     short_name: string,
     *     address: string,
     *     phone: string,
     *     phone_alt: string,
     *     email: string,
     *     president: string,
     *     president_title: string,
     *     bank: array{usd: array<string, string>, afn: array<string, string>}
     * }
     */
    public static function profile(): array
    {
        $company = WebsiteContent::company();
        $contact = WebsiteContent::contact();

        return [
            'name' => $company['name'],
            'name_fa' => 'کمپنی خدمات امنیتی سن سکای گلوبل',
            'short_name' => 'Sun Sky Global Security Service Co.',
            'address' => $contact['address'],
            'phone' => $contact['phone'],
            'phone_alt' => '+(93) 799 508 888',
            'email' => $contact['email'],
            'president' => 'Haji Ghulam Qader SARWARI',
            'president_title' => 'President',
            'bank' => [
                'usd' => [
                    'name' => $company['name'],
                    'bank' => 'AZIZI BANK',
                    'account' => '000101214289945',
                    'swift' => 'AZBAAFKA',
                ],
                'afn' => [
                    'name' => $company['name'],
                    'bank' => 'AZIZI BANK',
                    'account' => '000101114289890',
                    'swift' => 'AZBAAFKA',
                ],
            ],
        ];
    }
}
