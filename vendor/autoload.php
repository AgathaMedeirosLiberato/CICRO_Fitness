<?php
// Autoloader mínimo (sem Composer) para as bibliotecas desta pasta vendor/.
// Se um dia você instalar o Composer, pode apagar este arquivo e rodar "composer install"
// (veja o composer.json na raiz do projeto): o resto do sistema continua igual.
spl_autoload_register(static function (string $classe): void {
    static $mapa = [
        'Piggly\\Pix\\'            => __DIR__ . '/piggly/php-pix/src/',
        'chillerlan\\QRCode\\'     => __DIR__ . '/chillerlan/php-qrcode/src/',
        'chillerlan\\Settings\\'   => __DIR__ . '/chillerlan/php-settings-container/src/',
        'Brazanation\\Documents\\' => __DIR__ . '/brazanation/documents/src/',
    ];
    foreach ($mapa as $prefixo => $pasta) {
        if (strncmp($classe, $prefixo, strlen($prefixo)) === 0) {
            $arquivo = $pasta . str_replace('\\', '/', substr($classe, strlen($prefixo))) . '.php';
            if (is_file($arquivo)) {
                require $arquivo;
            }
            return;
        }
    }
});
