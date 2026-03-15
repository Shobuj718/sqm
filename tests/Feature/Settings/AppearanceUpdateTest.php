<?php

use App\Models\User;

test('appearance settings screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/appearance');

    $response->assertStatus(200);
});

test('users can update theme to light', function () {
    $user = User::factory()->create([
        'theme_preference' => 'system',
    ]);

    $response = $this->actingAs($user)
        ->from('/settings/appearance')
        ->put('/settings/appearance', [
            'theme_preference' => 'light',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect('/settings/appearance');

    expect($user->refresh()->theme_preference)->toEqual('light');
});

test('users can update theme to dark', function () {
    $user = User::factory()->create([
        'theme_preference' => 'light',
    ]);

    $response = $this->actingAs($user)->put('/settings/appearance', [
        'theme_preference' => 'dark',
    ]);

    $response->assertSessionHasNoErrors();
    expect($user->refresh()->theme_preference)->toEqual('dark');
});

test('users can update theme to system', function () {
    $user = User::factory()->create([
        'theme_preference' => 'dark',
    ]);

    $response = $this->actingAs($user)->put('/settings/appearance', [
        'theme_preference' => 'system',
    ]);

    $response->assertSessionHasNoErrors();
    expect($user->refresh()->theme_preference)->toEqual('system');
});

test('theme update requires valid value', function () {
    $user = User::factory()->create([
        'theme_preference' => 'system',
    ]);

    $response = $this->actingAs($user)->put('/settings/appearance', [
        'theme_preference' => 'invalid-theme',
    ]);

    $response->assertSessionHasErrors('theme_preference');
    expect($user->refresh()->theme_preference)->toEqual('system');
});

test('guests cannot update theme preference', function () {
    $response = $this->put('/settings/appearance', [
        'theme_preference' => 'dark',
    ]);

    $response->assertRedirect('/login');
});