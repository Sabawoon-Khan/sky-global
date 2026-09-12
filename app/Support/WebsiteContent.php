<?php

namespace App\Support;

class WebsiteContent
{
    public static function all(): array
    {
        return [
            'company' => self::company(),
            'about' => self::aboutContent(),
            'whatWeDo' => self::whatWeDoContent(),
            'stats' => self::stats(),
            'services' => self::services(),
            'projects' => self::projects(),
            'trainings' => self::trainings(),
            'testimonials' => self::testimonials(),
            'certificates' => self::certificates(),
            'contact' => self::contact(),
            'clients' => self::clients(),
        ];
    }

    public static function home(): array
    {
        $company = self::company();
        $clients = self::clients();

        return [
            'seo' => [
                'title' => $company['name'],
                'description' => $company['tagline'],
                'image' => '/images/website/hero.jpg',
            ],
            'hero' => [
                'brand' => $company['shortName'].' Global Security',
                'headline' => 'Professional security for complex environments',
                'support' => $company['tagline'],
                'image' => '/images/website/hero.jpg',
            ],
            'aboutTeaser' => [
                'title' => 'About us',
                'body' => self::aboutTeaser(),
                'image' => '/images/website/about.jpg',
            ],
            'services' => self::services(),
            'stats' => self::stats(),
            'statsTitle' => 'Where We Operate',
            'statsLead' => 'Our security services cover Afghanistan\'s largest cities and most popular destinations. We are accessible anytime and present everywhere across Afghanistan.',
            'projects' => array_slice(self::projects(), 0, 4),
            'testimonials' => self::testimonials(),
            'clients' => $clients,
            'clientsBlurb' => $clients['blurb'],
            'footerBlurb' => $clients['blurb'],
            'contact' => self::contact(),
        ];
    }

    public static function company(): array
    {
        return [
            'name' => 'Sun Sky Global Security Services Company',
            'shortName' => 'Sun Sky',
            'tagline' => 'Integrated guarding, mobile escort, risk advisory, and training across Afghanistan.',
        ];
    }

    public static function aboutContent(): array
    {
        return [
            'title' => 'About us',
            'lead' => 'Independent, professional security for people, assets, and operations in complex environments.',
            'paragraphs' => [
                'Sun Sky Global Security Services Company is an independent, non-governmental, and apolitical security organization committed to delivering high-caliber, professional security solutions across Afghanistan and beyond. Established with a clear vision of excellence, integrity, and operational effectiveness, Sun Sky is dedicated to safeguarding people, assets, infrastructure, and operations in complex and high-risk environments. With a strong emphasis on professionalism, discipline, and specialized expertise, Sun Sky provides integrated security services designed to meet the evolving needs of governmental institutions, international organizations, diplomatic missions, non-governmental organizations (NGOs), development agencies, and private sector entities. Our operational philosophy is rooted in proactive risk management, strict compliance with national and international regulations, and a client-centered approach that ensures tailored and effective security strategies.',
            ],
            'image' => '/images/website/about/about-main.jpg',
        ];
    }

    public static function about(): array
    {
        $about = self::aboutContent();

        return [
            'seo' => [
                'title' => 'About us | Sun Sky Global Security',
                'description' => self::aboutTeaser(),
                'image' => $about['image'],
            ],
            'about' => $about,
            'title' => $about['title'],
            'lead' => $about['lead'],
            'paragraphs' => $about['paragraphs'],
            'image' => $about['image'],
            'stats' => self::stats(),
            'contact' => self::contact(),
        ];
    }

    public static function aboutTeaser(): string
    {
        return 'Sun Sky Global Security Services Company is an independent, non-governmental, and apolitical security organization committed to delivering high-caliber, professional security solutions across Afghanistan and beyond. Established with a clear vision of excellence, integrity, and operational effectiveness, Sun Sky is dedicated to safeguarding people, assets, infrastructure, and operations in complex and high-risk environments.';
    }

