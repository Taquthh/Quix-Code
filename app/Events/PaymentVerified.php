<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;

/** Admin menerima bukti bayar; dikirim setelah transaksi verifikasi selesai (T4-09). */
final class PaymentVerified
{
    use Dispatchable;

    public function __construct(public readonly Payment $payment) {}
}
