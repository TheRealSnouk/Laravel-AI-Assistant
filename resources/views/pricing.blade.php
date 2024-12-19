<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl font-bold text-white mb-4">Choose Your Plan</h2>
                        <p class="text-gray-400 max-w-2xl mx-auto">
                            Get access to powerful AI models and features. Choose the plan that best fits your needs.
                        </p>
                    </div>

                    <x-pricing-section />
                </div>
            </div>

            <!-- Features Grid -->
            <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Fast Response Times -->
                <div class="bg-gray-800 p-6 rounded-lg">
                    <div class="h-12 w-12 flex items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-white">Fast Response Times</h3>
                    <p class="mt-2 text-gray-400">Get instant responses from our AI models with minimal latency.</p>
                </div>

                <!-- Multiple AI Models -->
                <div class="bg-gray-800 p-6 rounded-lg">
                    <div class="h-12 w-12 flex items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-white">Multiple AI Models</h3>
                    <p class="mt-2 text-gray-400">Access various AI models optimized for different types of tasks.</p>
                </div>

                <!-- Flexible Payment Options -->
                <div class="bg-gray-800 p-6 rounded-lg">
                    <div class="h-12 w-12 flex items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-white">Flexible Payments</h3>
                    <p class="mt-2 text-gray-400">Pay with crypto (HBAR/USDT) or traditional methods (PayPal/Cards).</p>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="mt-16">
                <h2 class="text-2xl font-bold text-white mb-8 text-center">Frequently Asked Questions</h2>
                
                <div class="grid gap-6 max-w-3xl mx-auto">
                    <div class="bg-gray-800 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-white mb-2">What payment methods do you accept?</h3>
                        <p class="text-gray-400">We accept both cryptocurrency (HBAR and USDT on Hedera) and traditional payment methods (PayPal and major credit/debit cards).</p>
                    </div>

                    <div class="bg-gray-800 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-white mb-2">Can I upgrade my plan later?</h3>
                        <p class="text-gray-400">Yes, you can upgrade your plan at any time. The new plan will take effect immediately, and you'll only be charged the difference.</p>
                    </div>

                    <div class="bg-gray-800 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-white mb-2">What happens if I exceed my API call limit?</h3>
                        <p class="text-gray-400">If you reach your daily API call limit, you'll need to wait until the next day or upgrade to a higher tier for more calls.</p>
                    </div>

                    <div class="bg-gray-800 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-white mb-2">Do you offer refunds?</h3>
                        <p class="text-gray-400">We offer a prorated refund if you're not satisfied with our service within the first 7 days of your subscription.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
