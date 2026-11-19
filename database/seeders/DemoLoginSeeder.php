<?php

namespace Database\Seeders;

use App\Enums\Role as RoleName;
use App\Models\Family;
use App\Models\Message;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoLoginSeeder extends Seeder
{
    /**
     * Seed demo student and parent portal logins with sample messages.
     */
    public function run(): void
    {
        $student = Student::query()
            ->whereHas('reportCards', fn ($query) => $query->whereNotNull('published_at'))
            ->orderBy('id')
            ->first();

        if (! $student instanceof Student) {
            $student = Student::query()->orderBy('id')->first();
        }

        $family = $student instanceof Student && $student->family !== null
            ? $student->family
            : Family::query()->orderBy('id')->first();

        $studentUser = null;
        $parentUser = null;

        if ($student instanceof Student) {
            $studentUser = User::query()->updateOrCreate(
                ['email' => 'student@example.com'],
                [
                    'name' => $student->displayName(),
                    'password' => 'password',
                ],
            );

            $studentUser->syncRoles([RoleName::Student->value]);
            $student->update(['user_id' => $studentUser->id]);
        }

        if ($family instanceof Family) {
            $parentUser = User::query()->updateOrCreate(
                ['email' => 'parent@example.com'],
                [
                    'name' => $family->name,
                    'password' => 'password',
                ],
            );

            $parentUser->syncRoles([RoleName::Parent->value]);
            $family->update(['user_id' => $parentUser->id]);
        }

        $administrator = User::query()->where('email', 'admin@example.com')->first();

        if (! $administrator instanceof User) {
            return;
        }

        if ($studentUser instanceof User) {
            $this->message(
                $administrator,
                $studentUser,
                'Welcome to the student portal. You can read announcements and your published report cards here.',
                now()->subDays(2),
            );

            $this->message(
                $studentUser,
                $administrator,
                'Thank you. I can see my report card for this term.',
                now()->subDay(),
                now()->subDay(),
            );
        }

        if ($parentUser instanceof User) {
            $this->message(
                $administrator,
                $parentUser,
                'Welcome to the family portal. Report cards appear here once they are published.',
                now()->subDays(3),
            );
        }
    }

    /**
     * Store a demo message if it does not exist yet.
     */
    private function message(
        User $sender,
        User $recipient,
        string $body,
        \DateTimeInterface $sentAt,
        ?\DateTimeInterface $readAt = null,
    ): void {
        $message = Message::query()->firstOrCreate(
            [
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'body' => $body,
            ],
            ['read_at' => $readAt],
        );

        $message->forceFill([
            'created_at' => $sentAt,
            'updated_at' => $sentAt,
        ])->save();
    }
}
