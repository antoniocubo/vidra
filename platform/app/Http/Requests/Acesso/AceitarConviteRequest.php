<?php

declare(strict_types=1);

namespace App\Http\Requests\Acesso;

use Illuminate\Foundation\Http\FormRequest;
use Vidra\Acesso\Application\AceitarConviteInput;

final class AceitarConviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->user() === null ? ['nome' => ['required', 'string', 'min:3', 'max:255'], 'password' => ['required', 'string', 'min:10', 'max:255', 'confirmed']] : [];
    }

    public function toInput(string $token): AceitarConviteInput
    {
        return new AceitarConviteInput($token, $this->string('nome')->value(), $this->string('password')->value(), $this->user() === null ? null : (int) $this->user()->getAuthIdentifier(), $this->user()?->email);
    }
}
