<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
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
            // Falha ao decifrar normalmente indica APP_KEY trocada ou dado
            // corrompido. SEMPRE registramos para auditoria/alerta — esse log
            // é um sinal crítico de problema de chave e deve disparar alerta.
            Log::error('Falha ao decifrar dado sensível (possível APP_KEY trocada ou dado corrompido).', [
                'erro' => $e->getMessage(),
            ]);

            // Comportamento por ambiente:
            // - Produção (padrão): FALHAR de forma visível. Devolver o texto
            //   cifrado bruto mascararia corrupção de chave e exibiria lixo
            //   ilegível como se fosse um dado válido para quem opera o sistema.
            // - Desenvolvimento: tolerar para não derrubar a aplicação enquanto
            //   se depura. Controlável via SGAN_DECRYPT_FAIL_SOFT.
            $failSoft = (bool) config('sgan.decrypt_fail_soft');

            if (! $failSoft) {
                throw new RuntimeException(
                    'Falha crítica de segurança: não foi possível decifrar um dado sensível. '
                    . 'Verifique a integridade da APP_KEY. Operação cancelada.'
                );
            }

            return $textoCifrado;
        }
    }

    /**
     * Gera um blind index determinístico (HMAC-SHA256) para permitir busca
     * por igualdade sobre campos cifrados, sem expor o texto claro.
     *
     * O texto é normalizado (trim + lowercase) antes do hash. A chave vem
     * de BLIND_INDEX_KEY (ou, como fallback, da APP_KEY).
     */
    public function blindIndex(?string $texto): ?string
    {
        if (is_null($texto) || trim($texto) === '') {
            return null;
        }

        $normalizado = mb_strtolower(trim($texto));
        $chave = (string) (config('sgan.blind_index_key') ?: config('app.key'));

        return hash_hmac('sha256', $normalizado, $chave);
    }
}
