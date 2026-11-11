<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Vidra\Bancada\Application\DigitarResultadosInput;

final class DigitarResultadosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,list<string>> */
    public function rules(): array
    {
        return ['valores' => ['required', 'array', 'min:1'], 'valores.*' => ['required', 'array:valor,texto', 'required_array_keys:valor,texto'], 'valores.*.valor' => ['nullable', 'string', 'max:20'], 'valores.*.texto' => ['nullable', 'string', 'max:2000']];
    }

    public function toInput(string $item): DigitarResultadosInput
    {
        /** @var array<string,array{valor:?string,texto:?string}> $valores */ $valores = $this->validated('valores');
        $user = $this->user('web');
        assert($user !== null);

        return new DigitarResultadosInput($item, $valores, (int) $user->getAuthIdentifier(), $user->name);
    }
}
