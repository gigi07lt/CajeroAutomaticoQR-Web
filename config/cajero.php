<?php
/** El ingreso manual se muestra solo explícitamente y en loopback. */
function qrManualPermitido(): bool {
    $config=require __DIR__.'/config.php';
    return ($config['qr_manual_desarrollo']??false)===true && in_array($_SERVER['REMOTE_ADDR']??'', ['127.0.0.1','::1','::ffff:127.0.0.1'],true);
}
/** El modo de captura es una preferencia de interfaz, NO una prueba de origen.
 * La autorización real depende de sesión, CSRF, propiedad y estado del QR/cuenta.
 */
function exigirModoQr(array $entrada): void {
    $modo=$entrada['modo_qr']??'manual';
    if ($modo==='camara') return;
    if ($modo==='manual' && qrManualPermitido()) return;
    throw new DomainException('Utiliza el lector QR para continuar.');
}
