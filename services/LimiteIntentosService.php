<?php

class LimiteIntentosService
{
    private PDO $conexion;

    private const MAX_SOLICITUDES = 10;

    private const VENTANA_SEGUNDOS = 60;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function permitir(string $ip): bool
    {
        $clave = hash("sha256", "login:" . $ip);

        $ahora = time();

        try {

            $this->conexion->beginTransaction();

            // Crear el registro si todavia no existe.

            $stmt = $this->conexion->prepare(
                "INSERT INTO limites_acceso
                 (clave, intentos, inicio_ventana)
                 VALUES (?, 0, ?)
                 ON DUPLICATE KEY UPDATE clave = clave"
            );

            $stmt->execute([$clave, $ahora]);

            // Bloquear el registro durante la validacion.

            $stmt = $this->conexion->prepare(
                "SELECT intentos, inicio_ventana
                 FROM limites_acceso
                 WHERE clave = ?
                 FOR UPDATE"
            );

            $stmt->execute([$clave]);

            $datos = $stmt->fetch();

            $intentos = (int) $datos["intentos"];

            $inicio = (int) $datos["inicio_ventana"];

            // Reiniciar el contador si termino la ventana.

            if (
                $ahora - $inicio >=
                self::VENTANA_SEGUNDOS
            ) {
                $intentos = 0;
                $inicio = $ahora;
            }

            // Rechazar solicitudes adicionales.

            if ($intentos >= self::MAX_SOLICITUDES) {

                $this->conexion->commit();

                return false;
            }

            // Registrar la nueva solicitud.

            $stmt = $this->conexion->prepare(
                "UPDATE limites_acceso
                 SET intentos = ?,
                     inicio_ventana = ?
                 WHERE clave = ?"
            );

            $stmt->execute([
                $intentos + 1,
                $inicio,
                $clave
            ]);

            $this->conexion->commit();

            return true;

        } catch (Throwable $e) {

            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $e;
        }
    }
}
