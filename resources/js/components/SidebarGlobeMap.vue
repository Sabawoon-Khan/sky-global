<script setup lang="ts">
import { computed } from 'vue';
import { useSidebar } from '@/components/ui/sidebar';

const { state } = useSidebar();
const isCollapsed = computed(() => state.value === 'collapsed');

const mapDots = [
    // North America
    ...Array.from({ length: 24 }, (_, i) => ({
        cx: 24 + (i % 7) * 4.5 + (Math.floor(i / 7) % 2) * 2,
        cy: 16 + Math.floor(i / 7) * 5.5,
    })),
    // Central America
    ...Array.from({ length: 5 }, (_, i) => ({
        cx: 46 + i * 3,
        cy: 34 + (i % 2),
    })),
    // South America
    ...Array.from({ length: 14 }, (_, i) => ({
        cx: 50 + (i % 4) * 3.5,
        cy: 38 + Math.floor(i / 4) * 5,
    })),
    // Europe
    ...Array.from({ length: 10 }, (_, i) => ({
        cx: 102 + (i % 4) * 3.5,
        cy: 14 + Math.floor(i / 4) * 4,
    })),
    // Africa
    ...Array.from({ length: 12 }, (_, i) => ({
        cx: 106 + (i % 3) * 4,
        cy: 28 + Math.floor(i / 3) * 5,
    })),
    // Middle East / Asia
    ...Array.from({ length: 26 }, (_, i) => ({
        cx: 128 + (i % 8) * 4.5,
        cy: 12 + Math.floor(i / 8) * 5,
    })),
    // Southeast Asia
    ...Array.from({ length: 8 }, (_, i) => ({
        cx: 168 + (i % 4) * 3,
        cy: 32 + Math.floor(i / 4) * 4,
    })),
    // Australia
    ...Array.from({ length: 8 }, (_, i) => ({
        cx: 176 + (i % 4) * 3.5,
        cy: 48 + Math.floor(i / 4) * 3.5,
    })),
];

const pulseSites = [
    { cx: 36, cy: 22, delay: '0s' },
    { cx: 58, cy: 44, delay: '1.1s' },
    { cx: 108, cy: 26, delay: '0.4s' },
    { cx: 152, cy: 20, delay: '1.8s' },
    { cx: 182, cy: 50, delay: '2.5s' },
];

const networkLinks = [
    [0, 2],
    [1, 3],
    [2, 4],
    [0, 3],
    [3, 4],
] as const;
</script>

