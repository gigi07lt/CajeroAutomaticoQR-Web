<?php

// Impedir la ejecucion desde el navegador.

if (PHP_SAPI !== "cli") {

    http_response_code(403);

    exit("Acceso no permitido.");
}

require_once __DIR__ .
    "/../config/database.php";

require_once __DIR__ .
    "/../models/Cliente.php";

require_once __DIR__ .
    "/../services/ClienteService.php";

require_once __DIR__ .
    "/../controllers/ClienteController.php";

// Contadores de pruebas.

$aprobadas = 0;

$fallidas = 0;

// Funcion reutilizable para comprobar resultados.

function comprobar(
    bool $condicion,
    string $descripcion
): void
{
    global $aprobadas, $fallidas;

    if ($condicion) {

        $aprobadas++;

        echo "[OK] " .
            $descripcion . PHP_EOL;

    } else {

        $fallidas++;

        echo "[ERROR] " .
            $descripcion . PHP_EOL;
    }
}

// Generar una cedula temporal disponible.

function generarCedula(Cliente $modelo): string
{
    do {

        $cedula = "99" .
            str_pad(
                (string) random_int(0, 99999999),
                8,
                "0",
                STR_PAD_LEFT
            );

    } while (
        $modelo->buscarPorCedula($cedula) !== null
    );

    return $cedula;
}

// Generar un numero de cuenta disponible.

function generarCuenta(PDO $conexion): string
{
    do {

        $numero = "98" .
            str_pad(
                (string) random_int(0, 99999999),
                8,
                "0",
                STR_PAD_LEFT
            );

        $consulta = $conexion->prepare(
            "SELECT COUNT(*)
             FROM cuentas
             WHERE numero = ?"
        );

        $consulta->execute([$numero]);

        $existe = (int) $consulta->fetchColumn();

    } while ($existe > 0);

    return $numero;
}

// Recuperar los registros originales de las tablas.

function obtenerEstadoBD(PDO $conexion): array
{
    return [

        "clientes" => $conexion->query(
            "SELECT * FROM clientes ORDER BY id"
        )->fetchAll(),

        "usuarios" => $conexion->query(
            "SELECT * FROM usuarios ORDER BY id"
        )->fetchAll(),

        "cuentas" => $conexion->query(
            "SELECT * FROM cuentas ORDER BY numero"
        )->fetchAll(),

        "tarjetas" => $conexion->query(
            "SELECT * FROM tarjetas ORDER BY codigo_qr"
        )->fetchAll(),

        "movimientos" => $conexion->query(
            "SELECT * FROM movimientos ORDER BY id"
        )->fetchAll()

    ];
}

// Encabezado de ejecucion.

echo PHP_EOL;

echo "PRUEBAS DEL CRUD DE CLIENTES" . PHP_EOL;

echo "========================================"
    . PHP_EOL;

$conexion = null;

