<?php

declare(strict_types=1);

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Vidra\Pedidos\Application\AbrirPedido\AbrirPedidoInput;
use Vidra\Pedidos\Domain\Pedido\Prioridade;
use Vidra\Pedidos\Domain\Pedido\TipoDePagador;

final class AbrirPedidoRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'paciente_id' => ['required', 'uuid'],
            'solicitante_id' => ['required', 'uuid'],
            'pagador' => ['required', Rule::enum(TipoDePagador::class)],
            'clinica_id' => ['nullable', 'required_if:pagador,clinica', 'uuid'],
            'prioridade' => ['required', Rule::enum(Prioridade::class)],
            'exame_ids' => ['required', 'array', 'min:1', 'max:60'],
            'exame_ids.*' => ['uuid', 'distinct'],
            'observacao' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'paciente_id.required' => 'Escolha o paciente do pedido.',
            'solicitante_id.required' => 'Escolha quem pede os exames.',
            'clinica_id.required_if' => 'Escolha a clínica que recebe a fatura.',
            'exame_ids.required' => 'Adicione pelo menos um exame.',
            'exame_ids.min' => 'Adicione pelo menos um exame.',
        ];
    }

    public function toInput(): AbrirPedidoInput
    {
        $clinica = $this->string('pagador')->value() === TipoDePagador::Clinica->value && $this->filled('clinica_id') ? $this->string('clinica_id')->value() : null;
        /** @var list<string> $exames */
        $exames = array_values(array_map(strval(...), $this->array('exame_ids')));

        return new AbrirPedidoInput(
            $this->string('paciente_id')->value(),
            $this->string('solicitante_id')->value(),
            $this->string('pagador')->value(),
            $clinica,
            $this->string('prioridade')->value(),
            $exames,
            $this->filled('observacao') ? $this->string('observacao')->value() : null,
            (int) $this->user()?->getAuthIdentifier(),
        );
    }
}
