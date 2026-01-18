<?php

namespace App\Services;

use App\Models\BogoOffer;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;

class BogoService
{
    /**
     * Calculate BOGO items for a cart.
     * Returns array of free/discounted items to add.
     *
     * @param array $cartItems Array of ['product_id' => int, 'quantity' => int]
     * @return array Array of BOGO items to add
     */
    public function calculateBogoForCart(array $cartItems): array
    {
        $bogoItems = [];
        
        // Get all active BOGO offers ordered by priority
        $activeOffers = BogoOffer::active()
            ->with(['buyProduct', 'getProduct'])
            ->orderBy('priority', 'desc')
            ->get();

        if ($activeOffers->isEmpty()) {
            return $bogoItems;
        }

        // Build a map of product_id => quantity from cart
        $cartMap = [];
        foreach ($cartItems as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'];
            
            if (isset($cartMap[$productId])) {
                $cartMap[$productId] += $quantity;
            } else {
                $cartMap[$productId] = $quantity;
            }
        }

        // Track which offers have been applied to avoid duplicates
        $appliedOffers = [];

        foreach ($activeOffers as $offer) {
            // Check if cart has the required buy product
            if (!isset($cartMap[$offer->buy_product_id])) {
                continue;
            }

            $cartQuantity = $cartMap[$offer->buy_product_id];
            $freeItemsCount = $offer->calculateFreeItems($cartQuantity);

            if ($freeItemsCount <= 0) {
                continue;
            }

            // Get the product to give for free/discounted
            $getProduct = $offer->getProduct;
            if (!$getProduct || !$getProduct->is_available) {
                continue;
            }

            // Check stock for free items (if product tracks inventory)
            if ($getProduct->has_inventory && $getProduct->stock_quantity !== null) {
                // Don't give more free items than available stock
                // But also consider if the same product is already in cart
                $alreadyInCart = $cartMap[$offer->get_product_id] ?? 0;
                $availableForFree = max(0, $getProduct->stock_quantity - $alreadyInCart);
                $freeItemsCount = min($freeItemsCount, $availableForFree);
            }

            if ($freeItemsCount <= 0) {
                continue;
            }

            // Calculate final price for the free/discounted item
            $originalPrice = $getProduct->discounted_price ?? $getProduct->price;
            $finalPrice = $offer->calculateFinalPrice((float) $originalPrice);

            $bogoItems[] = [
                'offer_id' => $offer->id,
                'offer_name' => $offer->name,
                'product_id' => $offer->get_product_id,
                'product' => $getProduct,
                'quantity' => $freeItemsCount,
                'original_price' => (float) $originalPrice,
                'final_price' => $finalPrice,
                'discount_type' => $offer->get_discount_type,
                'discount_value' => $offer->get_discount_value,
                'is_free' => $offer->get_discount_type === 'free',
                'buy_product_id' => $offer->buy_product_id,
                'buy_quantity_required' => $offer->buy_quantity,
            ];

            $appliedOffers[] = $offer->id;
        }

        return $bogoItems;
    }

    /**
     * Apply BOGO items to an order during checkout.
     *
     * @param Order $order The order to add BOGO items to
     * @param array $bogoItems Array from calculateBogoForCart
     * @return float Total discount amount from BOGO
     */
    public function applyBogoToOrder(Order $order, array $bogoItems): float
    {
        $totalBogoDiscount = 0;

        foreach ($bogoItems as $bogoItem) {
            $product = $bogoItem['product'];
            
            // Create order item for the BOGO product
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $bogoItem['product_id'],
                'product_price' => $bogoItem['final_price'],
                'quantity' => $bogoItem['quantity'],
                'product_snapshot' => [
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'description' => $product->description,
                    'short_description' => $product->short_description,
                    'price' => $product->price,
                    'discounted_price' => $bogoItem['final_price'],
                    'has_discount' => true,
                    'discount_percentage' => $bogoItem['is_free'] ? 100 : $bogoItem['discount_value'],
                    'currency' => $product->currency,
                    'images' => $product->images,
                    'meta' => $product->meta,
                    'category' => $product->category?->name,
                    'is_bogo' => true,
                    'bogo_offer_name' => $bogoItem['offer_name'],
                ],
                'is_bogo_item' => true,
                'bogo_offer_id' => $bogoItem['offer_id'],
            ]);

            // Calculate discount amount (original price - final price) * quantity
            $discountPerItem = $bogoItem['original_price'] - $bogoItem['final_price'];
            $totalBogoDiscount += $discountPerItem * $bogoItem['quantity'];

            // Decrement stock for free items
            if ($product->has_inventory) {
                $product->decreaseStock($bogoItem['quantity'], $order->id, null, "BOGO: {$bogoItem['offer_name']}");
            }

            // Increment offer usage count
            $offer = BogoOffer::find($bogoItem['offer_id']);
            if ($offer) {
                // Increment by number of times offer was triggered
                $timesTriggered = ceil($bogoItem['quantity'] / $offer->get_quantity);
                $offer->incrementUsage($timesTriggered);
            }
        }

        return round($totalBogoDiscount, 3);
    }

    /**
     * Get BOGO offer info for a specific product (for frontend display).
     *
     * @param int $productId
     * @return array|null
     */
    public function getBogoOfferForProduct(int $productId): ?array
    {
        $offer = BogoOffer::active()
            ->where('buy_product_id', $productId)
            ->with(['buyProduct', 'getProduct'])
            ->orderBy('priority', 'desc')
            ->first();

        if (!$offer) {
            return null;
        }

        return [
            'id' => $offer->id,
            'name' => $offer->name,
            'description' => $offer->description,
            'buy_quantity' => $offer->buy_quantity,
            'get_quantity' => $offer->get_quantity,
            'get_product_id' => $offer->get_product_id,
            'get_product_title' => $offer->getProduct?->title,
            'get_product_image' => $offer->getProduct?->images[0] ?? null,
            'is_same_product' => $offer->buy_product_id === $offer->get_product_id,
            'discount_type' => $offer->get_discount_type,
            'discount_value' => $offer->get_discount_value,
            'formatted_offer' => $offer->formatted_offer,
        ];
    }

    /**
     * Check if a product has an active BOGO offer.
     *
     * @param int $productId
     * @return bool
     */
    public function productHasBogoOffer(int $productId): bool
    {
        return BogoOffer::active()
            ->where('buy_product_id', $productId)
            ->exists();
    }

    /**
     * Get all active BOGO offers.
     *
     * @return Collection
     */
    public function getActiveOffers(): Collection
    {
        return BogoOffer::active()
            ->with(['buyProduct', 'getProduct'])
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Validate cart items have enough quantity for BOGO.
     *
     * @param array $cartItems
     * @return array Validation result
     */
    public function validateCartForBogo(array $cartItems): array
    {
        $bogoItems = $this->calculateBogoForCart($cartItems);
        
        return [
            'has_bogo' => !empty($bogoItems),
            'bogo_items' => $bogoItems,
            'total_free_items' => array_reduce($bogoItems, fn($sum, $item) => $sum + $item['quantity'], 0),
            'total_bogo_savings' => array_reduce($bogoItems, fn($sum, $item) => 
                $sum + (($item['original_price'] - $item['final_price']) * $item['quantity']), 0
            ),
        ];
    }
}
