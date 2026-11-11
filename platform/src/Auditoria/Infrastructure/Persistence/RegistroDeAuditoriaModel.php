<?php

declare(strict_types=1);

namespace Vidra\Auditoria\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use Vidra\Shared\Infrastructure\Tenancy\PertenceAoLaboratorio;

final class RegistroDeAuditoriaModel extends Model
{
    use PertenceAoLaboratorio;

    protected $table = 'registros_auditoria';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected static function booted(): void
    {
        self::updating(static fn () => throw new LogicException('A trilha de auditoria é imutável.'));
        self::deleting(static fn () => throw new LogicException('A trilha de auditoria é imutável.'));
    }
}
