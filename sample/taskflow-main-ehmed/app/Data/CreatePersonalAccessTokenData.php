<?php

namespace App\Data;

final readonly class CreatePersonalAccessTokenData
{
    public function __construct(
        public string $email,
        public string $password,
        public string $deviceName,
    ) {}

    /**
     * @param  array{email: string, password: string, device_name: string}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            email: $validated['email'],
            password: $validated['password'],
            deviceName: $validated['device_name'],
        );
    }
}