    public static function whatWeDoContent(): array
    {
        return [
            'title' => 'What We Do',
            'lead' => 'Professional, adaptive, and dependable security solutions throughout Afghanistan.',
            'paragraphs' => [
                'At Sun Sky Global Security Services Company, we are committed to delivering professional, adaptive, and dependable security solutions throughout Afghanistan. Our services are specifically designed to support organizations operating in complex and high-risk environments. By combining internationally recognized security standards with extensive local knowledge and operational experience, we ensure the safety, continuity, and resilience of our clients’ operations. With a strong emphasis on risk mitigation, operational efficiency, and client-focused service delivery, Sun Sky provides integrated security solutions tailored to governmental institutions, international organizations, NGOs, diplomatic missions, and private sector entities.',
            ],
        ];
    }

    public static function whatWeDo(): array
    {
        $whatWeDo = self::whatWeDoContent();

        return [
            'seo' => [
                'title' => 'What We Do | Sun Sky Global Security',
                'description' => $whatWeDo['lead'],
            ],
            'whatWeDo' => $whatWeDo,
            'title' => $whatWeDo['title'],
            'lead' => $whatWeDo['lead'],
            'paragraphs' => $whatWeDo['paragraphs'],
            'services' => self::services(),
            'contact' => self::contact(),
        ];
    }

    public static function stats(): array
    {
        return [
            ['value' => 34, 'label' => 'Provinces covered'],
            ['value' => 11, 'label' => 'Service categories'],
            ['value' => 8, 'label' => 'Satisfied Customer'],
            ['value' => 300, 'label' => 'People Guarded'],
        ];
    }

    /**
     * @return list<array{title: string, body: string}>
     */
    public static function trustFeatures(): array
    {
        return [
            [
                'title' => 'Professional guard force',
                'body' => 'Trained armed and unarmed personnel with background checks, discipline, and continuous performance evaluation.',
            ],
            [
                'title' => 'High-risk operational experience',
                'body' => 'Proven delivery for compounds, hotels, warehouses, and project sites across complex Afghan environments.',
            ],
            [
                'title' => 'Journey & escort capability',
                'body' => 'Convoy protection, VIP movement, route risk assessment, and real-time operations-center coordination.',
            ],
            [
                'title' => 'Risk advisory & assessments',
                'body' => 'Threat analysis, site assessments, contingency planning, and client-centered security strategies.',
            ],
            [
                'title' => 'Training & capacity building',
                'body' => 'HEAT, awareness, K9, first aid, and site-assessment programs that strengthen organizational readiness.',
            ],
            [
                'title' => 'Client-tailored solutions',
                'body' => 'Security plans shaped to governmental, diplomatic, NGO, development, and private-sector requirements.',
            ],
        ];
    }

