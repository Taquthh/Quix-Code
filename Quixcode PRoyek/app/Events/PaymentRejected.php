<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;

/** Admin menolak bukti bayar dengan alasan; dikirim setelah transaksi selesai (T4-09). */
final class PaymentRejected
{
    use Dispatchable;

    public function __construct(public readonly Payment $payment) {}
}
