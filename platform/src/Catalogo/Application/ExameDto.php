<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Application;

use Vidra\Catalogo\Domain\Exame\Exame;

final readonly class ExameDto
{
    public string $id;

    public string $codigo;

    public string $nome;

    public string $setor;

    public string $material;

    public ?string $metodo;

    public int $prazo_horas;

    public int $preco_centavos;

    public bool $ativo;

    public int $contagemAnalitos;

    public int $perfisCobertos;

    /** @var list<array{id:string,nome:string,unidade:?string,casas_decimais:int,faixas:list<array{perfil:string,minimo:?string,maximo:?string,criticoBaixo:?string,criticoAlto:?string}>}> */
    public array $analitos;

    public function __construct(Exame $exame)
    {
        $this->id = $exame->id->value;
        $this->codigo = $exame->codigo->value;
        $this->nome = $exame->nome;
        $this->setor = $exame->setor->value;
        $this->material = $exame->material;
        $this->metodo = $exame->metodo;
        $this->prazo_horas = $exame->prazoHoras;
        $this->preco_centavos = $exame->precoCentavos;
        $this->ativo = $exame->ativo;
        $this->contagemAnalitos = count($exame->analitos);
        $perfis = [];
        $analitos = [];
        foreach ($exame->analitos as $a) {
            $faixas = [];
            foreach ($a->faixas as $f) {
                $perfis[$f->perfil] = true;
                $faixas[] = ['perfil' => $f->perfil, 'minimo' => $f->minimo?->value, 'maximo' => $f->maximo?->value, 'criticoBaixo' => $f->criticoBaixo?->value, 'criticoAlto' => $f->criticoAlto?->value];
            }
            $analitos[] = ['id' => $a->id->value, 'nome' => $a->nome, 'unidade' => $a->unidade, 'casas_decimais' => $a->casasDecimais, 'faixas' => $faixas];
        }
        $this->analitos = $analitos;
        $this->perfisCobertos = count($perfis);
    }
}