    public static function services(): array
    {
        return [
            [
                'slug' => 'manned-guarding-static-security',
                'title' => 'Manned Guarding & Static Security',
                'summary' => 'Sun Sky offers highly trained armed and unarmed security personnel to safeguard compounds, offices, residential facilities, warehouses, and project sites.',
                'body' => [
                    'Sun Sky offers highly trained armed and unarmed security personnel to safeguard compounds, offices, residential facilities, warehouses, and project sites. Our static guarding services are structured to provide professional coverage aligned with client SOPs and international best practices.',
                    'All guards undergo rigorous background checks, structured training programs, and continuous performance evaluations to ensure professionalism, discipline, and operational readiness.',
                ],
                'bullets' => [
                    '24/7 professional guard coverage',
                    'Strict access control and visitor management systems',
                    'Perimeter security and surveillance',
                    'Screening and inspection procedures',
                    'Rapid response support teams',
                    'Compliance with client SOPs and international best practices',
                ],
                'image' => '/images/website/services/manned-guarding.jpg',
            ],
            [
                'slug' => 'mobile-security-escort-services',
                'title' => 'Mobile Security & Escort Services',
                'summary' => 'Our mobile security division provides secure transportation and convoy escort services to ensure safe movement of personnel and assets across Afghanistan.',
                'body' => [
                    'Our mobile security division provides secure transportation and convoy escort services to ensure safe movement of personnel and assets across Afghanistan.',
                    'Our operations center continuously monitors movements and provides real-time communication and coordination to mitigate threats and ensure rapid response when required.',
                ],
                'bullets' => [
                    'Armed escort for high-risk movements',
                    'Secure transport for staff and VIPs',
                    'Journey management and route risk assessment',
                    'Airport transfers and movement coordination',
                    'Protective driving services',
                    'Emergency response and extraction support',
                ],
                'image' => '/images/website/services/mobile-escort.jpg',
            ],
            [
                'slug' => 'security-risk-management-advisory',
                'title' => 'Security & Risk Management Advisory',
                'summary' => 'Sun Sky specializes in comprehensive risk management and advisory services designed to help organizations operate safely in dynamic environments.',
                'body' => [
                    'Sun Sky specializes in comprehensive risk management and advisory services designed to help organizations operate safely in dynamic environments.',
                    'We tailor each security framework according to client-specific operational needs and local threat dynamics.',
                ],
                'bullets' => [
                    'Security risk assessments and threat analysis',
                    'Area security mapping and intelligence updates',
                    'Crisis preparedness and contingency planning',
                    'Incident reporting and monitoring systems',
                    'Development of security policies and Standard Operating Procedures (SOPs)',
                    'Emergency evacuation and relocation planning',
                ],
                'image' => '/images/website/services/risk-advisory.jpg',
            ],
            [
                'slug' => 'security-training-capacity-building',
                'title' => 'Security Training & Capacity Building',
                'summary' => 'We believe that strong security begins with well-trained personnel. Sun Sky provides structured training programs aimed at enhancing individual and organizational security capacity.',
                'body' => [
                    'We believe that strong security begins with well-trained personnel. Sun Sky provides structured training programs aimed at enhancing individual and organizational security capacity.',
                    'Our training programs combine theoretical instruction with practical scenario-based exercises to ensure operational competence.',
                ],
                'bullets' => [
                    'Hostile Environment Awareness Training (HEAT)',
                    'Personal safety and situational awareness',
                    'Emergency response and crisis management',
                    'First aid and trauma care',
                    'Fire safety and evacuation drills',
                    'Security induction training for staff and drivers',
                    'Defensive and protective driving courses',
                    'K9 Training, Supply, and Rental Services',
                ],
                'image' => '/images/website/services/training.jpg',
            ],
        ];
    }

    public static function service(string $slug): ?array
    {
        foreach (self::services() as $service) {
            if ($service['slug'] === $slug) {
                return $service;
            }
        }

        return null;
    }

