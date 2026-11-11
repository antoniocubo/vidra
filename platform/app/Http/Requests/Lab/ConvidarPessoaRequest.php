<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Vidra\Acesso\Application\ConvidarPessoaInput;
use Vidra\Acesso\Domain\Papel;

final class ConvidarPessoaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['nome' => ['required', 'string', 'min:3', 'max:255'], 'email' => ['required', 'email', 'max:255'], 'papel' => ['required', Rule::enum(Papel::class)]];
    }

    public function toInput(): ConvidarPessoaInput
    {
        return new ConvidarPessoaInput($this->string('nome')->value(), $this->string('email')->value(), $this->string('papel')->value(), (int) $this->user()?->getAuthIdentifier());
    }
}
