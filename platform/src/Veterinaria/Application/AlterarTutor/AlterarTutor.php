<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\AlterarTutor;

use Vidra\Shared\Domain\Transacao;
use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Veterinaria\Domain\Relogio;
use Vidra\Veterinaria\Domain\Tutor\Cpf;
use Vidra\Veterinaria\Domain\Tutor\Exceptions\CelularJaCadastrado;
use Vidra\Veterinaria\Domain\Tutor\Exceptions\TutorNaoEncontrado;
use Vidra\Veterinaria\Domain\Tutor\TutorId;
use Vidra\Veterinaria\Domain\Tutor\TutorRepository;

final readonly class AlterarTutor
{
    public function __construct(private TutorRepository $tutores, private Relogio $relogio, private Transacao $transacao) {}

    public function __invoke(AlterarTutorInput $input): TutorId
    {
        return $this->transacao->executar(function () use ($input): TutorId {
            $tutor = $this->tutores->porId(new TutorId($input->tutorId)) ?? throw TutorNaoEncontrado::para();
            $celular = new Telefone($input->celular);
            $existente = $this->tutores->porCelular($celular);
            if ($existente !== null && $existente->id->value !== $tutor->id->value) {
                throw CelularJaCadastrado::para($existente->nome());
            }
            $tutor->alterarContato($input->nome, $celular, $input->email === null ? null : new Email($input->email), $input->cpf === null ? null : new Cpf($input->cpf), $input->cep);
            if ($input->autorizaEmail) {
                $tutor->autorizarLaudoPorEmail($input->atendenteId, $this->relogio->agora());
            } else {
                $tutor->retirarAutorizacao();
            }
            $this->tutores->save($tutor);

            return $tutor->id;
        });
    }
}
