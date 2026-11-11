<?php

declare(strict_types=1);

use Vidra\Auditoria\Infrastructure\Persistence\RegistroDeAuditoriaModel;

uses(Tests\TestCase::class);

it('o model recusa atualizar um evento existente antes de executar SQL', function (): void {
    $model = new RegistroDeAuditoriaModel;
    $model->exists = true;
    $model->setRawAttributes(['id' => 'registro', 'detalhe' => 'Original'], true);
    $model->setAttribute('detalhe', 'Alterado');
    $model->save();
})->throws(LogicException::class, 'A trilha de auditoria é imutável.');

it('o model recusa apagar um evento existente antes de executar SQL', function (): void {
    $model = new RegistroDeAuditoriaModel;
    $model->exists = true;
    $model->setAttribute('id', 'registro');
    $model->delete();
})->throws(LogicException::class, 'A trilha de auditoria é imutável.');
