<?php

namespace App\Livewire\Portal;

use App\Support\InAppNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** T4-09: pusat notifikasi klien. Semua query dibatasi ke notifikasi milik pengguna yang login (anti-IDOR). */
#[Layout('layouts::portal')]
class NotificationCenter extends Component
{
    private const LIMIT = 50;

    /**
     * @return Collection<int, array{id: string, read: bool, title: string, body: string, url: string|null, time: string}>
     */
    #[Computed]
    public function items(): Collection
    {
        return auth()->user()->notifications()
            ->latest()
            ->limit(self::LIMIT)
            ->get()
            ->map(function (DatabaseNotification $notification): array {
                $view = InAppNotification::present($notification);

                return [
                    'id' => (string) $notification->id,
                    'read' => $notification->read_at !== null,
                    'title' => $view['title'],
                    'body' => $view['body'],
                    'url' => $view['url'],
                    'time' => $notification->created_at->diffForHumans(),
                ];
            });
    }

    #[Computed]
    public function unread(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    /** Tandai satu notifikasi dibaca lalu buka tujuannya. ID milik pengguna lain tidak ditemukan (404). */
    public function open(string $id): void
    {
        $notification = auth()->user()->notifications()->whereKey($id)->firstOrFail();

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $this->dispatch('notifications-updated');

        $url = InAppNotification::present($notification)['url'];

        if ($url !== null) {
            $this->redirect($url, navigate: true);

            return;
        }

        unset($this->items, $this->unread);
    }

    public function markAsRead(string $id): void
    {
        $notification = auth()->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $this->dispatch('notifications-updated');
        unset($this->items, $this->unread);
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        $this->dispatch('notifications-updated');
        unset($this->items, $this->unread);
    }

    public function render(): View
    {
        return view('livewire.portal.notification-center')->title(__('notifications.title'));
    }
}
