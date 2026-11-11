<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

it('guarda os laudos num disco privado', function (): void {
    $config = config('filesystems.disks.laudos');

    expect($config)->toHaveKey('driver')
        ->and($config)->not->toHaveKey('url')
        ->and($config['visibility'] ?? 'private')->toBe('private')
        ->and(Storage::disk('laudos'))->not->toBeNull();
});

it('usa o S3 de sa-east-1 quando LAUDOS_DISK é s3', function (): void {
    $anterior = getenv('LAUDOS_DISK');
    putenv('LAUDOS_DISK=s3');
    $_ENV['LAUDOS_DISK'] = 's3';

    try {
        $config = (require config_path('filesystems.php'))['disks']['laudos'];
    } finally {
        $anterior === false ? putenv('LAUDOS_DISK') : putenv('LAUDOS_DISK='.$anterior);
        unset($_ENV['LAUDOS_DISK']);
    }

    expect($config['driver'])->toBe('s3')
        ->and($config['region'])->toBe('sa-east-1')
        ->and($config)->not->toHaveKey('visibility');
});

it('só deixa abrir o Horizon quem está em HORIZON_EMAILS', function (): void {
    config(['horizon.emails' => 'dono@vidralab.com.br, outro@vidralab.com.br']);

    try {
        $dono = User::factory()->make(['email' => 'outro@vidralab.com.br']);
        $intruso = User::factory()->make(['email' => 'alguem@clinica.com.br']);

        expect(Gate::forUser($dono)->allows('viewHorizon'))->toBeTrue()
            ->and(Gate::forUser($intruso)->allows('viewHorizon'))->toBeFalse()
            ->and(Gate::allows('viewHorizon'))->toBeFalse();
    } finally {
        config(['horizon.emails' => '']);
    }
});

it('responde 403 no /horizon para usuário fora da lista em produção', function (): void {
    $this->app['env'] = 'production';

    $this->actingAs(User::factory()->create())->get('/horizon')->assertForbidden();
});
