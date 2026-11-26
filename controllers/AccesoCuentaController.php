<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/cajero.php';
require_once __DIR__.'/../services/CajeroService.php';
require_once __DIR__.'/../services/SesionService.php';
require_once __DIR__.'/../services/CsrfService.php';
require_once __DIR__.'/../services/LimiteIntentosService.php';

/** Valida una cuenta de un cliente ya autenticado. No crea sesiones. */
class AccesoCuentaController
{
    public function validar(array $entrada, array $actual, PDO $db): array
    {
        if (($actual['rol'] ?? '') !== 'CLIENTE' || empty($actual['id_cliente'])) {
            throw new DomainException('Acceso no disponible.');
        }
        if (!(new LimiteIntentosService($db))->permitir($_SERVER['REMOTE_ADDR']??'desconocida')) {
            throw new DomainException('Demasiados intentos. Espera un minuto.');
        }
        exigirModoQr($entrada);
        $numero=$entrada['numero']??null;
        $qr=$entrada['qr']??null;
        if (!is_string($numero)||!is_string($qr)) throw new DomainException('Datos de cuenta inválidos.');
        return (new CajeroService($db))->seleccion((int)$actual['id_cliente'], CajeroService::numero($numero), CajeroService::codigo($qr));
    }
}
