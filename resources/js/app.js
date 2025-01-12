console.log('App.js loaded');
import './bootstrap';
import Alpine from 'alpinejs';
import { walletHandlers } from './wallets';
import { loadStripe } from '@stripe/stripe-js';

window.Alpine = Alpine;

// Initialize AlpineJS
document.addEventListener('alpine:init', () => {
    // Initialize crypto wallet store
    Alpine.store('cryptoWallet', walletHandlers);
});

// Initialize Stripe
window.initStripe = async () => {
    const publishableKey = document.querySelector('meta[name="stripe-key"]').content;
    window.stripe = await loadStripe(publishableKey);
};

Alpine.start();
