<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ChatSesi;
use App\Models\User;

/**
 * Riwayat percakapan sepenuhnya milik penggunanya.
 *
 * Tidak ada pengecualian untuk admin. Orang bertanya kepada asisten
 * lingkungan tentang keadaan di rumahnya, bau dari saluran air,
 * tetangga yang membakar sampah, dan percakapan semacam itu tidak
 * pantas dibaca siapa pun selain penanyanya.
 */
class ChatSesiPolicy
{
    public function view(User $user, ChatSesi $sesi): bool
    {
        return $sesi->user_id === $user->id;
    }

    public function update(User $user, ChatSesi $sesi): bool
    {
        return $sesi->user_id === $user->id;
    }

    public function delete(User $user, ChatSesi $sesi): bool
    {
        return $sesi->user_id === $user->id;
    }
}
