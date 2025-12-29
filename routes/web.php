<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(["middleware" => ["guest"]], function () {
    Route::get("/", "Auth\LoginController@showLoginForm");
    Route::post("/", "Auth\LoginController@login")->name("login");
});
Route::group(["middleware" => ["auth"]], function () {
    Route::post("/logout", "Auth\LoginController@logout")->name("logout");
    Route::get("/dashboard", "DashboardController");
    //Notificaciones
    Route::post("/notification/get", "NotificationController@get");

    Route::get("/main", function () {
        return view("contenido/contenido");
    })->name("main");

    Route::group(["middleware" => ["Almacenero"]], function () {
        Route::get("/categoria", "CategoriaController@index");
        Route::post("/categoria/registrar", "CategoriaController@store");
        Route::put("/categoria/actualizar", "CategoriaController@update");
        Route::put("/categoria/desactivar", "CategoriaController@desactivar");
        Route::put("/categoria/activar", "CategoriaController@activar");
        Route::get(
            "/categoria/selectCategoria",
            "CategoriaController@selectCategoria"
        );
        Route::get('/categoria/consultaProductosServicios', 'VentaController@sincronizarListaProductosServicios');
        Route::get('/categoria/consultaActividadEconomica', 'VentaController@sincronizarActividades');

        Route::get("/articulo", "ArticuloController@index");
        Route::get("/articulonewindex", "ArticuloController@index2");
        Route::post("/articulo/registrar", "ArticuloController@store");
        Route::put("/articulo/actualizar", "ArticuloController@update");
        Route::get('/articulo/detalle/{id}', 'ArticuloController@detalle');
        Route::put('/articulo/actualizar-descuento/{id}', 'ArticuloController@updateDescuento');
        Route::put("/articulo/desactivar", "ArticuloController@desactivar");
        Route::put("/articulo/activar", "ArticuloController@activar");
        Route::get(
            "/articulo/buscarArticulo",
            "ArticuloController@buscarArticulo"
        );
        Route::get(
            "/articulo/listarArticulo",
            "ArticuloController@listarArticulo"
        );
        Route::get("/articulo/reporteExcel", "ArticuloController@descargarExcel")->name(
            "articulos_pdf"
        );
        Route::get("/ajusteinv", "AjusteInventarioController@index");
        Route::post("/ajuste/registrar", "AjusteInventarioController@store");

        Route::get('/ajuste-inventario', 'AjusteInventarioController@index');
        Route::post('/ajuste-inventario/registrar', 'AjusteInventarioController@store');
        Route::get('/ajuste-inventario/obtenerStock', 'AjusteInventarioController@obtenerStock');
        Route::post('/ajuste/registrar-multiple', 'AjusteInventarioController@registrarMultiple');
        Route::get('/motivo', 'AjusteInventarioController@listarMotivo');
        Route::post('/motivo/registrar', 'AjusteInventarioController@registrarMotivo');
        Route::post('/ajustes-inventario/exportar-pdf', 'AjusteInventarioController@exportarPDF')->name('ajustes.exportar-pdf');


        Route::get('/proveedornewview', 'ProveedorController@index2');
        Route::get("/proveedor", "ProveedorController@index");
        Route::post("/proveedor/registrar", "ProveedorController@store");
        Route::put("/proveedor/actualizar", "ProveedorController@update");
        Route::get(
            "/proveedor/selectProveedor",
            "ProveedorController@selectProveedor"
        );
        Route::get("/proveedor/selectNombreProveedor", "ProveedorController@selectNombreProveedor");
        Route::get("/ingreso", "IngresoController@index");
        Route::post(
            "/ingreso/registrarIngreso",
            "IngresoController@registrarIngreso"
        );
        Route::post("/ingreso/registrar", "IngresoController@store");
        Route::put("/ingreso/desactivar", "IngresoController@desactivar");
        Route::get(
            "/ingreso/obtenerCabecera",
            "IngresoController@obtenerCabecera"
        );
        Route::get(
            "/ingreso/imprimir/{id}",
            "IngresoController@PDFNotaCompra"
        );
        Route::get(
            "/ingreso/obtenerDetalles",
            "IngresoController@obtenerDetalles"
        );
    });

    Route::group(["middleware" => ["Vendedor"]], function () {
        Route::get("/verificar-descuento", "VentaController@verificarDescuento");

        Route::get("/cliente", "ClienteController@index");
        Route::get("/clienteUbicacion", "ClienteController@indexClientesConSaldoCredito");
        Route::get("/cliente2", "ClienteController@indexConSaldoCredito");
        Route::post("/cliente/registrar", "ClienteController@store");
        Route::put("/cliente/actualizar", "ClienteController@update");
        Route::get("/cliente/selectCliente", "ClienteController@selectCliente");
        Route::get('/cliente/ubicaciones', "ClienteController@listarUbicaciones");

        Route::get(" /cliente/buscarDireccion", "ClienteController@buscarDireccion");

        Route::get("/cliente/usuario", "ClienteController@indexUsuario");
        Route::get(
            "/cliente/selectUusarioVend",
            "ClienteController@selectUsuarioVendedor"
        );
        Route::get(
            "/cliente/usuario/filtro",
            "ClienteController@indexUsuarioFiltro"
        );
        Route::get("/user-info", "ClienteController@getUserInfo");

        Route::get(
            "/articulo/buscarArticuloVenta",
            "ArticuloController@buscarArticuloVenta"
        );
        Route::get(
            "/articulo/listarArticuloVenta",
            "ArticuloController@listarArticuloVenta"
        );
        Route::get(
            "/articulo/verificarLotes",
            "ArticuloController@verificarLotes"
        );

        Route::get("/venta", "VentaController@index");
        Route::get("/ventaBuscar", "VentaController@indexBuscar");
        Route::post("/venta/registrar", "VentaController@store");
        Route::put("/venta/desactivar", "VentaController@desactivar");
        Route::get("/venta/obtenerCabecera", "VentaController@obtenerCabecera");
        Route::get("/venta/obtenerDetalles", "VentaController@obtenerDetalles");
        Route::get('/venta/obtenerCuotas', 'VentaController@obtenerCuotas');
        Route::get('/credito/cuotas/{idcredito}', 'VentaController@obtenerCuotasid');

        Route::get("/venta/pdf/{id}", "VentaController@pdf")->name("venta_pdf");
    });

    Route::group(["middleware" => ["Administrador"]], function () {
        Route::get(
            "/api/clientes/existe",
            "ClienteController@verificarExistencia"
        );
        Route::get("/verificar-descuento", "VentaController@verificarDescuento");

        Route::post("/qr/generarqr", "QrController@generarQr");
        Route::post("/qr/verificarestado", "QrController@verificarEstado");
        Route::post("/enviarWhatsapp", "WhatsappController@enviaReporte");
        Route::get("/ventas-diarias", "VentaController@reporteVentasDiarias");

        Route::get("/usuario/selectUsuario", "VentaController@selectUsuarios");
        Route::post("/venta/emitirRecibo", "VentaController@emitirRecibo");

        Route::get("/empresa", "EmpresaController@index");
        //Route::post('/empresa/registrar', 'EmpresaController@store');
        Route::put("/empresa/actualizar", "EmpresaController@update");
        // Route::put('/empresa/desactivar', 'EmpresaController@desactivar');
        // Route::put('/empresa/activar', 'EmpresaController@activar');
        Route::get("/empresa/selectEmpresa", "EmpresaController@selectEmpresa");
        Route::post("/empresa/actualizar", "EmpresaController@update");
        //Rutas de configuracion de trabajo
        // Route::get('/configuracion/saldos-negativos', 'ConfiguracionTrabajoController@obtenerSaldosNegativos');
        Route::get(
            "/configuracion/iva",
            "ConfiguracionTrabajoController@obtenerIva"
        );
        Route::get(
            "/configuracion/editar",
            "ConfiguracionTrabajoController@edit"
        );
        Route::get(
            "/configuracion/porcentajes",
            "ConfiguracionTrabajoController@getPorcentajes"
        );
        Route::put(
            "/configuracion/porcentajes",
            "ConfiguracionTrabajoController@PutPorcentajes"
        );
        Route::put(
            "/configuracion/actualizar",
            "ConfiguracionTrabajoController@update"
        );
        Route::get("/configuracion", "ConfiguracionTrabajoController@index");
        Route::get(
            "/configuracion/ruta-a-tu-endpoint-para-obtener-almacenes",
            "ConfiguracionTrabajoController@listarAlmacen"
        );
        Route::get(
            "/api/configuracion/almacen-predeterminado",
            "ConfiguracionTrabajoController@obtenerAlmacenPredeterminado"
        );

        Route::get("/backup", "BackupDbController@createBackup");
        Route::get("/categoria", "CategoriaController@index");
        Route::get("/categorianewview", "CategoriaController@index2");
        Route::post("/categoria/registrar", "CategoriaController@store");
        Route::put("/categoria/actualizar", "CategoriaController@update");
        Route::put("/categoria/desactivar", "CategoriaController@desactivar");
        Route::put("/categoria/activar", "CategoriaController@activar");
        Route::get(
            "/categoria/selectCategoria",
            "CategoriaController@selectCategoria"
        );
        //----importacion y exportacion--
        Route::get(
            "/linea/exportexcel",
            "CategoriaController@excelLinea"
        )->name("exportar_excel");
        Route::post(
            "/linea/import_excel",
            "CategoriaController@importsaveExecelUser"
        )->name("import_excel");

        Route::get("/marca", "MarcaController@index");
        Route::get("/marcanewview", "MarcaController@index2");
        Route::post("/marca/registrar", "MarcaController@store");
        Route::put("/marca/actualizar", "MarcaController@update");
        Route::put("/marca/desactivar", "MarcaController@desactivar");
        Route::put("/marca/activar", "MarcaController@activar");
        Route::get("/marca/exportexcel", "MarcaController@excelMarca")->name(
            "exportar_excel"
        );
        Route::post(
            "/marca/import_excel",
            "MarcaController@saveExecelUser"
        )->name("import_excel");

        Route::get("/articulo", "ArticuloController@index");
        Route::post("/articulo/registrar", "ArticuloController@store");
        Route::post("/articulo/actualizar", "ArticuloController@update");
        Route::put("/articulo/desactivar", "ArticuloController@desactivar");
        Route::put("/articulo/activar", "ArticuloController@activar");
        Route::get(
            "/articulo/buscarArticulo",
            "ArticuloController@buscarArticulo"
        );
        Route::get(
            "/articulo/listarArticulo",
            "ArticuloController@listarArticulo"
        );
        Route::get(
            "/articulo/listarArticuloVenta",
            "ArticuloController@listarArticuloVenta"
        );
        Route::get(
            "/articulo/buscarArticuloVenta",
            "ArticuloController@buscarArticuloVenta"
        );
        Route::get('/venta/filtrar', 'VentaController@indexFiltrar');
        Route::post('/venta/abonar-cuota', 'VentaController@abonarCuota');

        Route::get("/articulo/listarPdf", "ArticuloController@listarPdf")->name(
            "articulos_pdf"
        );
        Route::get(
            "/articulo/listarArticuloPedido",
            "ArticuloController@listPedProve"
        ); //aumente esto 21 sept
        Route::post("/articulo/actualizarPrecioVenta", "ArticuloController@actualizarPrecioVenta");
        Route::post("/articulos/importar", "ArticuloController@importar")->name(
            "articulos.importar"
        );
        Route::post(
            "/articulo/actualizarPrecios",
            "ArticuloController@editarCostoUnidadPaquete"
        );
        Route::post(
            "/articulo/editarCostoUnidadPaquete",
            "ArticuloController@editarPrecioCompraVenta"
        );
        Route::post(
            "/articulo/actualizarPrecios",
            "ArticuloController@editarCostoUnidadPaquete"
        );

        Route::get("/proveedor", "ProveedorController@index");
        Route::post("/proveedor/registrar", "ProveedorController@store");
        Route::put("/proveedor/actualizar", "ProveedorController@update");
        Route::get(
            "/proveedor/selectProveedor",
            "ProveedorController@selectProveedor"
        );
        Route::post(
            "/proveedor/importar",
            "ProveedorController@importar"
        )->name("provedores.importar");

        Route::get("/cliente", "ClienteController@index");
        Route::post("/cliente/registrar", "ClienteController@store");
        Route::put("/cliente/actualizar", "ClienteController@update");
        Route::get("/cliente/selectCliente", "ClienteController@selectCliente");
        Route::post("/cliente/importar", "ClienteController@importar")->name(
            "clientes.importar"
        );
        Route::get(
            "/cliente/selectClientePorNumero",
            "ClienteController@seleccionarClientePorNumero"
        );
        Route::get(
            "/cliente/listarReporteClienteExcel",
            "ClienteController@listarReporteClienteExcel"
        );

        Route::get(
            "/cliente/selectUusarioVend",
            "ClienteController@selectUsuarioVendedor"
        );
        Route::get("/cliente/usuario", "ClienteController@indexUsuario");
        Route::get(
            "/cliente/usuario/filtro",
            "ClienteController@indexUsuarioFiltro"
        );
        Route::get("/user-info", "ClienteController@getUserInfo");
        Route::get("/user/selectUser/filter", "UserController@selectUsuarios");
        Route::get(
            "/user/selectUser/rol",
            "UserController@selectUsuariosPorRol"
        );
        Route::get('/usuario-autenticado', 'UserController@getUsuarioAutenticado')->middleware('auth');

        Route::get("/articuloAjusteInven", "ArticuloController@indexAjusteInven");

        Route::get("/venta", "VentaController@index");
        Route::get("/venta/offline", "VentaController@ventaOffline");
        Route::post("/venta/registrar", "VentaController@store");
        Route::put("/venta/desactivar", "VentaController@desactivar");
        Route::get("/venta/obtenerCabecera", "VentaController@obtenerCabecera");
        Route::get("/venta/obtenerDetalles", "VentaController@obtenerDetalles");
        Route::get("/venta/pdf/{id}", "VentaController@pdf")->name("venta_pdf");

        Route::get('/ventaReporte', 'VentaController@indexRecibo');
        Route::get('/ventaReporteFactura', 'VentaController@indexFactura');
        Route::post('/venta/actualizarEstado', 'VentaController@actualizarEstado');
        Route::get('/ventaselect/{id}', 'VentaController@ventaSelecionada');
        Route::get('/api/clientes/existe', 'ClienteController@verificarExistencia');
        Route::get('/api/clientes/existe2', 'ClienteController@verificarExistencia2');
        Route::put('/venta/cerrarVenta', 'VentaController@cerrarVenta');


        //new
        Route::post(
            "/venta/verificarComunicacion",
            "VentaController@verificarComunicacion"
        );
        Route::post("/venta/cuis", "VentaController@cuis");
        Route::post("/venta/cufd", "VentaController@nuevoCufd");
        Route::post("/venta/nuevoCufd", "VentaController@nuevoCufd");
        Route::post("/venta/emitirFactura", "VentaController@emitirFactura");
        Route::post(
            "/venta/insertarFactura",
            "VentaController@insertarFactura"
        );
        Route::post("/venta/paqueteFactura", "VentaController@paqueteFactura");
        Route::post("/venta/enviarPaquete", "VentaController@enviarPaquete");
        Route::post(
            "/venta/validarPaquete",
            "VentaController@validacionRecepcionPaqueteFactura"
        );
        //credito_venta
        Route::get("/credito", "CreditoVentaController@index");
        Route::post("/credito/registrar", "CreditoVentaController@store");
        Route::put("/credito/actualizar", "CreditoVentaController@update");
        Route::post("/credito/actualizarCuotas", "CreditoVentaController@actualizarCuotas");
        Route::post("/credito/eliminarCuota", "CreditoVentaController@eliminarCuota");
        Route::get("/credito/eliminar", "CreditoVentaController@destroy");
        Route::post(
            "/credito/cuotas",
            "CreditoVentaController@obtenerCuotasCredito"
        );
        Route::post(
            "/credito/cuotas/registrar",
            "CreditoVentaController@registrarPagoCuota"
        );
        Route::get(
            "/credito/cuotas/venta/{idventa}",
            "CreditoVentaController@obtenerCreditoYCuotas"
        );

        Route::get(
            "/credito/{id}/comprobante-pago",
            "CreditoVentaController@pdf"
        )->name("credito.comprobante-pago");

        // cuota_credito
        Route::get("/cuota", "CuotasCreditoController@index");
        Route::post("/cuota/registrar", "CuotasCreditoController@store");
        Route::put("/cuota/actualizar/{id}", "CuotasCreditoController@actualizarCuota");
        Route::get("/cuota/eliminar", "CuotasCreditoController@destroy");
        Route::get('/ventas/cuotas-cliente/{idCliente}', 'VentaController@obtenerCuotasPorCliente');



        // cotizacionventa
        Route::get("/cotizacionventa", "CotizacionVentaController@index");
        Route::post(
            "/cotizacionventa/registrar",
            "CotizacionVentaController@store"
        );
        Route::put(
            "/cotizacionventa/desactivar",
            "CotizacionVentaController@desactivar"
        );
        Route::get(
            "/cotizacionventa/obtenerCabecera",
            "CotizacionVentaController@obtenerCabecera"
        );
        Route::get(
            "/cotizacionventa/obtenerDetalles",
            "CotizacionVentaController@obtenerDetalles"
        );
        Route::get(
            "/cotizacionventa/pdf/{id}",
            "CotizacionVentaController@pdf"
        )->name("venta_pdf");
        Route::put(
            "/cotizacionventa/activar",
            "CotizacionVentaController@activar"
        );
        Route::put(
            "/cotizacionventa/eliminar",
            "CotizacionVentaController@delete"
        );
        Route::get(
            "/cotizacionventa/obtenerDetalles",
            "CotizacionVentaController@obtenerDetalles"
        );
        Route::put(
            "cotizacionventa/editar",
            "CotizacionVentaController@editar"
        );
        Route::get(
            "/cotizacionventa/imprimir/{id}",
            "CotizacionVentaController@imprimirTicket"
        );

        Route::get("/rol", "RolController@index");
        Route::get("/rol/selectRol", "RolController@selectRol");

        Route::get("/user", "UserController@index");
        Route::post("/user/registrar", "UserController@store");
        Route::post("/user/actualizar", "UserController@update");
        Route::put("/user/desactivar", "UserController@desactivar");
        Route::put("/user/activar", "UserController@activar");
        Route::get(
            "/user/listarReporteUsuariosExcel",
            "UserController@listarReporteUsuariosExcel"
        );

        //Rura para que el usuario pueda editar su perfil
        Route::get("/user/editarpersona", "UserController@editarPersona");
        //Route::put('/editarperfil', 'UserController@editarPerfil');

        Route::get(
            "/ingresoCuotas/listarCuotas",
            "IngresoCuotaController@index"
        );
        Route::get(
            "/ingresoCuotas/cuotasPorIngreso",
            "IngresoCuotaController@getCuotasByIngreso"
        );
        Route::post(
            "/ingresoCuotas/pagarCuota",
            "IngresoCuotaController@pagarCuota"
        );

        Route::get("/ingreso", "IngresoController@index");
        Route::post("/ingreso/registrar", "IngresoController@store");
        Route::post(
            "/ingreso/registrarIngreso",
            "IngresoController@registrarIngreso"
        );
        Route::put("/ingreso/desactivar", "IngresoController@desactivar");
        Route::get(
            "/ingreso/obtenerCabecera",
            "IngresoController@obtenerCabecera"
        );
        Route::get(
            "/ingreso/obtenerDetalles",
            "IngresoController@obtenerDetalles"
        );
        Route::get("/ingreso/pdf/{id}", "VentaController@pdf")->name(
            "venta_pdf"
        );
        Route::get(
            "/ingreso/generar-pdf-boleta/{idIngreso}",
            "IngresoController@generarPdfBoleta"
        );

        //------sucursales
        //Lisar sucursal
        Route::get("/sucursal", "SucursalController@index");
        //---desactivar registro
        Route::post("/sucursal/registrar", "SucursalController@store");

        Route::put("/sucursal/activar", "SucursalController@activar");
        Route::put("/sucursal/desactivar", "SucursalController@desactivar");
        // actualizar
        Route::put("/sucursal/actualizar", "SucursalController@update");
        Route::get(
            "/sucursal/selectSucursal",
            "SucursalController@selectSucursal"
        );
        Route::get(
            "/tipoPago/selectTipoPago",
            "TipoPagoController@selectTipoPago"
        );
        Route::get(
            "/sucursal/selectedSucursal/filter",
            "SucursalController@selectedSucursal"
        );
        //Puntos de Venta
        Route::get('/puntoVenta', 'PuntoVentaController@index');
        Route::get('/puntoVenta/obtenerDatosTipoPuntoVenta', 'PuntoVentaController@obtenerDatosTipoPuntoVenta');
        Route::get('/puntoVenta/obtenerDatosSucursal', 'PuntoVentaController@obtenerDatosSucursal');
        Route::post('/puntoVenta/registrar', 'PuntoVentaController@store');
        Route::post('/puntoVenta/habilitar', 'VentaController@registroPuntoVenta');
        Route::post('/puntoVenta/cerrar', 'VentaController@cierrePuntoVenta');
        Route::put('/puntoVenta/cambioEstado', 'PuntoVentaController@cambioEstado');
        Route::get('/api/puntosDeVenta/{idSucursal}', 'PuntoVentaController@obtenerPorSucursal');
        Route::get('/puntoVenta/consulta', 'VentaController@consultaPuntoVenta');

        //Listar Moneda
        Route::get("/moneda", "MonedaController@index");
        Route::post("/moneda/registrar", "MonedaController@store");
        Route::put("/moneda/activar", "MonedaController@activar");
        Route::put("/moneda/desactivar", "MonedaController@desactivar");
        Route::put("/moneda/actualizar", "MonedaController@update");
        Route::get("/moneda/selectMoneda", "MonedaController@selectMoneda");
        Route::get("/moneda/selectMoneda", "MonedaController@selectMoneda");

        Route::get("/moneda/{id}", "MonedaController@getMonedaById");

        //APERTURA/CIERRE CAJA
        //Listar
        Route::get("/caja", "CajaController@index");
        Route::post("/caja/registrar", "CajaController@store");
        Route::put("/caja/depositar", "CajaController@depositar");
        Route::put("/caja/retirar", "CajaController@retirar");
        Route::get("/caja/transacciones", "CajaController@transacciones");
        Route::post("/caja/arqueoCaja", "CajaController@arqueoCaja");
        Route::put("/caja/cerrar", "CajaController@cerrar");
        Route::get('/venta/{id}/reporte-credito', 'VentaController@generarReporteCreditoPDF');


        //TRANSACCIONES CAJA
        Route::get("/transacciones/{id}", "TransaccionesCajaController@index");

        //ARQUEO CAJA
        Route::post("/arqueoCaja/registrar", "ArqueoCajaController@store");

        //FACTURAS
        Route::get("/factura", "SiatController@index");
        Route::get("/factura/getFactura/{id}", "SiatController@getFactura");
        Route::get(
            "/factura/imprimirRollo/{id}",
            "VentaController@imprimirFacturaRollo"
        );
        Route::get(
            "/factura/imprimirCarta/{id}/{correo}",
            "VentaController@imprimirFactura"
        );
        Route::get(
            "/factura/imprimirRolloOffline/{id}/{correo}",
            "VentaController@imprimirFacturaRolloOffline"
        );
        Route::get(
            "/factura/imprimirCartaOffline/{id}/{correo}",
            "VentaController@imprimirFacturaOffline"
        );
        Route::get(
            "/factura/anular/{cuf}/{motivoSeleccionado}/{opcionReposicionCaja}/{id}/{total}",
            "VentaController@anulacionFactura"
        );
        Route::get(
            "/factura/sincronizarActividades",
            "VentaController@sincronizarActividades"
        );
        Route::get(
            "/factura/sincronizarParametricaTiposFactura",
            "VentaController@sincronizarParametricaTiposFactura"
        );
        Route::get(
            "/factura/sincronizarListaLeyendasFactura",
            "VentaController@sincronizarListaLeyendasFactura"
        );
        Route::get(
            "/factura/sincronizarListaProductosServicios",
            "VentaController@sincronizarListaProductosServicios"
        );
        Route::get(
            "/factura/sincronizarParametricaMotivoAnulacion",
            "VentaController@sincronizarParametricaMotivoAnulacion"
        );
        Route::get(
            "/factura/sincronizarParametricaEventosSignificativos",
            "VentaController@sincronizarParametricaEventosSignificativos"
        );
        Route::get(
            "/factura/sincronizarParametricaUnidadMedida",
            "VentaController@sincronizarParametricaUnidadMedida"
        );
        Route::get(
            "/factura/obtenerDatosMotivoAnulacion",
            "FacturaController@obtenerDatosMotivoAnulacion"
        );
        Route::get(
            "/factura/obtenerLeyendaAleatoria",
            "FacturaController@obtenerLeyendaAleatoria"
        );
        Route::post(
            "/factura/verificarNit/{numeroDocumento}",
            "VentaController@verificarNit"
        );

        //--INDUSTRIA--
        //registrar
        Route::post("/industria/registrar", "IndustriaController@store");
        Route::get("/industria", "IndustriaController@index");
        Route::put("/industria/activar", "IndustriaController@activar");
        Route::put("/industria/desactivar", "IndustriaController@desactivar");
        Route::put("/industria/actualizar", "IndustriaController@update");
        Route::post("/industrias/importar", "IndustriaController@importar");
        Route::get("/industrianewview", "IndustriaController@index2");

        //MEDIDAS
        Route::get("/medida", "MedidaController@index");
        Route::get("/medida2", "MedidaController@index2");
        Route::post("/medida/registrar", "MedidaController@store");
        Route::put("/medida/actualizar", "MedidaController@update");
        Route::put("/medida/desactivar", "MedidaController@desactivar");
        Route::put("/medida/activar", "MedidaController@activar");
        Route::get("/medida/selectCategoria", "MedidaController@selectMedida");

        Route::get("/medida/exportexcel", "MedidaController@excelMedida")->name(
            "exportar_excel"
        );
        Route::post(
            "/medida/import_excel",
            "MedidaController@importsaveExecelUser"
        )->name("import_excel");

        //Obtener último numero de comprobante
        Route::get(
            "/obtener-ultimo-comprobante",
            "VentaController@obtenerUltimoComprobante"
        );

        //Obtener último numero de codigoSucursal
        Route::get(
            "/ruta-api-para-obtener-ultimo-codigo-sucursal",
            "SucursalController@obtenerUltimoCodigoSucursal"
        );

        //Obtener la sesion guardada de Codigo Evento
        Route::get(
            "/obtener-datos-sesion",
            "EventosSignificativosController@obtenerDatosSesion"
        );

        Route::get("/backup", "BackupDbController@createBackup");

        Route::get("/bancos", "BancoController@index");
        Route::post("/bancos/registrar", "BancoController@store");
        Route::put("/bancos/actualizar", "BancoController@update");
        Route::get("/bancos/select", "BancoController@getAllData");
        Route::get("/bancos/selectBancos", "BancoController@SelectBancos");
        Route::get("/bancos/listar", "BancoController@ListarBancos");

        Route::get("/transferencias", "TransferenciaController@index");
        Route::post(
            "/transferencias/registrar",
            "TransferenciaController@store"
        );

        Route::get("/motivos", "AjusteInventarioController@listarMotivo");
        Route::post("/motivo/registrar", "AjusteInventarioController@registrarMotivo");
        //grupo
        Route::get('/gruposnewview', 'GrupoController@index2');
        Route::get("/grupos", "GrupoController@index");
        Route::post("/grupos/registrar", "GrupoController@store");
        Route::put("/grupos/actualizar", "GrupoController@update");

        Route::get("/grupo/exportexcel", "GrupoController@excelGrupo")->name(
            "exportar_excel"
        );
        Route::post(
            "/grupo/import_excel",
            "GrupoController@importsaveExecelUser"
        )->name("import_excel");

        //precio
        Route::get("/precios", "PrecioController@indexanctivo");
        Route::get("/preciosactivos", "PrecioController@indexactivo"); //activos
        Route::post("/precios/registrar", "PrecioController@store");
        Route::put("/precios/{id}/{accion}", "PrecioController@cambiarEstado");

        //ALMACENES
        Route::get("/almacen", "AlmacenController@index");
        Route::get("/almacennewview", "AlmacenController@index2");
        Route::post("/almacen/registrar", "AlmacenController@store");
        Route::put("/almacen/editar", "AlmacenController@update");
        Route::get('/api/ventas/{idventa}', 'VentaController@obtenerVenta');

        Route::get("/almacen/selectAlmacen", "AlmacenController@selectAlmacen");
        Route::get("/almacen/almaceneslista", "AlmacenController@AlmacenesLista");
        Route::get("/almacen/listarAlmacen", "AlmacenController@listarAlmacen");

        Route::get(
            "/almacen/selectAlmacenDest",
            "AlmacenController@selectAlmacenDestino"
        );
        Route::get("/almacenes/lista", "AlmacenController@listarAlmacenes");

        //inventarios
        //Route::get('/inventarios', 'InventarioController@index');
        Route::post("/inventarios/registrar", "InventarioController@store");
        Route::post(
            "/inventarios/registrarInventario",
            "InventarioController@registrarInventario"
        );

        Route::get(
            "/inventarios/productosporvencer",
            "InventarioController@productosPorVencer"
        );

        Route::get(
            "/inventarios/productosvencidos",
            "InventarioController@productosVencidos"
        );
        Route::get(
            "/inventarios/productosbajostock",
            "InventarioController@productosBajoStock"
        );
        Route::get(
            "/inventarios/listarReportePorVencerExcel",
            "InventarioController@listarReportePorVencerExcel"
        );
        Route::get(
            "/inventarios/listarReporteVencidosExcel",
            "InventarioController@listarReporteVencidosExcel"
        );
        Route::get(
            "/inventarios/listarReporteBajoStockExcel",
            "InventarioController@listarReporteBajoStockExcel"
        );
        //listado para seleccionar producto En TRASPASO
        Route::get(
            "/inventariosTraspaso",
            "InventarioController@indextraspaso"
        ); //listar en traspaso para seleccionar el arTiculo de invenTario
        Route::get(
            "/inventarios/itemLote/{tipo}",
            "InventarioController@indexItemLote"
        ); //listato por filtro
        //saldostosk
        Route::get(
            "/inventarios/saldostock",
            "InventarioController@indexsaldostock"
        ); //listar el saldo_stock
        Route::post(
            "/inventarios/importar",
            "InventarioController@importar"
        )->name("inventario.importar");

        //traspaso
        Route::get("/list/traspasos", "TraspasoController@index");
        Route::post("/traspaso/registrar", "TraspasoController@store");
        Route::get(
            "/traspaso/obtenerTraspaso",
            "TraspasoController@indexPorID"
        );

        //Eventos Significativos
        Route::get("/eventos", "EventosSignificativosController@index");
        Route::get(
            "/eventos/obtenerDatosMotivoEvento",
            "EventosSignificativosController@obtenerDatosMotivoEvento"
        );
        Route::post(
            "/eventos/registrar",
            "EventosSignificativosController@store"
        );
        Route::put(
            "/eventos/finalizarEvento",
            "EventosSignificativosController@finalizarEvento"
        );
        Route::put(
            "/eventos/errorEvento",
            "EventosSignificativosController@errorEvento"
        );
        Route::put(
            "/eventos/cambioEstadoEvento",
            "EventosSignificativosController@cambioEstadoEvento"
        );
        Route::post(
            "/factura/eventosSignificativos",
            "VentaController@registroEventoSignificativo"
        );

        //PEDIDO A PROVEEDOR--
        Route::post(
            "/registrar/pedidoprovee",
            "PedidoProvController@store"
        )->name("PEDIDO");
        Route::get("/pedidoProveedor", "PedidoProvController@indexpedido");
        Route::get("/pedido/obtPediPrv", "PedidoProvController@indexPedProv");
        Route::put("/editar/pedidoprovee", "PedidoProvController@editar");
        Route::delete("/pedido/proveedor", "PedidoProvController@eliminar");
        //-----seleccionar usuario , roles REGISTROS DE VENTAS
        Route::get("/roles/selectRoles", "VentaController@selectRoles");

        // OFERTAS
        Route::get("/ofertas", "OfertaController@index");
        Route::post("/ofertas/registrar", "OfertaController@store");
        Route::put("/ofertas/actualizar", "OfertaController@update");
        Route::get("/ofertas/id", "OfertaController@obtenerDatosPromocion");
        Route::put("/ofertas/estado", "OfertaController@modificarEstado");

        Route::get("/kits/id", "OfertaController@obtenerDatosKit");
        Route::get(
            "/promocion/id",
            "OfertaController@obtenerPromocionPorIdArticulo"
        );

        Route::post(
            "/ofertasespeciales/registrar",
            "PreciosEspecialesController@store"
        );

        Route::get("/kits", "OfertaController@indexKits");
        Route::get("/ofertasespeciales", "PreciosEspecialesController@index");
        Route::put(
            "/ofertasespeciales/actualizar",
            "PreciosEspecialesController@update"
        );

        //REPORTES
        Route::get("/ventas-diarias", "VentaController@reporteVentasDiarias");
        Route::get("/reporte-almacen", "InventarioController@reporteAlmacenes");
        Route::get(
            "/reporte-kardex-fisico-valorado",
            "ReporteKardexFisicoInventarioController@generarReporte"
        );
        Route::get(
            "/reporte-kardex-fisico",
            "ReporteKardexFisicoInventarioController@generarReporteFisico"
        );
        Route::get("/top-vendedores", "VentaController@topVendedores");
        Route::get("/nuevoprecioarti", "InventarioController@productosActualizadosRecientemente");
        Route::get("/top-clientes", "VentaController@topClientes");
        Route::get("/top-articulos", "VentaController@topProductos");
        Route::get("/resumen-ventas-documento", "ReportesVentas@ResumenVentasPorDocumento");
        Route::get("/reporte-articulos-vendidos", "ReportesVentas@reporteArticulosVendidos");
        Route::get('/descargar-reporte-general-pdf', "ReportesVentas@descargarReporteGeneralPDF");
        Route::get('/descargar-ventas-detalladas-pdf', "ReportesVentas@descargarVentasDetalladasPDF");
        Route::get('/descargar-ventas-general-excel', 'ReportesVentas@exportarVentasGeneralExcel');
        Route::get('/descargar-ventas-detalladas-excel', 'ReportesVentas@exportarVentasDetalladasExcel');

        Route::get(
            "/reporte-resumen-clientes",
            "ReporteResumenClientesController@clientesPorVendedor"
        );
        Route::get(
            "/kardex-clientes-detallado-global",
            "ReporteKardexClientesDetalladoGlobalController@articulosPorCliente"
        );
        Route::get(
            "/kardex-clientes-resumen-global",
            "ReporteKardexClientesResumenGlobalController@ventasPorCliente"
        );
        Route::get(
            "/recibo-cliente-por-documento",
            "ReporteReciboClientePorDocumentoController@clientesPorDocumento"
        );
        Route::get(
            "/resumen-ventas-y-cobranzas",
            "ReporteResumenVentasYCobranzas@ventasYcobranzas"
        );
        Route::get(
            "/reporte-ventas-producto",
            "ReportesVentas@ventasPorProducto"
        );
        Route::get(
            "/reporte-resumen-fisico-movimientos",
            "ReportesInventariosController@resumenFisicoMovimientos"
        );
        Route::get(
            "/resumen-ventas-documento-detallado",
            "ReportesVentas@ResumenVentasPorDocumentoDetallado"
        );
        Route::get(
            "/reporte-inventario-fisico-valorado/{tipo}",
            "ReportesInventariosController@inventarioFisicoValorado"
        );
        Route::get('/reporte-detalle-movimientos', 'ReportesInventariosController@detalleMovimientosProducto');
        Route::get('/reporte-resumen-general-pdf', 'ReportesInventariosController@exportarPDFResumenGeneral');
        Route::get('/reporte-resumen-detallado-pdf', 'ReportesInventariosController@exportarPDFDetallado');
        Route::get('inventario/exportar-excel', 'InventarioController@exportarExcel')->name('inventario.exportarExcel');
        Route::get('inventario/exportar-pdf', 'InventarioController@exportarPdf')->name('inventario.exportarPdf');
        //RECIVO
        Route::post("/venta/emitirRecibo", "VentaController@emitirRecibo");
        Route::post("/venta/registrargastos", "VentaController@registrarGastoCredito");
        Route::get(
            "/resivo/imprimirRollo/{id}",
            "VentaController@imprimirResivoRollo"
        );
        Route::get(
            "/resivo/imprimirCarta/{id}",
            "VentaController@imprimirResivoCarta"
        );

        //NOTA DE REMISION
        Route::get(
            "/remision/imprimirRollo/{id}",
            "VentaController@imprimirRemisionRollo"
        );
        Route::get(
            "/remision/imprimirCarta/{id}",
            "VentaController@imprimirRemisionCarta"
        );

        Route::delete('/venta/eliminarVenta/{id}', 'VentaController@eliminarVenta');
        Route::delete('/venta/eliminarVentaFalloSiat/{id}', 'VentaController@eliminarVentaFalloSiat');
        Route::get('/facturas/ultimo-numero', 'FacturaController@obtenerUltimoNumero');
        Route::get('/ventas/{idventa}/num-comprobante', 'FacturaController@obtenerNumeroPorVenta');

        //VARIABLES TEMPORALES
        Route::post(
            "/variables/registrarVariable",
            "VentasInstitucionalesController@registrarVariable"
        );
        Route::post(
            "/variables/registrarArticuloVariable",
            "VentasInstitucionalesController@registrarArticuloVariable"
        );
        Route::post(
            "/variables/registrarArticuloVariable2",
            "VentasInstitucionalesController@registrarArticuloVariable2"
        );
        Route::get(
            "/variables/listarArticulosVariable",
            "VentasInstitucionalesController@obtenerArticulosPorVariableTemporal"
        );
        Route::delete(
            "/variables/excluirArticulo",
            "VentasInstitucionalesController@excluirArticulo"
        );
        Route::post(
            "/variables/registrar-venta-institucional",
            "VentasInstitucionalesController@registrarVentaInstitucional"
        );
        Route::post(
            "/variables/ventas_institucionales",
            "VentasInstitucionalesController@registrarModificacionVentasInstitucionales"
        );
        Route::get(
            "/variables/facturaInstitucional",
            "VentasInstitucionalesController@listarFacturas"
        );
        Route::post(
            "/variables/emitirFacturaInstitucional",
            "VentaController@emitirFacturaInstitucional"
        );
        Route::post(
            "/variables/insertarFacturaInstitucional",
            "VentaController@insertarFacturaInstitucional"
        );
        Route::get(
            "/variables/imprimirCarta/{id}/{idventainstitucional}",
            "VentasInstitucionalesController@imprimirFactura"
        );

        //BUSCAR CLIENTE
        Route::get("/api/clientes", "ClienteController@buscarPorDocumento");

        Route::get(
            "/ingreso/generar-nota-ingreso/{id}",
            "IngresoController@generarNotaIngreso"
        );
        Route::get(
            "/credito/recibo-cuota/{id}",
            "CreditoVentaController@generarReciboCuota"
        )->name("credito.recibo-cuota");
        Route::get(
            "/credito/recibo-general/{id}",
            "CreditoVentaController@generarReciboGeneral"
        );

        Route::get('/inventario/stockPorSucursal', 'InventarioController@obtenerStockPorSucursal');

        Route::get('/monto-bonificacion', 'MontoBonificacionController@index');
        Route::post('/monto-bonificacion/registrar', 'MontoBonificacionController@store');
        Route::put('/monto-bonificacion/actualizar/{id}', 'MontoBonificacionController@update');
        Route::delete('/monto-bonificacion/eliminar/{id}', 'MontoBonificacionController@destroy');
        Route::get('/ventas/obtener-completa/{id}', 'VentaController@obtenerVentaCompleta');
        Route::post('/ventas/actualizar', 'VentaController@actualizarVenta');
        Route::get('/ingresos/obtener-completo/{id}', 'IngresoController@obtenerCompleto');
        Route::get('/ingreso/obtener/{id}', 'IngresoController@obtenerIngreso');
        Route::post('/ingreso/actualizar', 'IngresoController@actualizar');
        Route::get('/reporte/caja/{idCaja}', 'CajaController@generarReporte')->name('reporte.caja');

    });

    //RUTA PARA RECUPERAR LA SESSION CON EL ID DE LA PERSONA LOGUEADA
    Route::get("/api/session", function () {
        return response()->json([
            "id" => session("id"),
        ]);
    });
});

//Route::get('/home', 'HomeController@index')->name('home');
