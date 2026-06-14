"""Generate three short notification WAV files for the admin dashboard.

Output:
  public/sounds/new-data.wav  — soft two-note chime (D5 -> F#5)
  public/sounds/payment.wav   — bright four-note fanfare (C5 -> E5 -> G5 -> C6)
  public/sounds/otp.wav       — distinctive double-pulse alert (square wave)

No external deps. WAV is supported natively by every modern browser, so the
admin dashboard can keep using the same <Audio> elements after the file
extension is changed in useAdminSounds.js.
"""

from __future__ import annotations

import math
import struct
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


def main() -> None:
    out_dir = Path(__file__).resolve().parent.parent / "public" / "sounds"
    files = {
        "new-data.wav": build_new_data(),
        "payment.wav": build_payment(),
        "otp.wav": build_otp(),
    }
    for name, samples in files.items():
        path = out_dir / name
        _write_wav(path, samples)
        print(f"wrote {path}  ({len(samples) / SAMPLE_RATE:.2f}s, {path.stat().st_size} bytes)")


if __name__ == "__main__":
    main()
