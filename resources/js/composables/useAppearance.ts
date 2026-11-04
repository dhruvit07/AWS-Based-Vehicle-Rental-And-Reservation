import { onMounted, ref } from 'vue';

export type Appearance = 'light' | 'dark' | 'system';

export function updateTheme(value: Appearance) {
    if (typeof window === 'undefined') return;

    if (value === 'system') {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.classList.toggle('dark', prefersDark);
    } else {
        document.documentElement.classList.toggle('dark', value === 'dark');
    }
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') return;

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const getCookie = (name: string): string | null => {
    if (typeof document === 'undefined') return null;
    const match = document.cookie.match(new RegExp('(^|;\\s*)' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[2]) : null;
};

const appearance = ref<Appearance>('system');

export function initializeTheme() {
    if (typeof window === 'undefined') return;

    const saved = (localStorage.getItem('appearance') || getCookie('appearance') || 'system') as Appearance;
    appearance.value = saved;
    updateTheme(saved);

    // Listen for OS system theme changes when mode is 'system'
    const media = window.matchMedia('(prefers-color-scheme: dark)');
    media.addEventListener('change', (e) => {
        if (appearance.value === 'system') {
            document.documentElement.classList.toggle('dark', e.matches);
        }
    });
}

export function useAppearance() {
    onMounted(() => {
        const saved = (localStorage.getItem('appearance') || getCookie('appearance') || 'system') as Appearance;
        appearance.value = saved;
        updateTheme(saved);
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;
        localStorage.setItem('appearance', value);
        setCookie('appearance', value);
        updateTheme(value);
    }

    return {
        appearance,
        updateAppearance,
    };
}
