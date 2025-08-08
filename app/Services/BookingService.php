<?php

namespace App\Services;

use App\Models\User;
use App\Models\Booking;
use App\Models\BookingSlot;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use InvalidArgumentException;

class BookingService
{
    /**
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserBookings(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return $user->bookings()->with('slots')->get();
    }

    /**
     * @param User $user
     * @param array $slotsData
     * @return mixed
     */
    public function createBooking(User $user, array $slotsData): mixed
    {
        return DB::transaction(function () use ($user, $slotsData) {
            $this->validateSlots($slotsData);
            $this->checkSlotAvailability($slotsData);

            $booking = $user->bookings()->create();

            foreach ($slotsData as $slotData) {
                $this->createBookingSlot($booking, $slotData);
            }

            return $booking;
        });
    }

    /**
     * @param Booking $booking
     * @param array $slotData
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function addSlotToBooking(Booking $booking, array $slotData)
    {
        $this->checkSingleSlotAvailability(
            $slotData['start_time'],
            $slotData['end_time'],
            $booking->id
        );

        return $booking->slots()->create($slotData);
    }

    public function updateBookingSlot(BookingSlot $slot, array $slotData)
    {
        $this->checkSingleSlotAvailability(
            $slotData['start_time'],
            $slotData['end_time'],
            $slot->booking_id,
            $slot->id
        );

        $slot->update($slotData);
        return $slot;
    }

    public function deleteBooking(Booking $booking)
    {
        $booking->delete();
    }

    /**
     * @param Booking $booking
     * @param array $slotData
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function createBookingSlot(Booking $booking, array $slotData)
    {
        $this->checkSingleSlotAvailability(
            $slotData['start_time'],
            $slotData['end_time'],
            $booking->id
        );

        return $booking->slots()->create($slotData);
    }

    /**
     * @param array $slotsData
     * @param int|null $bookingId
     * @return void
     */
    protected function checkSlotAvailability(array $slotsData, ?int $bookingId = null): void
    {
        if (empty($slotsData)) {
            return;
        }

        foreach ($slotsData as $slot) {
            if (!isset($slot['start_time']) || !isset($slot['end_time'])) {
                throw new InvalidArgumentException('Неверная структура данных слота');
            }

            $this->checkSingleSlotAvailability(
                $slot['start_time'],
                $slot['end_time'],
                $bookingId
            );
        }
    }

    /**
     * @param string $startTime
     * @param string $endTime
     * @param int|null $excludeBookingId
     * @param int|null $excludeSlotId
     * @return void
     */
    protected function checkSingleSlotAvailability(
        string $startTime,
        string $endTime,
        ?int $excludeBookingId = null,
        ?int $excludeSlotId = null
    ): void {
        $query = BookingSlot::where(function($q) use ($startTime, $endTime) {
            $q->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime);
        });

        if ($excludeBookingId !== null) {
            $query->where('booking_id', '!=', $excludeBookingId);
        }

        if ($excludeSlotId !== null) {
            $query->where('id', '!=', $excludeSlotId);
        }

        if ($query->exists()) {
            throw new HttpException(
                409,
                'Выбранный временной слот пересекается с существующим бронированием',
                null,
                [
                    'conflicting_slot' => [
                        'start_time' => $startTime,
                        'end_time' => $endTime
                    ],
                    'existing_slots' => $query->get()->toArray()
                ]
            );
        }
    }

    /**
     * @param array $slots
     * @return void
     */
    private function validateSlots(array $slots): void
    {
        if (empty($slots)) {
            throw new InvalidArgumentException('Необходимо указать хотя бы один слот');
        }

        $this->checkInternalConflicts($slots);

        foreach ($slots as $slot) {
            if (!isset($slot['start_time']) || !isset($slot['end_time'])) {
                throw new InvalidArgumentException('Неверная структура данных слота');
            }

            if (strtotime($slot['start_time']) >= strtotime($slot['end_time'])) {
                throw new InvalidArgumentException('Дата окончания должна быть позже даты начала');
            }
        }
    }

    /**
     * @param array $slots
     * @return void
     */
    private function checkInternalConflicts(array $slots): void
    {
        $events = [];
        foreach ($slots as $slot) {
            $events[] = ['time' => strtotime($slot['start_time']), 'type' => 'start'];
            $events[] = ['time' => strtotime($slot['end_time']), 'type' => 'end'];
        }

        usort($events, fn($a, $b) => $a['time'] <=> $b['time']);

        $active = 0;
        foreach ($events as $event) {
            $active += ($event['type'] === 'start') ? 1 : -1;
            if ($active > 1) {
                throw new HttpException(422, 'Обнаружено пересечение временных слотов');
            }
        }
    }
}
