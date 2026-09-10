import { ref } from 'vue';

export const cartCount = ref(0);

export function bumpCart() {
    cartCount.value += 1;
}