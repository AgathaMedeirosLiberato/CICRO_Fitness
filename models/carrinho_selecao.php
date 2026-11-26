<?php

function carrinho_definir_selecao(array $ids): void
{
    $_SESSION['carrinho_sel'] = array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0)));
}

function carrinho_selecao(): ?array
{
    return isset($_SESSION['carrinho_sel']) && is_array($_SESSION['carrinho_sel']) ? $_SESSION['carrinho_sel'] : null;
}

function carrinho_marcas(array $ids): string
{
    return implode(',', array_fill(0, max(1, count($ids)), '?'));
}
