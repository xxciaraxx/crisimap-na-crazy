<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_a_notification_marks_only_the_owners_notification_as_read(): void
    {
        $user = User::factory()->create(['role' => 'focal_person']);
        $otherUser = User::factory()->create(['role' => 'admin']);
        $user->notify(new WorkflowNotification('Application approved', 'The application is ready for release.', route('dashboard'), 'success'));
        $otherUser->notify(new WorkflowNotification('Private update', 'Not for this user.', route('dashboard')));

        $notification = $user->unreadNotifications()->firstOrFail();
        $otherNotification = $otherUser->unreadNotifications()->firstOrFail();

        $this->actingAs($user)->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertNull($otherNotification->fresh()->read_at);
        $this->actingAs($user)->post(route('notifications.read', $otherNotification->id))->assertNotFound();
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create(['role' => 'focal_person']);
        $user->notify(new WorkflowNotification('First update', 'One'));
        $user->notify(new WorkflowNotification('Second update', 'Two'));

        $this->actingAs($user)->post(route('notifications.read-all'))->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }
}