    /**
     * @param  string|null  $filter  null|all|ongoing|completed
     */
    public static function projects(?string $filter = null): array
    {
        $projects = [
            [
                'slug' => 'shiangge-rila-guest-house',
                'title' => 'Shiangge Rila Guest House',
                'status' => 'ongoing',
                'date' => '2026-05-13 — 2027-05-12',
                'start_date' => '2026-05-13',
                'end_date' => '2027-05-12',
                'summary' => 'SECURITY CONTRACT BETWEEN SUN SKY GLOBAL SECURITY SERVICES COMPANY AND SHIANGGE RILA GUEST HOUSE — professional security and protective services at the guest house premises in Kabul.',
                'body' => [
                    'SECURITY CONTRACT BETWEEN SUN SKY GLOBAL SECURITY SERVICES COMPANY AND SHIANGGE RILA GUEST HOUSE',
                    'Sun Sky Global Security Services Company entered into a professional security services agreement with Shiangge Rila Guest House for the provision of security and protective services at the guest house premises in Kabul, Afghanistan. The purpose of this cooperation is to ensure a safe, secure, and well-organized environment for guests, staff members, visitors, and property.',
                    'Under this agreement, Sun Sky Global Security Services Company provides trained and professional security personnel to perform security-related duties on a continuous basis. The services include access control, monitoring of movements, guarding of the premises, internal patrols, incident reporting, and implementation of security procedures in accordance with operational requirements.',
                    'The security services are carried out under professional supervision and in compliance with the applicable laws and regulations of Afghanistan. Sun Sky Global Security Services Company remains committed to maintaining professional standards, operational discipline, confidentiality, and rapid response capability in all security-related matters.',
                    'This cooperation reflects mutual trust and professional coordination between both parties with the shared objective of maintaining safety, stability, and secure operations within the facility. Sun Sky Global Security Services Company continues its commitment to delivering reliable and lawful security services in support of private and commercial establishments throughout Afghanistan.',
                ],
                'image' => '/images/website/projects/shiangge-rila.jpg',
            ],
            [
                'slug' => 'darya-village-hotel-services-dvh',
                'title' => 'Darya Village Hotel Services (DVH)',
                'status' => 'ongoing',
                'date' => '2026-04-21 — 2027-04-20',
                'start_date' => '2026-04-21',
                'end_date' => '2027-04-20',
                'summary' => 'Ongoing security services for Darya Village Hotel Services (DVH).',
                'body' => [
                    'Sun Sky Global Security Services Company is an independent, non-governmental, and apolitical security organization committed to delivering high-caliber, professional security solutions across Afghanistan and beyond. Established with a clear vision of excellence, integrity, and operational effectiveness, Sun Sky is dedicated to safeguarding people, assets, infrastructure, and operations in complex and high-risk environments. With a strong emphasis on professionalism, discipline, and specialized expertise, Sun Sky provides integrated security services designed to meet the evolving needs of governmental institutions, international organizations, diplomatic missions, non-governmental organizations (NGOs), development agencies, and private sector entities.',
                ],
                'image' => '/images/website/projects/dvh.jpg',
            ],
            [
                'slug' => 'new-project-number-three',
                'title' => 'New project Number Three',
                'status' => 'completed',
                'date' => '2026-05-19 — 2026-05-30',
                'start_date' => '2026-05-19',
                'end_date' => '2026-05-30',
                'summary' => 'Completed security engagement — New project Number Three.',
                'body' => [
                    'Sun Sky Global Security Services Company is an independent, non-governmental, and apolitical security organization committed to delivering high-caliber, professional security solutions across Afghanistan and beyond. Established with a clear vision of excellence, integrity, and operational effectiveness, Sun Sky is dedicated to safeguarding people, assets, infrastructure, and operations in complex and high-risk environments. With a strong emphasis on professionalism, discipline, and specialized expertise, Sun Sky provides integrated security services designed to meet the evolving needs of governmental institutions, international organizations, diplomatic missions, non-governmental organizations (NGOs), development agencies, and private sector entities.',
                ],
                'image' => '/images/website/projects/project-three.jpg',
            ],
            [
                'slug' => 'new-project-number-two',
                'title' => 'New project Number Two',
                'status' => 'completed',
                'date' => '2026-05-19 — 2026-05-30',
                'start_date' => '2026-05-19',
                'end_date' => '2026-05-30',
                'summary' => 'Completed security engagement — New project Number Two.',
                'body' => [
                    'Sun Sky Global Security Services Company is an independent, non-governmental, and apolitical security organization committed to delivering high-caliber, professional security solutions across Afghanistan and beyond. Established with a clear vision of excellence, integrity, and operational effectiveness, Sun Sky is dedicated to safeguarding people, assets, infrastructure, and operations in complex and high-risk environments. With a strong emphasis on professionalism, discipline, and specialized expertise, Sun Sky provides integrated security services designed to meet the evolving needs of governmental institutions, international organizations, diplomatic missions, non-governmental organizations (NGOs), development agencies, and private sector entities.',
                ],
                'image' => '/images/website/projects/project-two.jpg',
            ],
        ];

        $filter = $filter === 'all' ? null : $filter;

        if ($filter === null) {
            return $projects;
        }

        return array_values(array_filter(
            $projects,
            fn (array $project): bool => $project['status'] === $filter
        ));
    }

    public static function project(string $slug): ?array
    {
        foreach (self::projects() as $project) {
            if ($project['slug'] === $slug) {
                return $project;
            }
        }

        return null;
    }

