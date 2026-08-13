<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Channel authorization for Reverb WebSocket broadcasting.
|
| Customer-facing channels use opaque hashes of browser/session tokens.
| Public channel names contain neither IP addresses nor raw session tokens.
|
| Admin channels use PrivateChannel with Sanctum auth.
|
*/

// ─── Admin Channels (private — requires Sanctum auth) ───────────────

// Admin dashboard — private channel for real-time customer activity updates
// Only authenticated admin users can subscribe
Broadcast::channel('dashboard', function ($user) {
    return $user?->isAdmin();
});

// Admin OTP channel — aggregated real-time OTP approve/reject notifications
// for the admin dashboard (all OTP events in a single channel)
Broadcast::channel('admin.otp', function ($user) {
    return $user?->isAdmin();
});

// Admin phone verification channel — phone approve/reject notifications
Broadcast::channel('admin.phone', function ($user) {
    return $user?->isAdmin();
});

// Admin Nafath channel — nafath approve/reject notifications
Broadcast::channel('admin.nafath', function ($user) {
    return $user?->isAdmin();
});

// Admin payment channel — payment card approve/reject notifications
Broadcast::channel('admin.payment', function ($user) {
    return $user?->isAdmin();
});

// Admin STC channel — STC waiting/otp/call approve/reject notifications
Broadcast::channel('admin.stc', function ($user) {
    return $user?->isAdmin();
});

// Admin live chat — real-time incoming visitor messages (private — admin only)
Broadcast::channel('admin-livechat', function ($user) {
    return $user?->isAdmin();
});
