<div x-data="{ 
    showModal: false,
    selectedTier: null,
    paymentMethod: null,
    cryptoNetwork: null,
    cryptoToken: 'native',
    loading: false,
    paymentDetails: null,
    init() {
        this.$watch('showModal', value => {
            if (!value) {
                this.reset();
            }
        });
    },
    reset() {
        this.selectedTier = null;
        this.paymentMethod = null;
        this.cryptoNetwork = null;
        this.cryptoToken = 'native';
        this.loading = false;
        this.paymentDetails = null;
    },
    async fetchPaymentDetails(tier) {
        this.loading = true;
        try {
            const response = await fetch(`/api/payment-details/${tier}`);
            this.paymentDetails = await response.json();
        } catch (error) {
            console.error('Failed to fetch payment details:', error);
            window.dispatchEvent(new CustomEvent('notify', {
                detail: {
                    message: 'Failed to load payment details',
                    type: 'error'
                }
            }));
        }
        this.loading = false;
    }
}" 
    @show-payment-modal.window="
        showModal = true;
        selectedTier = $event.detail.tier;
        await fetchPaymentDetails($event.detail.tier);
    "
>
    <div x-show="showModal" 
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
    >
        <div class="flex items-center justify-center min-h-screen px-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>

            <!-- Modal -->
            <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full mx-auto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-4"
            >
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900">
                            Complete Your Purchase
                        </h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Close</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <template x-if="loading">
                    <div class="p-6">
                        <div class="flex justify-center">
                            <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                </template>

                <!-- Content -->
                <template x-if="!loading && paymentDetails">
                    <div class="p-6">
                        <!-- Payment Method Selection -->
                        <div class="space-y-4">
                            <div class="font-medium text-gray-900">Select Payment Method</div>
                            
                            <!-- Traditional Payment Methods -->
                            <div class="grid grid-cols-2 gap-4">
                                <button @click="paymentMethod = 'card'" 
                                        :class="{'ring-2 ring-indigo-500': paymentMethod === 'card'}"
                                        class="flex items-center justify-center p-4 border rounded-lg hover:border-indigo-500 transition-colors">
                                    <img src="/images/credit-card.svg" alt="Credit Card" class="h-8 w-8">
                                    <span class="ml-2">Credit Card</span>
                                </button>
                                
                                <button @click="paymentMethod = 'paypal'"
                                        :class="{'ring-2 ring-indigo-500': paymentMethod === 'paypal'}"
                                        class="flex items-center justify-center p-4 border rounded-lg hover:border-indigo-500 transition-colors">
                                    <img src="/images/paypal-logo.svg" alt="PayPal" class="h-8 w-8">
                                    <span class="ml-2">PayPal</span>
                                </button>
                            </div>

                            <!-- Crypto Payment Methods -->
                            <div class="mt-6">
                                <div class="font-medium text-gray-900 mb-4">Cryptocurrency</div>
                                <div class="grid grid-cols-3 gap-4">
                                    <!-- MetaMask -->
                                    <button @click="paymentMethod = 'metamask'" 
                                            :class="{'ring-2 ring-indigo-500': paymentMethod === 'metamask'}"
                                            class="flex flex-col items-center p-4 border rounded-lg hover:border-indigo-500 transition-colors">
                                        <img src="/images/metamask-fox.svg" alt="MetaMask" class="h-8 w-8">
                                        <span class="mt-2 text-sm">MetaMask</span>
                                    </button>

                                    <!-- Keplr -->
                                    <button @click="paymentMethod = 'keplr'"
                                            :class="{'ring-2 ring-indigo-500': paymentMethod === 'keplr'}"
                                            class="flex flex-col items-center p-4 border rounded-lg hover:border-indigo-500 transition-colors">
                                        <img src="/images/keplr-logo.svg" alt="Keplr" class="h-8 w-8">
                                        <span class="mt-2 text-sm">Keplr</span>
                                    </button>

                                    <!-- Hashpack -->
                                    <button @click="paymentMethod = 'hashpack'"
                                            :class="{'ring-2 ring-indigo-500': paymentMethod === 'hashpack'}"
                                            class="flex flex-col items-center p-4 border rounded-lg hover:border-indigo-500 transition-colors">
                                        <img src="/images/hedera-logo.svg" alt="HashPack" class="h-8 w-8">
                                        <span class="mt-2 text-sm">HashPack</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Network Selection for MetaMask -->
                            <template x-if="paymentMethod === 'metamask'">
                                <div class="mt-4 space-y-4">
                                    <div class="font-medium text-gray-900">Select Network</div>
                                    <div class="grid grid-cols-3 gap-4">
                                        <template x-for="network in paymentDetails.crypto.evm.networks" :key="network.chainId">
                                            <button @click="cryptoNetwork = network"
                                                    :class="{'ring-2 ring-indigo-500': cryptoNetwork?.chainId === network.chainId}"
                                                    class="flex flex-col items-center p-4 border rounded-lg hover:border-indigo-500 transition-colors">
                                                <span x-text="network.name" class="text-sm font-medium"></span>
                                                <div class="mt-2 space-y-1 text-xs text-gray-500">
                                                    <div>
                                                        Native: <span x-text="network.nativeCurrency.amount + ' ' + network.nativeCurrency.symbol"></span>
                                                    </div>
                                                    <div>
                                                        USDT: <span x-text="network.usdt.amount + ' USDT'"></span>
                                                    </div>
                                                </div>
                                            </button>
                                        </template>
                                    </div>

                                    <!-- Token Selection -->
                                    <template x-if="cryptoNetwork">
                                        <div class="space-y-2">
                                            <div class="font-medium text-gray-900">Select Token</div>
                                            <div class="flex space-x-4">
                                                <label class="flex items-center space-x-2">
                                                    <input type="radio" x-model="cryptoToken" value="native" class="text-indigo-600">
                                                    <span x-text="cryptoNetwork.nativeCurrency.symbol"></span>
                                                </label>
                                                <label class="flex items-center space-x-2">
                                                    <input type="radio" x-model="cryptoToken" value="usdt" class="text-indigo-600">
                                                    <span>USDT</span>
                                                </label>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Network Selection for Keplr -->
                            <template x-if="paymentMethod === 'keplr'">
                                <div class="mt-4 space-y-4">
                                    <div class="font-medium text-gray-900">Cosmos Hub Network</div>
                                    <div class="p-4 border rounded-lg">
                                        <div class="space-y-2">
                                            <div class="text-sm">
                                                ATOM: <span x-text="paymentDetails.crypto.cosmos.networks['cosmos-hub'].nativeCurrency.amount + ' ATOM'"></span>
                                            </div>
                                            <div class="text-sm">
                                                USDT: <span x-text="paymentDetails.crypto.cosmos.networks['cosmos-hub'].usdt.amount + ' USDT'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Payment Button -->
                            <div class="mt-6">
                                <button @click="$store.cryptoWallet.pay({
                                            wallet: paymentMethod,
                                            network: cryptoNetwork?.name.toLowerCase(),
                                            chainId: cryptoNetwork?.chainId,
                                            token: cryptoToken,
                                            amount: cryptoToken === 'native' ? 
                                                cryptoNetwork?.nativeCurrency.amount : 
                                                cryptoNetwork?.usdt.amount,
                                            address: cryptoToken === 'native' ? 
                                                cryptoNetwork?.nativeCurrency.address : 
                                                cryptoNetwork?.usdt.address,
                                            contractAddress: cryptoToken === 'usdt' ? 
                                                cryptoNetwork?.usdt.contract : null
                                        })"
                                        x-show="paymentMethod && (paymentMethod !== 'metamask' || (paymentMethod === 'metamask' && cryptoNetwork))"
                                        class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                        :disabled="loading">
                                    <span x-text="loading ? 'Processing...' : 'Pay Now'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function paymentModal() {
    return {
        isOpen: false,
        selectedTier: null,
        price: null,
        paymentMethod: null,
        cryptoDetails: null,
        selectedCrypto: 'hbar_hedera',
        transactionHash: '',
        isProcessing: false,

        async openModal(details) {
            this.selectedTier = details.tier;
            this.price = details.price;
            this.isOpen = true;
            
            // Fetch crypto payment details
            try {
                const response = await fetch(`/payment/details?tier=${this.selectedTier}`);
                const data = await response.json();
                this.cryptoDetails = data.payment_details;
            } catch (error) {
                console.error('Failed to fetch payment details:', error);
            }
        },

        closeModal() {
            this.isOpen = false;
            this.resetForm();
        },

        resetForm() {
            this.selectedTier = null;
            this.price = null;
            this.paymentMethod = null;
            this.cryptoDetails = null;
            this.selectedCrypto = 'hbar_hedera';
            this.transactionHash = '';
            this.isProcessing = false;
        },

        getCryptoAmount() {
            if (!this.cryptoDetails) return '';
            const [currency, network] = this.selectedCrypto.split('_');
            return currency === 'hbar' 
                ? `${this.cryptoDetails.crypto.hbar.amount} HBAR`
                : `${this.cryptoDetails.crypto.usdt.amount} USDT`;
        },

        async copyToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);
                // You could add a toast notification here
            } catch (err) {
                console.error('Failed to copy:', err);
            }
        },

        async processPayment() {
            this.isProcessing = true;

            try {
                let response;

                switch (this.paymentMethod) {
                    case 'crypto':
                        response = await fetch('/payment/hedera/verify', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                transaction_id: this.transactionHash,
                                tier: this.selectedTier,
                                type: this.selectedCrypto.split('_')[0]
                            })
                        });
                        break;

                    case 'paypal':
                        response = await fetch('/payment/paypal/create', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                tier: this.selectedTier
                            })
                        });
                        const paypalData = await response.json();
                        window.location.href = paypalData.order.links.find(link => link.rel === 'approve').href;
                        return;

                    case 'card':
                        response = await fetch('/payment/stripe/create', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                tier: this.selectedTier
                            })
                        });
                        const stripeData = await response.json();
                        const stripe = Stripe(stripeData.publishableKey);
                        await stripe.redirectToCheckout({
                            sessionId: stripeData.session_id
                        });
                        return;
                }

                const data = await response.json();

                if (data.success) {
                    window.location.href = '/dashboard?success=payment';
                } else {
                    throw new Error(data.error || 'Payment failed');
                }

            } catch (error) {
                console.error('Payment processing failed:', error);
                // You could add error handling UI here
            } finally {
                this.isProcessing = false;
            }
        }
    }
}
</script>
@endpush
