<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\WebsiteContent;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('website/Home', WebsiteContent::home());
    }

    public function about(): Response
    {
        return Inertia::render('website/About', WebsiteContent::about());
    }

    public function whatWeDo(): Response
    {
        return Inertia::render('website/WhatWeDo', WebsiteContent::whatWeDo());
    }

    public function services(): Response
    {
        return Inertia::render('website/Services/Index', [
            'seo' => [
                'title' => 'Our Services | Sun Sky Global Security',
                'description' => 'Manned guarding, mobile escort, risk advisory, and security training across Afghanistan.',
                'image' => '/images/website/services/manned-guarding.jpg',
            ],
            'title' => 'Our Services',
            'lead' => 'We specialize in professional physical security across Afghanistan. Our trained personnel combine local operational knowledge with disciplined procedures to protect people, assets, and facilities — from static guarding and mobile escort to risk advisory and capacity building.',
            'services' => WebsiteContent::services(),
            'stats' => WebsiteContent::stats(),
            'trustFeatures' => WebsiteContent::trustFeatures(),
            'contact' => WebsiteContent::contact(),
        ]);
    }

    public function service(string $slug): Response
    {
        $service = WebsiteContent::service($slug);

        abort_if($service === null, 404);

        return Inertia::render('website/Services/Show', [
            'seo' => [
                'title' => $service['title'].' | Sun Sky Global Security',
                'description' => $service['summary'],
                'image' => $service['image'] ?? null,
            ],
            'service' => $service,
            'services' => WebsiteContent::services(),
            'contact' => WebsiteContent::contact(),
        ]);
    }

    public function projects(?string $filter = null): Response
    {
        $status = match ($filter) {
            'ongoing', 'completed' => $filter,
            default => null,
        };

        $title = match ($status) {
            'ongoing' => 'On-Going Projects',
            'completed' => 'Completed Projects',
            default => 'All Projects',
        };

        return Inertia::render('website/Projects/Index', [
            'seo' => [
                'title' => $title.' | Sun Sky Global Security',
                'description' => 'Explore our portfolio of security projects delivered across Afghanistan.',
            ],
            'title' => $title,
            'lead' => 'Explore our portfolio of security projects delivered across Afghanistan.',
            'projects' => WebsiteContent::projects($status),
            'filter' => $status ?? 'all',
            'contact' => WebsiteContent::contact(),
        ]);
    }

    public function project(string $slug): Response
    {
        $project = WebsiteContent::project($slug);

        abort_if($project === null, 404);

        return Inertia::render('website/Projects/Show', [
            'seo' => [
                'title' => $project['title'].' | Sun Sky Global Security',
                'description' => $project['summary'],
                'image' => $project['image'] ?? null,
            ],
            'project' => $project,
            'contact' => WebsiteContent::contact(),
        ]);
    }

    public function trainings(): Response
    {
        return Inertia::render('website/Trainings/Index', [
            'seo' => [
                'title' => 'Training & Awareness | Sun Sky Global Security',
                'description' => 'Security awareness, K9 services, and site assessments.',
            ],
            'title' => 'Training & Awareness',
            'lead' => 'Structured programs that strengthen individual and organizational security capacity.',
            'trainings' => WebsiteContent::trainings(),
            'contact' => WebsiteContent::contact(),
        ]);
    }

    public function training(string $slug): Response
    {
        $training = WebsiteContent::training($slug);

        abort_if($training === null, 404);

        return Inertia::render('website/Trainings/Show', [
            'seo' => [
                'title' => $training['title'].' | Sun Sky Global Security',
                'description' => $training['summary'],
                'image' => $training['image'] ?? null,
            ],
            'training' => $training,
            'contact' => WebsiteContent::contact(),
        ]);
    }

    public function certificates(): Response
    {
        return Inertia::render('website/Certificates', [
            'seo' => [
                'title' => 'Certificates | Sun Sky Global Security',
                'description' => 'Company certificates and credentials.',
            ],
            'title' => 'Certificates',
            'lead' => 'Credentials that reflect our commitment to professional standards.',
            'certificates' => WebsiteContent::certificates(),
            'contact' => WebsiteContent::contact(),
        ]);
    }

    public function contact(): Response
    {
        return Inertia::render('website/Contact', [
            'seo' => [
                'title' => 'Contact Us | Sun Sky Global Security',
                'description' => 'Get in touch with Sun Sky Global Security in Kabul.',
            ],
            'title' => 'Contact Us',
            'lead' => 'Call, email, or send a message — we respond promptly.',
            'contact' => WebsiteContent::contact(),
        ]);
    }
}
