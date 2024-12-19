<div class="py-24 sm:py-32">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-4xl text-center">
            <h2 class="text-base font-semibold leading-7 text-indigo-600">Pricing</h2>
            <p class="mt-2 text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
                Choose the right plan for&nbsp;you
            </p>
        </div>
        <p class="mx-auto mt-6 max-w-2xl text-center text-lg leading-8 text-gray-600">
            Get access to powerful AI models and features with our flexible pricing plans
        </p>
        
        <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3" x-data="{ prices: null }" x-init="
            fetch('/payment/pricing')
                .then(response => response.json())
                .then(data => prices = data.pricing)
        ">
            <template x-for="(tier, name) in prices" :key="name">
                <div class="relative p-8 bg-white rounded-3xl shadow-xl ring-1 ring-gray-200" 
                     :class="{'ring-2 ring-indigo-600': name === 'pro'}">
                    <h3 class="text-2xl font-bold tracking-tight text-gray-900 capitalize" x-text="name"></h3>
                    
                    <p class="mt-4 text-sm leading-6 text-gray-600" x-text="tier.description"></p>
                    
                    <p class="mt-6 flex items-baseline gap-x-1">
                        <span class="text-4xl font-bold tracking-tight text-gray-900" x-text="'$' + tier.price"></span>
                        <span class="text-sm font-semibold leading-6 text-gray-600">/month</span>
                    </p>

                    <ul role="list" class="mt-8 space-y-3 text-sm leading-6 text-gray-600">
                        <template x-for="feature in tier.features" :key="feature">
                            <li class="flex gap-x-3">
                                <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" />
                                </svg>
                                <span x-text="feature"></span>
                            </li>
                        </template>
                    </ul>

                    <template x-if="name !== 'free'">
                        <button @click="$dispatch('open-payment-modal', { tier: name, price: tier.price })"
                                class="mt-8 w-full rounded-md py-2.5 px-3.5 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                                :class="name === 'pro' ? 'bg-indigo-600 text-white hover:bg-indigo-500 focus-visible:outline-indigo-600' : 'bg-indigo-50 text-indigo-600 hover:bg-indigo-100'">
                            Get started
                        </button>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>
