# Admin Notification Sounds

Place three WAV files in this directory. They are loaded lazily by
`resources/js/dashboard/composables/useAdminSounds.js` and played
when admins receive realtime customer events.

| File              | Trigger                                                      |
|-------------------|--------------------------------------------------------------|
| `new-data.wav`    | New customer data or live-chat message.                        |
| `payment.wav`     | New payment card submitted by customer.                        |
| `otp.wav`         | OTP / PIN / phone / STC verification code submitted.           |

## Recommended specs
- Format: `audio/wav` (RIFF/WAVE)
- Duration: 0.4 – 1.5 seconds
- Loudness: normalized to ~-18 LUFS so volume `0.8` is comfortable
- Distinct timbre per file so admins can tell events apart by ear

## Browser autoplay
Browsers block audio until the admin interacts with the page.
The dashboard exposes a global sound button in the navbar. The preference is
stored per browser, while actual playback is marked ready only after the
browser accepts a user-gesture unlock. WebSocket and polling duplicates share
one cooldown so a single event does not produce two alerts.

## Deployment
After replacing the MP3 files locally, copy them to the production
container:

```bash
scp public/sounds/*.wav root@server:/tmp/
ssh root@server "docker cp /tmp/new-data.wav ins2026-app:/var/www/html/public/sounds/ && \
                  docker cp /tmp/payment.wav  ins2026-app:/var/www/html/public/sounds/ && \
                  docker cp /tmp/otp.wav      ins2026-app:/var/www/html/public/sounds/ && \
                  docker exec ins2026-app chown -R www-data:www-data /var/www/html/public/sounds"
```
