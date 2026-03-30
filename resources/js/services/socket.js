import { destroyEcho } from "@/services/echo";
import logger from "@/utils/logger";

/**
 * WebSocket service for real-time push notifications via Laravel Echo + Reverb.
 */

/**
 * Disconnect WebSocket and clean up.
 */
export function destroySocket() {
    destroyEcho();
    logger.info("[Socket] Disconnected");
}
