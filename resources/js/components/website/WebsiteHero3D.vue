<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import * as THREE from 'three';

const host = ref<HTMLElement | null>(null);

let cleanup: (() => void) | null = null;

onMounted(() => {
    const el = host.value;
    if (!el || typeof window === 'undefined') {
        return;
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const width = el.clientWidth || 640;
    const height = el.clientHeight || 520;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(38, width / height, 0.1, 100);
    camera.position.set(0.15, 0.1, 5.2);

    const renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true,
        powerPreference: 'high-performance',
    });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75));
    renderer.setSize(width, height, false);
    renderer.setClearColor(0x000000, 0);
    el.appendChild(renderer.domElement);

    // Logo colors: navy #203070, gold #f0c000, deep #001060
    const navy = new THREE.Color('#203070');
    const navyDeep = new THREE.Color('#001060');
    const gold = new THREE.Color('#f0c000');
    const silver = new THREE.Color('#90a0b0');

    const root = new THREE.Group();
    scene.add(root);

    const shieldShape = new THREE.Shape();
    shieldShape.moveTo(0, 1.35);
    shieldShape.bezierCurveTo(0.95, 1.2, 1.25, 0.55, 1.2, -0.05);
    shieldShape.bezierCurveTo(1.1, -0.85, 0.55, -1.35, 0, -1.55);
    shieldShape.bezierCurveTo(-0.55, -1.35, -1.1, -0.85, -1.2, -0.05);
    shieldShape.bezierCurveTo(-1.25, 0.55, -0.95, 1.2, 0, 1.35);

    const extrude = new THREE.ExtrudeGeometry(shieldShape, {
        depth: 0.28,
        bevelEnabled: true,
        bevelThickness: 0.05,
        bevelSize: 0.04,
        bevelSegments: 3,
        curveSegments: 28,
    });
    extrude.center();

    const shield = new THREE.Mesh(
        extrude,
        new THREE.MeshPhysicalMaterial({
            color: navy,
            metalness: 0.55,
            roughness: 0.28,
            clearcoat: 0.7,
            clearcoatRoughness: 0.25,
            emissive: navyDeep,
            emissiveIntensity: 0.18,
        }),
    );
    root.add(shield);

    const rim = new THREE.LineSegments(
        new THREE.EdgesGeometry(extrude, 28),
        new THREE.LineBasicMaterial({ color: gold, transparent: true, opacity: 0.85 }),
    );
    shield.add(rim);

    const emblem = new THREE.Mesh(
        new THREE.CircleGeometry(0.42, 48),
        new THREE.MeshStandardMaterial({
            color: gold,
            metalness: 0.8,
            roughness: 0.22,
            emissive: gold,
            emissiveIntensity: 0.2,
        }),
    );
    emblem.position.z = 0.2;
    shield.add(emblem);

    const ringA = new THREE.Mesh(
        new THREE.TorusGeometry(2.05, 0.028, 16, 120),
        new THREE.MeshStandardMaterial({
            color: gold,
            metalness: 0.9,
            roughness: 0.2,
            emissive: gold,
            emissiveIntensity: 0.15,
            side: THREE.DoubleSide,
        }),
    );
    ringA.rotation.x = Math.PI / 2.4;

    const ringB = new THREE.Mesh(
        new THREE.TorusGeometry(2.35, 0.018, 12, 140),
        new THREE.MeshStandardMaterial({
            color: silver,
            metalness: 0.7,
            roughness: 0.35,
            transparent: true,
            opacity: 0.7,
            side: THREE.DoubleSide,
        }),
    );
    ringB.rotation.y = Math.PI / 3.2;
    root.add(ringA, ringB);

    const particleGeo = new THREE.BufferGeometry();
    const count = 140;
    const positions = new Float32Array(count * 3);
    for (let i = 0; i < count; i += 1) {
        const r = 2.1 + Math.random() * 1.6;
        const theta = Math.random() * Math.PI * 2;
        const phi = (Math.random() - 0.5) * Math.PI;
        positions[i * 3] = r * Math.cos(theta) * Math.cos(phi);
        positions[i * 3 + 1] = r * Math.sin(phi) * 0.75;
        positions[i * 3 + 2] = r * Math.sin(theta) * Math.cos(phi);
    }
    particleGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    const dots = new THREE.Points(
        particleGeo,
        new THREE.PointsMaterial({
            color: gold,
            size: 0.035,
            transparent: true,
            opacity: 0.85,
            depthWrite: false,
        }),
    );
    root.add(dots);

    scene.add(new THREE.AmbientLight(0x90a0b0, 0.55));
    const key = new THREE.DirectionalLight(0xffffff, 1.15);
    key.position.set(3.5, 2.8, 4.2);
    scene.add(key);
    const fill = new THREE.DirectionalLight(0xf0c000, 0.55);
    fill.position.set(-3.2, -1.2, 2.4);
    scene.add(fill);
    const glow = new THREE.PointLight(0xf0c000, 1.4, 12);
    glow.position.set(0.4, 1.6, 2.2);
    scene.add(glow);

    const pointer = { x: 0, y: 0 };
    const onPointer = (event: PointerEvent) => {
        const rect = el.getBoundingClientRect();
        pointer.x = ((event.clientX - rect.left) / Math.max(rect.width, 1)) * 2 - 1;
        pointer.y = -(((event.clientY - rect.top) / Math.max(rect.height, 1)) * 2 - 1);
    };
    el.addEventListener('pointermove', onPointer);

    const clock = new THREE.Clock();
    let running = true;
    let animationId = 0;

    const tick = () => {
        if (!running) {
            return;
        }
        const t = clock.getElapsedTime();
        root.rotation.y = t * 0.28 + pointer.x * 0.35;
        root.rotation.x = Math.sin(t * 0.35) * 0.08 + pointer.y * 0.2;
        ringA.rotation.z = t * 0.45;
        ringB.rotation.x = t * 0.28;
        dots.rotation.y = -t * 0.12;
        shield.position.y = Math.sin(t * 0.9) * 0.06;
        renderer.render(scene, camera);
        animationId = window.requestAnimationFrame(tick);
    };
    tick();

    const onResize = () => {
        const w = el.clientWidth || 1;
        const h = el.clientHeight || 1;
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        renderer.setSize(w, h, false);
    };
    const resizeObserver = new ResizeObserver(onResize);
    resizeObserver.observe(el);

    const onVisibility = () => {
        const visible = document.visibilityState === 'visible';
        if (visible && !running) {
            running = true;
            tick();
        } else if (!visible) {
            running = false;
            window.cancelAnimationFrame(animationId);
            animationId = 0;
        }
    };
    document.addEventListener('visibilitychange', onVisibility);

    cleanup = () => {
        running = false;
        window.cancelAnimationFrame(animationId);
        el.removeEventListener('pointermove', onPointer);
        document.removeEventListener('visibilitychange', onVisibility);
        resizeObserver.disconnect();
        scene.traverse((obj) => {
            if (
                obj instanceof THREE.Mesh ||
                obj instanceof THREE.Points ||
                obj instanceof THREE.LineSegments
            ) {
                obj.geometry.dispose();
                const material = obj.material;
                if (Array.isArray(material)) {
                    material.forEach((item) => item.dispose());
                } else {
                    material.dispose();
                }
            }
        });
        renderer.dispose();
        renderer.domElement.remove();
    };
});

onUnmounted(() => {
    cleanup?.();
    cleanup = null;
});
</script>

<template>
    <div ref="host" class="ws-hero-3d" aria-hidden="true" />
</template>
