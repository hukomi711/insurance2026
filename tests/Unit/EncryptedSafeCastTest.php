<?php

namespace Tests\Unit;

use App\Casts\EncryptedSafe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * Tests for EncryptedSafe cast — encryption at rest with plaintext fallback.
 */
class EncryptedSafeCastTest extends TestCase
{
    private EncryptedSafe $cast;
    private Model $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cast = new EncryptedSafe();
        $this->model = new class extends Model {};
    }

    public function test_null_value_returns_null_on_get(): void
    {
        $this->assertNull($this->cast->get($this->model, 'otp', null, []));
    }

    public function test_null_value_returns_null_on_set(): void
    {
        $this->assertNull($this->cast->set($this->model, 'otp', null, []));
    }

    public function test_set_encrypts_the_value(): void
    {
        $encrypted = $this->cast->set($this->model, 'otp', '123456', []);

        $this->assertNotEquals('123456', $encrypted);
        $this->assertEquals('123456', Crypt::decryptString($encrypted));
    }

    public function test_get_decrypts_encrypted_value(): void
    {
        $encrypted = Crypt::encryptString('secret-otp');

        $result = $this->cast->get($this->model, 'otp', $encrypted, []);

        $this->assertEquals('secret-otp', $result);
    }

    public function test_get_returns_plaintext_as_is_when_not_encrypted(): void
    {
        // Simulates a legacy row that was never encrypted
        $result = $this->cast->get($this->model, 'otp', 'plain-123', []);

        $this->assertEquals('plain-123', $result);
    }

    public function test_roundtrip_encrypt_then_decrypt(): void
    {
        $original = 'my-secret-value';
        $encrypted = $this->cast->set($this->model, 'field', $original, []);
        $decrypted = $this->cast->get($this->model, 'field', $encrypted, []);

        $this->assertEquals($original, $decrypted);
    }

    public function test_set_produces_different_ciphertext_each_time(): void
    {
        $enc1 = $this->cast->set($this->model, 'field', 'same', []);
        $enc2 = $this->cast->set($this->model, 'field', 'same', []);

        // Laravel's encryption includes a random IV, so ciphertexts differ
        $this->assertNotEquals($enc1, $enc2);
        // But both decrypt to the same value
        $this->assertEquals('same', Crypt::decryptString($enc1));
        $this->assertEquals('same', Crypt::decryptString($enc2));
    }
}