<template>
    <div class="sidebar-globe-map-wrap" aria-hidden="true">
        <!-- Expanded: full spanning map -->
        <Transition name="sidebar-map-fade" mode="out-in">
            <div
                v-if="!isCollapsed"
                key="expanded"
                class="sidebar-globe-map relative overflow-hidden rounded-xl border border-white/8 bg-linear-to-b from-white/4 to-black/30"
            >
                <div
                    class="pointer-events-none absolute inset-0 opacity-30"
                    style="
                        background-image: radial-gradient(
                            rgba(255, 255, 255, 0.07) 1px,
                            transparent 1px
                        );
                        background-size: 12px 12px;
                    "
                />

                <svg
                    viewBox="0 0 260 88"
                    class="relative block w-full"
                    preserveAspectRatio="xMidYMid slice"
                >
                    <defs>
                        <linearGradient
                            id="sidebar-map-scan"
                            x1="0%"
                            y1="0%"
                            x2="100%"
                            y2="0%"
                        >
                            <stop
                                offset="0%"
                                stop-color="#ef4444"
                                stop-opacity="0"
                            />
                            <stop
                                offset="45%"
                                stop-color="#ef4444"
                                stop-opacity="0.85"
                            />
                            <stop
                                offset="100%"
                                stop-color="#ef4444"
                                stop-opacity="0"
                            />
                        </linearGradient>
                        <linearGradient
                            id="sidebar-map-scan-soft"
                            x1="0%"
                            y1="0%"
                            x2="100%"
                            y2="0%"
                        >
                            <stop
                                offset="0%"
                                stop-color="#dc2626"
                                stop-opacity="0"
                            />
                            <stop
                                offset="50%"
                                stop-color="#dc2626"
                                stop-opacity="0.35"
                            />
                            <stop
                                offset="100%"
                                stop-color="#dc2626"
                                stop-opacity="0"
                            />
                        </linearGradient>
                        <radialGradient
                            id="sidebar-map-glow"
                            cx="50%"
                            cy="100%"
                            r="75%"
                        >
                            <stop
                                offset="0%"
                                stop-color="#dc2626"
                                stop-opacity="0.22"
                            />
                            <stop
                                offset="100%"
                                stop-color="#dc2626"
                                stop-opacity="0"
                            />
                        </radialGradient>
                        <filter id="sidebar-map-blur">
                            <feGaussianBlur stdDeviation="1.5" />
                        </filter>
                    </defs>

                    <!-- Radar arc -->
                    <path
                        d="M 20 88 A 110 110 0 0 1 240 88"
                        fill="none"
                        stroke="rgba(220,38,38,0.12)"
                        stroke-width="1"
                    />
                    <path
                        d="M 52 88 A 78 78 0 0 1 208 88"
                        fill="none"
                        stroke="rgba(255,255,255,0.06)"
                        stroke-width="0.75"
                    />
                    <path
                        d="M 84 88 A 46 46 0 0 1 176 88"
                        fill="none"
                        stroke="rgba(255,255,255,0.05)"
                        stroke-width="0.75"
                    />

                    <!-- Latitude arcs -->
                    <path
                        d="M 0 28 Q 130 16 260 28"
                        fill="none"
                        stroke="rgba(255,255,255,0.07)"
                        stroke-width="0.75"
                    />
                    <path
                        d="M 0 44 Q 130 34 260 44"
                        fill="none"
                        stroke="rgba(255,255,255,0.09)"
                        stroke-width="0.75"
                    />
                    <path
                        d="M 0 60 Q 130 52 260 60"
                        fill="none"
                        stroke="rgba(255,255,255,0.06)"
                        stroke-width="0.75"
                    />

                    <!-- Longitude curves -->
                    <path
                        d="M 65 0 Q 56 44 65 88"
                        fill="none"
                        stroke="rgba(255,255,255,0.05)"
                        stroke-width="0.75"
                    />
                    <path
                        d="M 130 0 Q 121 44 130 88"
                        fill="none"
                        stroke="rgba(255,255,255,0.08)"
                        stroke-width="0.75"
                    />
                    <path
                        d="M 195 0 Q 186 44 195 88"
                        fill="none"
                        stroke="rgba(255,255,255,0.05)"
                        stroke-width="0.75"
                    />

                    <!-- Network links -->
                    <line
                        v-for="([a, b], i) in networkLinks"
                        :key="`link-${i}`"
                        :x1="pulseSites[a].cx"
                        :y1="pulseSites[a].cy"
                        :x2="pulseSites[b].cx"
                        :y2="pulseSites[b].cy"
                        stroke="rgba(220,38,38,0.18)"
                        stroke-width="0.75"
                        stroke-dasharray="3 4"
                        class="sidebar-map-link"
                        :style="{ animationDelay: `${i * 0.4}s` }"
                    />

                    <!-- Continent dots -->
                    <circle
                        v-for="(dot, i) in mapDots"
                        :key="i"
                        :cx="dot.cx"
                        :cy="dot.cy"
                        r="1.15"
                        class="sidebar-map-dot"
                        :style="{ animationDelay: `${(i % 14) * 0.12}s` }"
                    />

                    <rect
                        x="0"
                        y="48"
                        width="260"
                        height="40"
                        fill="url(#sidebar-map-glow)"
                    />

                    <!-- Dual scan beams -->
                    <rect
                        x="-48"
                        y="0"
                        width="48"
                        height="88"
                        fill="url(#sidebar-map-scan)"
                        class="sidebar-map-scan"
                    />
                    <rect
                        x="-80"
                        y="0"
                        width="64"
                        height="88"
                        fill="url(#sidebar-map-scan-soft)"
                        class="sidebar-map-scan sidebar-map-scan-slow"
                    />

                    <!-- Sweeping radar wedge -->
                    <g class="sidebar-map-radar" style="transform-origin: 130px 88px">
                        <path
                            d="M 130 88 L 130 20 A 68 68 0 0 1 188 52 Z"
                            fill="rgba(220,38,38,0.08)"
                            filter="url(#sidebar-map-blur)"
                        />
                    </g>

                    <!-- Active sites -->
                    <g v-for="(site, i) in pulseSites" :key="`pulse-${i}`">
                        <circle
                            :cx="site.cx"
                            :cy="site.cy"
                            r="4"
                            fill="none"
                            stroke="#ef4444"
                            stroke-width="0.75"
                            class="sidebar-map-pulse-ring"
                            :style="{ animationDelay: site.delay }"
                        />
                        <circle
                            :cx="site.cx"
                            :cy="site.cy"
                            r="2"
                            fill="#ef4444"
                            class="sidebar-map-pulse-core"
                            :style="{ animationDelay: site.delay }"
                        />
                    </g>
                </svg>

                <div class="sidebar-map-footer">
                    <div class="flex items-center gap-1.5">
                        <span class="sidebar-map-live-dot size-1.5 rounded-full bg-red-500" />
                        <span
                            class="text-[9px] font-semibold uppercase tracking-[0.16em] text-white/55"
                        >
                            Global Coverage
                        </span>
                    </div>
                    <span
                        class="font-mono text-[9px] tabular-nums text-white/35"
                    >
                        5 sites active
                    </span>
                </div>
            </div>

            <!-- Collapsed: compact radar -->
            <div
                v-else
                key="collapsed"
                class="sidebar-globe-map-mini relative mx-auto flex size-10 items-center justify-center overflow-hidden rounded-xl border border-white/8 bg-black/30"
            >
                <svg viewBox="0 0 40 40" class="size-10">
                    <circle
                        cx="20"
                        cy="20"
                        r="14"
                        fill="none"
                        stroke="rgba(255,255,255,0.1)"
                        stroke-width="0.75"
                    />
                    <circle
                        cx="20"
                        cy="20"
                        r="9"
                        fill="none"
                        stroke="rgba(255,255,255,0.08)"
                        stroke-width="0.75"
                    />
                    <circle
                        cx="20"
                        cy="20"
                        r="4"
                        fill="none"
                        stroke="rgba(255,255,255,0.06)"
                        stroke-width="0.75"
                    />
                    <g
                        class="sidebar-map-radar-mini"
                        style="transform-origin: 20px 20px"
                    >
                        <path
                            d="M 20 20 L 20 6 A 14 14 0 0 1 32 16 Z"
                            fill="rgba(220,38,38,0.25)"
                        />
                    </g>
                    <circle cx="20" cy="20" r="2" fill="#ef4444" />
                    <circle
                        cx="20"
                        cy="20"
                        r="2"
                        fill="none"
                        stroke="#ef4444"
                        stroke-width="0.75"
                        class="sidebar-map-pulse-ring"
                    />
                </svg>
            </div>
        </Transition>
    </div>
</template>
