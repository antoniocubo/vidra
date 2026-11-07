<?php

declare(strict_types=1);

namespace Vidra\Shared\Domain;

/**
 * Base de toda regra de negócio violada. A camada HTTP traduz para mensagem ao usuário.
 */
abstract class DomainException extends \DomainException {}
