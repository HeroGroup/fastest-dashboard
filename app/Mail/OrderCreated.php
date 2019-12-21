<?php

namespace App\Mail;

use FastestModels\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderCreated extends Mailable
{
    use Queueable, SerializesModels;

    protected $order;

    public function __construct($orderId)
    {
        if ($orderId > 0)
            $this->order = Order::find($orderId);
        else
            $this->order = null;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.orders.created')
            ->with([
                'uniqueNumber' => $this->order->unique_number,
                'items' => $this->order->items,
                'totalPrice' => $this->order->total_price,
                'totalDiscount' => $this->order->total_discount,
                'date' => $this->order->created_at,
                'paymentMethod' => $this->order->payment_method
            ]);
    }
}
