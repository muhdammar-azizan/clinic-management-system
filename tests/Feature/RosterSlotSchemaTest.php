<?php

namespace Tests\Feature;

use App\Enums\SlotStatus;
use App\Models\RosterSlot;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RosterSlotSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_slot_defaults_to_available_and_has_a_readable_code(): void
    {
        $slot = RosterSlot::factory()->create(['start_time' => '09:30']);

        $this->assertSame(SlotStatus::Available, $slot->fresh()->status);
        $this->assertSame('SLT-'.str_pad((string) $slot->id, 5, '0', STR_PAD_LEFT), $slot->slot_code);
        $this->assertSame('09:30', $slot->fresh()->start_time_label);
        $this->assertTrue($slot->doctor->isDoctor());
    }

    public function test_a_doctor_cannot_have_two_slots_at_the_same_time(): void
    {
        $slot = RosterSlot::factory()->create();

        $this->expectException(QueryException::class);
        RosterSlot::factory()->create([
            'doctor_id' => $slot->doctor_id,
            'slot_date' => $slot->slot_date->toDateString(),
            'start_time' => $slot->start_time,
        ]);
    }

    public function test_different_doctors_can_share_the_same_time(): void
    {
        $slot = RosterSlot::factory()->create();
        RosterSlot::factory()->create(['slot_date' => $slot->slot_date->toDateString(), 'start_time' => $slot->start_time]);

        $this->assertSame(2, RosterSlot::count());
    }

    public function test_available_scope_ignores_occupied_slots(): void
    {
        RosterSlot::factory()->create();
        RosterSlot::factory()->occupied()->create();

        $this->assertSame(1, RosterSlot::available()->count());
    }

    public function test_deleting_a_doctor_removes_their_slots(): void
    {
        $slot = RosterSlot::factory()->create();
        $slot->doctor->delete();

        $this->assertSame(0, RosterSlot::count());
    }
}
