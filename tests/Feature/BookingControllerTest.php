<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected string $token;
    protected string $otherToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'api_token' => Str::random(60)
        ]);
        $this->otherUser = User::factory()->create([
            'api_token' => Str::random(60)
        ]);

        $this->token = $this->user->api_token;
        $this->otherToken = $this->otherUser->api_token;
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/bookings');
        $response->assertStatus(401);

        $booking = Booking::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/bookings/{$booking->id}");
        $response->assertStatus(401);
    }

    /** @test */
    public function user_can_create_booking_with_multiple_slots()
    {
        $slots = [
            ['start_time' => now()->addDay()->toDateTimeString(),
                'end_time' => now()->addDay()->addHour()->toDateTimeString()],
            ['start_time' => now()->addDays(2)->toDateTimeString(),
                'end_time' => now()->addDays(2)->addHour()->toDateTimeString()]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/bookings', ['slots' => $slots]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'user_id',
                'slots' => [
                    '*' => ['id', 'start_time', 'end_time', 'booking_id']
                ]
            ]);
    }

    /** @test */
    public function cannot_create_booking_with_conflicting_slots()
    {
        $existingBooking = Booking::factory()->create(['user_id' => $this->otherUser->id]);
        $existingSlot = BookingSlot::factory()->create([
            'booking_id' => $existingBooking->id,
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDay()->addHour()->toDateTimeString()
        ]);

        $conflictingSlots = [
            ['start_time' => $existingSlot->start_time, 'end_time' => $existingSlot->end_time]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/bookings', ['slots' => $conflictingSlots]);

        $response->assertStatus(409);
    }

    /** @test */
    public function user_can_add_slot_to_own_booking()
    {
        $booking = Booking::factory()->create(['user_id' => $this->user->id]);

        $slotData = [
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDay()->addHour()->toDateTimeString()
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson("/api/bookings/{$booking->id}/slots", $slotData);

        $response->assertStatus(201)
            ->assertJson($slotData);

        $this->assertDatabaseHas('booking_slots', [
            'booking_id' => $booking->id,
            'start_time' => $slotData['start_time'],
            'end_time' => $slotData['end_time']
        ]);
    }

    /** @test */
    public function cannot_add_slot_with_conflict()
    {
        $existingBooking = Booking::factory()->create(['user_id' => $this->otherUser->id]);
        $existingSlot = BookingSlot::factory()->create([
            'booking_id' => $existingBooking->id,
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDay()->addHour()->toDateTimeString()
        ]);

        $booking = Booking::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson("/api/bookings/{$booking->id}/slots", [
            'start_time' => $existingSlot->start_time,
            'end_time' => $existingSlot->end_time
        ]);

        $response->assertStatus(409);
    }

    /** @test */
    public function user_can_update_own_booking_slot()
    {
        $booking = Booking::factory()->create(['user_id' => $this->user->id]);
        $slot = BookingSlot::factory()->create([
            'booking_id' => $booking->id,
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDay()->addHour()->toDateTimeString()
        ]);

        $newData = [
            'start_time' => now()->addDays(2)->toDateTimeString(),
            'end_time' => now()->addDays(2)->addHour()->toDateTimeString()
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->patchJson("/api/bookings/{$booking->id}/slots/{$slot->id}", $newData);

        $response->assertStatus(200)
            ->assertJson($newData);

        $this->assertDatabaseHas('booking_slots', array_merge(['id' => $slot->id], $newData));
    }

    /** @test */
    public function cannot_update_slot_to_conflicting_time()
    {
        $existingBooking = Booking::factory()->create(['user_id' => $this->otherUser->id]);
        $existingSlot = BookingSlot::factory()->create([
            'booking_id' => $existingBooking->id,
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDay()->addHour()->toDateTimeString()
        ]);

        $booking = Booking::factory()->create(['user_id' => $this->user->id]);
        $slot = BookingSlot::factory()->create([
            'booking_id' => $booking->id,
            'start_time' => now()->addDays(2)->toDateTimeString(),
            'end_time' => now()->addDays(2)->addHour()->toDateTimeString()
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->patchJson("/api/bookings/{$booking->id}/slots/{$slot->id}", [
            'start_time' => $existingSlot->start_time,
            'end_time' => $existingSlot->end_time
        ]);

        $response->assertStatus(409);
    }

    /** @test */
    public function cannot_update_slot_of_another_user()
    {
        $otherUserBooking = Booking::factory()->create(['user_id' => $this->otherUser->id]);
        $slot = BookingSlot::factory()->create(['booking_id' => $otherUserBooking->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->patchJson("/api/bookings/{$otherUserBooking->id}/slots/{$slot->id}", [
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDay()->addHour()->toDateTimeString()
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_view_own_bookings()
    {
        Booking::factory()->count(3)->create(['user_id' => $this->user->id]);
        Booking::factory()->count(2)->create(['user_id' => $this->otherUser->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/bookings');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
    }

    /** @test */
    public function user_can_delete_own_booking()
    {
        $booking = Booking::factory()->create(['user_id' => $this->user->id]);
        BookingSlot::factory()->count(2)->create(['booking_id' => $booking->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->deleteJson("/api/bookings/{$booking->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Бронирование успешно удалено',
                'deleted_booking_id' => $booking->id
            ]);

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);

        $this->assertDatabaseMissing('booking_slots', ['booking_id' => $booking->id]);
    }

    /** @test */
    public function cannot_delete_another_users_booking()
    {
        $booking = Booking::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->deleteJson("/api/bookings/{$booking->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);

        if ($booking->slots()->exists()) {
            $this->assertDatabaseHas('booking_slots', ['booking_id' => $booking->id]);
        }
    }
}
