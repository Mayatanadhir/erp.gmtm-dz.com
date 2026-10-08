<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class DateDisplayTest extends TestCase
{
    public function test_date_is_displayed_in_day_month_year_order(): void
    {
        $view = $this->blade('<x-date :value="$date" />', [
            'date' => Carbon::parse('2026-10-01'),
        ]);

        $view->assertSee('01/10/2026');
    }

    public function test_datetime_keeps_time_after_the_formatted_date(): void
    {
        $view = $this->blade('<x-date :value="$date" format="datetime" />', [
            'date' => Carbon::parse('2026-10-01 14:35:00'),
        ]);

        $view->assertSee('01/10/2026 14:35');
    }
}
