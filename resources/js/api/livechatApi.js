/**
 * livechatApi.js — Admin Live Chat API endpoints
 *
 * Uses the project's centralized request wrapper (baseURL = /api).
 */

import request from './request';

/**
 * Get list of active conversations with unread counts.
 * @returns {Promise<{ conversations: Array, unreadCount: number }>}
 */
export function getConversations ()
{
    return request.get( '/admin/livechat/conversations' );
}

/**
 * Get messages for a specific conversation.
 * @param {string} sessionId
 * @returns {Promise<{ messages: Array }>}
 */
export function getConversation ( sessionId )
{
    return request.get( `/admin/livechat/conversations/${ sessionId }` );
}

/**
 * Send a reply to a conversation.
 * @param {string} sessionId
 * @param {string} message
 * @returns {Promise<{ message: Object }>}
 */
export function sendReply ( sessionId, message )
{
    return request.post( `/admin/livechat/conversations/${ sessionId }/reply`, { message } );
}
