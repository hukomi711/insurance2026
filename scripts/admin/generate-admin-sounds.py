"""Generate four short notification sound files for the admin dashboard.

Output:
  public/sounds/new-data.mp3                     — soft two-note chime (D5 -> F#5)
  public/sounds/payment.mp3                      — bright four-note fanfare (C5 -> E5 -> G5 -> C6)
  public/sounds/otp.mp3                          — distinctive double-pulse alert (square wave)
  public/sounds/quiet-notification-sound.mp3     — gentle quiet notification (G4, soft)

Generates WAV files, then attempts conversion to MP3 using ffmpeg (if available).
If ffmpeg is not installed, generates WAV files as fallback (use Audacity or online converter to MP3).

Installation:
  macOS:    brew install ffmpeg
  Linux:    sudo apt-get install ffmpeg
  Windows:  https://ffmpeg.org/download.html or choco install ffmpeg
"""

from __future__ import annotations

import math
import struct
import subprocess
import wave
from pathlib import Path

SAMPLE_RATE = 44100
AMPLITUDE = 0.5  # peak amplitude (0..1)


def _envelope(i: int, total: int) -> float:
    """Simple linear attack + exponential decay envelope."""
    attack = max(1, int(0.01 * SAMPLE_RATE))
    if i < attack:
        return i / attack
    # exponential decay from 1.0 to ~0.001 over the remainder
    progress = (i - attack) / max(1, total - attack)
    return math.exp(-5.0 * progress)


def _tone(freq: float, duration: float, waveform: str = "sine", volume: float = 1.0) -> list[int]:
    n_samples = int(duration * SAMPLE_RATE)
    out: list[int] = []
    for i in range(n_samples):
        t = i / SAMPLE_RATE
        if waveform == "sine":
            v = math.sin(2 * math.pi * freq * t)
        elif waveform == "triangle":
            v = 2 * abs(2 * (t * freq - math.floor(t * freq + 0.5))) - 1
        elif waveform == "square":
            v = 1.0 if math.sin(2 * math.pi * freq * t) >= 0 else -1.0
        else:
            v = math.sin(2 * math.pi * freq * t)
        v *= _envelope(i, n_samples) * AMPLITUDE * volume
        out.append(int(max(-1.0, min(1.0, v)) * 32767))
    return out


def _silence(duration: float) -> list[int]:
    return [0] * int(duration * SAMPLE_RATE)


def _write_wav(path: Path, samples: list[int]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with wave.open(str(path), "wb") as w:
        w.setnchannels(1)
        w.setsampwidth(2)
        w.setframerate(SAMPLE_RATE)
        w.writeframes(b"".join(struct.pack("<h", s) for s in samples))


def _convert_wav_to_mp3(wav_path: Path, mp3_path: Path, bitrate: str = "128k") -> bool:
    """Convert WAV to MP3 using ffmpeg. Returns True if successful, False if ffmpeg not available."""
    try:
        result = subprocess.run(
            [
                "ffmpeg",
                "-f",
                "wav",
                "-i",
                str(wav_path),
                "-codec:a",
                "libmp3lame",
                "-b:a",
                bitrate,
                "-y",
                str(mp3_path),
            ],
            check=False,
            capture_output=True,
            text=True,
        )
        return result.returncode == 0
    except (FileNotFoundError, OSError):
        return False


def build_new_data() -> list[int]:
    # D5 (587 Hz) -> F#5 (740 Hz): soft chime
    return (
        _tone(587, 0.14, "sine", 0.9)
        + _silence(0.02)
        + _tone(740, 0.22, "sine", 0.9)
    )


def build_payment() -> list[int]:
    # C5 -> E5 -> G5 -> C6 fanfare with triangle wave
    return (
        _tone(523, 0.10, "triangle", 0.95)
        + _silence(0.015)
        + _tone(659, 0.10, "triangle", 0.95)
        + _silence(0.015)
        + _tone(784, 0.10, "triangle", 0.95)
        + _silence(0.015)
        + _tone(1047, 0.26, "triangle", 1.0)
    )


def build_otp() -> list[int]:
    # Two short square-wave double pulses — sounds like a verification beep
    pulse_a = _tone(880, 0.07, "square", 0.7) + _tone(1100, 0.07, "square", 0.7)
    pulse_b = _tone(1175, 0.07, "square", 0.7) + _tone(1397, 0.10, "square", 0.7)
    return pulse_a + _silence(0.10) + pulse_b


def build_quiet_notification() -> list[int]:
    # Gentle, quiet notification sound for new visitors and reactivated users
    # G4 (392 Hz) — soft, non-intrusive
    # Shorter duration (~0.25s), lower amplitude
    return _tone(392, 0.25, "sine", 0.3)


def main() -> None:
    out_dir = Path(__file__).resolve().parent.parent / "public" / "sounds"
    wav_dir = out_dir / ".wav_temp"  # Temporary directory for WAV files

    # Build all sounds
    builders = {
        "new-data": build_new_data(),
        "payment": build_payment(),
        "otp": build_otp(),
        "quiet-notification-sound": build_quiet_notification(),
    }

    # Write WAV files temporarily
    print("Generating WAV files...")
    for name, samples in builders.items():
        wav_path = wav_dir / f"{name}.wav"
        _write_wav(wav_path, samples)
        print(f"  ✓ {name}.wav ({len(samples) / SAMPLE_RATE:.2f}s, {wav_path.stat().st_size} bytes)")

    # Try to convert WAV to MP3 using ffmpeg
    print("\nAttempting MP3 conversion (requires ffmpeg)...")
    ffmpeg_available = False
    for name in builders.keys():
        wav_path = wav_dir / f"{name}.wav"
        mp3_path = out_dir / f"{name}.mp3"

        if _convert_wav_to_mp3(wav_path, mp3_path):
            mp3_size = mp3_path.stat().st_size
            print(f"  ✅ {name}.mp3 ({mp3_size} bytes)")
            ffmpeg_available = True
            try:
                wav_path.unlink()  # Delete temporary WAV
            except OSError:
                pass
        else:
            # Fallback: keep WAV copy so manual conversion is possible later.
            fallback_path = out_dir / f"{name}.wav"
            if not fallback_path.exists():
                fallback_path.write_bytes(wav_path.read_bytes())
                print(f"  ⚠️  {name}.mp3 failed — using {name}.wav as fallback")

    # Clean up temporary directory
    try:
        wav_dir.rmdir()
    except OSError:
        pass

    if not ffmpeg_available:
        print("\n⚠️  ffmpeg not found — using WAV files as fallback.")
        print("\n📝 To convert WAV to MP3, install ffmpeg and run again:")
        print("   macOS:  brew install ffmpeg")
        print("   Linux:  sudo apt-get install ffmpeg")
        print("   Windows: https://ffmpeg.org/download.html or choco install ffmpeg")
        print("\n   Alternatively, use Audacity or an online converter to convert:")
        for name in builders.keys():
            print(f"      public/sounds/{name}.wav → public/sounds/{name}.mp3")

    print("\n✅ Sound generation complete!")


if __name__ == "__main__":
    main()
