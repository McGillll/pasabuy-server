<?php

namespace App\Services;

use App\Interfaces\DailyVerse\DailyVerseServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DailyVerseService implements DailyVerseServiceInterface
{
    protected const BASE_URL = 'https://api.youversion.com/v1';

    public function getToday()
    {
        $cacheKey = 'daily-verse:'.now()->toDateString();

        return Cache::remember($cacheKey, now()->endOfDay(), function () {
            return $this->fetchToday();
        });
    }

    protected function fetchToday()
    {
        $fallback = ['reference' => null, 'text' => null];

        $appKey = config('services.youversion.app_key');

        if (! $appKey) {
            return $fallback;
        }

        try {
            $votd = Http::withHeaders(['X-YVP-App-Key' => $appKey])
                ->get(self::BASE_URL.'/verse_of_the_days/'.now()->dayOfYear)
                ->throw()
                ->json();

            $passage = Http::withHeaders(['X-YVP-App-Key' => $appKey])
                ->get(self::BASE_URL.'/bibles/'.config('services.youversion.bible_id').'/passages/'.$votd['passage_id'], [
                    'format' => 'text',
                ])
                ->throw()
                ->json();

            return [
                'reference' => $passage['reference'],
                'text' => trim($passage['content']),
            ];
        } catch (Throwable $exception) {
            Log::warning('Failed to fetch YouVersion daily verse.', [
                'error' => $exception->getMessage(),
            ]);

            return $fallback;
        }
    }
}