    public static function trainings(): array
    {
        return [
            [
                'slug' => 'security-awareness-training',
                'title' => 'Security Awareness Training',
                'summary' => 'Educating employees about security best practices is vital in creating a security conscious culture.',
                'body' => [
                    'Educating employees about security best practices is vital in creating a security conscious culture. We recommend conducting regular security awareness training sessions to educate employees about potential risks, social engineering tactics, and the importance of adhering to security policies and procedures.',
                    'During Security Awareness Training, participants are provided with information on best practices for protecting sensitive information, identifying and reporting suspicious activities, and maintaining strong passwords. They are also educated on the importance of regularly updating software and systems, using secure networks, and being cautious while accessing and sharing information online.',
                    'The training may include interactive modules, videos, quizzes, and real-life examples to engage participants and reinforce key concepts. It may also cover topics like email security, safe browsing habits, mobile device security, and the importance of physical security measures.',
                    'By undergoing Security Awareness Training, individuals can develop the knowledge and skills necessary to identify and mitigate potential security risks. This, in turn, helps organizations enhance their overall security posture and reduce the likelihood of security incidents or data breaches.',
                ],
                'image' => '/images/website/trainings/awareness.jpg',
            ],
            [
                'slug' => 'k9-training-supply-and-rental-services',
                'title' => 'K9 Training, Supply, and Rental Services',
                'summary' => 'Professional K9 training, supply, and rental services tailored to explosives detection, narcotics detection, perimeter security, and facility protection.',
                'body' => [
                    'Sun Sky Global Security Services Company provides professional K9 training, supply, and rental services tailored to meet diverse security needs. Our trained detection and protection dogs are prepared for assignments such as explosives detection, narcotics detection, perimeter security, and facility protection. All K9 units undergo structured training programs conducted by certified handlers to ensure discipline, reliability, and operational effectiveness. We offer both short-term and long-term deployment and rental options, supported by experienced K9 handlers. Our services are delivered in compliance with recognized security standards to ensure maximum safety and performance efficiency.',
                ],
                'image' => '/images/website/trainings/k9.jpg',
            ],
            [
                'slug' => 'security-site-assessments',
                'title' => 'Security Site Assessments',
                'summary' => 'Professional site security assessments to identify vulnerabilities and strengthen protective measures.',
                'body' => [
                    'Sun Sky conducts professional site security assessments to identify vulnerabilities and strengthen protective measures. Our assessment services include:',
                ],
                'bullets' => [
                    'Facility vulnerability assessments',
                    'Travel risk assessments and route evaluations',
                    'Security audits and compliance reviews',
                    'Infrastructure and perimeter analysis',
                    'Review of guard force performance',
                    'Strategic security planning and tailored advisory solutions',
                ],
                'image' => '/images/website/trainings/site-assessments.jpg',
            ],
        ];
    }

    public static function training(string $slug): ?array
    {
        foreach (self::trainings() as $training) {
            if ($training['slug'] === $slug) {
                return $training;
            }
        }

        return null;
    }

    public static function testimonials(): array
    {
        return [
            [
                'quote' => 'Excellent convoy protection and on-time movement coordination; highly recommended.',
                'name' => 'Omar Karimi',
                'role' => 'Logistics Manager',
            ],
            [
                'quote' => 'Sun Sky kept our Kabul office safe and our team reassured — professional from day one',
                'name' => 'Amina Rahimi',
                'role' => 'Country Director',
            ],
        ];
    }

    public static function certificates(): array
    {
        return [
            [
                'title' => 'Certificate 1',
                'src' => '/images/website/certificates/cert-1.png',
                'alt' => 'Sun Sky certificate 1',
            ],
            [
                'title' => 'Certificate 2',
                'src' => '/images/website/certificates/cert-2.png',
                'alt' => 'Sun Sky certificate 2',
            ],
            [
                'title' => 'Certificate 3',
                'src' => '/images/website/certificates/cert-3.png',
                'alt' => 'Sun Sky certificate 3',
            ],
        ];
    }

    public static function contact(): array
    {
        return [
            'phone' => '+(93) 799 111 911',
            'phone_raw' => '+93799111911',
            'email' => 'm.office@sunskyglobalsecurity.com',
            'address' => 'House 16# 4th Street of old Taimani, PD4, Kabul/ Afghanistan',
            'whatsapp' => 'https://wa.me/93799111911',
        ];
    }

    public static function clients(): array
    {
        return [
            'title' => 'Clients & Partners',
            'blurb' => 'Our security team develops comprehensive security plans and strategies based on the specific needs and risks faced by our clients.',
            'logos' => [
                [
                    'src' => '/images/website/clients/client-1.png',
                    'alt' => 'Client partner',
                ],
            ],
        ];
    }
}
