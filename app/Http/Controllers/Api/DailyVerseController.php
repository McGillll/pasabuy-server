<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\DailyVerse\DailyVerseServiceInterface;
use App\Traits\ApiResponse;

class DailyVerseController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DailyVerseServiceInterface $dailyVerseService,
    ) {}

    public function show()
    {
        return $this->success($this->dailyVerseService->getToday());
    }
}
