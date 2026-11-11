<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class PrivacidadeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('site/privacidade');
    }
}
