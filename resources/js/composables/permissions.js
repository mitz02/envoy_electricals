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

export const MASK_GLYPH = '********';

/**
 * Field-level masking configured per role by the super admin.
 * The owner (represented by the '*' permission) always sees real values.
 */
export function useMask() {
    const page = usePage();
    const user = page.props.auth?.user;
    const maskedFields = (user?.permissions ?? []).includes('*') ? [] : (user?.masked_fields ?? []);

    return {
        isMasked: (slug) => maskedFields.includes(slug),
        /** Returns the mask glyph when masked, otherwise the original value. */
        mask: (slug, value) => (maskedFields.includes(slug) ? MASK_GLYPH : value),
    };
}