<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Vidra\Acesso\Domain\LaboratorioDoUsuario;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            // Preenchidos pelo middleware 'laboratorio'; nas telas públicas ficam nulos.
            'laboratorio' => fn (): ?array => $this->laboratorio($request->attributes->get('laboratorio')),
            'laboratorios' => fn (): array => array_values(array_filter(array_map($this->laboratorio(...), (array) $request->attributes->get('laboratorios', [])))),
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
            ],
        ]);
    }

    /** @return array{id: string, nome: string, papel: string, eRt: bool}|null */
    private function laboratorio(mixed $laboratorio): ?array
    {
        return $laboratorio instanceof LaboratorioDoUsuario
            ? ['id' => $laboratorio->id->value, 'nome' => $laboratorio->nome, 'papel' => $laboratorio->papel, 'eRt' => $laboratorio->eRt]
            : null;
    }
}
