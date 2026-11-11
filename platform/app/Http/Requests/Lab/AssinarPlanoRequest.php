<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Vidra\Acesso\Application\Assinatura\AssinarPlanoInput;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\Plano;

final class AssinarPlanoRequest extends FormRequest
{
    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'plano' => ['required', Rule::enum(Plano::class)],
            'cobranca' => ['required', Rule::enum(Cobranca::class)],
        ];
    }

    public function toInput(): AssinarPlanoInput
    {
        return new AssinarPlanoInput($this->string('plano')->value(), $this->string('cobranca')->value());
    }
}