try {

    // =====================================
    // PREPARACION DEL ENTORNO
    // =====================================

    $conexion = obtenerConexion();

    $modelo = new Cliente($conexion);

    $servicio = new ClienteService($conexion);

    $controlador = new ClienteController($conexion);

    $estadoInicial = obtenerEstadoBD($conexion);

    $totalInicial = $modelo->contar();

    $conexion->beginTransaction();

    echo PHP_EOL;

    echo "1. PRUEBAS DEL MODELO" . PHP_EOL;

    echo "----------------------------------------"
        . PHP_EOL;

    // =====================================
    // 1. PRUEBAS DEL MODELO
    // =====================================

    $cedulaModelo = generarCedula($modelo);

    $idModelo = $modelo->crear(
        "Prueba Modelo Temporal",
        $cedulaModelo
    );

    comprobar(
        $idModelo > 0,
        "Creacion de cliente desde el modelo"
    );

    comprobar(
        $modelo->contar() === $totalInicial + 1,
        "Incremento del total de clientes"
    );

    $cliente = $modelo->buscarPorId($idModelo);

    comprobar(
        $cliente !== null &&
        $cliente["nombres"] ===
            "Prueba Modelo Temporal",
        "Busqueda del cliente por ID"
    );

    $cliente = $modelo->buscarPorCedula(
        $cedulaModelo
    );

    comprobar(
        $cliente !== null &&
        (int) $cliente["id"] === $idModelo,
        "Busqueda del cliente por cedula"
    );

    comprobar(
        $modelo->contar($cedulaModelo) === 1,
        "Conteo filtrado por cedula"
    );

    $resultados = $modelo->listar(
        $cedulaModelo
    );

    comprobar(
        count($resultados) === 1 &&
        (int) $resultados[0]["id"] === $idModelo,
        "Listado filtrado por cedula"
    );

    $resultados = $modelo->listar(
        "Prueba Modelo Temporal"
    );

    comprobar(
        in_array(
            $idModelo,
            array_map(
                "intval",
                array_column($resultados, "id")
            ),
            true
        ),
        "Busqueda por nombres del cliente"
    );

    comprobar(
        $modelo->actualizar(
            $idModelo,
            "Prueba Modelo Actualizado"
        ),
        "Actualizacion de nombres"
    );

    $cliente = $modelo->buscarPorId($idModelo);

    comprobar(
        $cliente !== null &&
        $cliente["nombres"] ===
            "Prueba Modelo Actualizado",
        "Persistencia de los nombres actualizados"
    );

    comprobar(
        $cliente !== null &&
        $cliente["cedula"] === $cedulaModelo,
        "Conservacion de la cedula original"
    );

    comprobar(
        $modelo->obtenerUsuario($idModelo) === null,
        "Cliente sin usuario asociado"
    );

    comprobar(
        count(
            $modelo->obtenerCuentas($idModelo)
        ) === 0,
        "Cliente sin cuentas asociadas"
    );

    comprobar(
        !$modelo->tieneDependencias($idModelo),
        "Deteccion de cliente sin dependencias"
    );

    comprobar(
        $modelo->eliminar($idModelo),
        "Eliminacion de cliente sin dependencias"
    );

    comprobar(
        $modelo->buscarPorId($idModelo) === null,
        "Verificacion del cliente eliminado"
    );

    comprobar(
        $modelo->contar() === $totalInicial,
        "Restauracion del conteo del modelo"
    );

    // =====================================
    // 2. PRUEBAS DEL SERVICIO
    // =====================================

    echo PHP_EOL;

    echo "2. PRUEBAS DEL SERVICIO" . PHP_EOL;

    echo "----------------------------------------"
        . PHP_EOL;

    $cedulaServicio = generarCedula($modelo);

    $resultado = $servicio->registrar(
        "Prueba Servicio Temporal",
        $cedulaServicio
    );

    comprobar(
        $resultado["exito"] === true,
        "Registro valido mediante el servicio"
    );

    if (!$resultado["exito"]) {

        throw new RuntimeException(
            "No se pudo preparar el cliente de prueba."
        );
    }

    $idServicio = (int) $resultado["datos"]["id"];

    $resultado = $servicio->registrar(
        "Otro Cliente",
        $cedulaServicio
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de cedula duplicada"
    );

    $resultado = $servicio->registrar(
        "",
        generarCedula($modelo)
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de nombres vacios"
    );

    $resultado = $servicio->registrar(
        "     ",
        generarCedula($modelo)
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de nombres con solo espacios"
    );

    $resultado = $servicio->registrar(
        "Cliente123",
        generarCedula($modelo)
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de numeros en los nombres"
    );

    $resultado = $servicio->registrar(
        "@#$%",
        generarCedula($modelo)
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de nombres con simbolos invalidos"
    );

    $resultado = $servicio->registrar(
        str_repeat("A", 101),
        generarCedula($modelo)
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de nombres mayores a 100 caracteres"
    );

    $resultado = $servicio->registrar(
        "Cliente Temporal",
        "12345"
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de cedula menor a diez digitos"
    );

    $resultado = $servicio->registrar(
        "Cliente Temporal",
        "12345678901"
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de cedula mayor a diez digitos"
    );

    $resultado = $servicio->registrar(
        "Cliente Temporal",
        "12345ABCDE"
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de letras en la cedula"
    );

    $resultado = $servicio->consultar(
        $idServicio
    );

    comprobar(
        $resultado["exito"] === true &&
        $resultado["datos"]["cliente"]["cedula"] ===
            $cedulaServicio,
        "Consulta individual mediante el servicio"
    );

    comprobar(
        $resultado["datos"]["usuario"] === null &&
        count($resultado["datos"]["cuentas"]) === 0,
        "Consulta de relaciones vacias"
    );

    comprobar(
        $resultado["datos"]["puede_eliminar"] === true,
        "Cliente disponible para eliminacion"
    );

    $resultado = $servicio->actualizar(
        $idServicio,
        "  María    José    Pérez  "
    );

    comprobar(
        $resultado["exito"] === true,
        "Actualizacion con caracteres acentuados"
    );

    $cliente = $modelo->buscarPorId($idServicio);

    comprobar(
        $cliente !== null &&
        $cliente["nombres"] === "María José Pérez",
        "Normalizacion de espacios en nombres"
    );

    comprobar(
        $cliente !== null &&
        $cliente["cedula"] === $cedulaServicio,
        "Conservacion de cedula durante la edicion"
    );

    $resultado = $servicio->actualizar(
        $idServicio,
        "María José Pérez"
    );

    comprobar(
        $resultado["exito"] === true &&
        $resultado["mensaje"] ===
            "No se realizaron cambios.",
        "Actualizacion sin cambios"
    );

    $resultado = $servicio->actualizar(
        $idServicio,
        "Nombre123"
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de nombres invalidos al editar"
    );

    $cliente = $modelo->buscarPorId($idServicio);

    comprobar(
        $cliente !== null &&
        $cliente["nombres"] === "María José Pérez",
        "Conservacion de datos ante edicion invalida"
    );

    $resultado = $servicio->eliminar(
        $idServicio
    );

    comprobar(
        $resultado["exito"] === true,
        "Eliminacion valida mediante el servicio"
    );

    comprobar(
        $modelo->buscarPorId($idServicio) === null,
        "Confirmacion de eliminacion del registro"
    );

    $resultado = $servicio->consultar(
        $idServicio
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de consulta de cliente inexistente"
    );

    $resultado = $servicio->actualizar(
        $idServicio,
        "Cliente Inexistente"
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de actualizacion de cliente inexistente"
    );

    $resultado = $servicio->eliminar(
        $idServicio
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de eliminacion de cliente inexistente"
    );

    // =====================================
    // 3. PROTECCION DE DEPENDENCIAS
    // =====================================

    echo PHP_EOL;

    echo "3. PROTECCION DE DEPENDENCIAS" . PHP_EOL;

    echo "----------------------------------------"
        . PHP_EOL;

    // Crear un cliente con usuario asociado.

    $cedulaUsuario = generarCedula($modelo);

    $idConUsuario = $modelo->crear(
        "Prueba Dependencia Usuario",
        $cedulaUsuario
    );

    $consulta = $conexion->prepare(
        "INSERT INTO usuarios
        (
            id_cliente,
            usuario,
            clave_hash,
            rol,
            estado,
            intentos_fallidos,
            bloqueado
        )
        VALUES (?, ?, ?, 'CLIENTE', 1, 0, 0)"
    );

    $consulta->execute([

        $idConUsuario,

        $cedulaUsuario,

        password_hash(
            "1234",
            PASSWORD_DEFAULT
        )

    ]);

    comprobar(
        $modelo->tieneDependencias($idConUsuario),
        "Deteccion de usuario asociado"
    );

    $resultado = $servicio->consultar(
        $idConUsuario
    );

    comprobar(
        $resultado["exito"] === true &&
        $resultado["datos"]["usuario"] !== null &&
        count($resultado["datos"]["cuentas"]) === 0,
        "Consulta de cliente con solo un usuario"
    );

    comprobar(
        !array_key_exists(
            "clave_hash",
            $resultado["datos"]["usuario"]
        ),
        "Proteccion del hash de autenticacion"
    );

    comprobar(
        $resultado["datos"]["puede_eliminar"] === false,
        "Restriccion de eliminacion por usuario"
    );

    $resultado = $servicio->eliminar(
        $idConUsuario
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de eliminacion con usuario activo"
    );

    comprobar(
        !$modelo->eliminar($idConUsuario),
        "Proteccion de usuario desde el modelo"
    );

    // Desactivar el usuario temporal.

    $consulta = $conexion->prepare(
        "UPDATE usuarios
         SET estado = 0
         WHERE id_cliente = ?"
    );

    $consulta->execute([$idConUsuario]);

    $resultado = $servicio->eliminar(
        $idConUsuario
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de eliminacion con usuario inactivo"
    );

    // Crear otro cliente con una cuenta asociada.

    $idConCuenta = $modelo->crear(
        "Prueba Dependencia Cuenta",
        generarCedula($modelo)
    );

    $numeroCuenta = generarCuenta($conexion);

    $consulta = $conexion->prepare(
        "INSERT INTO cuentas
        (
            numero,
            id_cliente,
            tipo,
            saldo,
            estado
        )
        VALUES (?, ?, 'AHORROS', 0, 1)"
    );

    $consulta->execute([

        $numeroCuenta,

        $idConCuenta

    ]);

    comprobar(
        $modelo->tieneDependencias($idConCuenta),
        "Deteccion de cuenta bancaria asociada"
    );

    $resultado = $servicio->consultar(
        $idConCuenta
    );

    comprobar(
        $resultado["exito"] === true &&
        $resultado["datos"]["usuario"] === null &&
        count($resultado["datos"]["cuentas"]) === 1,
        "Consulta de cliente con solo una cuenta"
    );

    comprobar(
        $resultado["datos"]["cuentas"][0]["numero"]
            === $numeroCuenta,
        "Recuperacion de la cuenta correspondiente"
    );

    comprobar(
        $resultado["datos"]["puede_eliminar"] === false,
        "Restriccion de eliminacion por cuenta"
    );

    $resultado = $servicio->eliminar(
        $idConCuenta
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de eliminacion con cuenta activa"
    );

    comprobar(
        !$modelo->eliminar($idConCuenta),
        "Proteccion de cuenta desde el modelo"
    );

    // Desactivar la cuenta temporal.

    $consulta = $conexion->prepare(
        "UPDATE cuentas
         SET estado = 0
         WHERE numero = ?"
    );

    $consulta->execute([$numeroCuenta]);

    $resultado = $servicio->eliminar(
        $idConCuenta
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de eliminacion con cuenta inactiva"
    );

    comprobar(
        $modelo->buscarPorId($idConUsuario) !== null &&
        $modelo->buscarPorId($idConCuenta) !== null,
        "Conservacion de clientes con dependencias"
    );

    // =====================================
    // 4. BUSQUEDA Y PAGINACION
    // =====================================

    echo PHP_EOL;

    echo "4. BUSQUEDA Y PAGINACION" . PHP_EOL;

    echo "----------------------------------------"
        . PHP_EOL;

    // Prefijo exclusivo para las pruebas.

    $prefijo = "Prueba Paginacion " .
        bin2hex(random_bytes(8));

    // Crear 23 clientes temporales.

    for ($i = 1; $i <= 23; $i++) {

        $modelo->crear(

            $prefijo . " " .
                str_pad(
                    (string) $i,
                    2,
                    "0",
                    STR_PAD_LEFT
                ),

            generarCedula($modelo)

        );
    }

    $pagina1 = $servicio->listar(
        $prefijo,
        1
    );

    comprobar(
        $pagina1["total"] === 23,
        "Conteo de 23 clientes temporales"
    );

    comprobar(
        $pagina1["limite"] === 10,
        "Limite de diez clientes por pagina"
    );

    comprobar(
        $pagina1["total_paginas"] === 3,
        "Calculo correcto de tres paginas"
    );

    comprobar(
        count($pagina1["clientes"]) === 10,
        "Primera pagina con diez clientes"
    );

    $pagina2 = $servicio->listar(
        $prefijo,
        2
    );

    comprobar(
        count($pagina2["clientes"]) === 10,
        "Segunda pagina con diez clientes"
    );

    $pagina3 = $servicio->listar(
        $prefijo,
        3
    );

    comprobar(
        count($pagina3["clientes"]) === 3,
        "Tercera pagina con tres clientes"
    );

    $todos = array_merge(

        $pagina1["clientes"],

        $pagina2["clientes"],

        $pagina3["clientes"]

    );

    comprobar(
        count(
            array_unique(
                array_column($todos, "id")
            )
        ) === 23,
        "Paginacion sin registros duplicados"
    );

    $nombres = array_column($todos, "nombres");

    $ordenados = $nombres;

    sort($ordenados, SORT_STRING);

    comprobar(
        $nombres === $ordenados,
        "Orden alfabetico de los resultados"
    );

    $resultado = $servicio->listar(
        $prefijo,
        999
    );

    comprobar(
        $resultado["pagina"] === 3,
        "Correccion de pagina superior al limite"
    );

    $resultado = $servicio->listar(
        $prefijo,
        -5
    );

    comprobar(
        $resultado["pagina"] === 1,
        "Correccion de pagina negativa"
    );

    $resultado = $servicio->listar(
        $prefijo . " Inexistente"
    );

    comprobar(
        $resultado["total"] === 0 &&
        count($resultado["clientes"]) === 0,
        "Busqueda sin coincidencias"
    );

    comprobar(
        $resultado["pagina"] === 1 &&
        $resultado["total_paginas"] === 1,
        "Paginacion sin resultados"
    );

    $resultado = $servicio->listar(
        $prefijo . "%"
    );

    comprobar(
        $resultado["total"] === 0,
        "Tratamiento literal del simbolo porcentaje"
    );

    $resultado = $servicio->listar(
        $prefijo . "_"
    );

    comprobar(
        $resultado["total"] === 0,
        "Tratamiento literal del guion bajo"
    );

    // =====================================
    // 5. PRUEBAS DEL CONTROLADOR
    // =====================================

    echo PHP_EOL;

    echo "5. PRUEBAS DEL CONTROLADOR" . PHP_EOL;

    echo "----------------------------------------"
        . PHP_EOL;

    comprobar(
        $controlador->obtenerId("1") === 1,
        "Conversion de identificador valido"
    );

    comprobar(
        $controlador->obtenerId(1) === 1,
        "Aceptacion de identificador entero"
    );

    comprobar(
        $controlador->obtenerId(null) === null,
        "Rechazo de identificador ausente"
    );

    comprobar(
        $controlador->obtenerId(0) === null,
        "Rechazo de identificador cero"
    );

    comprobar(
        $controlador->obtenerId(-1) === null,
        "Rechazo de identificador negativo"
    );

    comprobar(
        $controlador->obtenerId("abc") === null,
        "Rechazo de identificador alfabetico"
    );

    comprobar(
        $controlador->obtenerId("1.5") === null,
        "Rechazo de identificador decimal"
    );

    comprobar(
        $controlador->obtenerId([1]) === null,
        "Rechazo de identificador tipo arreglo"
    );

    comprobar(
        $controlador->obtenerId(true) === null,
        "Rechazo de identificador booleano"
    );

    $resultado = $controlador->listar([

        "buscar" => $prefijo,

        "pagina" => "2"

    ]);

    comprobar(
        $resultado["pagina"] === 2 &&
        count($resultado["clientes"]) === 10,
        "Listado paginado desde el controlador"
    );

    $resultado = $controlador->listar([

        "buscar" => $prefijo,

        "pagina" => "abc"

    ]);

    comprobar(
        $resultado["pagina"] === 1,
        "Correccion de parametro de pagina invalido"
    );

    $resultado = $controlador->listar([

        "buscar" => ["valor"],

        "pagina" => ["1"]

    ]);

    comprobar(
        $resultado["busqueda"] === "" &&
        $resultado["pagina"] === 1,
        "Tratamiento seguro de parametros manipulados"
    );

    $resultado = $controlador->consultar(
        $idConUsuario
    );

    comprobar(
        $resultado["exito"] === true &&
        (int) $resultado["datos"]["cliente"]["id"]
            === $idConUsuario,
        "Consulta valida desde el controlador"
    );

    $resultado = $controlador->consultar(
        "abc"
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de consulta con ID invalido"
    );

    $resultado = $controlador->consultar(
        $idServicio
    );

    comprobar(
        !$resultado["exito"],
        "Rechazo de consulta de cliente eliminado"
    );

    // =====================================
    // 6. RESTAURACION DE DATOS
    // =====================================

    echo PHP_EOL;

    echo "6. INTEGRIDAD DE LA BASE DE DATOS" . PHP_EOL;

    echo "----------------------------------------"
        . PHP_EOL;

    $conexion->rollBack();

    $estadoFinal = obtenerEstadoBD($conexion);

    foreach ($estadoInicial as $tabla => $registros) {

        comprobar(

            $registros === $estadoFinal[$tabla],

            "Conservacion de registros de " . $tabla

        );
    }

    comprobar(
        $modelo->contar() === $totalInicial,
        "Conservacion del total original de clientes"
    );

    comprobar(
        $modelo->contar($prefijo) === 0,
        "Eliminacion de los registros de paginacion"
    );

    comprobar(
        !$conexion->inTransaction(),
        "Finalizacion correcta de la transaccion"
    );

} catch (Throwable $e) {

    $fallidas++;

    echo PHP_EOL;

    echo "[ERROR] " . $e->getMessage() . PHP_EOL;

} finally {

    // Garantizar el rollback ante errores.

    if (
        $conexion instanceof PDO &&
        $conexion->inTransaction()
    ) {

        $conexion->rollBack();

        echo PHP_EOL;

        echo "Transaccion de prueba revertida."
            . PHP_EOL;
    }
}

// =========================================
// RESUMEN FINAL
// =========================================

echo PHP_EOL;

echo "========================================"
    . PHP_EOL;

echo "RESULTADOS FINALES" . PHP_EOL;

echo "========================================"
    . PHP_EOL;

echo "Pruebas aprobadas: " .
    $aprobadas . PHP_EOL;

echo "Pruebas fallidas: " .
    $fallidas . PHP_EOL;

echo "Total de pruebas: " .
    ($aprobadas + $fallidas) . PHP_EOL;

if ($fallidas === 0) {

    echo PHP_EOL;

    echo "TODAS LAS PRUEBAS FUERON APROBADAS."
        . PHP_EOL;

    exit(0);
}

echo PHP_EOL;

echo "EXISTEN PRUEBAS PENDIENTES DE CORREGIR."
    . PHP_EOL;

exit(1);
