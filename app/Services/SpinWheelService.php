<?php

namespace App\Services;

use App\Models\SpinWheelItem;
use App\Models\SpinWheelResult;
use Illuminate\Support\Facades\Log;

class SpinWheelService
{
    /**
     * Get all active spin wheel items ordered by order.
     */
    public function getActiveItems()
    {
        return SpinWheelItem::active()
            ->ordered()
            ->get();
    }

    /**
     * Perform a spin and return the result based on probability.
     * This ensures the result comes from the backend to prevent manipulation.
     */
    public function performSpin(string $userName, string $userPhone): array
    {
        // Get all active items
        $items = $this->getActiveItems();

        if ($items->isEmpty()) {
            throw new \Exception('No active spin wheel items found');
        }

        // Calculate total probability
        $totalProbability = $items->sum('probability');

        // If total probability is 0 or less, randomly select an item
        if ($totalProbability <= 0) {
            $selectedItem = $items->random();
        } else {
            // Generate a random number between 0 and total probability
            $random = mt_rand(0, (int)($totalProbability * 100)) / 100;

            // Select item based on probability
            $selectedItem = $this->selectItemByProbability($items, $random);
        }

        // Save the result
        $result = SpinWheelResult::create([
            'spin_wheel_item_id' => $selectedItem->id,
            'user_name' => $userName,
            'user_phone' => $userPhone,
            'discount_code' => $selectedItem->discount_code,
            'text' => $selectedItem->text,
        ]);

        Log::info('Spin wheel result', [
            'user_name' => $userName,
            'user_phone' => $userPhone,
            'item_id' => $selectedItem->id,
            'discount_code' => $selectedItem->discount_code,
        ]);

        return [
            'success' => true,
            'item' => [
                'id' => $selectedItem->id,
                'text' => $selectedItem->text,
                'discount_code' => $selectedItem->discount_code,
                'description' => $selectedItem->description,
            ],
            'result_id' => $result->id,
        ];
    }

    /**
     * Select an item based on probability.
     */
    private function selectItemByProbability($items, float $random): SpinWheelItem
    {
        $cumulative = 0;

        foreach ($items as $item) {
            $cumulative += $item->probability;
            if ($random <= $cumulative) {
                return $item;
            }
        }

        // Fallback to last item if no match (shouldn't happen, but safety)
        return $items->last();
    }

    /**
     * Get statistics for spin wheel.
     */
    public function getStatistics(): array
    {
        $totalSpins = SpinWheelResult::count();
        $totalItems = SpinWheelItem::count();
        $activeItems = SpinWheelItem::active()->count();

        // Get results per item
        $resultsPerItem = SpinWheelResult::selectRaw('spin_wheel_item_id, COUNT(*) as count')
            ->groupBy('spin_wheel_item_id')
            ->with('spinWheelItem')
            ->get()
            ->map(function ($result) {
                return [
                    'item_id' => $result->spin_wheel_item_id,
                    'item_text' => $result->spinWheelItem->text ?? 'N/A',
                    'count' => $result->count,
                ];
            });

        return [
            'total_spins' => $totalSpins,
            'total_items' => $totalItems,
            'active_items' => $activeItems,
            'results_per_item' => $resultsPerItem,
        ];
    }

    /**
     * Get recent results.
     */
    public function getRecentResults(int $limit = 50)
    {
        return SpinWheelResult::with('spinWheelItem')
            ->recent($limit)
            ->get();
    }

    /**
     * Check if user has already spun the wheel by phone number.
     * Returns the previous result if exists.
     */
    public function checkPreviousSpin(string $userPhone): ?array
    {
        $previousResult = SpinWheelResult::where('user_phone', $userPhone)
            ->with('spinWheelItem')
            ->latest()
            ->first();

        if (!$previousResult) {
            return null;
        }

        return [
            'has_previous_spin' => true,
            'result' => [
                'id' => $previousResult->id,
                'user_name' => $previousResult->user_name,
                'user_phone' => $previousResult->user_phone,
                'discount_code' => $previousResult->discount_code,
                'text' => $previousResult->text,
                'created_at' => $previousResult->created_at,
                'item' => [
                    'id' => $previousResult->spinWheelItem->id ?? null,
                    'text' => $previousResult->spinWheelItem->text ?? $previousResult->text,
                    'discount_code' => $previousResult->spinWheelItem->discount_code ?? $previousResult->discount_code,
                    'description' => $previousResult->spinWheelItem->description ?? null,
                ],
            ],
        ];
    }
}

