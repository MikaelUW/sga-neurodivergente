<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Exception;
use RuntimeException;

class CriptografiaService
{
    /**
     * Cifra uma string de texto sensível. Lança exceção em caso de erro para impedir gravação de texto claro (LGPD Art. 46).
     *
     * @param string|null $texto
     * @return string|null
     * @throws RuntimeException
     */
    public function cifrarDados(?string $texto): ?string
    {
        if (is_null($texto) || $texto === '') {
            return $texto;
        }

        try {
            return Crypt::encryptString($texto);
        } catch (Exception $e) {
            throw new RuntimeException('Falha crítica de segurança: Nao foi possível criptografar o dado sensível. Operacao cancelada para conformidade com a LGPD.');
        }
    }

    /**
     * Decifra uma string previamente cifrada.
     *
     * @param string|null $textoCifrado
     * @return string|null
     */
    public function decifrarDados(?string $textoCifrado): ?string
    {
        if (is_null($textoCifrado) || $textoCifrado === '') {
            return $textoCifrado;
        }

        try {
            return Crypt::decryptString($textoCifrado);
        } catch (Exception $e) {
            // Caso ja esteja em texto claro ou falhe a decifragem
            return $textoCifrado;
        }
    }
}
