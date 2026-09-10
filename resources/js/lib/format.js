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

export const stockStatusLabel = {
    in_stock: { label: 'In Stock', cls: 'bg-emerald-100 text-emerald-800' },
    low_stock: { label: 'Low Stock', cls: 'bg-amber-100 text-amber-800' },
    out_of_stock: { label: 'Out of Stock', cls: 'bg-red-100 text-red-700' },
};

export function badgeClass(colors) {
    return `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colors}`;
}