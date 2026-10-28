<?php

namespace App\Services;

use App\Exceptions\InvalidPayloadException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedActionException;
use App\Interfaces\Invoice\InvoiceServiceInterface;
use App\Interfaces\Order\OrderRepositoryInterface;
use App\Interfaces\OrderPayment\OrderPaymentRepositoryInterface;
use App\Interfaces\OrderPayment\OrderPaymentServiceInterface;
use App\Mail\PaymentConfirmationMail;
use App\Models\PasabuyOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderPaymentService implements OrderPaymentServiceInterface
{
    public function __construct(
        protected OrderPaymentRepositoryInterface $orderPaymentRepository,
        protected OrderRepositoryInterface $orderRepository,
        protected InvoiceServiceInterface $invoiceService,
    ) {}

    public function listForOrder(string $orderUuid, User $user)
    {
        $order = $this->orderRepository->findByUuid($orderUuid);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        $this->ensureOrderOwnership($order, $user);

        return $this->orderPaymentRepository->listForOrder($order);
    }

    public function create(string $orderUuid, object $payload)
    {
        $order = $this->orderRepository->findByUuid($orderUuid);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        $this->ensureOrderOwnership($order, $payload->user);

        if ($order->pasabuySchedule->status !== 'finished') {
            throw new InvalidPayloadException('Payments can only be recorded once the pasabuy schedule is finished.');
        }

        $payload->order = $order;
        $wasAlreadyPaid = $order->payment_status === 'paid';

        $payment = DB::transaction(function () use ($payload, $order) {
            $payment = $this->orderPaymentRepository->create($payload);
            $this->orderRepository->recalcPaymentStatus($order);

            return $payment;
        });

        if (! $wasAlreadyPaid && $order->payment_status === 'paid') {
            $this->sendPaymentConfirmationEmail($order);
        }

        return $payment;
    }

    public function delete(string $orderUuid, string $uuid, User $user)
    {
        $order = $this->orderRepository->findByUuid($orderUuid);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        $this->ensureOrderOwnership($order, $user);

        $payment = $this->orderPaymentRepository->findByUuid($uuid);

        if (! $payment || $payment->order_id !== $order->id) {
            throw new NotFoundException('Payment not found.');
        }

        $wasPaid = $order->payment_status === 'paid';

        DB::transaction(function () use ($payment, $order) {
            $this->orderPaymentRepository->delete($payment);
            $this->orderRepository->recalcPaymentStatus($order);
        });

        if ($wasPaid && $order->payment_status !== 'paid') {
            Log::warning('Order dropped out of paid status after a payment was voided.', [
                'order_uuid' => $order->uuid,
                'voided_payment_uuid' => $payment->uuid,
                'voided_amount' => $payment->amount,
                'new_payment_status' => $order->payment_status,
            ]);
        }
    }

    protected function ensureOrderOwnership(object $order, User $user)
    {
        if ($user->isSuperadmin()) {
            return;
        }

        if ($order->pasabuySchedule->user_id !== $user->id) {
            throw new UnauthorizedActionException('You do not own this order.');
        }
    }

    protected function sendPaymentConfirmationEmail(PasabuyOrder $order)
    {
        if (! $order->user || ! $order->user->email) {
            Log::warning('Skipped payment confirmation email — order has no customer email on file.', [
                'order_uuid' => $order->uuid,
            ]);

            return;
        }

        try {
            $invoice = $this->invoiceService->buildForOrder($order);

            Mail::to($order->user->email)->queue(new PaymentConfirmationMail($order, $invoice));
        } catch (Throwable $exception) {
            Log::warning('Failed to queue payment confirmation email for order.', [
                'order_uuid' => $order->uuid,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
