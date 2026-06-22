<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Archive,
    ArrowRight,
    BarChart3,
    Briefcase,
    Building2,
    DollarSign,
    FileText,
    Shield,
    Users,
} from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard, login } from '@/routes';

const page = usePage();
const appName = page.props.name as string;

const modules = [
    {
        title: 'Organizations',
        description: 'Manage clients, partners, and organization profiles in one place.',
        icon: Building2,
    },
    {
        title: 'Bidding',
        description: 'Track opportunities, prepare bids, and monitor win rates.',
        icon: FileText,
    },
    {
        title: 'Projects',
        description: 'Oversee active contracts, sites, issues, and delivery milestones.',
        icon: Briefcase,
    },
    {
        title: 'Finance',
        description: 'Monitor income, expenses, overhead, and project profitability.',
        icon: DollarSign,
    },
    {
        title: 'Human Resources',
        description: 'Handle employees, contractors, payroll, and attendance records.',
        icon: Users,
    },
    {
        title: 'Analytics',
        description: 'Visualize bidding performance and financial trends at a glance.',
        icon: BarChart3,
    },
    {
        title: 'Archive',
        description: 'Store and retrieve historical documents and project records.',
        icon: Archive,
    },
    {
        title: 'Security Operations',
        description: 'Built for teams that need reliable, centralized operational control.',
        icon: Shield,
    },
];
</script>

<template>
    <Head title="Welcome" />

    <div class="relative min-h-screen overflow-hidden bg-background">
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(120,119,198,0.12),transparent)] dark:bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(120,119,198,0.08),transparent)]"
        />
        <div
            class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,rgba(0,0,0,0.02)_1px,transparent_1px),linear-gradient(to_bottom,rgba(0,0,0,0.02)_1px,transparent_1px)] bg-[size:4rem_4rem] dark:bg-[linear-gradient(to_right,rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.03)_1px,transparent_1px)]"
        />

        <div class="relative mx-auto flex min-h-screen max-w-6xl flex-col px-4 sm:px-6 lg:px-8">
            <header class="flex items-center justify-between py-6">
                <Link
                    href="/"
                    class="flex items-center gap-3 transition-opacity hover:opacity-80"
                >
                    <div
                        class="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm"
                    >
                        <AppLogoIcon class="size-5 fill-current" />
                    </div>
                    <span class="text-sm font-semibold tracking-tight">{{
                        appName
                    }}</span>
                </Link>

                <nav class="flex items-center gap-2">
                    <LanguageSwitcher />
                    <template v-if="$page.props.auth.user">
                        <Button as-child size="sm">
                            <Link :href="dashboard()">Dashboard</Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button as-child size="sm">
                            <Link :href="login()">Log in</Link>
                        </Button>
                    </template>
                </nav>
            </header>

            <main class="flex flex-1 flex-col justify-center pb-16 pt-8 sm:pt-12">
                <section class="mx-auto max-w-3xl text-center">
                    <Badge variant="secondary" class="mb-6 px-3 py-1">
                        Management Information System
                    </Badge>

                    <h1
                        class="text-4xl font-bold tracking-tight text-balance sm:text-5xl lg:text-6xl"
                    >
                        Run your operations from a single, secure platform
                    </h1>

                    <p
                        class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-muted-foreground sm:text-lg"
                    >
                        Streamline bidding, projects, finance, and HR with one
                        integrated workspace built for security and service
                        organizations.
                    </p>

                    <div class="mt-10 flex justify-center">
                        <Button as-child size="lg" class="min-w-44">
                            <Link
                                :href="
                                    $page.props.auth.user
                                        ? dashboard()
                                        : login()
                                "
                            >
                                {{
                                    $page.props.auth.user
                                        ? 'Open dashboard'
                                        : 'Sign in'
                                }}
                                <ArrowRight class="size-4" />
                            </Link>
                        </Button>
                    </div>
                </section>

                <section class="mt-20 sm:mt-28">
                    <div class="mb-10 text-center">
                        <h2 class="text-2xl font-semibold tracking-tight">
                            Everything your team needs
                        </h2>
                        <p class="mt-2 text-sm text-muted-foreground sm:text-base">
                            Modular tools that work together across your entire
                            workflow.
                        </p>
                    </div>

                    <div
                        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:gap-5"
                    >
                        <Card
                            v-for="module in modules"
                            :key="module.title"
                            class="border-border/60 bg-card/80 backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-border hover:shadow-md"
                        >
                            <CardHeader class="pb-3">
                                <div
                                    class="mb-3 flex size-10 items-center justify-center rounded-lg bg-muted"
                                >
                                    <component
                                        :is="module.icon"
                                        class="size-5 text-foreground"
                                    />
                                </div>
                                <CardTitle class="text-base">{{
                                    module.title
                                }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <CardDescription class="leading-relaxed">
                                    {{ module.description }}
                                </CardDescription>
                            </CardContent>
                        </Card>
                    </div>
                </section>
            </main>

            <footer
                class="border-t border-border/60 py-6 text-center text-sm text-muted-foreground"
            >
                <p>&copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.</p>
            </footer>
        </div>
    </div>
</template>
