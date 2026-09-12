<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { Mail, MapPin, Phone } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import PageHero from '@/components/website/PageHero.vue';
import SeoHead from '@/components/website/SeoHead.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { WebsiteContact, WebsiteSeo } from '@/types/website';

defineProps<{
    seo?: WebsiteSeo | null;
    title?: string | null;
    lead?: string | null;
    contact?: WebsiteContact | null;
}>();

const { t } = useTranslations();
const page = usePage();

const flashSuccess = computed(() => {
    const fromProps = (page.props as { flash?: Record<string, unknown> }).flash;
    const fromPage = (page as { flash?: Record<string, unknown> }).flash;
    const flash = fromProps ?? fromPage;
    return typeof flash?.success === 'string' ? flash.success : null;
});
</script>

<template>
    <div>
        <SeoHead
            :title="seo?.title || title || t('website.nav.contact')"
            :description="seo?.description || lead"
            :image="seo?.image"
        />
        <PageHero
            :eyebrow="t('website.nav.contact')"
            :title="title || t('website.contact.title')"
            :lead="lead || t('website.contact.lead')"
            image="/images/website/ops-atmosphere.jpg"
            compact
        />

        <section class="ws-section">
            <div class="ws-container ws-contact">
                <aside class="ws-aside-stack">
                    <div class="ws-contact-row">
                        <span><MapPin :size="18" /></span>
                        <div>
                            <em>{{ t('website.contact.infoTitle') }}</em>
                            <strong>
                                {{
                                    contact?.address ||
                                    'House 16# 4th Street of old Taimani, PD4, Kabul/ Afghanistan'
                                }}
                            </strong>
                        </div>
                    </div>
                    <a
                        class="ws-contact-row"
                        :href="`tel:${(contact?.phone || '+93799111911').replace(/\s+/g, '')}`"
                    >
                        <span><Phone :size="18" /></span>
                        <div>
                            <em>{{ t('website.form.phone') }}</em>
                            <strong>{{ contact?.phone || '+ (93) 799 111 911' }}</strong>
                        </div>
                    </a>
                    <a
                        class="ws-contact-row"
                        :href="`mailto:${contact?.email || 'm.office@sunskyglobalsecurity.com'}`"
                    >
                        <span><Mail :size="18" /></span>
                        <div>
                            <em>{{ t('website.form.email') }}</em>
                            <strong>
                                {{ contact?.email || 'm.office@sunskyglobalsecurity.com' }}
                            </strong>
                        </div>
                    </a>
                </aside>

                <div class="ws-contact-form">
                    <div
                        v-if="flashSuccess"
                        class="ws-panel"
                        style="
                            margin-bottom: 1.25rem;
                            border-color: rgba(16, 185, 129, 0.25);
                            background: rgba(236, 253, 245, 0.9);
                            color: #065f46;
                            font-size: 0.9rem;
                        "
                    >
                        {{ flashSuccess }}
                    </div>

                    <h2 class="ws-title" style="font-size: 1.4rem">
                        {{ t('website.contact.formTitle') }}
                    </h2>
                    <p class="ws-prose" style="margin-top: 0.5rem; font-size: 0.92rem">
                        {{ t('website.contact.formLead') }}
                    </p>

                    <Form
                        action="/contact"
                        method="post"
                        class="ws-aside-stack"
                        style="position: relative; margin-top: 1.5rem"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div
                            style="
                                pointer-events: none;
                                position: absolute;
                                left: -9999px;
                                height: 0;
                                width: 0;
                                overflow: hidden;
                                opacity: 0;
                            "
                            aria-hidden="true"
                        >
                            <label for="website">Website</label>
                            <input
                                id="website"
                                type="text"
                                name="website"
                                tabindex="-1"
                                autocomplete="off"
                            />
                        </div>

                        <div class="ws-card-grid-2">
                            <label class="ws-field">
                                <span>{{ t('website.form.name') }}</span>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    required
                                    autocomplete="name"
                                />
                                <InputError :message="errors.name" />
                            </label>
                            <label class="ws-field">
                                <span>{{ t('website.form.email') }}</span>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                    autocomplete="email"
                                />
                                <InputError :message="errors.email" />
                            </label>
                        </div>

                        <div class="ws-card-grid-2">
                            <label class="ws-field">
                                <span>{{ t('website.form.phone') }}</span>
                                <input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    autocomplete="tel"
                                />
                                <InputError :message="errors.phone" />
                            </label>
                            <label class="ws-field">
                                <span>{{ t('website.form.subject') }}</span>
                                <input
                                    id="subject"
                                    name="subject"
                                    type="text"
                                    required
                                />
                                <InputError :message="errors.subject" />
                            </label>
                        </div>

                        <label class="ws-field">
                            <span>{{ t('website.form.message') }}</span>
                            <textarea
                                id="message"
                                name="message"
                                rows="5"
                                required
                            />
                            <InputError :message="errors.message" />
                        </label>

                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem">
                            <button
                                type="submit"
                                class="ws-btn ws-btn-primary"
                                :disabled="processing"
                            >
                                {{
                                    processing
                                        ? t('website.form.sending')
                                        : t('website.form.submit')
                                }}
                            </button>
                            <p
                                v-if="recentlySuccessful"
                                style="margin: 0; font-size: 0.9rem; font-weight: 600; color: #047857"
                            >
                                {{ t('website.form.success') }}
                            </p>
                        </div>
                    </Form>
                </div>
            </div>
        </section>
    </div>
</template>
