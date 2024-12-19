<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subscription;
use App\Models\SubscriptionModifier;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\PaymentMethod;
use Stripe\Price;
use Stripe\Coupon;
use Stripe\PromotionCode;
use Stripe\SubscriptionSchedule;
use Stripe\Subscription as StripeSubscription;
use Stripe\SubscriptionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class SubscriptionService
{
    protected $stripe;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a new subscription
     */
    public function createSubscription(User $user, string $priceId, array $options = [])
    {
        try {
            // Ensure user has a Stripe customer ID
            if (!$user->stripe_customer_id) {
                $customer = Customer::create([
                    'email' => $user->email,
                    'metadata' => [
                        'user_id' => $user->id
                    ]
                ]);
                $user->update(['stripe_customer_id' => $customer->id]);
            }

            // Create the subscription
            $subscription = StripeSubscription::create([
                'customer' => $user->stripe_customer_id,
                'items' => [['price' => $priceId]],
                'payment_behavior' => 'default_incomplete',
                'payment_settings' => ['save_default_payment_method' => 'on_subscription'],
                'expand' => ['latest_invoice.payment_intent'],
                'metadata' => array_merge([
                    'user_id' => $user->id
                ], $options['metadata'] ?? []),
                'trial_period_days' => $options['trial_days'] ?? null,
            ]);

            return [
                'subscriptionId' => $subscription->id,
                'clientSecret' => $subscription->latest_invoice->payment_intent->client_secret,
            ];
        } catch (Exception $e) {
            Log::error('Failed to create subscription', [
                'user_id' => $user->id,
                'price_id' => $priceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Subscription $subscription, bool $immediately = false)
    {
        try {
            $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id);

            if ($immediately) {
                $stripeSubscription->cancel();
                $subscription->update([
                    'stripe_status' => 'canceled',
                    'ends_at' => now(),
                ]);
            } else {
                $stripeSubscription->cancel_at_period_end = true;
                $stripeSubscription->save();
                $subscription->update([
                    'ends_at' => now()->timestamp($stripeSubscription->current_period_end),
                ]);
            }

            return $subscription->fresh();
        } catch (Exception $e) {
            Log::error('Failed to cancel subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Resume a canceled subscription
     */
    public function resumeSubscription(Subscription $subscription)
    {
        try {
            if ($subscription->stripe_status !== 'active' && $subscription->ends_at > now()) {
                $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id);
                $stripeSubscription->cancel_at_period_end = false;
                $stripeSubscription->save();

                $subscription->update([
                    'ends_at' => null,
                ]);
            }

            return $subscription->fresh();
        } catch (Exception $e) {
            Log::error('Failed to resume subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Change subscription plan
     */
    public function changePlan(Subscription $subscription, string $newPriceId)
    {
        try {
            $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id);
            
            // Update the subscription item with the new price
            StripeSubscription::update($subscription->stripe_subscription_id, [
                'items' => [
                    [
                        'id' => $stripeSubscription->items->data[0]->id,
                        'price' => $newPriceId,
                    ],
                ],
                'proration_behavior' => 'always_invoice',
            ]);

            $subscription->update([
                'stripe_price_id' => $newPriceId,
            ]);

            return $subscription->fresh();
        } catch (Exception $e) {
            Log::error('Failed to change subscription plan', [
                'subscription_id' => $subscription->id,
                'new_price_id' => $newPriceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get subscription details
     */
    public function getSubscriptionDetails(string $subscriptionId)
    {
        try {
            return StripeSubscription::retrieve([
                'id' => $subscriptionId,
                'expand' => [
                    'latest_invoice',
                    'customer',
                    'default_payment_method',
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get subscription details', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update subscription quantity
     */
    public function updateQuantity(Subscription $subscription, int $quantity)
    {
        try {
            $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id);
            
            StripeSubscription::update($subscription->stripe_subscription_id, [
                'items' => [
                    [
                        'id' => $stripeSubscription->items->data[0]->id,
                        'quantity' => $quantity,
                    ],
                ],
            ]);

            $subscription->update([
                'quantity' => $quantity,
            ]);

            return $subscription->fresh();
        } catch (Exception $e) {
            Log::error('Failed to update subscription quantity', [
                'subscription_id' => $subscription->id,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Report usage for metered billing
     */
    public function reportUsage(Subscription $subscription, int $quantity, string $timestamp = null, string $action = 'increment')
    {
        try {
            $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id);
            $subscriptionItem = $stripeSubscription->items->data[0];

            $usageRecord = SubscriptionItem::createUsageRecord(
                $subscriptionItem->id,
                [
                    'quantity' => $quantity,
                    'timestamp' => $timestamp ?? time(),
                    'action' => $action,
                ]
            );

            Log::info('Usage reported successfully', [
                'subscription_id' => $subscription->id,
                'quantity' => $quantity,
                'usage_record_id' => $usageRecord->id
            ]);

            return $usageRecord;
        } catch (Exception $e) {
            Log::error('Failed to report usage', [
                'subscription_id' => $subscription->id,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Schedule future subscription changes
     */
    public function scheduleSubscriptionUpdate(Subscription $subscription, array $phases)
    {
        try {
            $schedule = SubscriptionSchedule::create([
                'from_subscription' => $subscription->stripe_subscription_id,
                'phases' => $phases,
            ]);

            Log::info('Subscription schedule created', [
                'subscription_id' => $subscription->id,
                'schedule_id' => $schedule->id
            ]);

            return $schedule;
        } catch (Exception $e) {
            Log::error('Failed to schedule subscription update', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Add a payment method to customer
     */
    public function addPaymentMethod(User $user, string $paymentMethodId, bool $setAsDefault = false)
    {
        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $user->stripe_customer_id]);

            if ($setAsDefault) {
                Customer::update($user->stripe_customer_id, [
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethodId
                    ]
                ]);
            }

            return $paymentMethod;
        } catch (Exception $e) {
            Log::error('Failed to add payment method', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Preview upcoming invoice
     */
    public function previewInvoice(Subscription $subscription, array $params = [])
    {
        try {
            return Invoice::upcoming([
                'customer' => $subscription->user->stripe_customer_id,
                'subscription' => $subscription->stripe_subscription_id,
                'subscription_items' => $params['subscription_items'] ?? null,
                'subscription_proration_date' => $params['proration_date'] ?? null,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to preview invoice', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Apply a coupon or promotion code
     */
    public function applyCoupon(Subscription $subscription, string $couponId)
    {
        try {
            $stripeSubscription = StripeSubscription::update($subscription->stripe_subscription_id, [
                'coupon' => $couponId,
            ]);

            Log::info('Coupon applied successfully', [
                'subscription_id' => $subscription->id,
                'coupon_id' => $couponId
            ]);

            return $stripeSubscription;
        } catch (Exception $e) {
            Log::error('Failed to apply coupon', [
                'subscription_id' => $subscription->id,
                'coupon_id' => $couponId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Set subscription tax rates
     */
    public function setTaxRates(Subscription $subscription, array $taxRateIds)
    {
        try {
            $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id);
            
            StripeSubscription::update($subscription->stripe_subscription_id, [
                'default_tax_rates' => $taxRateIds,
            ]);

            Log::info('Tax rates updated successfully', [
                'subscription_id' => $subscription->id,
                'tax_rates' => $taxRateIds
            ]);

            return $stripeSubscription;
        } catch (Exception $e) {
            Log::error('Failed to set tax rates', [
                'subscription_id' => $subscription->id,
                'tax_rates' => $taxRateIds,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get subscription usage history
     */
    public function getUsageHistory(Subscription $subscription)
    {
        try {
            $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id);
            $subscriptionItem = $stripeSubscription->items->data[0];

            return SubscriptionItem::allUsageRecordSummaries($subscriptionItem->id, [
                'limit' => 100,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get usage history', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Pause subscription
     */
    public function pauseSubscription(Subscription $subscription, string $resumeAt = null)
    {
        try {
            $stripeSubscription = StripeSubscription::update($subscription->stripe_subscription_id, [
                'pause_collection' => [
                    'behavior' => 'mark_uncollectible',
                    'resumes_at' => $resumeAt ? strtotime($resumeAt) : null,
                ],
            ]);

            $subscription->update([
                'stripe_status' => 'paused',
                'ends_at' => $resumeAt ? now()->createFromTimestamp(strtotime($resumeAt)) : null,
            ]);

            Log::info('Subscription paused successfully', [
                'subscription_id' => $subscription->id,
                'resumes_at' => $resumeAt
            ]);

            return $stripeSubscription;
        } catch (Exception $e) {
            Log::error('Failed to pause subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create a subscription with multiple items
     */
    public function createMultiItemSubscription(User $user, array $items, array $options = [])
    {
        try {
            // Ensure user has a Stripe customer ID
            if (!$user->stripe_customer_id) {
                $customer = Customer::create([
                    'email' => $user->email,
                    'metadata' => ['user_id' => $user->id]
                ]);
                $user->update(['stripe_customer_id' => $customer->id]);
            }

            // Format subscription items
            $subscriptionItems = array_map(function($item) {
                return [
                    'price' => $item['price_id'],
                    'quantity' => $item['quantity'] ?? 1,
                    'metadata' => $item['metadata'] ?? [],
                    'tax_rates' => $item['tax_rates'] ?? [],
                ];
            }, $items);

            // Create the subscription
            $subscription = StripeSubscription::create([
                'customer' => $user->stripe_customer_id,
                'items' => $subscriptionItems,
                'payment_behavior' => 'default_incomplete',
                'payment_settings' => ['save_default_payment_method' => 'on_subscription'],
                'expand' => ['latest_invoice.payment_intent'],
                'metadata' => array_merge([
                    'user_id' => $user->id
                ], $options['metadata'] ?? []),
                'trial_period_days' => $options['trial_days'] ?? null,
            ]);

            return [
                'subscriptionId' => $subscription->id,
                'clientSecret' => $subscription->latest_invoice->payment_intent->client_secret,
            ];
        } catch (Exception $e) {
            Log::error('Failed to create multi-item subscription', [
                'user_id' => $user->id,
                'items' => $items,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Add an item to an existing subscription
     */
    public function addSubscriptionItem(Subscription $subscription, string $priceId, array $options = [])
    {
        try {
            $subscriptionItem = SubscriptionItem::create([
                'subscription' => $subscription->stripe_subscription_id,
                'price' => $priceId,
                'quantity' => $options['quantity'] ?? 1,
                'metadata' => $options['metadata'] ?? [],
                'tax_rates' => $options['tax_rates'] ?? [],
            ]);

            Log::info('Subscription item added successfully', [
                'subscription_id' => $subscription->id,
                'item_id' => $subscriptionItem->id
            ]);

            return $subscriptionItem;
        } catch (Exception $e) {
            Log::error('Failed to add subscription item', [
                'subscription_id' => $subscription->id,
                'price_id' => $priceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create a volume-based price
     */
    public function createVolumePrice(array $tiers, string $currency = 'usd', array $options = [])
    {
        try {
            return Price::create([
                'unit_amount' => null, // Required for tiered pricing
                'currency' => $currency,
                'recurring' => [
                    'interval' => $options['interval'] ?? 'month',
                    'interval_count' => $options['interval_count'] ?? 1,
                ],
                'billing_scheme' => 'tiered',
                'tiers_mode' => 'volume',
                'tiers' => $tiers,
                'product' => $options['product_id'],
                'metadata' => $options['metadata'] ?? [],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create volume price', [
                'tiers' => $tiers,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create a graduated price (tiered pricing)
     */
    public function createGraduatedPrice(array $tiers, string $currency = 'usd', array $options = [])
    {
        try {
            return Price::create([
                'unit_amount' => null,
                'currency' => $currency,
                'recurring' => [
                    'interval' => $options['interval'] ?? 'month',
                    'interval_count' => $options['interval_count'] ?? 1,
                ],
                'billing_scheme' => 'tiered',
                'tiers_mode' => 'graduated',
                'tiers' => $tiers,
                'product' => $options['product_id'],
                'metadata' => $options['metadata'] ?? [],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create graduated price', [
                'tiers' => $tiers,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update quantities for multiple subscription items
     */
    public function updateItemQuantities(Subscription $subscription, array $items)
    {
        try {
            $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id);
            
            $updates = [];
            foreach ($items as $itemId => $quantity) {
                $updates[] = [
                    'id' => $itemId,
                    'quantity' => $quantity,
                ];
            }

            StripeSubscription::update($subscription->stripe_subscription_id, [
                'items' => $updates,
            ]);

            Log::info('Subscription item quantities updated', [
                'subscription_id' => $subscription->id,
                'updates' => $updates
            ]);

            return $stripeSubscription->fresh();
        } catch (Exception $e) {
            Log::error('Failed to update item quantities', [
                'subscription_id' => $subscription->id,
                'items' => $items,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate price based on usage tiers
     */
    public function calculateTieredPrice(array $tiers, int $quantity)
    {
        $total = 0;
        $remainingQuantity = $quantity;

        foreach ($tiers as $tier) {
            $tierQuantity = 0;
            
            if ($tier['up_to'] === null || $remainingQuantity <= ($tier['up_to'] - ($tier['up_to'] === null ? 0 : $tier['flat_amount']))) {
                $tierQuantity = $remainingQuantity;
                $remainingQuantity = 0;
            } else {
                $tierQuantity = $tier['up_to'] - ($tier['up_to'] === null ? 0 : $tier['flat_amount']);
                $remainingQuantity -= $tierQuantity;
            }

            if ($tier['flat_amount']) {
                $total += $tier['flat_amount'];
            } else {
                $total += $tierQuantity * $tier['unit_amount'];
            }

            if ($remainingQuantity <= 0) {
                break;
            }
        }

        return $total;
    }

    /**
     * Get subscription item usage details
     */
    public function getItemUsageDetails(string $subscriptionItemId)
    {
        try {
            return SubscriptionItem::retrieve([
                'id' => $subscriptionItemId,
                'expand' => ['tiers']
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get subscription item usage details', [
                'subscription_item_id' => $subscriptionItemId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Add an addon to a subscription
     */
    public function addAddon(Subscription $subscription, array $addonData)
    {
        try {
            // Create or retrieve the price for the addon
            $price = isset($addonData['stripe_price_id']) 
                ? Price::retrieve($addonData['stripe_price_id'])
                : Price::create([
                    'unit_amount' => $addonData['amount'] * 100, // Convert to cents
                    'currency' => $addonData['currency'],
                    'recurring' => [
                        'interval' => $addonData['interval'] ?? 'month',
                    ],
                    'product_data' => [
                        'name' => $addonData['name'],
                        'metadata' => $addonData['metadata'] ?? [],
                    ],
                ]);

            // Add the addon as a new subscription item
            $subscriptionItem = $this->addSubscriptionItem($subscription, $price->id, [
                'quantity' => $addonData['quantity'] ?? 1,
                'metadata' => [
                    'type' => 'addon',
                    'name' => $addonData['name'],
                ],
            ]);

            // Create subscription modifier record
            $modifier = SubscriptionModifier::create([
                'subscription_id' => $subscription->id,
                'type' => 'addon',
                'stripe_price_id' => $price->id,
                'name' => $addonData['name'],
                'description' => $addonData['description'] ?? null,
                'amount' => $addonData['amount'],
                'currency' => $addonData['currency'],
                'billing_type' => 'recurring',
                'status' => 'active',
                'starts_at' => now(),
                'metadata' => $addonData['metadata'] ?? null,
            ]);

            Log::info('Addon added successfully', [
                'subscription_id' => $subscription->id,
                'modifier_id' => $modifier->id
            ]);

            return $modifier;
        } catch (Exception $e) {
            Log::error('Failed to add addon', [
                'subscription_id' => $subscription->id,
                'addon_data' => $addonData,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Apply a discount to a subscription
     */
    public function applyDiscount(Subscription $subscription, array $discountData)
    {
        try {
            $coupon = null;

            // Create or retrieve the coupon
            if (isset($discountData['stripe_coupon_id'])) {
                $coupon = Coupon::retrieve($discountData['stripe_coupon_id']);
            } else {
                $coupon = Coupon::create([
                    'name' => $discountData['name'],
                    'amount_off' => $discountData['amount'] * 100, // Convert to cents
                    'currency' => $discountData['currency'],
                    'duration' => $discountData['duration'] ?? 'forever',
                    'duration_in_months' => $discountData['duration_months'] ?? null,
                    'max_redemptions' => $discountData['max_redemptions'] ?? null,
                    'metadata' => $discountData['metadata'] ?? [],
                ]);
            }

            // Apply the coupon to the subscription
            $stripeSubscription = StripeSubscription::update($subscription->stripe_subscription_id, [
                'coupon' => $coupon->id,
            ]);

            // Create subscription modifier record
            $modifier = SubscriptionModifier::create([
                'subscription_id' => $subscription->id,
                'type' => 'discount',
                'stripe_coupon_id' => $coupon->id,
                'name' => $discountData['name'],
                'description' => $discountData['description'] ?? null,
                'amount' => $discountData['amount'],
                'currency' => $discountData['currency'],
                'billing_type' => $discountData['duration'] === 'once' ? 'one_time' : 'recurring',
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => $discountData['duration'] === 'repeating' 
                    ? now()->addMonths($discountData['duration_months']) 
                    : null,
                'metadata' => $discountData['metadata'] ?? null,
            ]);

            Log::info('Discount applied successfully', [
                'subscription_id' => $subscription->id,
                'modifier_id' => $modifier->id
            ]);

            return $modifier;
        } catch (Exception $e) {
            Log::error('Failed to apply discount', [
                'subscription_id' => $subscription->id,
                'discount_data' => $discountData,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create and apply a promotion code
     */
    public function createPromotionCode(array $promoData)
    {
        try {
            // Create or retrieve the coupon
            $coupon = isset($promoData['stripe_coupon_id'])
                ? Coupon::retrieve($promoData['stripe_coupon_id'])
                : Coupon::create([
                    'name' => $promoData['name'],
                    'percent_off' => $promoData['percent_off'],
                    'duration' => $promoData['duration'] ?? 'once',
                    'duration_in_months' => $promoData['duration_months'] ?? null,
                    'max_redemptions' => $promoData['max_redemptions'] ?? null,
                    'metadata' => $promoData['metadata'] ?? [],
                ]);

            // Create the promotion code
            $promotionCode = PromotionCode::create([
                'coupon' => $coupon->id,
                'code' => $promoData['code'],
                'max_redemptions' => $promoData['max_redemptions'] ?? null,
                'expires_at' => isset($promoData['expires_at']) 
                    ? strtotime($promoData['expires_at']) 
                    : null,
                'metadata' => $promoData['metadata'] ?? [],
            ]);

            Log::info('Promotion code created successfully', [
                'promotion_code' => $promotionCode->code,
                'coupon_id' => $coupon->id
            ]);

            return $promotionCode;
        } catch (Exception $e) {
            Log::error('Failed to create promotion code', [
                'promo_data' => $promoData,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remove a subscription modifier
     */
    public function removeModifier(SubscriptionModifier $modifier)
    {
        try {
            $subscription = $modifier->subscription;

            if ($modifier->type === 'addon') {
                // Remove the subscription item
                $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id);
                foreach ($stripeSubscription->items->data as $item) {
                    if ($item->price->id === $modifier->stripe_price_id) {
                        $item->delete();
                        break;
                    }
                }
            } elseif ($modifier->type === 'discount') {
                // Remove the coupon from the subscription
                StripeSubscription::update($subscription->stripe_subscription_id, [
                    'coupon' => null,
                ]);
            }

            // Update the modifier status
            $modifier->update([
                'status' => 'removed',
                'ends_at' => now(),
            ]);

            Log::info('Modifier removed successfully', [
                'subscription_id' => $subscription->id,
                'modifier_id' => $modifier->id
            ]);

            return $modifier;
        } catch (Exception $e) {
            Log::error('Failed to remove modifier', [
                'modifier_id' => $modifier->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get active modifiers for a subscription
     */
    public function getActiveModifiers(Subscription $subscription)
    {
        return $subscription->modifiers()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->get();
    }

    /**
     * Check if a modifier can be applied based on rules
     */
    private function validateModifierRules(SubscriptionModifier $modifier, Subscription $subscription, array $context = []): bool
    {
        // Get all active modifiers on the subscription
        $activeModifiers = $this->getActiveModifiers($subscription);
        
        // Build context for rule evaluation
        $evaluationContext = array_merge([
            'subscription' => [
                'id' => $subscription->id,
                'plan' => $subscription->plan,
                'tier' => $subscription->tier,
                'status' => $subscription->status,
            ],
            'active_modifiers' => $activeModifiers->map(function ($mod) {
                return [
                    'id' => $mod->id,
                    'type' => $mod->type,
                    'amount' => $mod->amount,
                ];
            })->toArray(),
            'modifier' => [
                'type' => $modifier->type,
                'amount' => $modifier->amount,
            ],
        ], $context);

        // Check stacking rules
        $stackingRules = $modifier->rules()->where('rule_type', 'stacking')->get();
        foreach ($stackingRules as $rule) {
            if (!$rule->evaluate($evaluationContext)) {
                Log::warning('Modifier stacking rule validation failed', [
                    'modifier_id' => $modifier->id,
                    'rule_id' => $rule->id,
                    'context' => $evaluationContext
                ]);
                return false;
            }
        }

        // Check exclusion rules
        $exclusionRules = $modifier->rules()->where('rule_type', 'exclusion')->get();
        foreach ($exclusionRules as $rule) {
            if (!$rule->evaluate($evaluationContext)) {
                Log::warning('Modifier exclusion rule validation failed', [
                    'modifier_id' => $modifier->id,
                    'rule_id' => $rule->id,
                    'context' => $evaluationContext
                ]);
                return false;
            }
        }

        // Check dependency rules
        $dependencyRules = $modifier->rules()->where('rule_type', 'dependency')->get();
        foreach ($dependencyRules as $rule) {
            if (!$rule->evaluate($evaluationContext)) {
                Log::warning('Modifier dependency rule validation failed', [
                    'modifier_id' => $modifier->id,
                    'rule_id' => $rule->id,
                    'context' => $evaluationContext
                ]);
                return false;
            }
        }

        // Check limit rules
        $limitRules = $modifier->rules()->where('rule_type', 'limit')->get();
        foreach ($limitRules as $rule) {
            if (!$rule->evaluate($evaluationContext)) {
                Log::warning('Modifier limit rule validation failed', [
                    'modifier_id' => $modifier->id,
                    'rule_id' => $rule->id,
                    'context' => $evaluationContext
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Add rules to a modifier
     */
    public function addModifierRule(SubscriptionModifier $modifier, array $ruleData): ModifierRule
    {
        try {
            $rule = ModifierRule::create([
                'modifier_id' => $modifier->id,
                'rule_type' => $ruleData['rule_type'],
                'target_type' => $ruleData['target_type'],
                'target_id' => $ruleData['target_id'],
                'condition' => $ruleData['condition'] ?? null,
                'priority' => $ruleData['priority'] ?? 0,
                'action' => $ruleData['action'],
                'metadata' => $ruleData['metadata'] ?? null,
            ]);

            Log::info('Modifier rule added successfully', [
                'modifier_id' => $modifier->id,
                'rule_id' => $rule->id,
                'rule_type' => $rule->rule_type
            ]);

            return $rule;
        } catch (Exception $e) {
            Log::error('Failed to add modifier rule', [
                'modifier_id' => $modifier->id,
                'rule_data' => $ruleData,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate total discount amount considering stacking rules
     */
    public function calculateStackedDiscounts(Subscription $subscription): float
    {
        $activeDiscounts = $this->getActiveModifiers($subscription)
            ->where('type', 'discount')
            ->sortByDesc(function ($modifier) {
                return $modifier->rules()
                    ->where('rule_type', 'stacking')
                    ->max('priority') ?? 0;
            });

        $totalDiscount = 0;
        $appliedDiscounts = [];

        foreach ($activeDiscounts as $discount) {
            // Check if this discount can be stacked with already applied discounts
            $context = [
                'applied_discounts' => $appliedDiscounts,
                'total_discount' => $totalDiscount
            ];

            if ($this->validateModifierRules($discount, $subscription, $context)) {
                $totalDiscount += $discount->amount;
                $appliedDiscounts[] = [
                    'id' => $discount->id,
                    'amount' => $discount->amount
                ];
            }
        }

        return $totalDiscount;
    }

    /**
     * Add a time-based rule to a modifier
     */
    public function addTimeBasedRule(SubscriptionModifier $modifier, array $ruleData): TimeBasedRule
    {
        try {
            // Set timezone default if not provided
            $ruleData['timezone'] = $ruleData['timezone'] ?? config('app.timezone');

            // Create the time-based rule
            $rule = TimeBasedRule::create(array_merge([
                'modifier_id' => $modifier->id,
                'status' => 'active'
            ], $ruleData));

            Log::info('Time-based rule added successfully', [
                'modifier_id' => $modifier->id,
                'rule_id' => $rule->id,
                'rule_type' => $rule->rule_type
            ]);

            return $rule;
        } catch (Exception $e) {
            Log::error('Failed to add time-based rule', [
                'modifier_id' => $modifier->id,
                'rule_data' => $ruleData,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate price with time-based rules applied
     */
    public function calculateTimeAdjustedPrice(SubscriptionModifier $modifier, float $basePrice): float
    {
        $adjustedPrice = $basePrice;
        
        // Get active time-based rules sorted by priority
        $timeRules = $modifier->timeBasedRules()
            ->where('status', 'active')
            ->orderByDesc('priority')
            ->get();

        foreach ($timeRules as $rule) {
            if ($rule->isActiveNow()) {
                $adjustedPrice = $rule->calculateAdjustment($adjustedPrice);
            }
        }

        return $adjustedPrice;
    }

    /**
     * Get currently active time-based rules for a modifier
     */
    public function getActiveTimeRules(SubscriptionModifier $modifier): Collection
    {
        return $modifier->timeBasedRules()
            ->where('status', 'active')
            ->get()
            ->filter(function ($rule) {
                return $rule->isActiveNow();
            });
    }

    /**
     * Update prices based on time rules
     */
    public function updateTimeBasedPrices(): void
    {
        try {
            $activeModifiers = SubscriptionModifier::with('timeBasedRules')
                ->whereHas('timeBasedRules', function ($query) {
                    $query->where('status', 'active');
                })
                ->get();

            foreach ($activeModifiers as $modifier) {
                $basePrice = $modifier->amount;
                $adjustedPrice = $this->calculateTimeAdjustedPrice($modifier, $basePrice);

                if ($basePrice !== $adjustedPrice) {
                    // Update Stripe price if needed
                    if ($modifier->stripe_price_id) {
                        $this->updateStripePrice($modifier, $adjustedPrice);
                    }

                    // Log price change
                    Log::info('Time-based price adjustment applied', [
                        'modifier_id' => $modifier->id,
                        'base_price' => $basePrice,
                        'adjusted_price' => $adjustedPrice,
                        'active_rules' => $this->getActiveTimeRules($modifier)
                            ->pluck('name')
                            ->toArray()
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to update time-based prices', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Schedule recurring time-based price updates
     */
    public function scheduleTimeBasedPriceUpdates(): void
    {
        // This method would be called from a scheduled task
        // to regularly update prices based on time rules
        $this->updateTimeBasedPrices();
    }

    /**
     * Add a holiday rule to a modifier
     */
    public function addHolidayRule(SubscriptionModifier $modifier, array $ruleData): HolidayRule
    {
        try {
            $rule = HolidayRule::create(array_merge([
                'modifier_id' => $modifier->id,
                'status' => 'active'
            ], $ruleData));

            Log::info('Holiday rule added successfully', [
                'modifier_id' => $modifier->id,
                'rule_id' => $rule->id,
                'country_code' => $rule->country_code
            ]);

            return $rule;
        } catch (Exception $e) {
            Log::error('Failed to add holiday rule', [
                'modifier_id' => $modifier->id,
                'rule_data' => $ruleData,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate price with holiday rules applied
     */
    public function calculateHolidayAdjustedPrice(
        SubscriptionModifier $modifier, 
        float $basePrice, 
        HolidayService $holidayService
    ): float {
        $adjustedPrice = $basePrice;
        
        // Get active holiday rules sorted by priority
        $holidayRules = $modifier->holidayRules()
            ->where('status', 'active')
            ->orderByDesc('priority')
            ->get();

        // Group rules by country
        $rulesByCountry = $holidayRules->groupBy('country_code');

        foreach ($rulesByCountry as $countryCode => $rules) {
            // Get holidays for this country
            $holidays = $holidayService->getHolidays($countryCode);

            // Apply rules that are active for current holidays
            foreach ($rules as $rule) {
                if ($rule->isHolidayPeriod($holidays)) {
                    $adjustedPrice = $rule->calculateAdjustment($adjustedPrice);
                }
            }
        }

        return $adjustedPrice;
    }

    /**
     * Get active holiday rules
     */
    public function getActiveHolidayRules(
        SubscriptionModifier $modifier, 
        HolidayService $holidayService
    ): array {
        $activeRules = [];
        $holidayRules = $modifier->holidayRules()
            ->where('status', 'active')
            ->get();

        // Group rules by country
        $rulesByCountry = $holidayRules->groupBy('country_code');

        foreach ($rulesByCountry as $countryCode => $rules) {
            $holidays = $holidayService->getHolidays($countryCode);
            
            foreach ($rules as $rule) {
                if ($rule->isHolidayPeriod($holidays)) {
                    $activeRules[] = [
                        'rule' => $rule,
                        'holidays' => array_filter($holidays, function ($holiday) use ($rule) {
                            return !$rule->excluded_holidays || 
                                   !in_array($holiday['name'], $rule->excluded_holidays);
                        })
                    ];
                }
            }
        }

        return $activeRules;
    }
}

class SubscriptionServiceNew
{
    private const CACHE_PREFIX = 'subscription:';
    private const USAGE_CACHE_TTL = 3600; // 1 hour

    /**
     * Get current subscription
     */
    public function getCurrentSubscription()
    {
        $user = auth()->user();
        return $user ? $user->subscription : null;
    }

    /**
     * Get current usage statistics
     */
    public function getCurrentUsage()
    {
        $user = auth()->user();
        if (!$user) return null;

        $cacheKey = self::CACHE_PREFIX . 'usage:' . $user->id;
        
        return Cache::remember($cacheKey, self::USAGE_CACHE_TTL, function () use ($user) {
            return [
                'requests' => [
                    'used' => $this->getRequestCount($user),
                    'limit' => $this->getCurrentLimits()['requests_per_day']
                ],
                'tokens' => [
                    'used' => $this->getTokenCount($user),
                    'limit' => $this->getCurrentLimits()['tokens_per_request']
                ],
                'snippets' => [
                    'used' => $this->getSnippetCount($user),
                    'limit' => $this->getCurrentLimits()['saved_snippets']
                ]
            ];
        });
    }

    /**
     * Get active features for current subscription
     */
    public function getActiveFeatures()
    {
        $subscription = $this->getCurrentSubscription();
        $plan = $subscription ? $subscription->plan : 'free';
        return config("subscription.plans.{$plan}.features", []);
    }

    /**
     * Get current subscription limits
     */
    public function getCurrentLimits()
    {
        $subscription = $this->getCurrentSubscription();
        $plan = $subscription ? $subscription->plan : 'free';
        return config("subscription.plans.{$plan}.limits", []);
    }

    /**
     * Change subscription plan
     */
    public function changePlan(string $newPlan)
    {
        $user = auth()->user();
        $currentPlan = $this->getCurrentSubscription()?->plan ?? 'free';
        $planConfig = config("subscription.plans.{$newPlan}");

        if (!$planConfig) {
            throw new \InvalidArgumentException("Invalid plan selected");
        }

        // Free plan change
        if ($newPlan === 'free') {
            if ($user->subscription) {
                $user->subscription->cancel();
            }
            return ['requires_payment' => false];
        }

        // Determine if payment is required
        $requiresPayment = $planConfig['price'] > 0 && 
            (!$user->subscription || $currentPlan === 'free');

        return [
            'requires_payment' => $requiresPayment,
            'price' => $planConfig['price']
        ];
    }

    /**
     * Create payment intent for subscription
     */
    public function createPaymentIntent(float $amount)
    {
        $user = auth()->user();
        return $user->createSetupIntent();
    }

    /**
     * Process subscription payment and activation
     */
    public function processSubscription(string $plan, string $paymentMethod)
    {
        $user = auth()->user();
        $planConfig = config("subscription.plans.{$plan}");

        if (!$planConfig) {
            throw new \InvalidArgumentException("Invalid plan selected");
        }

        try {
            // Handle new subscription
            if (!$user->subscription) {
                $user->newSubscription('default', $plan)
                    ->create($paymentMethod);
            } 
            // Handle plan change
            else {
                $user->subscription->swap($plan);
            }

            $this->clearUsageCache($user);
            return true;

        } catch (IncompletePayment $e) {
            throw new \Exception("Payment requires additional confirmation");
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription()
    {
        $subscription = $this->getCurrentSubscription();
        
        if ($subscription) {
            $subscription->cancel();
            $this->clearUsageCache(auth()->user());
        }
    }

    /**
     * Resume cancelled subscription
     */
    public function resumeSubscription()
    {
        $subscription = $this->getCurrentSubscription();
        
        if ($subscription && $subscription->cancelled()) {
            $subscription->resume();
            $this->clearUsageCache(auth()->user());
        }
    }

    /**
     * Get usage history
     */
    public function getUsageHistory()
    {
        $user = auth()->user();
        return $user->usageLogs()
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get();
    }

    /**
     * Get billing history
     */
    public function getBillingHistory()
    {
        $user = auth()->user();
        return $user->invoices();
    }

    /**
     * Check if user can use a specific feature
     */
    public function canUseFeature(string $feature): bool
    {
        $features = $this->getActiveFeatures();
        return isset($features[$feature]) && $features[$feature];
    }

    /**
     * Check if user has reached their usage limit
     */
    public function hasReachedLimit(string $limitType): bool
    {
        $usage = $this->getCurrentUsage();
        return $usage[$limitType]['used'] >= $usage[$limitType]['limit'];
    }

    /**
     * Record usage
     */
    public function recordUsage(string $type, int $amount = 1)
    {
        $user = auth()->user();
        
        if (!$user) return;

        $user->usageLogs()->create([
            'type' => $type,
            'amount' => $amount
        ]);

        $this->clearUsageCache($user);
    }

    /**
     * Clear usage cache
     */
    private function clearUsageCache(User $user)
    {
        Cache::forget(self::CACHE_PREFIX . 'usage:' . $user->id);
    }

    /**
     * Get request count for today
     */
    private function getRequestCount(User $user): int
    {
        return $user->usageLogs()
            ->where('type', 'request')
            ->where('created_at', '>=', now()->startOfDay())
            ->sum('amount');
    }

    /**
     * Get token count for current request
     */
    private function getTokenCount(User $user): int
    {
        return $user->usageLogs()
            ->where('type', 'token')
            ->where('created_at', '>=', now()->startOfDay())
            ->sum('amount');
    }

    /**
     * Get saved snippet count
     */
    private function getSnippetCount(User $user): int
    {
        return $user->snippets()->count();
    }
}
