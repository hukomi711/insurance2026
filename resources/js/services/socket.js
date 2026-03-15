import { useNotificationsStore } from "@/store";
import { getEcho, destroyEcho } from "@/services/echo";
import logger from "@/utils/logger";

/**
 * WebSocket service for real-time push notifications via Laravel Echo + Reverb.
 *
 * Prerequisites:
 *   npm install laravel-echo
 *
 * .env variables needed:
 *   VITE_REVERB_APP_KEY=your_key
 *   VITE_REVERB_HOST=localhost
 *   VITE_REVERB_PORT=8080
 *   VITE_REVERB_SCHEME=http
 *
 * Laravel backend:
 *   broadcast(new UserNotificationCreated($user));
 *
 * Usage:
 *   import { initSocket, destroySocket } from '@/services/socket';
 *
 *   // After login:
 *   initSocket(userId, authToken);
 *
 *   // On logout:
 *   destroySocket();
 */

let echoInstance = null;

/**
 * Initialize WebSocket connection and listen for push notifications.
 *
 * @param {string|number} userId — Authenticated user ID
 * @param {string} token — Bearer token for broadcasting auth
 * @returns {object|null} Echo instance (or null if dependencies missing)
 */
export async function initSocket(userId, token) {
    // Guard: don't re-init
    if (echoInstance) return echoInstance;

    echoInstance = await getEcho({
        authEndpoint: "/api/broadcasting/auth",
        auth: {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        },
    });

    if (!echoInstance) {
        logger.warn(
            "[Socket] Echo/Reverb not available — skipping WebSocket init",
        );
        return null;
    }

    const notifications = useNotificationsStore();

    // Listen for push notifications on the user's private channel
    echoInstance
        .private(`users.${userId}`)
        .listen(".notification.created", (event) => {
            notifications.push({
                type: event.type || "info",
                message: event.message,
                priority: event.priority || 1,
            });
        })
        .listen(".policy.issued", (event) => {
            notifications.push({
                type: "success",
                message: event.message || "تم إصدار الوثيقة بنجاح",
                priority: 3,
            });
        })
        .listen(".payment.failed", (event) => {
            notifications.push({
                type: "error",
                message: event.message || "فشلت عملية الدفع",
                priority: 10,
            });
        })
        .listen(".session.expired", () => {
            notifications.push({
                type: "error",
                message: "انتهت الجلسة — يرجى تسجيل الدخول مرة أخرى",
                priority: 10,
            });
        });

    logger.info(`[Socket] Connected — listening on users.${userId}`);

    return echoInstance;
}

/**
 * Broadcasts a general-purpose client event to other clients.
 * Note: This requires the channel to be a "presence" channel on the backend.
 *
 * @param {string} eventName — e.g., 'navigateTo', 'updateBasmah'
 * @param {object} data — The payload to send
 */
export function broadcastClientEvent(eventName, data) {
    if (!echoInstance) {
        logger.warn("[Socket] Cannot broadcast event, not connected.");
        return;
    }

    const channel = echoInstance.private(`users.${data.ip}`);
    channel.whisper(eventName, data);
}

/**
 * Disconnect WebSocket and clean up.
 */
export function destroySocket() {
    destroyEcho();
    echoInstance = null;
    logger.info("[Socket] Disconnected");
}
