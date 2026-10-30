import { ref, watch } from 'vue';

const STORAGE_KEY = 'envoy-cart';

function loadStored() {
    if (typeof window === 'undefined') return [];
    try {
        const parsed = JSON.parse(window.localStorage.getItem(STORAGE_KEY) ?? '[]');
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

export const cartItems = ref(loadStored());

export const cartCount = ref(cartItems.value.reduce((n, i) => n + i.quantity, 0));

function persist() {
    if (typeof window !== 'undefined') {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(cartItems.value));
    }
}

watch(cartItems, persist, { deep: true });

function syncCount() {
    cartCount.value = cartItems.value.reduce((n, i) => n + i.quantity, 0);
}

export function addItem(product, qty = 1) {
    const existing = cartItems.value.find((i) => i.product_id === product.id);
    if (existing) {
        existing.quantity += qty;
    } else {
        cartItems.value.push({
            product_id: product.id,
            name: product.name,
            price: Number(product.selling_price),
            image: product.images?.[0]?.path ?? '',
            quantity: qty,
            available: Number(product.current_quantity ?? 0),
        });
    }
    syncCount();
}

export function updateQuantity(productId, qty) {
    const item = cartItems.value.find((i) => i.product_id === productId);
    if (!item) return;
    item.quantity = Math.max(1, Math.min(qty, item.available || qty));
    syncCount();
}

export function removeItem(productId) {
    cartItems.value = cartItems.value.filter((i) => i.product_id !== productId);
    syncCount();
}

export function clearCart() {
    cartItems.value = [];
    syncCount();
}

/**
 * Rebuild the cart from a dead order's items.
 *
 * Checkout clears the cart the moment an order is created so the same basket
 * cannot be ordered twice. If that order is later cancelled or refunded the
 * customer should not silently lose their basket, so this puts it back. Lines
 * whose product has since been deleted are dropped, and quantities are kept as
 * ordered rather than clamped: the server re-validates stock on the next
 * checkout, so an item that is no longer available fails loudly there instead
 * of quietly disappearing from the cart.
 *
 * @param {Array} items Order items, each with an eager-loaded `product`.
 * @returns {number} Number of lines restored.
 */
export function restoreCart(items) {
    const lines = (Array.isArray(items) ? items : [])
        .filter((item) => item?.product)
        .map((item) => ({
            product_id: item.product.id,
            name: item.product.name,
            price: Number(item.product.selling_price ?? item.unit_price ?? 0),
            image: item.product.images?.[0]?.path ?? '',
            quantity: Math.max(1, Number(item.quantity) || 1),
            available: Number(item.product.current_quantity ?? 0),
        }));

    if (lines.length === 0) {
        return 0;
    }

    cartItems.value = lines;
    syncCount();

    return lines.length;
}

export function cartSubtotal() {
    return cartItems.value.reduce((sum, i) => sum + i.price * i.quantity, 0);
}

export function hasCartItems() {
    return cartItems.value.length > 0;
}

export function bumpCart() {
    syncCount();
}