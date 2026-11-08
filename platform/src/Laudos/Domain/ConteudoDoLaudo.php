<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

final readonly class ConteudoDoLaudo
{
    /**
     * @param  array{nome:string,endereco:string,telefone:string,cnpj:string}  $laboratorio
     * @param  array{nome:string,descricao:string,responsavel:string}  $paciente
     * @param  array{nome:string,registro:string}  $solicitante
     * @param  array{pedido:string,recebimento:?string,liberacao:string}  $datas
     * @param  list<array{nome:string,material:string,metodo:string,resultados:list<array{nome:string,valor:string,unidade:?string,referencia:string,classificacao:string,anterior:?string,anteriorEm:?string}>}>  $exames
     * @param  list<string>  $ressalvas
     * @param  list<string>  $avisos
     * @param  array{paciente:string,responsavel:string,solicitante:string}  $textos
     * @param  list<array{email:string,papel:string}>  $destinatarios
     */
    public function __construct(
        public string $id, public string $pedidoId, public string $laboratorioId,
        public string $numero, public string $codigo, public string $link,
        public array $laboratorio, public array $paciente, public array $solicitante,
        public string $protocolo, public array $datas, public array $exames,
        public array $ressalvas, public ?string $observacao, public array $avisos,
        public string $rtNome, public string $rtRegistro, public array $textos,
        public string $textoWhatsapp, public ?string $whatsapp, public array $destinatarios,
        public bool $resumoDiario, public bool $pdfPronto = false,
    ) {}

    public function nomeArquivo(): string
    {
        $nome = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $this->paciente['nome']) ?: 'paciente';
        $nome = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $nome) ?? 'paciente', '-'));

        return 'laudo-'.($nome ?: 'paciente').'-'.$this->numero.'.pdf';
    }
}
