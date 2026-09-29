<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CriptografiaService;

class CriptografiaServiceTest extends TestCase
{
    private CriptografiaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CriptografiaService();
    }

    public function test_cifrar_e_decifrar_texto_valido()
    {
        $textoOriginal = "Relato confidencial de comportamento";
        
        $textoCifrado = $this->service->cifrarDados($textoOriginal);
        $this->assertNotEquals($textoOriginal, $textoCifrado);

        $textoDecifrado = $this->service->decifrarDados($textoCifrado);
        $this->assertEquals($textoOriginal, $textoDecifrado);
    }

    public function test_cifrar_e_decifrar_nulo_ou_vazio()
    {
        $this->assertNull($this->service->cifrarDados(null));
        $this->assertEquals('', $this->service->cifrarDados(''));

        $this->assertNull($this->service->decifrarDados(null));
        $this->assertEquals('', $this->service->decifrarDados(''));
    }
}
