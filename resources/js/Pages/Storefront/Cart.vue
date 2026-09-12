<script setup>
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { cartItems, cartSubtotal, updateQuantity, removeItem, clearCart } from '@/lib/cart';
import { naira } from '@/lib/format';

defineOptions({ layout: PublicLayout });

function imageUrl(item) {
    return item.image?.startsWith('/images/') || !item.image ? '/images/landing/solar_products.jpg' : `/storage/${item.image}`;
}
</script>

<template>
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
        <h1 class="text-2xl font-black text-slate-900">Your Cart</h1>
        <p class="mt-1 text-sm text-slate-500">Review your items and proceed to checkout.</p>

        <div
            v-if="cartItems.length"
            class="mt-8 space-y-4 overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:p-6"
        >
            <div v-for="item in cartItems" :key="item.product_id" class="flex items-center gap-4 border-b border-slate-100 pb-4 last:border-0 last:pb-0">
                <img :src="imageUrl(item)" :alt="item.name" class="h-16 w-16 rounded-xl object-cover" />
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold text-slate-900">{{ item.name }}</p>
                    <p class="text-sm text-slate-500">{{ naira(item.price) }} each</p>
                    <p class="text-xs text-slate-400">Line total: {{ naira(item.price * item.quantity) }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="h-8 w-8 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100"
                        @click="updateQuantity(item.product_id, item.quantity - 1)"
                    >−</button>
                    <span class="w-8 text-center text-sm font-bold text-slate-900">{{ item.quantity }}</span>
                    <button
                        type="button"
                        class="h-8 w-8 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100"
                        :disabled="item.available > 0 && item.quantity >= item.available"
                        @click="updateQuantity(item.product_id, item.quantity + 1)"
                    >+</button>
                </div>
                <button type="button" class="text-sm font-semibold text-red-600 hover:text-red-700" @click="removeItem(item.product_id)">Remove</button>
            </div>

            <div class="flex flex-col items-end gap-3 pt-4 sm:flex-row sm:justify-between">
                <button type="button" class="text-sm font-medium text-slate-500 underline hover:text-slate-700" @click="clearCart">
                    Clear cart
                </button>
                <div class="text-right">
                    <p class="text-sm text-slate-500">Subtotal</p>
                    <p class="text-2xl font-black text-slate-900">{{ naira(cartSubtotal(), 2) }}</p>
                </div>
            </div>

            <div class="flex justify-end">
                <Link
                    href="/checkout"
                    class="rounded-xl bg-[#0D1527] px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                >Proceed to Checkout</Link>
            </div>
        </div>

        <div v-else class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
            <p class="text-xl font-semibold text-slate-700">Your cart is empty</p>
            <p class="mt-2 text-sm text-slate-500">Browse the shop and add some products to get started.</p>
            <Link href="/shop" class="mt-6 inline-block rounded-xl bg-[#0D1527] px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                Go to Shop
            </Link>
        </div>
    </div>
</template>