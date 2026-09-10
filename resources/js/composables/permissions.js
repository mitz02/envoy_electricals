import { usePage } from '@inertiajs/vue3';

export function useCan() {
    const page = usePage();
    const permissions = page.props.auth?.user?.permissions ?? [];

    return {
        all: permissions,
        has: (slug) => permissions.includes('*') || permissions.includes(slug),
        any: (slugs) => permissions.includes('*') || slugs.some((s) => permissions.includes(s)),
    };
}