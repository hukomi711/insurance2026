<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Channel authorization for Reverb WebSocket broadcasting.
|
| Customer-facing channels are public (no auth required) since customers
| are identified by IP address, not authenticated users.
|
| Admin channels use PrivateChannel with Sanctum auth.
|
*/

// ─── Customer Channels (public — IP-based identification) ───────────
//
// These channels use `new Channel(...)` (not PrivateChannel) in their
// corresponding event classes, so the authorization callbacks below are
// only invoked if a client attempts to subscribe via private-channel
// protocol. Returning `true` is intentional — customers are identified
// by IP, not by authenticated user sessions.
//

// OTP verification channel — customer listens for approve/reject
// Channels use public Channel (not PrivateChannel) in events,
// so these callbacks are fallback guards. IP-based identification
// is acceptable here since events are broadcast to a specific IP channel
// and contain no secrets — they only signal status changes.
Broadcast::channel('otp.{ip}', function ($user, string $ip) {
    return true;
});

// Note: PIN events share the 'otp' channel prefix — no separate pin.{ip} channel needed

// Phone OTP verification channel
Broadcast::channel('phone.{ip}', function ($user, string $ip) {
    return true;
});

// Payment card approval channel
Broadcast::channel('payment.{ip}', function ($user, string $ip) {
    return true;
});

// STC verification channel (waiting, OTP, call stages)
Broadcast::channel('stc.{ip}', function ($user, string $ip) {
    return true;
});

// Nafath login verification channel
Broadcast::channel('nafath.{ip}', function ($user, string $ip) {
    return true;
});

// Admin-initiated customer redirect channel
Broadcast::channel('customer.{ip}', function ($user, string $ip) {
    return true;
});

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
