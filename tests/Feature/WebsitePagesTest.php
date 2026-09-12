<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WebsitePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_pages_render(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('website/Home')
            ->has('hero')
            ->has('clients.blurb')
            ->has('contact.phone_raw')
            ->has('services')
        );

        $this->get('/about')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('website/About')
            ->has('about.title')
            ->has('about.paragraphs')
            ->has('stats')
            ->has('contact')
        );

        $this->get('/what-we-do')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('website/WhatWeDo')
            ->has('whatWeDo.title')
            ->has('services')
            ->has('contact')
        );

        $this->get('/services')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('website/Services/Index')
            ->has('services')
        );

        $this->get('/projects')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('website/Projects/Index')
            ->has('projects')
        );

        $this->actingAs(User::factory()->create())
            ->get('/projects')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('website/Projects/Index')
                ->has('projects')
            );

        $this->get('/projects/ongoing')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('website/Projects/Index')
            ->where('filter', 'ongoing')
        );

        $this->get('/contact')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('website/Contact')
            ->has('contact.phone_raw')
            ->has('contact.email')
        );

        $this->get('/certificates')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('website/Certificates')
            ->has('certificates')
        );

        $this->get('/trainings')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('website/Trainings/Index')
            ->has('trainings')
        );
    }

    public function test_show_pages_and_unknown_slugs(): void
    {
        $this->get('/services/manned-guarding-static-security')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('website/Services/Show')
                ->has('service')
                ->has('services')
            );

        $this->get('/projects/shiangge-rila-guest-house')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('website/Projects/Show')
                ->has('project.body')
            );

        $this->get('/trainings/security-awareness-training')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('website/Trainings/Show')
                ->has('training.body')
            );

        $this->get('/services/does-not-exist')->assertNotFound();
        $this->get('/projects/does-not-exist')->assertNotFound();
        $this->get('/trainings/does-not-exist')->assertNotFound();
    }
}
