<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\LeaveRequest;
use Illuminate\Support\Carbon;

class LeaveCalculationTest extends TestCase
{
    /**
     * Test working days vs calendar days.
     */
    public function test_calculate_days_types(): void
    {
        // 1. Paid leave (Congé payé légal) - Monday to next Monday = 6 working days (Mon-Fri + Mon)
        // Weekend is Sat-Sun (2 days excluded)
        $start = '2026-07-20'; // Monday
        $end = '2026-07-27';   // Monday
        $daysCp = LeaveRequest::calculateDays($start, $end, 'Congé payé légal');
        $this->assertEquals(6, $daysCp);

        // 2. RTT - Monday to Friday = 5 working days
        $daysRtt = LeaveRequest::calculateDays('2026-07-20', '2026-07-24', 'RTT');
        $this->assertEquals(5, $daysRtt);

        // 3. Sick leave (Arrêt maladie) - Monday to next Monday = 8 calendar days
        $daysMaladie = LeaveRequest::calculateDays($start, $end, 'Arrêt maladie');
        $this->assertEquals(8, $daysMaladie);

        // 4. Unpaid leave (Congé sans solde) - Monday to Friday = 5 calendar days
        $daysSansSolde = LeaveRequest::calculateDays('2026-07-20', '2026-07-24', 'Congé sans solde');
        $this->assertEquals(5, $daysSansSolde);
    }
}
