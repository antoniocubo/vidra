<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

final class LaboratorioModel extends Model
{
    protected $table = 'laboratorios';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
