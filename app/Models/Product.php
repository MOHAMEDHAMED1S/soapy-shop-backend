<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'short_description',
        'price',
        'weight_grams',
        'currency',
        'is_available',
        'has_inventory',
        'stock_quantity',
        'low_stock_threshold',
        'stock_last_updated_at',
        'category_id',
        'images',
        'meta',
    ];

    protected $casts = [
        'images' => 'array',
        'meta' => 'array',
        'is_available' => 'boolean',
        'has_inventory' => 'boolean',
        'stock_quantity' => 'integer',
        'weight_grams' => 'integer',
        'low_stock_threshold' => 'integer',
        'stock_last_updated_at' => 'datetime',
        'price' => 'decimal:3',
    ];

    protected $appends = [
        'has_discount',
        'discount_percentage',
        'discounted_price',
        'price_before_discount',
        'is_in_stock',
        'is_low_stock',
        'has_bogo_offer',
        'bogo_offer_info',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the order items for the product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the discounts associated with this product.
     */
    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(ProductDiscount::class, 'product_discount_products', 'product_id', 'product_discount_id')
            ->withTimestamps();
    }

    /**
     * Get the inventory transactions for the product.
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Get the active discount for this product (highest priority).
     */
    public function getActiveDiscountAttribute(): ?ProductDiscount
    {
        // Get all active discounts that apply to all products
        $allProductsDiscounts = ProductDiscount::active()
            ->forAllProducts()
            ->orderBy('priority', 'desc')
            ->get();

        // Get all active discounts that specifically include this product
        $specificDiscounts = ProductDiscount::active()
            ->forSpecificProducts()
            ->whereHas('products', function ($query) {
                $query->where('product_id', $this->id);
            })
            ->orderBy('priority', 'desc')
            ->get();

        // Merge and get the highest priority discount
        $allDiscounts = $allProductsDiscounts->merge($specificDiscounts)
            ->sortByDesc('priority');

        return $allDiscounts->first();
    }

    /**
     * Check if product has an active discount.
     */
    public function getHasDiscountAttribute(): bool
    {
        return $this->active_discount !== null;
    }

    /**
     * Get discount percentage (if applicable).
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        $discount = $this->active_discount;
        
        if (!$discount) {
            return null;
        }

        if ($discount->discount_type === 'percentage') {
            return (float) $discount->discount_value;
        }

        // Calculate percentage for fixed discount
        if ($this->price > 0) {
            return round(($discount->discount_value / $this->price) * 100, 2);
        }

        return null;
    }

    /**
     * Get the discounted price.
     */
    public function getDiscountedPriceAttribute(): float
    {
        $discount = $this->active_discount;
        
        if (!$discount) {
            return (float) $this->price;
        }

        return $discount->calculateDiscountedPrice((float) $this->price);
    }

    /**
     * Get the price before discount (original price).
     */
    public function getPriceBeforeDiscountAttribute(): float
    {
        return (float) $this->price;
    }

    /**
     * Get the discount amount.
     */
    public function getDiscountAmountAttribute(): float
    {
        $discount = $this->active_discount;
        
        if (!$discount) {
            return 0;
        }

        return $discount->calculateDiscountAmount((float) $this->price);
    }

    /**
     * Check if product is in stock.
     * Returns true if product doesn't track inventory OR if it has stock.
     */
    public function getIsInStockAttribute(): bool
    {
        // If product doesn't track inventory, it's always in stock
        if (!$this->has_inventory) {
            return true;
        }

        // If it tracks inventory, check stock quantity
        return $this->stock_quantity > 0;
    }

    /**
     * Check if product stock is low.
     */
    public function getIsLowStockAttribute(): bool
    {
        // Only applicable if product tracks inventory
        if (!$this->has_inventory) {
            return false;
        }

        return $this->stock_quantity <= $this->low_stock_threshold && $this->stock_quantity > 0;
    }

    /**
     * Check if product can be ordered with given quantity.
     */
    public function canOrder(int $quantity = 1): bool
    {
        // If product doesn't track inventory, it can always be ordered
        if (!$this->has_inventory) {
            return true;
        }

        // If it tracks inventory, check available stock
        return $this->stock_quantity >= $quantity;
    }

    /**
     * Decrease stock quantity.
     */
    public function decreaseStock(int $quantity, ?int $orderId = null, ?int $userId = null, string $notes = null): bool
    {
        // Only decrease if product tracks inventory
        if (!$this->has_inventory) {
            return true;
        }

        // Check if enough stock
        if ($this->stock_quantity < $quantity) {
            return false;
        }

        $quantityBefore = $this->stock_quantity;
        $this->stock_quantity -= $quantity;
        $this->stock_last_updated_at = now();
        $this->save();

        // Record transaction
        InventoryTransaction::create([
            'product_id' => $this->id,
            'type' => 'decrease',
            'quantity' => -$quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->stock_quantity,
            'reason' => 'sale',
            'notes' => $notes,
            'order_id' => $orderId,
            'user_id' => $userId,
        ]);

        return true;
    }

    /**
     * Increase stock quantity.
     */
    public function increaseStock(int $quantity, string $reason = 'adjustment', ?int $userId = null, string $notes = null): bool
    {
        // Only increase if product tracks inventory
        if (!$this->has_inventory) {
            return true;
        }

        $quantityBefore = $this->stock_quantity ?? 0;
        $this->stock_quantity = $quantityBefore + $quantity;
        $this->stock_last_updated_at = now();
        $this->save();

        // Record transaction
        InventoryTransaction::create([
            'product_id' => $this->id,
            'type' => 'increase',
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->stock_quantity,
            'reason' => $reason,
            'notes' => $notes,
            'user_id' => $userId,
        ]);

        return true;
    }

    /**
     * Set stock quantity (adjustment).
     */
    public function setStock(int $quantity, ?int $userId = null, string $notes = null): bool
    {
        // Only set if product tracks inventory
        if (!$this->has_inventory) {
            return true;
        }

        $quantityBefore = $this->stock_quantity ?? 0;
        $difference = $quantity - $quantityBefore;

        $this->stock_quantity = $quantity;
        $this->stock_last_updated_at = now();
        $this->save();

        // Record transaction
        InventoryTransaction::create([
            'product_id' => $this->id,
            'type' => $difference >= 0 ? 'increase' : 'decrease',
            'quantity' => $difference,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->stock_quantity,
            'reason' => 'adjustment',
            'notes' => $notes,
            'user_id' => $userId,
        ]);

        return true;
    }

    /**
     * Scope for products with low stock.
     */
    public function scopeLowStock($query)
    {
        return $query->where('has_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0);
    }

    /**
     * Scope for products out of stock.
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('has_inventory', true)
            ->where('stock_quantity', '<=', 0);
    }

    /**
     * Scope for products in stock.
     */
    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('has_inventory', false)
              ->orWhere('stock_quantity', '>', 0);
        });
    }

    /**
     * Get BOGO offers where this product is the "buy" product.
     */
    public function bogoOffersAsBuy(): HasMany
    {
        return $this->hasMany(BogoOffer::class, 'buy_product_id');
    }

    /**
     * Get BOGO offers where this product is the "get" product.
     */
    public function bogoOffersAsGet(): HasMany
    {
        return $this->hasMany(BogoOffer::class, 'get_product_id');
    }

    /**
     * Check if product has an active BOGO offer.
     */
    public function getHasBogoOfferAttribute(): bool
    {
        return BogoOffer::active()
            ->where('buy_product_id', $this->id)
            ->exists();
    }

    /**
     * Get BOGO offer info for display.
     */
    public function getBogoOfferInfoAttribute(): ?array
    {
        $offer = BogoOffer::active()
            ->where('buy_product_id', $this->id)
            ->with('getProduct')
            ->orderBy('priority', 'desc')
            ->first();

        if (!$offer) {
            return null;
        }

        return [
            'id' => $offer->id,
            'name' => $offer->name,
            'buy_quantity' => $offer->buy_quantity,
            'get_quantity' => $offer->get_quantity,
            'get_product_id' => $offer->get_product_id,
            'get_product_title' => $offer->getProduct?->title,
            'is_same_product' => $offer->buy_product_id === $offer->get_product_id,
            'discount_type' => $offer->get_discount_type,
            'formatted_offer' => $offer->formatted_offer,
        ];
    }
}
