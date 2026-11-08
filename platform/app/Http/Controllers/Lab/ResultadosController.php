<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Validation\ValidationException;
use Vidra\Bancada\Domain\ItemNaoEncontrado;
use Vidra\Laudos\Domain\PedidoNaoEncontrado;
use Vidra\Shared\Domain\DomainException;

abstract class ResultadosController extends Controller
{
    /** @template T
     * @param Closure():T $acao
     * @return T */
    protected function executar(Closure $acao): mixed
    {
        try {
            return $acao();
        } catch (ItemNaoEncontrado|PedidoNaoEncontrado) {
            abort(404);
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['form' => $e->getMessage()]);
        }
    }

    protected function data(?DateTimeImmutable $data, string $formato = 'd/m/Y'): ?string
    {
        return $data?->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format($formato);
    }
}
