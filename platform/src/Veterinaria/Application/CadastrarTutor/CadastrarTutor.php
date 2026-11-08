<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\CadastrarTutor;

use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Shared\Domain\Transacao;
use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Telefone;
use Vidra\Veterinaria\Application\AdicionarAnimal\AdicionarAnimal;
use Vidra\Veterinaria\Application\AdicionarAnimal\AdicionarAnimalInput;
use Vidra\Veterinaria\Domain\Relogio;
use Vidra\Veterinaria\Domain\Tutor\Cpf;
use Vidra\Veterinaria\Domain\Tutor\Exceptions\CelularJaCadastrado;
use Vidra\Veterinaria\Domain\Tutor\Tutor;
use Vidra\Veterinaria\Domain\Tutor\TutorId;
use Vidra\Veterinaria\Domain\Tutor\TutorRepository;

final readonly class CadastrarTutor
{
    public function __construct(private TutorRepository $tutores, private LaboratorioAtual $laboratorio, private Transacao $transacao, private Relogio $relogio, private AdicionarAnimal $adicionarAnimal) {}

    public function __invoke(CadastrarTutorInput $input): TutorId
    {
        return $this->transacao->executar(function () use ($input): TutorId {
            if (count($input->animais) < 1 || count($input->animais) > 2) {
                throw InvalidValue::for('animais', 'cadastre um ou dois animais');
            }
            $celular = new Telefone($input->celular);
            $existente = $this->tutores->porCelular($celular);
            if ($existente !== null) {
                throw CelularJaCadastrado::para($existente->nome());
            }
            $tutor = Tutor::registrar($this->tutores->nextIdentity(), $this->laboratorio->id(), $input->nome, $celular, $input->email === null ? null : new Email($input->email), $input->cpf === null ? null : new Cpf($input->cpf), $input->cep);
            if ($input->autorizaEmail) {
                $tutor->autorizarLaudoPorEmail($input->atendenteId, $this->relogio->agora());
            }
            $this->tutores->save($tutor);
            foreach ($input->animais as $animal) {
                ($this->adicionarAnimal)(new AdicionarAnimalInput($tutor->id->value, $animal));
            }

            return $tutor->id;
        });
    }
}
