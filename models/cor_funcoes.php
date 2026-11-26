<?php

const CORES_PADRAO = [
    'Preto', 'Branco', 'Cinza', 'Bege', 'Marrom', 'Vermelho', 'Vinho', 'Rosa', 'Pink', 'Roxo',
    'Lilás', 'Azul', 'Azul-marinho', 'Verde', 'Amarelo', 'Laranja',
];
const COR_OUTRA = '__outra';

function cor_do_form($escolha, $digitada): ?string
{
    $cor = is_string($escolha) ? $escolha : '';
    if ($cor === COR_OUTRA) {
        $cor = is_string($digitada) ? $digitada : '';
    }
    $cor = trim(preg_replace('/\s+/u', ' ', $cor) ?? '');
    $cor = trim(mb_substr($cor, 0, 30));
    if ($cor === '') {
        return null;
    }
    foreach (CORES_PADRAO as $padrao) {
        if (mb_strtolower($padrao) === mb_strtolower($cor)) {
            return $padrao;
        }
    }
    return $cor;
}

function cor_visivel(?string $cor): string
{
    return ($cor === null || $cor === 'Padrão') ? '' : $cor;
}

function campo_cor(string $nome_lista, string $nome_texto, ?string $atual): string
{
    $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    $outra = $atual !== null && $atual !== '' && !in_array($atual, CORES_PADRAO, true);

    $html = '<span class="cor-campo"><select name="' . $e($nome_lista) . '" aria-label="Cor da foto">'
          . '<option value="">Sem cor</option>';
    foreach (CORES_PADRAO as $c) {
        $html .= '<option value="' . $e($c) . '"' . ($atual === $c ? ' selected' : '') . '>' . $e($c) . '</option>';
    }
    $html .= '<option value="' . COR_OUTRA . '"' . ($outra ? ' selected' : '') . '>Outra (digitar)…</option></select>'
           . '<input type="text" name="' . $e($nome_texto) . '" maxlength="30" placeholder="Digite a cor" aria-label="Digite a cor"'
           . ' value="' . ($outra ? $e($atual) : '') . '"' . ($outra ? ' required' : ' hidden') . '></span>';
    return $html;
}
