<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingSlotRequest;
use App\Http\Requests\AddBookingSlotRequest;
use App\Services\BookingService;
use App\Models\Booking;
use App\Models\BookingSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $bookings = $this->bookingService->getUserBookings($user);
        return response()->json($bookings);
    }

    /**
     * @param StoreBookingRequest $request
     * @return JsonResponse
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->createBooking(
            $request->user(),
            $request->validated()['slots']
        );

        return response()->json($booking->load('slots'), 201);
    }

    /**
     * @param AddBookingSlotRequest $request
     * @param Booking $booking
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function addSlot(AddBookingSlotRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('update', $booking);

        $slot = $this->bookingService->addSlotToBooking(
            $booking,
            $request->validated()
        );

        return response()->json($slot, 201);
    }

    /**
     * @param UpdateBookingSlotRequest $request
     * @param Booking $booking
     * @param BookingSlot $slot
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateSlot(UpdateBookingSlotRequest $request, Booking $booking, BookingSlot $slot): JsonResponse
    {
        $this->authorize('update', $booking);

        $slot = $this->bookingService->updateBookingSlot(
            $slot,
            $request->validated()
        );

        return response()->json($slot);
    }

    /**
     * @param Booking $booking
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Booking $booking): JsonResponse
    {
        $this->authorize('delete', $booking);
        $this->bookingService->deleteBooking($booking);
        return response()->json([
            'message' => 'Бронирование успешно удалено',
            'deleted_booking_id' => $booking->id
        ], 200);
    }
}
