<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;

/** Invoice manual diterbitkan admin (draf menjadi terkirim); dikirim setelah transaksi DB selesai (T4-09). */
final class InvoiceIssued
{
    use Dispatchable;

    public function __construct(public readonly Invoice $invoice) {}
}
