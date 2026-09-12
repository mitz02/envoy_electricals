<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { cartItems, cartSubtotal } from '@/lib/cart';
import { naira } from '@/lib/format';

defineOptions({ layout: PublicLayout });

const form = useForm({
    customer_name: '',
    customer_phone: '',
    customer_email: '',
    delivery_address: '',
});

function submit() {
    form.post('/checkout', {
        items: cartItems.value.map((i) => ({ product_id: i.product_id, quantity: i.quantity })),
    });
}

function imageUrl(item) {
    return item.image?.startsWith('/images/') || !item.image ? '/images/landing/solar_products.jpg' : `/storage/${item.image}`;
}
</script>

<template>
    <FlashMessages />
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
        <h1 class="text-2xl font-black text-slate-900">Checkout</h1>
        <p class="mt-1 text-sm text-slate-500">Enter your delivery details to place the order.</p>

        <div
            v-if="cartItems.length"
            class="mt-8 grid gap-6 lg:grid-cols-5"
        >
            <form class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs lg:col-span-3" @submit.prevent="submit">
                <div>
                    <label class="text-sm font-medium text-slate-700">Full name</label>
                    <input
                        v-model="form.customer_name"
                        type="text"
                        required
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    />
                    <p v-if="form.errors.customer_name" class="mt-1 text-xs text-red-600">{{ form.errors.customer_name }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Phone number</label>
                    <input
                        v-model="form.customer_phone"
                        type="tel"
                        required
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    />
                    <p v-if="form.errors.customer_phone" class="mt-1 text-xs text-red-600">{{ form.errors.customer_phone }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Email (optional — used for Paystack payment)</label>
                    <input
                        v-model="form.customer_email"
                        type="email"
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    />
                    <p v-if="form.errors.customer_email" class="mt-1 text-xs text-red-600">{{ form.errors.customer_email }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Delivery address</label>
                    <textarea
                        v-model="form.delivery_address"
                        rows="3"
                        required
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    ></textarea>
                    <p v-if="form.errors.delivery_address" class="mt-1 text-xs text-red-600">{{ form.errors.delivery_address }}</p>
                </div>

                <p v-if="form.errors.items" class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">{{ form.errors.items }}</p>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-[#0D1527] px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                >
                    Place Order
                </button>
            </form>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs lg:col-span-2">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Order summary</h2>
                <ul class="mt-4 space-y-3">
                    <li v-for="item in cartItems" :key="item.product_id" class="flex items-start gap-3">
                        <img :src="imageUrl(item)" :alt="item.name" class="h-12 w-12 rounded-lg object-cover" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-800">{{ item.name }}</p>
                            <p class="text-xs text-slate-500">{{ item.quantity }} × {{ naira(item.price) }}</p>
                        </div>
                        <p class="text-sm font-semibold text-slate-900">{{ naira(item.price * item.quantity) }}</p>
                    </li>
                </ul>
                <div class="mt-5 border-t border-slate-200 pt-4">
                    <div class="flex justify-between text-sm text-slate-600">
                        <span>Subtotal</span>
                        <span>{{ naira(cartSubtotal(), 2) }}</span>
                    </div>
                    <div class="mt-2 flex justify-between text-lg font-black text-slate-900">
                        <span>Total</span>
                        <span>{{ naira(cartSubtotal(), 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
            <p class="text-xl font-semibold text-slate-700">Your cart is empty</p>
            <Link href="/shop" class="mt-6 inline-block rounded-xl bg-[#0D1527] px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                Go to Shop
            </Link>
        </div>
    </div>
</template>