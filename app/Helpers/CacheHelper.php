<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Redis;

class CacheHelper
{
    /**
     * Clear all FNB related cache
     */
    public static function clearFnbCache($itemId = null)
    {
        try {
            $prefix = config('database.redis.options.prefix');
            
            // 1. Clear specific detail if ID provided
            if ($itemId) {
                \Illuminate\Support\Facades\Log::info("Clearing detail cache for item: $itemId");
                Redis::del('fnb_item_detail_' . $itemId);
            }

            // 2. Clear all categories related cache (using wildcard)
            \Illuminate\Support\Facades\Log::info("Clearing all FNB category caches");
            $categoryKeys = Redis::keys('*fnb_categories*');
            foreach ($categoryKeys as $key) {
                $cleanKey = str_replace($prefix, '', $key);
                Redis::del($cleanKey);
            }
            // Also del explicit name just in case
            Redis::del('fnb_categories');

            // 3. Clear all item list caches (using wildcard)
            \Illuminate\Support\Facades\Log::info("Clearing all FNB item list caches");
            $itemKeys = Redis::keys('*fnb_items_*');
            foreach ($itemKeys as $key) {
                $cleanKey = str_replace($prefix, '', $key);
                Redis::del($cleanKey);
            }

            // 4. Clear FNB detail caches if no specific ID (clear all detail caches)
            if (!$itemId) {
                $detailKeys = Redis::keys('*fnb_item_detail_*');
                foreach ($detailKeys as $key) {
                    $cleanKey = str_replace($prefix, '', $key);
                    Redis::del($cleanKey);
                }
            }

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to clear Redis cache: ' . $e->getMessage());
            return false;
        }
    }
}
