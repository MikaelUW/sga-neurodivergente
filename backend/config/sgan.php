<?php

return [
    /*
    | Chave do blind index usado para busca em campos cifrados.
    | Deve ser FIXA e secreta (como a APP_KEY). Se vazia, cai para APP_KEY.
    | Em produção defina BLIND_INDEX_KEY antes de popular dados — mudá-la
    | depois invalida os hashes de busca já gravados.
    */
    'blind_index_key' => env('BLIND_INDEX_KEY', ''),

    /*
    | Comportamento quando a decifragem de um dado sensível falha
    | (normalmente indica APP_KEY trocada ou dado corrompido).
    |
    |  false (padrão) -> FALHA VISÍVEL: lança exceção. Recomendado em
    |                    produção; evita exibir lixo cifrado como dado válido.
    |  true           -> TOLERANTE: devolve o valor bruto e apenas loga.
    |                    Útil em desenvolvimento/depuração.
    |
    | Default seguro: tolerante apenas fora de produção.
    */
    'decrypt_fail_soft' => env('SGAN_DECRYPT_FAIL_SOFT', env('APP_ENV', 'production') !== 'production'),
];
