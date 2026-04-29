# Admin Notification Sounds

Place three MP3 files in this directory. They are loaded by
`resources/js/dashboard/composables/useAdminSounds.js` and played
when admins receive realtime customer events.

| File              | Trigger                                                      |
|-------------------|--------------------------------------------------------------|
| `new-data.mp3`    | New customer data (vehicle / insurance fields filled in).    |
| `payment.mp3`     | New payment card submitted by customer.                       |
| `otp.mp3`         | OTP / PIN / phone / STC verification code submitted.          |

## Recommended specs
- Format: `audio/mpeg` (MP3)
- Duration: 0.4 – 1.5 seconds
- Loudness: normalized to ~-18 LUFS so volume `0.8` is comfortable
- Distinct timbre per file so admins can tell events apart by ear

## Browser autoplay
Browsers block audio until the admin interacts with the page.
The dashboard exposes a "تفعيل التنبيهات الصوتية" button that calls
`enableSounds()` to unlock playback. After one click, all three sounds
play normally for the rest of the session.

## Deployment
After replacing the MP3 files locally, copy them to the production
container:

```bash
scp public/sounds/*.mp3 root@server:/tmp/
ssh root@server "docker cp /tmp/new-data.mp3 ins2026-app:/var/www/html/public/sounds/ && \
                  docker cp /tmp/payment.mp3  ins2026-app:/var/www/html/public/sounds/ && \
                  docker cp /tmp/otp.mp3      ins2026-app:/var/www/html/public/sounds/ && \
                  docker exec ins2026-app chown -R www-data:www-data /var/www/html/public/sounds"
```
