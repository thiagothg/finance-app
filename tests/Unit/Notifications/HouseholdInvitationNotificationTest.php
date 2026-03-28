<?php

declare(strict_types=1);

use App\Models\Household;
use App\Models\User;
use App\Notifications\HouseholdInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds the invitation mail action URL using the frontend URL and invitation data', function (): void {
    config()->set('app.frontend_url', 'http://localhost:55139/#');

    $user = User::factory()->create([
        'name' => 'Pat Doe',
        'email' => 'pat@example.com',
    ]);

    $household = Household::factory()->create([
        'name' => 'Doe Household',
        'invitation_code' => '12345678',
    ]);

    $mailMessage = (new HouseholdInvitationNotification($household, 'Chris'))->toMail($user);

    expect($mailMessage->actionText)->toBe('Accept Invitation')
        ->and($mailMessage->actionUrl)->toBe('http://localhost:55139/#/invite/accept?code=12345678&email=pat%40example.com');
});
