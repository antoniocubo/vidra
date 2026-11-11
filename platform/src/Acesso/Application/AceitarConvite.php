<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application;

use Vidra\Acesso\Domain\ContasDeUsuario;
use Vidra\Acesso\Domain\ConviteInvalido;
use Vidra\Acesso\Domain\ConviteRepository;
use Vidra\Acesso\Domain\Equipe;
use Vidra\Acesso\Domain\JaFazParteDaEquipe;
use Vidra\Acesso\Domain\Relogio;
use Vidra\Acesso\Domain\TransacaoDaEquipe;
use Vidra\Auditoria\Application\RegistrarNaTrilha;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;

final readonly class AceitarConvite
{
    public function __construct(private ConviteRepository $convites, private Equipe $equipe, private ContasDeUsuario $contas, private TransacaoDaEquipe $transacao, private Relogio $relogio, private RegistrarNaTrilha $trilha) {}

    public function __invoke(AceitarConviteInput $input): ConviteAceito
    {
        $hash = hash('sha256', $input->token);
        $inicial = $this->convites->findByTokenHash($hash) ?? throw ConviteInvalido::para();

        // O aceite público descobre o laboratório pelo hash e revalida sob o mesmo bloqueio usado pela equipe.
        $aceito = $this->transacao->executar($inicial->laboratorioId, function () use ($hash, $input): ConviteAceito {
            $convite = $this->convites->findByTokenHash($hash) ?? throw ConviteInvalido::para();
            $convite->conferirValidade($this->relogio->agora());
            if ($input->usuarioId !== null && strtolower(trim($input->emailAutenticado ?? '')) !== $convite->email) {
                throw InvalidValue::for('form', 'Entre com a conta que recebeu este convite.');
            }
            if ($this->equipe->contemEmail($convite->laboratorioId, $convite->email)) {
                throw JaFazParteDaEquipe::para();
            }
            $usuarioId = $input->usuarioId;
            if ($usuarioId === null) {
                if ($this->contas->existe($convite->email)) {
                    throw InvalidValue::for('form', 'Este e-mail já tem uma conta. Entre com sua senha para aceitar.');
                }
                if (mb_strlen(trim($input->nome)) < 3 || mb_strlen($input->senha) < 10) {
                    throw InvalidValue::for('form', 'Informe seu nome e uma senha com pelo menos 10 caracteres.');
                }
                $usuarioId = $this->contas->criar(trim($input->nome), $convite->email, $input->senha);
            }
            $this->equipe->adicionar($convite->laboratorioId, $usuarioId, $convite->papel);
            $convite->aceitar($this->relogio->agora());
            $this->convites->save($convite);

            return new ConviteAceito($usuarioId, $convite->laboratorioId);
        });
        $this->trilha->aceiteDeConvite($aceito->laboratorioId, $aceito->usuarioId, trim($input->nome) ?: $inicial->nome);

        return $aceito;
    }
}
