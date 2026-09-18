#!/usr/bin/env node

/**
 * تحديث جدول الأسعار الثابتة في plans.js
 *
 * يقرأ plans.js، ويحدّث basePrice لكل companyId حسب الجدول الجديد
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const PRICES_FILE = path.join(__dirname, '../resources/js/data/plans.js');

// جدول الأسعار الثابتة الجديدة (نموذج التسعير المبسط 2026-09-18)
// نفس السعر لكل من ضد الغير والشامل
const FIXED_PRICES = {
    // companyId: price (ريال سعودي)
    1: 499,     // تري للتأمين
    5: 749,     // ملاذ للتأمين
    8: 999,     // العناية السعودية للتأمين
    6: 1249,    // سايكو للتأمين
    13: 1499,   // التعاونية
    16: 1749,   // الراجحي للتأمين
    2: 1999,    // العربية (AICC)
    19: 2249,   // GIG
    20: 2499,   // الإنماء طوكيو مارين
    3: 2749,    // ولاء
    4: 2999,    // ميدغلف
    21: 3249,   // ليفا
    17: 3499,   // الوطنية
};

// قراءة الملف
let content = fs.readFileSync(PRICES_FILE, 'utf8');

// عداد للتغييرات
let changedCount = 0;

// معالجة كل companyId
Object.entries(FIXED_PRICES).forEach(([companyId, price]) => {
    // ضد الغير: نفس السعر
    const thirdPartyPattern = new RegExp(
        `(companyId:\\s*${companyId},\\s*[^}]*?type:\\s*['"]thirdParty['"],\\s*[^}]*?basePrice:\\s*)(\\d+)(,)`,
        'g'
    );

    const thirdPartyMatches = [...content.matchAll(thirdPartyPattern)];
    thirdPartyMatches.forEach(match => {
        const oldPrice = match[2];
        if (oldPrice !== price.toString()) {
            changedCount++;
            console.log(`✓ companyId ${companyId} (Third Party): ${oldPrice} → ${price} SAR`);
        }
    });
    content = content.replace(thirdPartyPattern, `$1${price}$3`);

    // الشامل: نفس السعر بالضبط (بدون إضافة فارق)
    const comprehensivePattern = new RegExp(
        `(companyId:\\s*${companyId},\\s*[^}]*?type:\\s*['"]comprehensive['"],\\s*[^}]*?basePrice:\\s*)(\\d+)(,)`,
        'g'
    );

    const comprehensiveMatches = [...content.matchAll(comprehensivePattern)];
    comprehensiveMatches.forEach(match => {
        const oldPrice = match[2];
        if (oldPrice !== price.toString()) {
            changedCount++;
            console.log(`✓ companyId ${companyId} (Comprehensive): ${oldPrice} → ${price} SAR`);
        }
    });
    content = content.replace(comprehensivePattern, `$1${price}$3`);
});

// كتابة الملف
fs.writeFileSync(PRICES_FILE, content, 'utf8');

console.log(`\n✅ تم تحديث ${changedCount} سعر`);
console.log(`📄 الملف: ${PRICES_FILE}`);
