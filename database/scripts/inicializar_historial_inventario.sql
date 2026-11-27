DROP PROCEDURE IF EXISTS inicializar_historial_inventario;

DELIMITER $$

CREATE PROCEDURE inicializar_historial_inventario()
BEGIN

    DECLARE v_inicializado INT DEFAULT NULL;
    DECLARE v_fecha_corte DATETIME;
    DECLARE v_total_registros INT DEFAULT 0;
    DECLARE v_total_movimientos INT DEFAULT 0;
    DECLARE v_total_esperado INT DEFAULT 0;
    DECLARE v_inconsistencias INT DEFAULT 0;

    -- Revertir todas las operaciones si ocurre un error.
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- ==========================================
    -- 1. BLOQUEAR CONFIGURACIÓN
    -- ==========================================

    SELECT inicializado
    INTO v_inicializado
    FROM configuracion_historial_inventario
    WHERE id = 1
    FOR UPDATE;

    IF v_inicializado IS NULL THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'No existe la configuracion del historial.';

    END IF;

    -- ==========================================
    -- 2. EVITAR DOBLE INICIALIZACIÓN
    -- ==========================================

    IF v_inicializado = 1 THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'El historial de inventario ya fue inicializado.';

    END IF;

    -- ==========================================
    -- 3. VERIFICAR MOVIMIENTOS EXISTENTES
    -- ==========================================

    SELECT COUNT(*)
    INTO v_total_movimientos
    FROM movimientos_inventario;

    IF v_total_movimientos > 0 THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Existen movimientos previos. Revisar antes de inicializar.';

    END IF;

    -- ==========================================
    -- 4. ESTABLECER FECHA DE CORTE
    -- Hora de Bolivia: UTC-4
    -- ==========================================

    SET v_fecha_corte = DATE_SUB(
        UTC_TIMESTAMP(),
        INTERVAL 4 HOUR
    );

    -- ==========================================
    -- 5. CONTAR REGISTROS ESPERADOS
    -- ==========================================

    SELECT COUNT(*)
    INTO v_total_esperado
    FROM (
        SELECT idarticulo, idalmacen
        FROM inventarios
        GROUP BY idarticulo, idalmacen
    ) AS inventario_agrupado;

    -- ==========================================
    -- 6. GENERAR STOCK INICIAL
    -- ==========================================

    INSERT INTO movimientos_inventario (
        idarticulo,
        idalmacen,
        idusuario,
        tipo_movimiento,
        cantidad,
        stock_anterior,
        stock_actual,
        tipo_referencia,
        idreferencia,
        iddetalle_referencia,
        observacion,
        fecha_movimiento,
        created_at,
        updated_at
    )
    SELECT
        idarticulo,
        idalmacen,
        NULL,
        'STOCK_INICIAL',
        SUM(saldo_stock),
        0,
        SUM(saldo_stock),
        'INICIALIZACION',
        1,
        NULL,
        'Stock inicial del historial de inventario',
        v_fecha_corte,
        v_fecha_corte,
        v_fecha_corte
    FROM inventarios
    GROUP BY
        idarticulo,
        idalmacen;

    SET v_total_registros = ROW_COUNT();

    -- ==========================================
    -- 7. VALIDAR CANTIDAD DE REGISTROS
    -- ==========================================

    IF v_total_registros <> v_total_esperado THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'La cantidad de registros iniciales no coincide.';

    END IF;

    -- ==========================================
    -- 8. VALIDAR SALDOS INICIALES
    -- ==========================================

    SELECT COUNT(*)
    INTO v_inconsistencias
    FROM (
        SELECT
            idarticulo,
            idalmacen,
            SUM(saldo_stock) AS stock
        FROM inventarios
        GROUP BY idarticulo, idalmacen
    ) AS inv
    LEFT JOIN movimientos_inventario AS mov
        ON mov.idarticulo = inv.idarticulo
        AND mov.idalmacen = inv.idalmacen
        AND mov.tipo_movimiento = 'STOCK_INICIAL'
    WHERE mov.id IS NULL
        OR mov.stock_actual <> inv.stock
        OR mov.cantidad <> inv.stock
        OR mov.stock_anterior <> 0;

    IF v_inconsistencias > 0 THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Se encontraron inconsistencias en el stock inicial.';

    END IF;

    -- ==========================================
    -- 9. GUARDAR CONFIGURACIÓN DEFINITIVA
    -- ==========================================

    UPDATE configuracion_historial_inventario
    SET
        fecha_corte = v_fecha_corte,
        inicializado = 1,
        total_registros = v_total_registros,
        updated_at = v_fecha_corte
    WHERE id = 1;

    COMMIT;

    -- ==========================================
    -- 10. MOSTRAR RESULTADO
    -- ==========================================

    SELECT
        'Historial inicializado correctamente' AS mensaje,
        v_fecha_corte AS fecha_corte,
        v_total_registros AS registros_generados,
        v_inconsistencias AS inconsistencias;

END$$

DELIMITER ;

CALL inicializar_historial_inventario();

DROP PROCEDURE IF EXISTS inicializar_historial_inventario;