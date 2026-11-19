<?php

namespace App\Concerns;

use App\Models\Announcement;

trait AnnouncementData
{
    /**
     * The announcement fields shown in lists and headers.
     *
     * @return array{
     *     id: int,
     *     title: string,
     *     audience: string,
     *     audience_label: string,
     *     status: string,
     *     published_at: string|null,
     *     expires_at: string|null,
     *     author: string|null
     * }
     */
    protected function announcementSummary(Announcement $announcement): array
    {
        return [
            'id' => $announcement->id,
            'title' => $announcement->title,
            'audience' => $announcement->audience->value,
            'audience_label' => $announcement->audience->label(),
            'status' => $announcement->status(),
            'published_at' => $announcement->published_at?->format('Y-m-d H:i'),
            'expires_at' => $announcement->expires_at?->format('Y-m-d H:i'),
            'author' => $announcement->author?->name,
        ];
    }
}
