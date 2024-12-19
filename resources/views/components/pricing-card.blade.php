@props(['tier', 'price', 'features', 'recommended' => false])

<div class="relative p-6 bg-white rounded-2xl shadow-xl transition-all duration-300 hover:shadow-2xl {{ $recommended ? 'border-2 border-indigo-500' : 'border border-gray-200' }}">
    @if($recommended)
        <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
            <span class="bg-indigo-500 text-white px-4 py-1 rounded-full text-sm font-medium">Recommended</span>
        </div>
    @endif
    
    <div class="text-center">
        <h3 class="text-2xl font-bold text-gray-900 capitalize">{{ $tier }}</h3>
        <div class="mt-4 flex items-baseline justify-center gap-x-2">
            <span class="text-5xl font-bold tracking-tight text-gray-900">${{ $price }}</span>
            <span class="text-sm font-semibold leading-6 tracking-wide text-gray-600">/month</span>
        </div>
    </div>

    <ul role="list" class="mt-8 space-y-3">
        @foreach($features as $feature)
            <li class="flex gap-x-3">
                <svg class="h-6 w-5 flex-none text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                <span class="text-sm leading-6 text-gray-600">{{ $feature }}</span>
            </li>
        @endforeach
    </ul>

    @if($tier !== 'free')
        <div class="mt-8">
            <button
                @click="$dispatch('open-payment-modal', { tier: '{{ $tier }}' })"
                class="w-full rounded-lg {{ $recommended ? 'bg-indigo-500 hover:bg-indigo-600' : 'bg-indigo-50 hover:bg-indigo-100' }} px-4 py-2.5 text-sm font-semibold {{ $recommended ? 'text-white' : 'text-indigo-600' }} shadow-sm hover:scale-105 transition-all duration-300"
            >
                Get Started
            </button>
        </div>
    @endif
</div>
