<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Vidra\Laudos\Application\LiberarLaudoInput;

final class LiberarLaudoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,list<string>> */
    public function rules(): array
    {
        return ['observacao' => ['nullable', 'string', 'max:10000']];
    }

    public function toInput(string $pedido): LiberarLaudoInput
    {
        $user = $this->user();
        assert($user !== null);

        return new LiberarLaudoInput($pedido, (int) $user->getAuthIdentifier(), $user->name, $this->filled('observacao') ? $this->string('observacao')->value() : null);
    }
}
