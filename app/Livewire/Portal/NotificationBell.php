<?php

namespace App\Livewire\Portal;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/** T4-09: lonceng di layout portal. Hanya menghitung notifikasi belum dibaca milik pengguna yang sedang login. */
class NotificationBell extends Component
{
    #[Computed]
    public function unread(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    /** Dipanggil pusat notifikasi setelah menandai dibaca; render ulang sudah cukup. */
    #[On('notifications-updated')]
    public function refreshCount(): void {}

    public function render(): View
    {
        return view('livewire.portal.notification-bell');
    }
}
