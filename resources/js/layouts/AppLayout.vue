<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import ClientLayout from '@/layouts/ClientLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');
</script>

<template>
    <AdminLayout v-if="isAdmin" :breadcrumbs="breadcrumbs">
        <slot />
    </AdminLayout>
    <ClientLayout v-else :breadcrumbs="breadcrumbs">
        <slot />
    </ClientLayout>
</template>
