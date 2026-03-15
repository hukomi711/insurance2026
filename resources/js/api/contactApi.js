/**
 * contactApi.js — Contact form API endpoint
 *
 * Uses the project's centralized request wrapper (baseURL = /api).
 */

import request from './request';

/**
 * Submit a contact form.
 * @param {{ name: string, email: string, phone?: string, subject?: string, message: string }} formData
 * @returns {Promise<{ data: { success: boolean, message: string, id: number } }>}
 */
export function submitContact ( formData )
{
    return request.post( '/contact', formData );
}
