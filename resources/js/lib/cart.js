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

export function cartSubtotal() {
    return cartItems.value.reduce((sum, i) => sum + i.price * i.quantity, 0);
}

export function hasCartItems() {
    return cartItems.value.length > 0;
}

export function bumpCart() {
    syncCount();
}