<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Conexões mantidas pelo RefreshDatabase: o acervo e as filas (ambos em memória nos testes).
     *
     * @var array<int, string|null>
     */
    protected $connectionsToTransact = [null, 'queue'];
}
