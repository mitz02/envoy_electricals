import { usePage } from '@inertiajs/vue3';

export const MASK_GLYPH = '********';

function maskedSlugs() {
    const user = usePage().props.auth?.user;
    if ((user?.permissions ?? []).includes('*')) return [];
    return user?.masked_fields ?? [];
}

export const isMasked = (slug) => maskedSlugs().includes(slug);

export function naira(value, fractionDigits = 0) {
    const n = Number(value ?? 0);
    if (Number.isNaN(n)) {
        return '₦0';
    }
    return '₦' + n.toLocaleString('en-NG', {
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
    });
}

/** Naira formatted, replaced with the mask glyph when `slug` is masked for the current user. */
export function maskNaira(slug, value, fractionDigits = 0) {
    return isMasked(slug) ? MASK_GLYPH : naira(value, fractionDigits);
}

/** Percent-ish (1 decimal) value, replaced with the mask glyph when masked. */
export function maskPercent(slug, value) {
    return isMasked(slug) ? MASK_GLYPH : `${Number(value ?? 0).toFixed(1)}%`;
}

export function formatDate(value) {
    if (!value) {
        return '-';
    }
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) {
        return value;
    }
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

export function formatDateTime(value) {
    if (!value) {
        return '-';
    }
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) {
        return value;
    }
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
        + ' ' + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
}

export function timeAgo(value) {
    if (!value) {
        return '';
    }
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) {
        return '';
    }
    const seconds = (Date.now() - d.getTime()) / 1000;
    if (seconds < 60) return 'just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`;
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

export const stockStatusLabel = {
    in_stock: { label: 'In Stock', cls: 'bg-emerald-100 text-emerald-800' },
    low_stock: { label: 'Low Stock', cls: 'bg-amber-100 text-amber-800' },
    out_of_stock: { label: 'Out of Stock', cls: 'bg-red-100 text-red-700' },
};

export function badgeClass(colors) {
    return `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colors}`;
}