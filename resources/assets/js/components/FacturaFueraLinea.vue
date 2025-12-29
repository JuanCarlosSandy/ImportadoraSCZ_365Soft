<template>
    <main class="main">
        <!-- Breadcrumb -->
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="/">Escritorio</a></li>
        </ol>
        <div class="container-fluid">
            <!-- Ejemplo de tabla Listado -->
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-align-justify"></i> Ventas Fuera de Línea
                    <button type="button" @click="mostrarDetalle" class="btn btn-secondary">
                        <i class="icon-plus"></i>&nbsp;Nuevo
                    </button>
                    <button v-if="permitirDevolucion === 1" type="button" @click="listado = 3"
                        class="btn btn-secondary">Devoluciones
                    </button>
                    <span class="badge bg-secondary" id="comunicacionSiat" style="color: white;"
                        v-show="mostrarComunicacionSiat">Desconectado</span>
                    <span class="badge bg-secondary" id="cuis" v-show="mostrarCufd">CUIS: Inexistente</span>
                    <span class="badge bg-secondary" id="cufd" v-show="mostrarCufd">No existe cufd vigente</span>
                    <span class="badge bg-secondary" id="direccion" v-show="mostrarDireccion">No hay dirección
                        registrada</span>
                    <span class="badge bg-primary" id="cufdValor" v-show="mostrarCUFD">No hay CUFD</span>
                </div>
                <!-- Listado-->
                <template v-if="listado == 1">
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-md-3">
                                <div class="input-group">
                                    <button type="button" class="btn btn-info" @click="obtenerNuevoCUFD">
                                        <i class="icon-reload"></i>
                                    </button>
                                    <input type="text" class="form-control" placeholder="Obtener Nuevo CUFD" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="spinner-container" v-if="mostrarSpinner">
                            <div class="spinner-message"><strong>REGISTRANDO VENTA...</strong></div>
                            <TileSpinner color="blue" />
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Opciones</th>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Razón Social</th>
                                        <th>Documento</th>
                                        <th>Número Factura</th>
                                        <th>Fecha Hora</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th> </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(venta, index) in arrayVenta" :key="venta.id">
                                        <td>
                                            <button type="button" @click="verVenta(venta.id)"
                                                class="btn btn-success btn-sm">
                                                <i class="icon-eye"></i>
                                            </button> &nbsp;

                                            <template v-if="venta.estado == 'Registrado'">
                                                <button type="button" class="btn btn-danger btn-sm"
                                                    @click="desactivarVenta(venta.id)">
                                                    <i class="icon-trash"></i>
                                                </button> &nbsp;
                                            </template>

                                            <button type="button" @click="enviarPaquete()" class="btn btn-info btn-sm"
                                                v-if="index === 0 && mostrarEnviarPaquete">
                                                Enviar Paquete
                                            </button>
                                            <button v-if="index === 0 && mostrarValidarPaquete" type="button"
                                                @click="validarPaquete()" class="btn btn-warning btn-sm">
                                                Validar Paquete
                                            </button>

                                        </td>
                                        <td v-text="venta.id"></td>
                                        <td v-text="venta.usuario"></td>
                                        <td v-text="venta.razonSocial"></td>
                                        <td v-text="venta.documentoid"></td>
                                        <td v-text="venta.num_comprobante"></td>
                                        <td v-text="venta.fecha_hora"></td>
                                        <td>
                                            {{ ((venta.total) * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                                monedaVenta[1]
                                            }}

                                        </td>
                                        <td>
                                            <a @click="verificarFactura(venta.cuf, venta.numeroFactura)" target="_blank"
                                                class="btn btn-info"><i class="icon-note"></i></a>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary" type="button"
                                                @click="imprimirFactura(venta.id, venta.correo)"><i
                                                    class="icon-printer"></i></button>
                                            <button class="btn btn-danger" type="button"
                                                @click="anularFactura(venta.id, venta.cuf)"><i
                                                    class="icon-close"></i></button>
                                        </td>
                                    </tr>
                                </tbody>

                            </table>
                        </div>
                        <nav>
                            <ul class="pagination">
                                <li class="page-item" v-if="pagination.current_page > 1">
                                    <a class="page-link" href="#"
                                        @click.prevent="cambiarPagina(pagination.current_page - 1, buscar, criterio)">Ant</a>
                                </li>
                                <li class="page-item" v-for="page in pagesNumber" :key="page"
                                    :class="[page == isActived ? 'active' : '']">
                                    <a class="page-link" href="#" @click.prevent="cambiarPagina(page, buscar, criterio)"
                                        v-text="page"></a>
                                </li>
                                <li class="page-item" v-if="pagination.current_page < pagination.last_page">
                                    <a class="page-link" href="#"
                                        @click.prevent="cambiarPagina(pagination.current_page + 1, buscar, criterio)">Sig</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </template>
                <!--Fin Listado-->
                <!-- Detalle-->
                <template v-else-if="listado == 0">
                    <div class="card-body">
                        <div class="form-group row border">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="" class="font-weight-bold">Cliente
                                        <span class="text-danger">*</span>
                                    </label>

                                    <v-select :on-search="selectCliente" label="num_documento" :options="arrayCliente"
                                        placeholder="Num de documento..." :onChange="getDatosCliente">
                                    </v-select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label for="" class="font-weight-bold">Razon social
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="nombreCliente" class="form-control" v-model="nombreCliente"
                                    ref="nombreRef" readonly>
                            </div>

                            <input type="hidden" id="idcliente" class="form-control" v-model="idcliente" ref="idRef"
                                readonly>
                            <input type="hidden" id="tipo_documento" class="form-control" v-model="tipo_documento"
                                ref="tipoDocumentoRef" readonly>
                            <input type="hidden" id="complemento_id" class="form-control" v-model="complemento_id"
                                ref="complementoIdRef" readonly>
                            <input type="hidden" id="usuarioAutenticado" class="form-control"
                                v-model="usuarioAutenticado" readonly>
                            <input type="hidden" id="email" class="form-control" v-model="email" readonly>

                            <div class="col-md-3">
                                <label for="" class="font-weight-bold">Documento
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="documento" class="form-control" v-model="documento"
                                    ref="documentoRef" readonly>
                            </div>

                            <div class="col-md-3">
                                <label for="" class="font-weight-bold">Casos especiales</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" v-model="casosEspeciales"
                                        id="casosEspecialesCheckbox" @change="habilitarNombreCliente">
                                    <label class="form-check-label" for="casosEspecialesCheckbox">
                                        Habilitar
                                    </label>
                                </div>
                            </div>

                            <div v-if="clienteDeudas > 0" class="alert alert-danger text-center " role="alert"
                                style="width: 100%;">
                                Este cliente tiene <b>{{ clienteDeudas }}</b> pagos pendientes de crédito.
                                <button type="button" class="close" @click="clienteDeudas = 0">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                            <div class="col-md-3">
                                <label for="" class="font-weight-bold">Almacen
                                    <span class="text-danger">*</span>
                                </label>
                                <v-select label="nombre_almacen" :options="arrayAlmacenes"
                                    placeholder="Seleccione un almacen" :onChange="getAlmacenProductos"></v-select>
                            </div>

                            <div class="col-md-3">
                                <label for="" class="font-weight-bold">Tipo de comprobante
                                    <span class="text-danger">*</span>
                                </label>

                                <select class="form-control" v-model="tipo_comprobante" ref="tipoComprobanteRef">
                                    <option disabled value="0">Seleccione</option>
                                    <option value="FACTURA">Factura</option>
                                    <option value="BOLETA">Boleta</option>
                                    <option value="TICKET">Ticket</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="" class="font-weight-bold">Numero de comprobante
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="num_comprobante" class="form-control" v-model="num_comprob"
                                    disabled>
                            </div>

                            <div class="col-md-3">
                                <label for="" class="font-weight-bold">Aplicar impuesto:
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group mb-3" id="seccionObjetivo">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text" style="height: 100%;">
                                            <input type="checkbox" aria-label="Checkbox for following text input">
                                        </div>
                                    </div>
                                    <input disabled type="text" class="form-control" value="0.18">
                                </div>
                            </div>

                            <div class="col-md-3" v-if="casosEspeciales">
                                <label for="email" class="font-weight-bold">Correo electrónico</label>
                                <input type="email" id="email" class="form-control" v-model="email" ref="emailRef">
                            </div>

                            <div class="col-md-3">
                                <div class="form-group"
                                    v-if="scodigorecepcion === 5 || scodigorecepcion === 6 || scodigorecepcion === 7">
                                    <label for="" class="font-weight-bold">Código CAFC</label>
                                    <input type="text" id="cafc" class="form-control" v-model="cafc" ref="cafc">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group"
                                    v-if="scodigorecepcion === 5 || scodigorecepcion === 6 || scodigorecepcion === 7">
                                    <label for="" class="font-weight-bold">Fecha y Hora</label>
                                    <input type="datetime-local" id="fecha" class="form-control" v-model="fecha"
                                        ref="fecha">
                                </div>
                            </div>

                        </div>

                        <div class="form-group row border">
                            <div class="col-md-3">
                                <label for="" class="font-weight-bold">Buscar articulo
                                </label>
                                <div class="input-group mb-3">
                                    <input :disabled="!idAlmacen" type="text" class="form-control" v-model="codigo"
                                        placeholder="Codigo del articulo" aria-label="Codigo del articulo"
                                        @keyup="buscarArticulo()">
                                    <button :disabled="!idAlmacen" class="btn btn-primary" type="button"
                                        @click="abrirModal()">...</button>
                                </div>
                            </div>


                            <!-- Desde aca comienza el seleccionado -->
                            <template v-if="arraySeleccionado && arraySeleccionado.id">

                                <div class="col-md-6">
                                    <div class="card-body">
                                        <h3 style="margin:0px">{{ arraySeleccionado.nombre }}</h3>
                                        <span class="badge bg-primary">Medida: {{ arraySeleccionado.medida }}</span>
                                        <span class="badge bg-primary">Linea: {{ arraySeleccionado.nombre_categoria
                                            }}</span>
                                        <p>
                                            {{ arraySeleccionado.descripcion }}

                                        </p>
                                        <p><b>Cantidad por empaque: </b>{{ arraySeleccionado.unidad_envase }}</p>

                                        <h3 v-if="arrayPromocion && arrayPromocion.id"
                                            style="display:flex;align-items:center;margin:0px;">
                                            <b v-if="arrayPromocion.porcentaje == 100">GRATIS</b>
                                            <b v-else>{{ (calcularPrecioConDescuento(resultadoMultiplicacion,
                                                arrayPromocion.porcentaje) * parseFloat(monedaVenta[0])).toFixed(2) }}
                                                {{
                                                    monedaVenta[1] }}</b>
                                            <s style="font-size:15px" class="lead">{{
                                                calcularPrecioConDescuento(resultadoMultiplicacion *
                                                parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                                    monedaVenta[1] }}</s>
                                        </h3>

                                        <h3 v-else style="display:flex;align-items:center;margin:0px;">
                                            <b>{{ calcularPrecioConDescuento(resultadoMultiplicacion *
                                                parseFloat(monedaVenta[0])).toFixed(2) }}
                                                {{
                                                    monedaVenta[1] }}</b>
                                        </h3>
                                        <p style="margin:0px" v-if="arrayPromocion && arrayPromocion.id" class="lead">
                                            {{ arrayPromocion.porcentaje }} % de descuento
                                        </p>
                                        <p style="margin:0px" v-if="arrayPromocion && arrayPromocion.id"
                                            class="text-danger">
                                            <i class="fa fa-clock-o" aria-hidden="true"></i> Esta oferta termina en {{
                                                calcularDiasRestantes(arrayPromocion.fecha_final) }} días
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex flex-column align-items-center">
                                    <img v-if="arraySeleccionado.length > 0 && arraySeleccionado.fotografia"
                                        :src="'img/articulo/' + arraySeleccionado.fotografia + '?t=' + new Date().getTime()"
                                        width="50" height="50" ref="imagen" class="card-img" />
                                    <img v-else src="img/productoSinImagen.png" alt="Imagen del Card" class="card-img">


                                    <div :class="{
                                        'alert': true,
                                        'alert-success': (arraySeleccionado.saldo_stock / unidadPaquete - cantidad) > arraySeleccionado.stock / unidadPaquete,
                                        'alert-warning': (arraySeleccionado.saldo_stock / unidadPaquete - cantidad) <= arraySeleccionado.stock / unidadPaquete,
                                        'alert-danger': (arraySeleccionado.saldo_stock / unidadPaquete - cantidad) <= 0
                                    }" role="alert">
                                        <p style="margin:0px">Stock disponible</p>
                                        <b>{{ arraySeleccionado.saldo_stock / unidadPaquete - cantidad }} {{
                                            unidadPaquete == 1 ? "Unidades" : "Paquetes" }}</b>
                                    </div>

                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="" class="font-weight-bold">Tipo de venta
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" v-model="unidadPaquete"
                                            aria-label="Default select example">
                                            <option :value="arraySeleccionado.unidad_envase">Por paquete</option>
                                            <option value="1">Por unidad</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="" class="font-weight-bold">Categoria de precios
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" placeholder="Seleccione"
                                            v-model="precioseleccionado" @change="mostrarSeleccion"
                                            :disabled="precioBloqueado">
                                            <option>Selecciona un precio: </option>
                                            <option :value="arraySeleccionado.precio_uno" v-if="arrayPrecios[0]">{{
                                                arrayPrecios[0].nombre_precio
                                                }}</option>
                                            <option :value="arraySeleccionado.precio_dos" v-if="arrayPrecios[1]">{{
                                                arrayPrecios[1].nombre_precio
                                                }}
                                            </option>
                                            <option :value="arraySeleccionado.precio_tres" v-if="arrayPrecios[2]">
                                                {{ arrayPrecios[2].nombre_precio }}</option>
                                            <option :value="arraySeleccionado.precio_cuatro" v-if="arrayPrecios[3]">
                                                {{ arrayPrecios[3].nombre_precio }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="" class="font-weight-bold">Cantidad
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" id="cantidad" value="1" class="form-control"
                                            v-model="cantidad">
                                    </div>
                                </div>

                            <!--     <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="" class="font-weight-bold">Descuento %
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" id="descuentoProducto" value="0" class="form-control"
                                            v-model="descuentoProducto" max="99">
                                    </div>
                                </div> -->

                                <div class="col-md-3">
                                    <div class="form-group d-flex">
                                        <button @click="agregarDetalle()" class="btn btn-success flex-fill btnagregar">
                                            <i class="icon-plus"></i> Agregar
                                        </button>
                                        <button @click="eliminarSeleccionado()"
                                            class="btn btn-danger flex-fill btnagregar ml-2">
                                            <i class="icon-minus"></i> Eliminar
                                        </button>
                                    </div>
                                </div>

                            </template>
                        </div>
                        <div class="form-group row border">
                            <div class="table-responsive col-md-12">
                                <table class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Opciones</th>
                                            <th>Artículo</th>
                                            <th>Unidad Medida</th>
                                            <th>Unidad Paq.</th>
                                            <th>Precio Unidad </th>
                                            <th>Unidades</th>
                                            <th>Cantidad Paquetes</th>
                                            <th>Total S/Descuento</th>
                                            <th>Descuento %</th>
                                            <th>Total C/Descuento</th>

                                        </tr>
                                    </thead>
                                    <tbody v-if="arrayDetalle.length">
                                        <tr v-for="(detalle, index) in arrayDetalle" :key="detalle.id">
                                            <td>
                                                <button v-if="detalle.medida != 'KIT'" @click="eliminarDetalle(index)"
                                                    type="button" class="btn btn-danger btn-sm">
                                                    <i class="icon-close"></i>
                                                </button>
                                                <button v-else @click="eliminarKit(detalle.idkit)" type="button"
                                                    class="btn btn-danger btn-sm">
                                                    <i class="icon-close"></i>
                                                </button>
                                            </td>
                                            <td v-text="detalle.articulo">
                                            </td>
                                            <td v-text="detalle.medida">
                                            </td>
                                            <td>{{ detalle.unidad_envase }}
                                            </td>
                                            <td>
                                                {{ (detalle.precioseleccionado * parseFloat(monedaVenta[0])).toFixed(2)
                                                }}
                                                {{
                                                    monedaVenta[1] }}

                                            </td>
                                            <td>
                                                <input type="number" v-model="detalle.cantidad" min="1"
                                                    @input="actualizarDetalle(index)"
                                                    style="border: none; outline: none; width: 50px;" />
                                            </td>

                                            <td> {{ (detalle.cantidad / detalle.unidad_envase).toFixed(2) }} </td>


                                            <td>

                                                {{ ((detalle.precioseleccionado * detalle.cantidad)
                                                    * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}


                                            </td>


                                            <td>
                                                <input type="number" v-model="detalle.descuento" max="99"
                                                    style="border: none; outline: none; width: 50px;" />
                                            </td>
                                            <!--<span style="color:red;"
                                                    v-show="detalle.descuento > (detalle.precioseleccionado * detalle.cantidad)">Descuento
                                                    superior</span>
                                                <input v-model="detalle.descuento" type="number" class="form-control">-->

                                            <td>
                                                {{ (((detalle.precioseleccionado * detalle.cantidad) -
                                                    (detalle.precioseleccionado * detalle.cantidad * detalle.descuento /
                                                        100))
                                                    * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}

                                            </td>
                                        </tr>
                                        <tr style="background-color: #CEECF5;">
                                            <td colspan="9" align="right"><strong>Sub Total: Bs.</strong></td>
                                            <td id="subTotal">
                                                {{ ((totalParcial = (calcularSubTotal))
                                                    * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}

                                            </td>
                                        </tr>
                                        <tr style="background-color: #CEECF5;">
                                            <td colspan="9" align="right"><strong>Descuento Adicional: </strong></td>
                                            <input id="descuentoAdicional" v-model="descuentoAdicional" type="number"
                                                class="form-control" @change="validarDescuentoAdicional">
                                        </tr>
                                        <tr style="background-color: #CEECF5;">
                                            <td colspan="9" align="right"><strong>Total Neto: Bs.</strong></td>
                                            <td id="montoTotal">
                                                {{ (calcularTotal * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                                    monedaVenta[1] }}

                                            </td>
                                        </tr>
                                    </tbody>
                                    <tbody v-else>
                                        <tr>
                                            <td colspan="10">

                                                No hay articulos agregados
                                            </td>

                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div class="col-md-4">
                                <label class="col-form-label" for="tipoVenta"><strong>Tipo de Venta</strong></label>
                                <select class="form-control" v-model="idtipo_venta">
                                    <option value="1">Contado</option>
                                    <option value="2">Crédito</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-end">
                                <button type="button" @click="ocultarDetalle()"
                                    class="btn btn-secondary btn-block mr-2">Cerrar</button>
                                <button type="button" class="btn btn-primary btn-block "
                                    @click="abrirTipoVenta()">Confirmar</button>
                            </div>
                        </div>
                    </div>
                </template>
                <!-- Fin Detalle-->
                <!--Ver ingreso-->
                <template v-else-if="listado == 2">
                    <div class="card-body">
                        <div class="form-group row border">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label for="">Cliente</label>
                                    <p v-text="cliente"></p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="">Impuesto</label>
                                <p v-text="impuesto"></p>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tipo Comprobante</label>
                                    <p v-text="tipo_comprobante"></p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Serie Comprobante</label>
                                    <p v-text="serie_comprobante"></p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Número Comprobante</label>
                                    <p v-text="num_comprobante"></p>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row border">
                            <div class="table-responsive col-md-12">
                                <table class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Artículo</th>
                                            <th>Precio</th>
                                            <th>Cantidad</th>
                                            <th>Descuento</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody v-if="arrayDetalle.length">
                                        <tr v-for="detalle in arrayDetalle" :key="detalle.id">
                                            <td v-text="detalle.articulo">
                                            </td>

                                            <td>
                                                {{ ((detalle.precio) * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                                    monedaVenta[1] }}

                                            </td>
                                            <td v-text="detalle.cantidad">
                                            </td>
                                            <td v-text="detalle.descuento">
                                            </td>
                                            <td>
                                                {{ ((detalle.precio * detalle.cantidad - detalle.descuento)
                                                    * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}

                                            </td>
                                        </tr>
                                        <tr style="background-color: #CEECF5;">
                                            <td colspan="4" align="right"><strong>Total Parcial:</strong>
                                            </td>
                                            <td>
                                                {{ ((totalParcial = (total - totalImpuesto))
                                                    * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}
                                            </td>

                                        </tr>
                                        <tr style="background-color: #CEECF5;">
                                            <td colspan="4" align="right"><strong>Total Impuesto:</strong></td>
                                            <td>
                                                {{ ((totalImpuesto = (total * impuesto))
                                                    * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}

                                            </td>
                                        </tr>
                                        <tr style="background-color: #CEECF5;">
                                            <td colspan="4" align="right"><strong>Total Neto:</strong></td>
                                            <td>
                                                {{ ((total) * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                                monedaVenta[1] }}

                                            </td>
                                        </tr>
                                    </tbody>
                                    <tbody v-else>
                                        <tr>
                                            <td colspan="5">
                                                No hay articulos agregados
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-12">
                                <button type="button" @click="ocultarDetalle()"
                                    class="btn btn-secondary">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </template>
                <!--Fin ver ingreso-->
                <!-- Vista Devoluciones -->
                <template v-else-if="listado == 3">
                    <devoluciones></devoluciones>
                    <div class="form-group row">
                        <div class="col-md-12">
                            <button type="button" @click="ocultarDetalle()" class="btn btn-secondary">Cerrar</button>
                        </div>
                    </div>
                </template>
            </div>
            <!-- HASTA AQUI DEVOLUCIONES -->
            <!-- Fin ejemplo de tabla Listado -->
        </div>
        <!--Inicio del modal agregar/actualizar-->
        <div class="modal " tabindex="-1" :class="{ 'mostrar': modal }" role="dialog" aria-labelledby="myModalLabel"
            style="display: none;" aria-hidden="true">
            <div class="modal-dialog modal-primary modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" v-text="tituloModal"></h4>
                        <button type="button" class="close" @click="cerrarModal()" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div>
                            <b-tabs content-class="mt-3">
                                <b-tab title="Articulos" active>
                                    <template>
                                        <div class="form-group row">
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <select class="form-control col-md-3" v-model="criterioA">
                                                        <option value="nombre">Nombre</option>
                                                        <option value="descripcion">Descripción</option>
                                                        <option value="codigo">Código</option>
                                                    </select>
                                                    <input type="text" v-model="buscarA"
                                                        @keyup="listarArticulo(buscarA, criterioA)" class="form-control"
                                                        placeholder="Texto a buscar">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Opciones</th>
                                                        <th>Código</th>
                                                        <th>Nombre</th>
                                                        <th>Categoría</th>
                                                        <th>Precio Venta</th>
                                                        <th>Stock</th>
                                                        <th>Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="articulo in arrayArticulo" :key="articulo.id">
                                                        <td>
                                                            <button type="button" @click="agregarDetalleModal(articulo)"
                                                                class="btn btn-success btn-sm">
                                                                <i class="icon-check"></i>
                                                            </button>
                                                        </td>
                                                        <td v-text="articulo.codigo"></td>
                                                        <td v-text="articulo.nombre"></td>
                                                        <td v-text="articulo.nombre_categoria"></td>
                                                        <td>
                                                            {{ ((articulo.precio_venta) *
                                                                parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                                                monedaVenta[1] }}

                                                        </td>
                                                        <td v-text="articulo.saldo_stock"></td>
                                                        <td>
                                                            <div v-if="articulo.condicion">
                                                                <span class="badge badge-success">Activo</span>
                                                            </div>
                                                            <div v-else>
                                                                <span class="badge badge-danger">Desactivado</span>
                                                            </div>

                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                    </template>

                                </b-tab>
                                <b-tab title="Kits">
                                    <template>
                                        <div class="form-group row">
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <select class="form-control col-md-3" v-model="criterioKit">
                                                        <option value="nombre">Nombres</option>
                                                    </select>
                                                    <input type="text" v-model="buscarKit" class="form-control"
                                                        placeholder="Texto a buscar"
                                                        @keyup="listarKits(1, buscarKit, criterioKit)">

                                                </div>
                                            </div>
                                        </div>


                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Acciones</th>
                                                        <th>Codigo</th>
                                                        <th>Nombre kit</th>
                                                        <th>Precio</th>
                                                        <th>Fecha limite</th>
                                                        <th>Articulos Incluidos</th>
                                                        <th>Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="kit in arrayKit" :key="kit.id">
                                                        <td>
                                                            <icon-button icon="icon-check" size="small" color="success"
                                                                @click="agregarKit(kit)" />
                                                            <icon-button icon="icon-eye" size="small" color="primary"
                                                                @click="abrirModalDetallesKit(kit)" />


                                                        </td>
                                                        <td v-text="kit.codigo"></td>
                                                        <td v-text="kit.nombre"></td>
                                                        <td>
                                                            {{ (kit.precio * parseFloat(monedaVenta[0])).toFixed(2) }}
                                                            {{
                                                                monedaVenta[1]
                                                            }}
                                                        </td>
                                                        <td>
                                                            {{ new Date(kit.fecha_final).toLocaleDateString('es-ES') }}
                                                        </td>
                                                        <td>{{ kit.cantidad_articulos }} articulos</td>
                                                        <td>

                                                            <i class="fa fa-circle"
                                                                :style="{ color: getColorForEstado(kit.estado, kit.fecha_final) }"></i>&nbsp;
                                                            {{ new Date(kit.fecha_final) < new Date() ? 'Inactivo' :
                                                                kit.estado }} </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </template>
                                </b-tab>
                            </b-tabs>
                        </div>


                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="cerrarModal()">Cerrar</button>
                        <button type="button" v-if="tipoAccion == 1" class="btn btn-primary"
                            @click="registrarPersona()">Guardar</button>
                        <button type="button" v-if="tipoAccion == 2" class="btn btn-primary"
                            @click="actualizarPersona()">Actualizar</button>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!--Fin del modal-->
        <!--#########- MODAL DE REISTRO DE TIPO VENTA--############3-->
        <div class="modal " tabindex="-1" :class="{ 'mostrar': modal2 }" role="dialog" aria-labelledby="myModalLabel"
            style="display: none;" aria-hidden="true">
            <div class="modal-dialog modal-primary modal-lg" role="document">
                <div class="modal-content" style="max-height: 90vh;min-height: 90vh">
                    <div class=" modal-header">
                        <h4 class="modal-title">Detalle de pago</h4>
                        <button type="button" class="close" @click="cerrarModal2()" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="">
                        <b-tabs content-class="" fill>
                            <b-tab>
                                <template #title>
                                    <span class="d-flex align-items-center">
                                        <i class="fa fa-money fa-2x icon-color mr-2" aria-hidden="true"></i>
                                        <label>Efectivo</label>
                                    </span>
                                </template>
                                <div class="container">
                                    <div class="row">
                                        <div class="col-md-7">
                                            <div class="card shadow-sm">
                                                <div class="card-body">

                                                    <form>
                                                        <div class="form-group">
                                                            <label for="montoEfectivo"><i class="fa fa-money mr-2"></i>
                                                                Monto
                                                                Recibido:</label>
                                                            <div class="input-group mb-3">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">
                                                                        {{
                                                                            monedaVenta[1] }}
                                                                    </span>
                                                                </div>
                                                                <input type="number" class="form-control"
                                                                    id="montoEfectivo" v-model="recibido"
                                                                    placeholder="Ingrese el monto recibido">
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="cambioRecibir"><i
                                                                    class="fa fa-exchange mr-2"></i>
                                                                Cambio a Entregar:</label>
                                                            <input type="text" class="form-control" id="cambioRecibir"
                                                                placeholder="Se calculará automáticamente"
                                                                :value="recibido - calcularTotal * parseFloat(monedaVenta[0])"
                                                                readonly>
                                                        </div>


                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="card shadow-sm">
                                                <div class="card-body">
                                                    <div class=" mb-3">
                                                        <h5 class="mb-0"> Detalle
                                                            de Venta
                                                        </h5>

                                                    </div>
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span><i class="fa fa-dollar mr-2"></i> Monto Total:</span>
                                                        <span class="font-weight-bold">
                                                            {{ (calcularTotal * parseFloat(monedaVenta[0])).toFixed(2)
                                                            }} {{
                                                                monedaVenta[1] }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span><i class="fa fa-tag mr-2 text-success"></i>
                                                            Descuento:</span>
                                                        <span class="font-weight-bold text-success">0.00</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-3">
                                                        <span><i class="fa fa-percent mr-2 text-danger"></i>
                                                            Impuestos:</span>
                                                        <span class="font-weight-bold text-danger">0.00</span>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="codigoDescuento"><i class="fa fa-gift mr-2"></i>
                                                            Código de Descuento Gift Card:</label>
                                                        <div class="input-group mb-3">
                                                            <input type="number" class="form-control"
                                                                id="descuentoGiftCard" v-model="descuentoGiftCard"
                                                                min="0">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="numeroTarjeta"><i
                                                                class="fa fa-credit-card mr-2"></i> Número
                                                            de Tarjeta:</label>
                                                        <div class="input-group mb-3">
                                                            <input type="text" class="form-control" id="numeroTarjeta"
                                                                v-model="numeroTarjeta"
                                                                placeholder="Ingrese el número de tarjeta">
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="d-flex justify-content-between">
                                                        <span><i class="fa fa-money mr-2"></i> Total a Pagar:</span>
                                                        <span class="font-weight-bold h5">

                                                            {{ (calcularTotal * parseFloat(monedaVenta[0])).toFixed(2)
                                                            }} {{
                                                                monedaVenta[1] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" @click="aplicarDescuento"
                                                class="btn btn-success btn-block"><i class="fa fa-check mr-2"></i>
                                                Registrar Pago</button>
                                        </div>
                                    </div>
                                </div>
                            </b-tab>
                            <b-tab>
                                <template #title>
                                    <span class="d-flex align-items-center">
                                        <i class="fa fa-gift fa-2x icon-color mr-2" aria-hidden="true"></i>
                                        <label>Gift Card</label>
                                    </span>
                                </template>
                                <div>
                                    <div class="mt-4">
                                        <form>
                                            <div class="form-group">
                                                <label for="descuentoGiftCard"><i class="fa fa-tag mr-2"></i> Monto de
                                                    la Gift
                                                    Card:</label>
                                                <div class="input-group mb-3">
                                                    <input type="number" class="form-control" id="descuentoGiftCard"
                                                        v-model="descuentoGiftCard" min="0">
                                                </div>
                                            </div>
                                            <button type="button" @click="registrarVenta(27)"
                                                class="btn btn-success btn-block"><i class="fa fa-check mr-2"></i>
                                                Confirmar</button>
                                        </form>
                                    </div>
                                </div>
                            </b-tab>
                            <b-tab>
                                <template #title>
                                    <span class="d-flex align-items-center">
                                        <i class="fa fa-credit-card fa-2x icon-color mr-2" aria-hidden="true"></i>
                                        <label>Tarjeta</label>
                                    </span>
                                </template>
                                <div>
                                    <div class="mt-4">
                                        <form>
                                            <div class="form-group">
                                                <label for="numeroTarjeta"><i class="fa fa-credit-card mr-2"></i> Número
                                                    de
                                                    Tarjeta:</label>
                                                <div class="input-group mb-3">
                                                    <input type="text" class="form-control" id="numeroTarjeta"
                                                        v-model="numeroTarjeta"
                                                        placeholder="Ingrese el número de tarjeta">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="codigoDescuento"><i class="fa fa-gift mr-2"></i>
                                                    Código de Descuento Gift Card:</label>
                                                <div class="input-group mb-3">
                                                    <input type="number" class="form-control" id="descuentoGiftCard"
                                                        v-model="descuentoGiftCard" min="0">
                                                </div>
                                            </div>
                                            <button type="button" @click="aplicarCombinacion"
                                                class="btn btn-success btn-block"><i class="fa fa-check mr-2"></i>
                                                Confirmar</button>
                                        </form>
                                    </div>
                                </div>
                            </b-tab>
                            <b-tab>
                                <template #title>
                                    <span class="d-flex align-items-center">
                                        <i class="fa fa-university fa-2x icon-color mr-2" aria-hidden="true"></i>
                                        <label>Transferencia bancaria</label>
                                    </span>
                                </template>
                                <div>
                                    <span class="text-secondary">SELECCIONE EL BANCO</span>

                                    <div class="mt-4">
                                        <form>
                                            <div class="form-group">
                                                <label for="numeroCuenta"><i class="fa fa-credit-card mr-2"></i> Número
                                                    de
                                                    Cuenta:</label>
                                                <div class="input-group mb-3">

                                                    <input type="text" class="form-control" id="numeroCuenta"
                                                        placeholder="Ingrese el número de cuenta">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="montoTransferencia"><i class="fa fa-money mr-2"></i>
                                                    Monto de
                                                    Transferencia:</label>
                                                <div class="input-group mb-3">

                                                    <input type="number" class="form-control" id="montoTransferencia"
                                                        placeholder="Ingrese el monto de transferencia">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="fechaTransferencia"><i class="fa fa-calendar mr-2"></i>
                                                    Fecha de
                                                    Transferencia:</label>
                                                <div class="input-group mb-3">

                                                    <input type="date" class="form-control" id="fechaTransferencia"
                                                        placeholder="Ingrese la fecha de transferencia">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="numeroOperacion"><i class="fa fa-hashtag mr-2"></i> Número
                                                    de
                                                    Operación:</label>
                                                <div class="input-group mb-3">
                                                    <input type="text" class="form-control" id="numeroOperacion"
                                                        placeholder="Ingrese el número de operación">
                                                </div>
                                            </div>
                                            <button type="button" @click="registrarVenta(7)"
                                                class="btn btn-success btn-block"><i class="fa fa-check mr-2"></i>
                                                Confirmar Transferencia</button>
                                        </form>
                                    </div>
                                </div>
                            </b-tab>
                            <b-tab>
                                <template #title>
                                    <span class="d-flex align-items-center">
                                        <i class="fa fa-list-alt fa-2x icon-color mr-2" aria-hidden="true"></i>
                                        <label>Otros</label>
                                    </span>
                                </template>
                                <div>
                                    <div class="mt-4">
                                        <form>
                                            <div class="form-group">
                                                <label for="otroMetodoPago"><i class="fa fa-tag mr-2"></i> Seleccione un
                                                    Método
                                                    de Pago:</label>
                                                <div class="input-group mb-3">
                                                    <select class="custom-select" id="otroMetodoPago"
                                                        v-model="metodoPago">
                                                        <option value="">Seleccione...</option>
                                                        <option value="32">BILLETERA MOVIL</option>
                                                        <option value="81">BILLETERA MOVIL – PAGO ONLINE</option>
                                                        <option value="31">CANAL DE PAGO</option>
                                                        <option value="79">CANAL DE PAGO – BILLETERA MOVIL</option>
                                                        <option value="80">CANAL DE PAGO – PAGO ONLINE</option>
                                                        <option value="294">CANAL DE PAGO – BILLETERA MOVIL – PAGO
                                                            ONLINE
                                                        </option>
                                                        <option value="3">CHEQUE</option>
                                                        <option value="51">CHEQUE – BILLETERA</option>
                                                        <option value="213">CHEQUE – BILLETERA MOVIL – PAGO ONLINE
                                                        </option>
                                                        <option value="50">CHEQUE – CANAL PAGO</option>
                                                        <option value="211">CHEQUE – CANAL PAGO - BILLETERA MOVIL
                                                        </option>
                                                        <option value="212">CHEQUE – CANAL PAGO - PAGO ONLINE</option>
                                                        <option value="47">CHEQUE – DEPOSITO</option>
                                                        <option value="202">CHEQUE – DEPOSITO EN CUENTA - BILLETERA
                                                            MOVIL
                                                        </option>
                                                        <option value="201">CHEQUE – DEPOSITO EN CUENTA - CANAL DE PAGO
                                                        </option>
                                                        <option value="203">CHEQUE – DEPOSITO EN CUENTA - PAGO ONLINE
                                                        </option>
                                                        <option value="199">CHEQUE – DEPOSITO EN CUENTA - TRANSFERENCIA
                                                            SWIFT
                                                        </option>
                                                        <option value="38">EFECTIVO – PAGO ONLINE</option>
                                                        <option value="39">TARJETA – PAGO POSTERIOR</option>
                                                        <option value="191">CHEQUE – PAGO POSTERIOR - BILLETERA MOVIL
                                                        </option>
                                                        <option value="190">CHEQUE – PAGO POSTERIOR - CANAL DE PAGO
                                                        </option>
                                                        <option value="187">CHEQUE – PAGO POSTERIOR - DEPOSITO EN CUENTA
                                                        </option>
                                                        <option value="192">CHEQUE – PAGO POSTERIOR - PAGO ONLINE
                                                        </option>
                                                        <option value="186">CHEQUE – PAGO POSTERIOR - TRANSFERENCIA
                                                            BANCARIA
                                                        </option>
                                                        <option value="188">CHEQUE – PAGO POSTERIOR - TRANSFERENCIA
                                                            SWIFT
                                                        </option>
                                                        <option value="48">CHEQUE – SWIFT</option>
                                                        <option value="206">CHEQUE – SWIFT - BILLETERA MOVIL</option>
                                                        <option value="207">CHEQUE – SWIFT - PAGO ONLINE</option>
                                                        <option value="208">CHEQUE – GIFT - CANAL DE PAGO</option>
                                                        <option value="46">CHEQUE – TRANSFERENCIA BANCARIA</option>
                                                        <option value="197">CHEQUE – TRANSFERENCIA BANCARIA – BILLETERA
                                                            MOVIL
                                                        </option>
                                                        <option value="196">CHEQUE – TRANSFERENCIA BANCARIA – CANAL DE
                                                            PAGO
                                                        </option>
                                                        <option value="193">CHEQUE – TRANSFERENCIA BANCARIA – DEPOSITO
                                                            EN CUENTA
                                                        </option>
                                                        <option value="198">CHEQUE – TRANSFERENCIA BANCARIA – PAGO
                                                            ONLINE
                                                        </option>
                                                        <option value="194">CHEQUE – TRANSFERENCIA BANCARIA –
                                                            TRANSFERENCIA
                                                            SWIFT</option>
                                                        <option value="44">CHEQUE – VALES</option>
                                                        <option value="178">CHEQUE – VALES - PAGO POSTERIOR</option>
                                                        <option value="179">CHEQUE – VALES - TRANSFERENCIA BANCARIA
                                                        </option>
                                                        <option value="180">CHEQUE – VALES - DEPOSITO EN CUENTA</option>
                                                        <option value="181">CHEQUE – VALES - TRANSFERENCIA SWIFT
                                                        </option>
                                                        <option value="183">CHEQUE – VALES - CANAL DE PAGO</option>
                                                        <option value="184">CHEQUE – VALES - BILLETERA MOVIL</option>
                                                        <option value="185">CHEQUE – VALES - PAGO ONLINE</option>
                                                        <option value="295">DEBITO AUTOMATICO</option>
                                                        <option value="296">DEBITO AUTOMATICO – EFECTIVO</option>
                                                        <option value="297">DEBITO AUTOMATICO -TARJETA</option>
                                                        <option value="298">DEBITO AUTOMATICO – CHEQUE</option>
                                                        <option value="299">DEBITO AUTOMATICO - VALE</option>
                                                        <option value="300">DEBITO AUTOMATICO - PAGO POSTERIOR</option>
                                                        <option value="301">DEBITO AUTOMATICO - TRANSFERENCIA BANCARIA
                                                        </option>
                                                        <option value="302">DEBITO AUTOMATICO - DEPOSITO EN CUENTA
                                                        </option>
                                                        <option value="303">DEBITO AUTOMATICO - TRANSFERENCIA SWIFT
                                                        </option>
                                                        <option value="304">DEBITO AUTOMATICO - GIFT CARD</option>
                                                        <option value="305">DEBITO AUTOMATICO - CANAL DE PAGO</option>
                                                        <option value="306">DEBITO AUTOMATICO - BILLETERA MOVIL</option>
                                                        <option value="307">DEBITO AUTOMATICO - PAGO ONLINE</option>
                                                        <option value="308">DEBITO AUTOMATICO – OTRO</option>
                                                        <option value="8">DEPOSITO EN CUENTA</option>
                                                        <option value="71">DEPOSITO EN CUENTA – PAGO ONLINE</option>
                                                        <option value="276">DEPOSITO EN CUENTA – SWIFT – CANAL DE PAGO
                                                        </option>
                                                        <option value="277">DEPOSITO EN CUENTA – SWIFT – BILLETERA MOVIL
                                                        </option>
                                                        <option value="278">DEPOSITO EN CUENTA – SWIFT – PAGO ONLINE
                                                        </option>
                                                        <option value="282">DEPOSITO EN CUENTA – CANAL DE PAGO –
                                                            BILLETERA MOVIL
                                                        </option>
                                                        <option value="70">DEPOSITO EN CUENTA – BILLETERA MOVIL</option>
                                                        <option value="284">DEPOSITO EN CUENTA – BILLETERA MOVIL – PAGO
                                                            ONLINE
                                                        </option>
                                                        <option value="69">DEPOSITO EN CUENTA – CANAL DE PAGO</option>
                                                        <option value="283">DEPOSITO EN CUENTA – CANAL DE PAGO – PAGO
                                                            ONLINE
                                                        </option>
                                                        <option value="5">OTROS</option>
                                                        <option value="33">PAGO ONLINE</option>
                                                        <option value="6">PAGO POSTERIOR</option>
                                                        <option value="62">PAGO POSTERIOR – BILLETERA</option>
                                                        <option value="259">PAGO POSTERIOR – BILLETERA MOVIL - PAGO
                                                            ONLINE
                                                        </option>
                                                        <option value="61">PAGO POSTERIOR – CANAL</option>
                                                        <option value="257">PAGO POSTERIOR – CANAL DE PAGO - BILLETERA
                                                            MOVIL
                                                        </option>
                                                        <option value="258">PAGO POSTERIOR – CANAL DE PAGO - PAGO ONLINE
                                                        </option>
                                                        <option value="58">PAGO POSTERIOR – DEPOSITO EN CUENTA</option>
                                                        <option value="245">PAGO POSTERIOR – DEPOSITO EN CUENTA –
                                                            TRANSFERENCIA
                                                            SWIFT</option>
                                                        <option value="247">PAGO POSTERIOR – DEPOSITO EN CUENTA – CANAL
                                                            DE PAGO
                                                        </option>
                                                        <option value="248">PAGO POSTERIOR – DEPOSITO EN CUENTA –
                                                            BILLETERA
                                                            MOVIL</option>
                                                        <option value="249">PAGO POSTERIOR – DEPOSITO EN CUENTA – PAGO
                                                            ONLINE
                                                        </option>
                                                        <option value="63">PAGO POSTERIOR – PAGO ONLINE</option>
                                                        <option value="59">PAGO POSTERIOR – SWIFT</option>
                                                        <option value="251">PAGO POSTERIOR – SWIFT - CANAL DE PAGO
                                                        </option>
                                                        <option value="252">PAGO POSTERIOR – SWIFT - BILLETERA MOVIL
                                                        </option>
                                                        <option value="253">PAGO POSTERIOR – SWIFT - PAGO ONLINE
                                                        </option>
                                                        <option value="57">PAGO POSTERIOR – TRANSFERENCIA BANCARIA
                                                        </option>
                                                        <option value="239">PAGO POSTERIOR – TRANSFERENCIA BANCARIA –
                                                            DEPOSITO
                                                            EN CUENTA</option>
                                                        <option value="240">PAGO POSTERIOR – TRANSFERENCIA BANCARIA –
                                                            TRANSFERENCIA SWIFT</option>
                                                        <option value="242">PAGO POSTERIOR – TRANSFERENCIA BANCARIA –
                                                            CANAL DE
                                                            PAGO</option>
                                                        <option value="243">PAGO POSTERIOR – TRANSFERENCIA BANCARIA –
                                                            BILLETERA
                                                            MOVIL</option>
                                                        <option value="244">PAGO POSTERIOR – TRANSFERENCIA BANCARIA –
                                                            PAGO
                                                            ONLINE</option>
                                                        <option value="74">SWIFT – BILLETERA MOVIL</option>
                                                        <option value="290">SWIFT – BILLETERA MOVIL – PAGO ONLINE
                                                        </option>
                                                        <option value="291">GIFT-CARD – CANAL DE PAGO – BILLETERA MOVIL
                                                        </option>
                                                        <option value="292">GIFT-CARD – CANAL DE PAGO – PAGO ONLINE
                                                        </option>
                                                        <option value="73">SWIFT – CANAL DE PAGO</option>
                                                        <option value="75">SWIFT – PAGO ONLINE</option>
                                                        <option value="7">TRANSFERENCIA BANCARIA</option>
                                                        <option value="66">TRANSFERENCIA BANCARIA – BILLETERA MOVIL
                                                        </option>
                                                        <option value="274">TRANSFERENCIA BANCARIA – BILLETERA MOVIL –
                                                            PAGO
                                                            ONLINE</option>
                                                        <option value="65">TRANSFERENCIA BANCARIA – CANAL DE PAGO
                                                        </option>
                                                        <option value="272">TRANSFERENCIA BANCARIA – CANAL DE PAGO –
                                                            BILLETERA
                                                            MOVIL</option>
                                                        <option value="273">TRANSFERENCIA BANCARIA – CANAL DE PAGO –
                                                            PAGO ONLINE
                                                        </option>
                                                        <option value="260">TRANSFERENCIA BANCARIA – DEPOSITO EN CUENTA
                                                            –
                                                            TRANSFERENCIA SWIFT</option>
                                                        <option value="262">TRANSFERENCIA BANCARIA – DEPOSITO EN CUENTA
                                                            – CANAL
                                                            DE PAGO</option>
                                                        <option value="263">TRANSFERENCIA BANCARIA – DEPOSITO EN CUENTA
                                                            –
                                                            BILLETERA MOVIL</option>
                                                        <option value="67">TRANSFERENCIA BANCARIA – PAGO ONLINE</option>
                                                        <option value="266">TRANSFERENCIA BANCARIA – SWIFT – CANAL DE
                                                            PAGO
                                                        </option>
                                                        <option value="267">TRANSFERENCIA BANCARIA – SWIFT – BILLETERA
                                                            MOVIL
                                                        </option>
                                                        <option value="268">TRANSFERENCIA BANCARIA – SWIFT – PAGO ONLINE
                                                        </option>
                                                        <option value="24">TRANSFERENCIA BANCARIA-DEPOSITO EN CUENTA
                                                        </option>
                                                        <option value="25">TRANSFERENCIA BANCARIA-TRANSFERENCIA SWIFT
                                                        </option>
                                                        <option value="264">TRANSFERENCIA BANCARIA– DEPOSITO EN CUENTA –
                                                            PAGO
                                                            ONLINE</option>
                                                        <option value="9">TRANSFERENCIA SWIFT</option>
                                                        <option value="4">VALES</option>
                                                        <option value="55">VALES – BILLETERA MOVIL</option>
                                                        <option value="233">VALES – BILLETERA MOVIL – CANAL DE PAGO
                                                        </option>
                                                        <option value="234">VALES – BILLETERA MOVIL – BILLETERA MOVIL
                                                        </option>
                                                        <option value="235">VALES – BILLETERA MOVIL – PAGO ONLINE
                                                        </option>
                                                        <option value="54">VALES – CANAL DE PAGO</option>
                                                        <option value="227">VALES – CANAL DE PAGO – TRANSFERENCIA SWIFT
                                                        </option>
                                                        <option value="229">VALES – CANAL DE PAGO – CANAL DE PAGO
                                                        </option>
                                                        <option value="230">VALES – CANAL DE PAGO – BILLETERA MOVIL
                                                        </option>
                                                        <option value="231">VALES – CANAL DE PAGO – PAGO ONLINE</option>
                                                        <option value="22">VALES – DEPOSITO EN CUENTA</option>
                                                        <option value="56">VALES – PAGO ONLINE</option>
                                                        <option value="236">VALES – PAGO ONLINE - CANAL DE PAGO</option>
                                                        <option value="237">VALES – PAGO ONLINE - BILLETERA MOVIL
                                                        </option>
                                                        <option value="238">VALES – PAGO ONLINE - PAGO ONLINE</option>
                                                        <option value="214">VALES – SWIFT - TRANSFERENCIA BANCARIA
                                                        </option>
                                                        <option value="215">VALES – SWIFT - DEPOSITO EN CUENTA</option>
                                                        <option value="216">VALES – SWIFT - TRANSFERENCIA SWIFT</option>
                                                        <option value="218">VALES – SWIFT - CANAL DE PAGO</option>
                                                        <option value="219">VALES – SWIFT - BILLETERA MOVIL</option>
                                                        <option value="220">VALES – SWIFT - PAGO ONLINE</option>
                                                        <option value="21">VALES-TRANSFERENCIA BANCARIA</option>
                                                        <option value="23">VALES-TRANSFERENCIA SWIFT</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <button type="button" @click="otroMetodo(metodoPago)"
                                                class="btn btn-success btn-block"><i class="fa fa-check mr-2"></i>
                                                Confirmar</button>
                                        </form>
                                    </div>
                                </div>
                            </b-tab>
                            <!--<b-tab>
                                <template #title>
                                    <span class="d-flex align-items-center">
                                        <i class="fa fa-qrcode fa-2x icon-color mr-2" aria-hidden="true"></i>
                                        <label>QR</label>
                                    </span>
                                </template>
                                <div class="text-center">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=Ejemplo"
                                        alt="Código QR" class="img-fluid">
                                    <p class="mt-3">Escanee el código QR para realizar el pago.</p>
                                </div>
                            </b-tab>-->
                        </b-tabs>
                    </div>
                    <!-- <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="cerrarModal2()">Volver</button>
                        <button type="button" v-if="tipoAccion2 == 1" class="btn btn-primary"
                            @click="registrar()">Cobrar</button>
                    </div> -->
                </div>
            </div>
        </div>
        <!--##################---HASTA AQUI---####################-->
        <!--##################---MODAL CREDITOS---####################-->
        <div class="modal " tabindex="-1" :class="{ 'mostrar': modal3 }" role="dialog" aria-labelledby="myModalLabel"
            style="display: none;" aria-hidden="true">
            <div class="modal-dialog modal-primary modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header responsive">
                        <h4 class="modal-title" v-text="tituloModal3"></h4>
                        <button type="button" class="close" @click="cerrarModal3()" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="" class="font-weight-bold">Cantidad de cuotas
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="number" id="nombreCliente" class="form-control" v-model="numero_cuotas">
                            </div>

                            <div class="col-md-4">
                                <label for="" class="font-weight-bold">Frecuencia de Pagos
                                    <span class="text-danger">*</span>
                                </label>

                                <div class="input-group mb-3">
                                    <input type="number" class="form-control" v-model="tiempo_diaz">
                                    <div class="input-group-append">
                                        <span class="input-group-text">Dias</span>
                                    </div>
                                </div>

                            </div>





                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">Total</label>
                                    <label>

                                        {{ (calcularTotal * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                        monedaVenta[1] }}

                                    </label>
                                    <button @click="generarCuotas" type="button" class="btn btn-success">GENERAR
                                        CUOTA</button>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" v-model="primera_cuota">
                                    <label class="form-check-label" for="defaultCheck1">
                                        Primera cuota pagada
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-3" v-if="primera_cuota">
                                <div class="form-group">
                                    <label class="form-control-label" for="select-input">Tipo de Pago</label>
                                    <select class="form-control" id="select-input" v-model="tipo_pago"
                                        @change="seleccionarTipoPago(tipo_pago)">
                                        <option v-for="(value, key) in tiposPago" :value="value">{{ key }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!--################################-listado de cuotas-#######################-->
                        <div class="form-group row border">
                            <div class="table-responsive col-md-12">
                                <table class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Fecha Pago</th>
                                            <th>Precio_Cuota</th>
                                            <th>Total Cancelado</th>
                                            <th>Saldo</th>
                                            <th>Fecha Cancelado</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(cuota, index) in cuotas" :key="index">
                                            <td>{{ index + 1 }}</td>

                                            <td>{{ new Date(cuota.fecha_pago).toLocaleDateString('es-ES') }}</td>

                                            <td>
                                                {{ (cuota.precio_cuota * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                                monedaVenta[1] }}

                                            </td>
                                            <td>
                                                {{ (cuota.totalCancelado * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                                monedaVenta[1] }}

                                            </td>
                                            <td>
                                                {{ (cuota.saldo_restante * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                                monedaVenta[1] }}

                                            </td>
                                            <td>{{ cuota.fecha_cancelado ? new Date(cuota.fecha_cancelado
                                            ).toLocaleDateString('es-ES') : "Sin fecha" }}</td>

                                            <td>{{ cuota.estado }}</td>
                                        </tr>
                                    </tbody>
                                    <!-- <tbody v-else>
                                        <tr>
                                            <td colspan="4">
                                                No hay Calculo
                                            </td>
                                        </tr>
                                    </tbody> -->
                                </table>
                            </div>
                        </div>
                        <!--######################################-hasta QUI-################################-->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="cerrarModal3()">Volver</button>
                        <button type="button" v-if="tipoAccion3 == 1" class="btn btn-primary"
                            @click="registrarVenta()">Registrar</button>
                    </div>
                </div>
            </div>
        </div>
        <!--#################-------HASTA AQUI MODAL CREDITOS------#################-->
        <div class="modal " tabindex="-1" :class="{ 'mostrar': modalDetalleKit }" role="dialog"
            aria-labelledby="myModalLabel" style="display: none;" aria-hidden="true">
            <div class="modal-dialog modal-primary modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        Detalle del kit
                    </div>
                    <div class="modal-body">
                        <div class="col-md-8">
                            <label for="" class="font-weight-bold">Nombre del kit: </label>
                            {{ datosFormularioKit.nombre }}
                        </div>
                        <div class="col-md-5">
                            <label for="" class="font-weight-bold">Codigo: </label>
                            {{ datosFormularioKit.codigo }}
                        </div>
                        <div class="col-md-5">
                            <label for="" class="font-weight-bold">Precio: </label>
                            {{ (datosFormularioKit.precio * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                monedaVenta[1]
                            }}
                        </div>
                        <div class="col-md-5">
                            <label for="" class="font-weight-bold">Fecha final: </label>
                            {{ datosFormularioKit.fecha_final }}
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre comercial</th>
                                        <th>Costo unidad</th>
                                        <th>Costo paquete</th>
                                        <th>Cantidad</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="articulo in arrayArticulosKit" :key="articulo.id">

                                        <td v-text="articulo.codigo"></td>
                                        <td v-text="articulo.nombre"></td>
                                        <td>
                                            <p>{{ (articulo.precio_costo_unid * parseFloat(monedaVenta[0])) }} {{
                                                monedaVenta[1] }} </p>

                                        </td>
                                        <td>
                                            <p>{{ (articulo.precio_costo_paq) * parseFloat(monedaVenta[0]) }} {{
                                                monedaVenta[1] }} </p>
                                        </td>
                                        <td>
                                            {{ articulo.cantidad }}
                                        </td>


                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" @click="() => modalDetalleKit = 0">Cerrar</button>

                    </div>
                </div>
            </div>
        </div>
        <div>
            <AbonarCuota v-if="cuotaSeleccionada" :cuota="cuotaSeleccionada" :modal="modalCuotas" :moneda="monedaVenta"
                :ventaCredito="arraySeleccionado" :arrayCuotas="arrayCuotas" @cerrar-modal="cerrarModalCuotas" />
        </div>
    </main>
</template>

<script>
import vSelect from 'vue-select';
import { TileSpinner } from 'vue-spinners';

export default {
    data() {
        return {
            clienteDeudas: 0,
            arrayCuotas: [],
            arraySeleccionado: [],
            cuotaSeleccionada: null,
            modalCuotas: 0,

            tipo_pago: '',
            criterioKit: 'nombre',
            buscarKit: '',

            mensajesKit: [],
            arrayArticulosKit: [],
            datosFormularioKit: [],
            modalDetalleKit: 0,
            arrayKit: [],

            arrayPromocion: [],
            unidadPaquete: 1,

            monedaVenta: [],
            permitirDevolucion: '',
            saldosNegativos: 1,
            venta_id: 0,
            idcliente: 0,
            usuarioAutenticado: null,
            puntoVentaAutenticado: null,
            cliente: '',
            email: '',
            nombreCliente: '',
            documento: '',
            tipo_documento: '',
            complemento_id: '',
            descuentoAdicional: 0.00,
            descuentoGiftCard: '',
            tipo_comprobante: 'FACTURA',
            serie_comprobante: '',
            last_comprobante: 0,
            num_comprob: "",
            impuesto: 0.18,
            total: 0.0,
            totalImpuesto: 0.0,
            totalParcial: 0.0,
            arrayVenta: [],
            arrayCliente: [],
            arrayDetalle: [],
            arrayProductos: [],
            listado: 1,
            modal: 0,
            tituloModal: '',
            tipoAccion: 0,
            errorVenta: 0,
            errorMostrarMsjVenta: [],
            pagination: {
                'total': 0,
                'current_page': 0,
                'per_page': 0,
                'last_page': 0,
                'from': 0,
                'to': 0,
            },
            offset: 3,
            criterio: 'num_comprobante',
            buscar: '',
            criterioA: 'nombre',
            buscarA: '',
            arrayArticulo: [],
            arraySeleccionado: [],
            idarticulo: 0,
            codigo: '',
            articulo: '',
            medida: '',
            codigoClasificador: '',
            codigoProductoSin: '',
            precio: 0,
            unidad_envase: 0,
            cantidad: 1,
            paquni: '',
            precioBloqueado: false,
            descuento: 0,
            descuentoProducto: 0,
            sTotal: 0,
            stock: 0,
            valorMaximoDescuento: '',
            mostrarComunicacionSiat: false,
            mostrarCuis: false,
            mostrarCufd: false,
            mostrarDireccion: false,
            mostrarCUFD: false,
            mostrarEnviarPaquete: true,
            mostrarValidarPaquete: false,
            cafc: '',
            fecha: '',
            scodigomotivo: null,
            casosEspeciales: false,
            numeroTarjeta: null,
            leyendaAl: '',
            codigoExcepcion: 0,
            mostrarSpinner: false,
            metodoPago: '',
            primer_precio_cuota: 0,

            //almacenes
            arrayAlmacenes: [],
            idAlmacen: null,
            //-----PRECIOS- AUMENTE 3/OCTUBRE--------
            precioseleccionado: '',
            //precio : '',
            arrayPrecios: [],
            nombre_precio: '',
            precio_uno: '',
            precio_dos: '',
            precio_tres: '',
            precio_cuatro: '',
            //-----MODAL 2---
            modal2: 0,
            tituloModal2: '',
            tipoAccion2: '',
            //-----MODAL 3---
            modal3: 0,
            tituloModal3: '',
            tipoAccion3: '',
            //--nue☺8os datos--
            // fecha_credito : '',
            // proforma : '',
            // refe_pago : '',
            // orden_compra : '',
            // fecha_entrega : '',
            // checkboxHabilitado :'',
            // diasToAdd : null,
            //----CALCULADORA---
            //totalprue: 80,//PRUEBA PORQUE NO DA EL CALCULAR
            recibido: 0,
            efectivo: 0,
            cambio: 0,
            faltante: 0,
            //-------DEStE AQUI 13-OCTUBRE--
            idtipo_pago: '',
            idtipo_venta: '1',
            tiempo_diaz: '',
            numero_cuotas: '',
            cuotas: [],//---para almacenar las fechas
            estadocrevent: 'activo',
            primera_cuota: '',
            habilitarPrimeraCuota: false,
            tipoPago: 'EFECTIVO',
            tiposPago: {
                EFECTIVO: 1,
                TARJETA: 2,
                QR: 3
            },
        }
    },
    watch: {
        codigo(newValue) {
            if (newValue) {
                this.buscarArticulo();
            }
        }
    },
    components: {
        TileSpinner,
        vSelect
    },
    computed: {

        resultadoMultiplicacion() {
            if (this.arraySeleccionado) {
                return this.precioseleccionado * this.unidadPaquete * this.cantidad;
            }
        },


        isActived: function () {
            return this.pagination.current_page;
        },

        //Calcula los elementos de la paginación
        pagesNumber: function () {
            if (!this.pagination.to) {
                return [];
            }

            var from = this.pagination.current_page - this.offset;
            if (from < 1) {
                from = 1;
            }

            var to = from + (this.offset * 2);
            if (to >= this.pagination.last_page) {
                to = this.pagination.last_page;
            }

            var pagesArray = [];
            while (from <= to) {
                pagesArray.push(from);
                from++;
            }
            return pagesArray;

        },

        calcularTotal: function () {
            var resultado = 0.0;
            for (var i = 0; i < this.arrayDetalle.length; i++) {
                resultado += ((this.arrayDetalle[i].precioseleccionado * this.arrayDetalle[i].cantidad) - (this.arrayDetalle[i].precioseleccionado * this.arrayDetalle[i].cantidad * this.arrayDetalle[i].descuento / 100))

            }
            resultado -= this.descuentoAdicional;
            resultado -= this.descuentoGiftCard;
            return resultado;
        },

        calcularSubTotal: function () {
            var resultado = 0.0;
            for (var i = 0; i < this.arrayDetalle.length; i++) {
                resultado = resultado + ((this.arrayDetalle[i].precioseleccionado * this.arrayDetalle[i].cantidad) - (this.arrayDetalle[i].precioseleccionado * this.arrayDetalle[i].cantidad * this.arrayDetalle[i].descuento / 100))
            }
            return resultado;
        },

    },
    methods: {
        abrirTipoVenta() {
            if (this.idtipo_venta == 1) {
                this.modal2 = 1;
                this.cliente = this.nombreCliente;
                this.tipoAccion2 = 1;
                this.scrollToTop()
            } else {
                this.modal3 = 1;
                this.cliente = this.nombreCliente;
                this.scrollToTop();
                this.tituloModal3 = 'CREDITOS ' + this.cliente;
                this.tipoAccion3 = 1;
                this.idtipo_pago = this.tipo_pago;
            }


        },
        cerrarModalCuotas() {
            this.modalCuotas = 0;
            this.listarVenta(1, this.buscar, this.criterio);

        },
        abrirModalCuotas(idventa) {
            this.modalCuotas = 1;
            axios.get(`/credito/cuotas/venta/${idventa}`)
                .then(response => {
                    this.arrayCuotas = response.data.cuotasCredito;
                    this.arraySeleccionado = response.data.creditoVenta;
                    const cuotasPendientes = this.arrayCuotas.filter(cuota => cuota.estado === 'Pendiente');
                    this.cuotaSeleccionada = cuotasPendientes.find(cuota => cuota.estado === 'Pendiente');
                })
                .catch(error => {
                    console.error('Error al obtener crédito y cuotas:', error);
                });

        },
        GetValidateKit(idPromocion) {
            return axios.get('/kits/id', {
                params: {
                    idPromocion: idPromocion
                }
            })
                .then((response) => {
                    const datos = response.data;
                    console.log(datos);

                    this.arrayArticulosKit = datos.articulosPorPromocion.map(item => ({
                        ...item.articulo,
                        cantidad: item.cantidad
                    }));
                    this.mensajesKit = datos.mensajes;
                })
                .catch((error) => {
                    console.error(error);
                    throw error; // Re-lanza el error para que pueda ser manejado en agregarKit
                });
        },
        seleccionarTipoPago(tipo) {
            this.tipoPago = tipo;
            this.tituloModal2 = `TIPO DE PAGO : ${tipo}`;
            this.idtipo_pago = this.tiposPago[tipo];
        },

        agregarKit(kit) {
            if (new Date(kit.fecha_final) < new Date()) {
                swal({
                    type: 'error',
                    title: 'Error...',
                    text: 'Este kit ha expirado!',
                });
                return;
            }
            //   this.GetValidateKit(kit['id'])
            this.GetValidateKit(kit['id'])
                .then(() => {

                    if (this.mensajesKit.length == 0) {

                        const totalKit = this.arrayArticulosKit.reduce((total, producto) => {
                            return total + (producto.cantidad * producto.precio_costo_unid);
                        }, 0);
                        this.arrayArticulosKit.forEach(producto => {
                            producto.porcentaje = ((producto.cantidad * producto.precio_costo_unid) / totalKit) * 100;
                        });

                        this.arrayArticulosKit.forEach(producto => {
                            producto.nuevo_precio = (kit.precio * producto.porcentaje) / 100;
                        });
                        console.log("Estos son los articulos: ", this.arrayArticulosKit);
                        this.arrayArticulosKit.forEach(articulo => {
                            this.arrayDetalle.push({
                                idkit: kit['id'],
                                idarticulo: articulo.id,
                                articulo: articulo.nombre,
                                medida: "KIT",
                                unidad_envase: articulo.unidad_envase,
                                cantidad: articulo.cantidad,
                                cantidad_paquetes: articulo.unidad_envase * articulo.cantidad,
                                precio: articulo.nuevo_precio,
                                descuento: (((articulo.precio_costo_unid * articulo.cantidad) - (articulo.nuevo_precio)) / (articulo.precio_costo_unid * articulo.cantidad)) * 100,
                                stock: articulo.stock,
                                precioseleccionado: articulo.precio_costo_unid
                            });
                            let actividadEconomica = 461021;

                            this.arrayProductos.push({

                                actividadEconomica: actividadEconomica,
                                codigoProductoSin: articulo.id,
                                codigoProducto: articulo.codigo,
                                descripcion: articulo.nombre,
                                cantidad: articulo.cantidad,
                                unidadMedida: 25,
                                precioUnitario: parseFloat(articulo.precio_costo_unid * this.monedaVenta[0]).toFixed(2),
                                montoDescuento: ((articulo.precio_costo_unid * articulo.cantidad) * this.monedaVenta[0] - articulo.nuevo_precio * this.monedaVenta[0]).toFixed(2),
                                subTotal: (parseFloat(articulo.nuevo_precio * this.monedaVenta[0])).toFixed(2),
                                numeroSerie: null,
                                numeroImei: null
                            });
                            this.cerrarModal()
                        });
                        // this.arrayDetalle.push({
                        //                 idarticulo: this.arraySeleccionado.id,
                        //                 articulo: this.arraySeleccionado.nombre,
                        //                 medida: this.arraySeleccionado.medida,
                        //                 unidad_envase: this.arraySeleccionado.unidad_envase,
                        //                 cantidad: this.cantidad * this.unidadPaquete,
                        //                 cantidad_paquetes: this.arraySeleccionado.unidad_envase,
                        //                 precio: precioArticulo,
                        //                 descuento: this.arrayPromocion && this.arrayPromocion.porcentaje !== undefined ? this.arrayPromocion.porcentaje : 0,
                        //                 stock: this.arraySeleccionado.saldo_stock,
                        //                 precioseleccionado: this.precioseleccionado

                        //             });

                    } else {

                        swal({
                            type: 'error',
                            title: 'Stock insuficiente',
                            text: this.mensajesKit.join("\n\n"),
                        })

                    }

                })
                .catch((error) => {
                    // Maneja el error aquí
                    console.error(error);
                });
        },
        abrirModalDetallesKit(data) {
            this.arrayArticulosSeleccionados = [];

            this.modalDetalleKit = 1;
            this.datosFormularioKit = {
                id: data['id'],
                nombre: data['nombre'],
                porcentaje: data['porcentaje'],
                codigo: data['codigo'],

                fecha_final: (new Date(data['fecha_final'])).toISOString().split('T')[0],
                tipo_promocion: data['tipo_promocion'],
                estado: data['estado'],
                precio: data['precio'],

            };
            this.obtenerDatosKit(data['id'])
        },
        obtenerDatosKit(idPromocion) {
            return axios.get('/ofertas/id', {
                params: {
                    idPromocion: idPromocion
                }
            })
                .then((response) => {
                    const datos = response.data.articulosPorPromocion;
                    console.log(datos);

                    this.arrayArticulosKit = datos.map(item => ({
                        ...item.articulo,
                        cantidad: item.cantidad
                    }));
                })
                .catch((error) => {
                    console.error(error);
                    throw error; // Re-lanza el error para que pueda ser manejado en agregarKit
                });
        },

        getColorForEstado(estado, fecha_final) {
            const fechaFinal = new Date(fecha_final) < new Date();

            if (fechaFinal) {
                return '#ff0000';
            }
            switch (estado) {
                case 'Activo':
                    return '#5ebf5f';
                case 'Inactivo':
                    return '#d76868';
                case 'Agotado':
                    return '#ce4444';
                default:
                    return '#f9dda6';
            }
        },

        listarKits(page, buscar, criterio) {
            let me = this;
            let url = '/kits';

            axios.get(url, {
                params: {
                    page: page,
                    buscar: buscar,
                    criterio: criterio
                }
            }).then(function (response) {
                let respuesta = response.data;
                me.arrayKit = response.data.ofertas.data;
                // me.pagination = respuesta.pagination;
            }).catch(function (error) {
                console.log(error);
            });
        },
        scrollToSection() {
            $('html, body').animate({
                scrollTop: $('#seccionObjetivo').offset().top
            }, 50);
        },

        scrollToTop() {
            $('html, body').animate({
                scrollTop: 0
            }, 50);
        },
        calcularPrecioConDescuento(precioOriginal, porcentajeDescuento) {
            /*const descuento = precioOriginal * (porcentajeDescuento / 100);
            const precioConDescuento = precioOriginal - descuento;
            return precioConDescuento;*/
            const descuento = this.precioseleccionado * (this.descuentoProducto / 100);
            const precioConDescuento = this.precioseleccionado - descuento;
            const precioFinal = precioConDescuento * this.unidadPaquete * this.cantidad;
            return precioFinal;
        },
        calcularDiasRestantes(fechaFinal) {
            const fechaActual = new Date();
            const fechaObjetivo = new Date(fechaFinal);
            const diferenciaEnMilisegundos = fechaObjetivo - fechaActual;
            const diasRestantes = Math.ceil(diferenciaEnMilisegundos / (1000 * 60 * 60 * 24));
            return diasRestantes;
        },
        actualizarDetalle(index) {
            this.arrayDetalle[index].total = (this.arrayDetalle[index].precioseleccionado * this.arrayDetalle[index].cantidad).toFixed(2);
        },
        actualizarDetalleDescuento(index) {
            this.calcularTotal(index);
        },
        validarDescuentoAdicional() {
            if (this.descuentoAdicional >= this.totalParcial) {
                this.descuentoAdicional = 0;
                alert("El descuento adicional no puede ser mayor o igual al total.");
            }
        },
        validarDescuentoGiftCard() {

            if (this.descuentoGiftCard >= this.calcularTotal) {
                this.descuentoGiftCard = 0;
                alert("El descuento Gift Card no puede ser mayor o igual al total.");
            }
        },

        habilitarNombreCliente() {
            if (this.casosEspeciales) {
                this.$refs.nombreRef.removeAttribute("readonly");
                this.documento = "99001";
                this.idcliente = "2";
                this.tipo_documento = "5";
            } else {
                this.$refs.nombreRef.setAttribute("readonly", true);
                this.documento = "";
                this.idcliente = "";
                this.tipo_documento = "";
            }
        },

        buscarPromocion(idArticulo) {
            // Supongamos que el ID del artículo es 1, ajusta según tus necesidades

            axios.get(`/promocion/id?idArticulo=${idArticulo}`)
                .then(response => {
                    // Maneja la respuesta aquí
                    this.arrayPromocion = response.data.promocion
                    console.log(response.data);
                })
                .catch(error => {
                    // Maneja los errores aquí
                    console.error('Error:', error);
                });
        },
        atajoButton: function (event) {
            //console.log(event.keyCode);
            //console.log(event.ctrlKey);
            if (event.shiftKey && event.keyCode === 81) {
                event.preventDefault();
                this.$refs.impuestoRef.focus();
            }
            if (event.shiftKey && event.keyCode === 87) {
                event.preventDefault();
                this.$refs.serieComprobanteRef.focus();
            }
            if (event.shiftKey && event.keyCode === 69) {
                event.preventDefault();
                this.$refs.numeroComprobanteRef.focus();
            }
            if (event.shiftKey && event.keyCode === 82) {
                event.preventDefault();
                this.$refs.articuloRef.focus();
            }
            if (event.shiftKey && event.keyCode === 84) {
                event.preventDefault();
                this.$refs.precioRef.focus();
            }
            if (event.shiftKey && event.keyCode === 89) {
                event.preventDefault();
                this.$refs.cantidadRef.focus();
            }
            if (event.shiftKey && event.keyCode === 65) {
                event.preventDefault();
                this.$refs.cantidad_paquetesRef.focus();
            }
            if (event.shiftKey && event.keyCode === 85) {
                event.preventDefault();
                this.$refs.descuentoRef.focus();
            }
        },

        verificarComunicacion() {
            axios.post('/venta/verificarComunicacion')
                .then(function (response) {
                    if (response.data.RespuestaComunicacion.transaccion === true) {
                        document.getElementById("comunicacionSiat").innerHTML = response.data.RespuestaComunicacion.mensajesList.descripcion;
                        document.getElementById("comunicacionSiat").className = "badge bg-success";
                        // Actualiza el valor de scuis
                        //this.scuis = response.data.scuis;
                    } else {
                        document.getElementById("comunicacionSiat").innerHTML = "Desconectado";
                        document.getElementById("comunicacionSiat").className = "badge bg-secondary";
                        // Actualiza el valor de scuis
                        //this.scuis = "Inexistente";
                    }
                })
                .catch(function (error) {
                    console.log(error);
                });
        },

        cuis() {
            axios.post('/venta/cuis')
                .then(function (response) {
                    if (response.data.RespuestaCuis.transaccion === false) {
                        document.getElementById("cuis").innerHTML = "CUIS: " + response.data.RespuestaCuis.codigo;
                        document.getElementById("cuis").className = "badge bg-primary";
                    } else {
                        document.getElementById("cuis").innerHTML = "CUIS: Inexistente";
                        document.getElementById("cuis").className = "badge bg-secondary";
                    }
                })
                .catch(function (error) {
                    console.log(error);
                });
        },
        cufd() {
            axios.post('/venta/cufd')
                .then(function (response) {
                    if (response.data.transaccion != false) {
                        document.getElementById("cufd").innerHTML = "CUFD vigente: " + response.data.fechaVigencia.substring(0, 16);
                        document.getElementById("direccion").innerHTML = response.data.direccion;
                        document.getElementById("cufdValor").innerHTML = response.data.codigo;
                        document.getElementById("cufd").className = "badge bg-info";
                    } else {
                        document.getElementById("cufd").innerHTML = "No existe CUFD vigente";
                        document.getElementById("cufd").className = "badge bg-secondary";
                    }
                })
                .catch(function (error) {
                    console.log(error);
                });
        },

        obtenerNuevoCUFD() {
            axios.post('/venta/nuevoCufd')
                .then(function (response) {
                    console.log(response);
                    if (response.data.RespuestaCufd.transaccion != false) {
                        document.getElementById("cufd").innerHTML = "CUFD vigente: " + response.data.RespuestaCufd.fechaVigencia.substring(0, 16);
                        document.getElementById("direccion").innerHTML = response.data.RespuestaCufd.direccion;
                        document.getElementById("cufdValor").innerHTML = response.data.RespuestaCufd.codigo;
                        document.getElementById("cufd").className = "badge bg-info";
                    } else {
                        document.getElementById("cufd").innerHTML = "No existe CUFD vigente";
                        document.getElementById("cufd").className = "badge bg-secondary";
                    }
                })
                .catch(function (error) {
                    console.log(error);
                });
        },

        nextNumber() {
            if (!this.num_comprob || this.num_comprob === "") {
                this.last_comprobante++;
                // Completa con ceros a la izquierda hasta alcanzar 5 dígitos
                this.num_comprob = this.last_comprobante.toString().padStart(5, "0");
            }
        },


        listarVenta(page, buscar, criterio) {
            let me = this;
            var url = '/venta/offline?page=' + page + '&buscar=' + buscar + '&criterio=' + criterio;
            axios.get(url).then(function (response) {
                var respuesta = response.data;
                me.arrayVenta = respuesta.facturasOffline.data;
                me.pagination = respuesta.pagination;
            })
                .catch(function (error) {
                    console.log(error);
                });
        },

        selectCliente(numero) {
            let me = this;
            var url = '/cliente/selectClientePorNumero?numero=' + numero;
            axios.get(url).then(function (response) {
                let respuesta = response.data;
                q: numero;
                me.arrayCliente = respuesta.clientes;
                console.log(me.arrayCliente);
            }).catch(function (error) {
                console.log(error);
            });
        },

        /*selectCliente2(search, loading) {
            let me = this;
            loading(true)
            var url = '/cliente/selectCliente?filtro=' + search;
            axios.get(url).then(function (response) {
                //console.log(response.clientes);
                let respuesta = response.data;
                q: search
                me.arrayCliente = respuesta.clientes;
                loading(false)
            })
                .catch(function (error) {
                    console.log(error);
                });
        },*/

        getDatosCliente(val1) {
            let me = this;
            me.loading = true;
            me.idcliente = val1.id;
            //console.log(val1);
            this.email = val1.email;
            this.nombreCliente = val1.nombre;
            this.documento = val1.num_documento;
            this.tipo_documento = val1.tipo_documento;
            this.complemento_id = val1.complemento_id;

        },

        buscarArticulo() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                let me = this;
                var url = '/articulo/buscarArticuloVenta?filtro=' + me.codigo;

                axios.get(url).then(function (response) {
                    let respuesta = response.data;
                    me.arraySeleccionado = respuesta.articulos[0];
                    console.log(me.arraySeleccionado)
                })
                    .catch(function (error) {
                        console.log(error);
                    });
            }, 1000);
        },

        enviarPaquete() {
            let me = this;
            var tzoffset = (new Date()).getTimezoneOffset() * 60000;
            let fechaEmision = (new Date(Date.now() - tzoffset)).toISOString().slice(0, -1);

            axios.post('/venta/enviarPaquete', {
                fechaEmision: fechaEmision
            }).then(function (response) {
                var data = response.data;
                if (data === "PENDIENTE") {
                    swal(
                        'PAQUETE ENVIADO CORRECTAMENTE',
                        data + ' A REVISIÓN - Seleccione "Validar Paquete"',
                        'success'
                    );
                    me.mostrarEnviarPaquete = false;
                    me.mostrarValidarPaquete = true;
                } else {
                    swal(
                        'ERROR AL ENVIAR PAQUETE',
                        data,
                        'error'
                    );
                    /*if (Array.isArray(data)) {
                        let errorMessage = 'Errores al enviar el paquete:<br>';
    
                        data.forEach((descripcion, index) => {
                            errorMessage += `${index + 1}: ${descripcion}<br>`;
                        });*/
                    /*swal({
                        title: 'ERROR AL ENVIAR PAQUETE',
                        html: errorMessage,
                        type: 'warning',
                        showCloseButton: true,
                        allowOutsideClick: false,
                    });*/
                }
                me.mostrarEnviarPaquete = false;
                me.mostrarValidarPaquete = true;
            }).catch(function (error) {
                if (error.response.data.message === "Trying to get property 'RespuestaServicioFacturacion' of non-object") {
                    swal('INTENTE DE NUEVO', 'Comunicación con SIAT fallida', 'error');
                } else {
                    swal('ERROR', error, 'error');
                }
            });
        },

        validarPaquete() {
            let me = this;

            axios.post('/venta/validarPaquete')
                .then(function (response) {
                    var data = response.data;
                    if (data === "VALIDADA") {
                        swal(
                            'ESTADO DEL PAQUETE',
                            data,
                            'success'
                        );
                        me.mostrarEnviarPaquete = true;
                        me.mostrarValidarPaquete = false;
                    } else {
                        if (Array.isArray(data)) {
                            let errorMessage = 'Errores al validar el paquete:<br>';

                            data.forEach((descripcion, index) => {
                                errorMessage += `${index + 1}: ${descripcion}<br>`;
                            });

                            swal({
                                title: 'ERROR AL VALIDAR PAQUETE',
                                html: errorMessage,
                                type: 'warning',
                                showCloseButton: true,
                                allowOutsideClick: false,
                            });
                        }
                        me.mostrarEnviarPaquete = false;
                        me.mostrarValidarPaquete = true;
                    }
                }).catch(function (error) {
                    swal(
                        'ERROR AL ENVIAR EL PAQUETE DE FACTURAS:',
                        'Intente de Nuevo',
                        'warning'
                    );
                });
        },



        cambiarPagina(page, buscar, criterio) {
            let me = this;
            //Actualiza la página actual
            me.pagination.current_page = page;
            //Envia la petición para visualizar la data de esa página
            me.listarVenta(page, buscar, criterio);
        },
        encuentra(id) {
            var sw = 0;
            for (var i = 0; i < this.arrayDetalle.length; i++) {
                if (this.arrayDetalle[i].idarticulo == id) {
                    sw = true;
                }
            }
            return sw;
        },
        eliminarDetalle(index) {

            let me = this;
            me.arrayDetalle.splice(index, 1);
            me.arrayProductos.splice(index, 1);

        },

        eliminarKit(id) {
            const indicesEliminar = [];
            for (let i = 0; i < this.arrayDetalle.length; i++) {
                if (this.arrayDetalle[i].idkit === id) {
                    indicesEliminar.push(i);
                }
            }

            indicesEliminar.forEach(index => {
                this.arrayProductos.splice(index, 1);
            });

            for (let i = indicesEliminar.length - 1; i >= 0; i--) {
                this.arrayDetalle.splice(indicesEliminar[i], 1);
            }
        },

        agregarDetalle() {
            let actividadEconomica = 461021;
            // let codigoProductoSin = document.getElementById("codigoProductoSin").value; si hay
            // let codigoProducto = document.getElementById("codigo").value; si 
            // let descripcion = document.getElementById("nombre_producto").value; nombre
            // let cantidad = document.getElementById("cantidad").value; cantidad
            // let unidadMedida = document.getElementById("codigoClasificador").value;
            // let precioUnitario = document.getElementById("precio").value; precio  sin descuento
            // let montoDescuento = document.getElementById("descuento").value; descuento
            // let subTotalValor = document.getElementById("sTotal");  total con descuento
            // let subTotal = subTotalValor.textContent;
            let numeroSerie = null;
            let numeroImei = null;
            let descuento = ((this.precioseleccionado * this.cantidad) * (this.descuentoProducto / 100)).toFixed(2);

            if (this.encuentra(this.arraySeleccionado.id)) {
                swal({
                    type: 'error',
                    title: 'Error...',
                    text: 'Este Artículo ya se encuentra agregado!',
                })
            } else {
                if (this.saldosNegativos === 0 && this.arraySeleccionado.saldo_stock < this.cantidad * this.unidadPaquete) {
                    swal({
                        type: 'error',
                        title: 'Error...',
                        text: 'No hay stock disponible!'
                    });
                    return
                }

                const precioArticulo = (this.calcularPrecioConDescuento(this.resultadoMultiplicacion, this.arrayPromocion ? this.arrayPromocion.porcentaje : 0)) * this.monedaVenta[0]
                console.log("Este es el precio del articulo: ", precioArticulo);
                this.arrayDetalle.push({
                    idkit: -1,
                    idarticulo: this.arraySeleccionado.id,
                    articulo: this.arraySeleccionado.nombre,
                    medida: this.arraySeleccionado.medida,
                    unidad_envase: this.arraySeleccionado.unidad_envase,
                    cantidad: this.cantidad * this.unidadPaquete,
                    cantidad_paquetes: this.arraySeleccionado.unidad_envase,
                    precio: precioArticulo,
                    //descuento: this.arrayPromocion && this.arrayPromocion.porcentaje !== undefined ? this.arrayPromocion.porcentaje : 0,
                    descuento: this.descuentoProducto,
                    stock: this.arraySeleccionado.saldo_stock,
                    precioseleccionado: this.precioseleccionado

                });
                console.log(this.arrayDetalle)
                this.arrayProductos.push({
                    actividadEconomica: actividadEconomica,
                    codigoProductoSin: this.arraySeleccionado.codigoProductoSin,
                    codigoProducto: this.arraySeleccionado.codigo,
                    descripcion: this.arraySeleccionado.nombre,
                    cantidad: this.cantidad * this.unidadPaquete,
                    unidadMedida: this.arraySeleccionado.codigoClasificador,
                    precioUnitario: parseFloat(this.precioseleccionado).toFixed(2),
                    //montoDescuento: this.arrayPromocion && this.arrayPromocion.porcentaje ? ((this.arrayPromocion.porcentaje / this.resultadoMultiplicacion) * 100).toFixed(2) : 0,
                    montoDescuento: descuento,
                    subTotal: precioArticulo.toFixed(2),
                    numeroSerie: numeroSerie,
                    numeroImei: numeroImei
                });
                console.log("pa la factura: ", this.arrayProductos)
                this.precioBloqueado = true;
                this.arraySeleccionado = [];
                this.cantidad = 1;
                this.unidadPaquete = 1;
                this.codigo = '';
                this.descuentoProducto = 0;
            }
        },

        agregarDetalleModal(data) {
            this.scrollToSection();
            this.codigo = data.codigo;
            console.log('SLECCIONE ESTO:', data);

            this.buscarPromocion(data.id);
            this.precioseleccionado = data.precio_uno;

            this.cerrarModal();
        },

        eliminarSeleccionado() {
            this.codigo = '';
            this.arraySeleccionado = []
        },

        listarArticulo(buscar, criterio) {
            let me = this;
            var url = '/articulo/listarArticuloVenta?buscar=' + buscar + '&criterio=' + criterio + '&idAlmacen=' + this.idAlmacen;
            axios.get(url).then(function (response) {
                var respuesta = response.data;
                me.arrayArticulo = respuesta.articulos;
                console.log('listar articulo', respuesta);
            })
                .catch(function (error) {
                    console.log(error);
                });
        },
        /*obtenerSaldosNegativos() {
            let me = this;
            axios.get('/configuracion/saldos-negativos')
                .then(response => {
                    me.saldosNegativos = response.data.saldosNegativos;
                    console.log('Saldos Negativos:', me.saldosNegativos);
                })
                .catch(error => {
                    console.error('Error al obtener saldos negativos:', error);
                });
        },*/
        datosConfiguracion() {
            let me = this;
            var url = '/configuracion';

            axios.get(url).then(function (response) {
                let respuesta = response.data;
                console.log(respuesta)
                me.saldosNegativos = respuesta.configuracionTrabajo.saldosNegativos;
                me.permitirDevolucion = respuesta.configuracionTrabajo.permitirDevolucion;
                me.monedaVenta = [respuesta.configuracionTrabajo.valor_moneda_venta, respuesta.configuracionTrabajo.simbolo_moneda_venta]

            })
                .catch(function (error) {
                    console.log(error);
                });
        },
        obtenerDatosUsuario() {
            axios.get('/venta')
                .then(response => {
                    this.usuarioAutenticado = response.data.usuario.usuario;
                    this.puntoVentaAutenticado = response.data.usuario.idpuntoventa;
                })
                .catch(error => {
                    console.error(error);
                });
        },

        verificarFactura(cuf, numeroFactura) {
            var url = 'https://siat.impuestos.gob.bo/consulta/QR?nit=8033811015&cuf=' + cuf + '&numero=' + numeroFactura + '&t=2';
            window.open(url);
        },

        anularFactura(id, cuf) {
            swal({
                title: '¿Está seguro de anular la factura?',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
                confirmButtonClass: 'btn btn-success',
                cancelButtonClass: 'btn btn-danger',
                buttonsStyling: false,
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    let me = this;
                    axios.get('/factura/obtenerDatosMotivoAnulacion')
                        .then(function (response) {
                            var respuesta = response.data;
                            me.arrayMotivosAnulacion = respuesta.motivo_anulaciones;

                            console.log('Motivos obtenidos:', me.arrayMotivosAnulacion);

                            let options = {};
                            me.arrayMotivosAnulacion.forEach(function (motivo) {
                                options[motivo.codigo] = motivo.descripcion;
                            });

                            // Muestra un segundo modal para seleccionar el motivo
                            swal({
                                title: 'Seleccione un motivo de anulación',
                                input: 'select',
                                inputOptions: options,
                                inputPlaceholder: 'Seleccione un motivo',
                                showCancelButton: true,
                                inputValidator: function (value) {
                                    return new Promise(function (resolve, reject) {
                                        if (value !== '') {
                                            resolve();
                                        } else {
                                            reject('Debe seleccionar un motivo');
                                        }
                                    });
                                }
                            }).then((result) => {
                                if (result.value) {
                                    // Aquí obtienes el motivo seleccionado y puedes realizar la solicitud para anular la factura
                                    const motivoSeleccionado = result.value;
                                    axios.get('/factura/anular/' + cuf + "/" + motivoSeleccionado)
                                        .then(function (response) {
                                            const data = response.data;
                                            if (data === 'ANULACION CONFIRMADA') {
                                                swal(
                                                    'FACTURA ANULADA',
                                                    data,
                                                    'success'
                                                );
                                            } else {
                                                swal(
                                                    'ANULACION RECHAZADA',
                                                    data,
                                                    'warning'
                                                );
                                            }
                                        })
                                        .catch(function (error) {
                                            console.log(error);
                                        });
                                }
                            });
                        })
                        .catch(function (error) {
                            console.log(error);
                        });
                }
            });
        },

        imprimirFactura(id, correo) {
            swal({
                title: 'Selecciona un tamaño de factura',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'CARTA',
                cancelButtonText: 'ROLLO',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    console.log("El boton CARTA fue presionado");
                    axios.get('/factura/imprimirCartaOffline/' + id + '/' + correo, { responseType: 'blob' })
                        .then(function (response) {
                            window.location.href = "docs/facturaCarta.pdf";
                            console.log("Se generó el factura en Carta correctamente");
                        })
                        .catch(function (error) {
                            console.log(error);
                        });
                } else if (result.dismiss === swal.DismissReason.cancel) {
                    console.log("El boton ROLLO fue presionado");
                    axios.get('/factura/imprimirRolloOffline/' + id + '/' + correo, { responseType: 'blob' })
                        .then(function (response) {
                            window.location.href = "docs/facturaRollo.pdf";
                            console.log("Se generó el la factura en Rollo correctamente");
                        })
                        .catch(function (error) {
                            console.log(error);
                        });
                }
            }).catch((error) => {
                console.error('Error al mostrar el diálogo:', error);
            });
        },

        selectAlmacen() {
            let me = this;
            var url = '/almacen/selectAlmacen';
            axios.get(url).then(function (response) {
                var respuesta = response.data;
                me.arrayAlmacenes = respuesta.almacenes;
                console.log(me.arrayAlmacenes);

            })
                .catch(function (error) {
                    console.log(error);
                });
        },

        getAlmacenProductos(almacen) {
            this.idAlmacen = almacen.id;
        },

        validarVenta() {
            let me = this;
            me.errorVenta = 0;
            me.errorMostrarMsjVenta = [];
            var art;

            me.arrayDetalle.map(function (x) {
                if (x.cantidad > x.stock) {
                    art = x.articulo + " Stock insuficiente";
                    me.errorMostrarMsjVenta.push(art);
                }
            });

            if (me.idcliente == 0) me.errorMostrarMsjVenta.push("Seleccione un Cliente");
            if (me.tipo_comprobante == 0) me.errorMostrarMsjVenta.push("Seleccione el Comprobante");
            if (!me.impuesto) me.errorMostrarMsjVenta.push("Ingrese el impuesto de compra");
            if (me.arrayDetalle.length <= 0) me.errorMostrarMsjVenta.push("Ingrese detalles");

            if (me.errorMostrarMsjVenta.length) me.errorVenta = 1;

            return me.errorVenta;
        },

        registrar() {
            this.registrarVenta();
        },

        aplicarDescuento() {
            const descuentoGiftCard = this.descuentoGiftCard;
            const numeroTarjeta = this.numeroTarjeta;
            let idtipo_pago;

            if (numeroTarjeta && descuentoGiftCard) {
                idtipo_pago = 86;
            } else if (numeroTarjeta && !descuentoGiftCard) {
                idtipo_pago = 10;
            } else {
                idtipo_pago = descuentoGiftCard ? 35 : 1;
            }

            this.registrarVenta(idtipo_pago);
        },

        aplicarCombinacion() {
            const descuentoGiftCard = this.descuentoGiftCard
            const idtipo_pago = descuentoGiftCard ? 40 : 2;

            this.registrarVenta(idtipo_pago);
        },

        otroMetodo(metodoPago) {
            const idtipo_pago = metodoPago;
            this.registrarVenta(idtipo_pago);
        },

        registrarVenta(idtipo_pago) {
            if (this.validarVenta()) {
                return;
            }

            let me = this;
            this.mostrarSpinner = true;
            this.idtipo_pago = idtipo_pago;

            for (let i = 0; i < me.cuotas.length; i++) {
                console.log('LLEGA ARRAYDATA!', me.cuotas[i]);
            }

            console.log("hola");
            console.log(this.primer_precio_cuota);
            axios.post('/venta/registrar', {
                'idcliente': this.idcliente,
                'tipo_comprobante': this.tipo_comprobante,
                'serie_comprobante': this.serie_comprobante,
                'num_comprobante': this.num_comprob,
                'impuesto': this.impuesto,
                'total': this.calcularTotal,
                'idAlmacen': this.idAlmacen,
                'idtipo_pago': idtipo_pago,
                'idtipo_venta': this.idtipo_venta,
                'primer_precio_cuota': this.primer_precio_cuota,
                //----creditos Ventas----
                'idpersona': this.idcliente,
                'numero_cuotasCredito': this.numero_cuotas, // Cambio de nombre
                'tiempo_dias_cuotaCredito': this.tiempo_diaz, // Cambio de nombre
                'totalCredito': this.primera_cuota ? this.calcularTotal - this.cuotas[0].totalCancelado : this.calcularTotal, // Asegúrate de tener esta variable
                'estadoCredito': "Pendiente", // Asegúrate de tener esta variable
                //-----hasta aqui-------
                //----Cuotas Credito----
                'cuotaspago': this.cuotas,
                //-----hasta aqui-------
                'data': this.arrayDetalle
            }).then((response) => {
                let idVentaRecienRegistrada = response.data.id;
                this.paqueteFactura(idVentaRecienRegistrada);

                if (response.data.id > 0) {
                    me.listado = 1;
                    me.listarVenta(1, '', 'num_comprob');
                    me.cerrarModal2();
                    me.cerrarModal3();
                    me.idproveedor = 0;
                    me.tipo_comprobante = 'FACTURA';
                    me.nombreCliente = '';
                    me.idcliente = 0;
                    me.tipo_documento = 0;
                    me.complemento_id = '';
                    me.documento = '';
                    me.email = '';
                    me.imagen = '';
                    me.serie_comprobante = '';
                    me.num_comprob = '';
                    me.impuesto = 0.18;
                    me.total = 0.0;
                    me.idarticulo = 0;
                    me.articulo = '';
                    me.cantidad = 0;
                    me.precio = 0;
                    me.stock = 0;
                    me.codigo = '';
                    me.descuento = 0;
                    me.arrayDetalle = [];
                    me.recibido = 0;
                    //window.open('/factura/imprimir/' + response.data.id);
                } else {
                    if (response.data.valorMaximo) {
                        swal(
                            'Aviso',
                            'El valor de descuento no puede exceder el ' + response.data.valorMaximo,
                            'warning'
                        )
                        return;
                    } else {
                        swal(
                            'Aviso',
                            response.data.caja_validado,
                            'warning'
                        )
                        return;
                    }
                }
            }).catch((error) => {
                console.log(error);
            });
        },

        async paqueteFactura(idVentaRecienRegistrada) {
            let me = this;

            let idventa = idVentaRecienRegistrada;
            let numeroFactura = document.getElementById("num_comprobante").value;
            let cuf = "464646464";
            let cufdValor = document.getElementById("cufdValor");
            let cufd = cufdValor.textContent;
            let direccionValor = document.getElementById("direccion");
            let direccion = direccionValor.textContent;
            var tzoffset = (new Date()).getTimezoneOffset() * 60000;
            let id_cliente = document.getElementById("idcliente").value;
            let nombreRazonSocial = document.getElementById("nombreCliente").value;
            let tipoDocumentoIdentidad = document.getElementById("tipo_documento").value;
            let numeroDocumento = document.getElementById("documento").value;
            let complemento = document.getElementById("complemento_id").value;
            /*let montoTotalValor = document.getElementById("montoTotal");
            let montoTotal = montoTotalValor.textContent;*/
            let montoTotal = (this.calcularTotal * parseFloat(this.monedaVenta[0])).toFixed(2);
            let descuentoAdicional = document.getElementById("descuentoAdicional").value;
            let leyenda = "Ley N° 453: El proveedor de servicios debe habilitar medios e instrumentos para efectuar consultas y reclamaciones.";
            let usuario = document.getElementById("usuarioAutenticado").value;
            //let codigoPuntoVenta = this.puntoVentaAutenticado;
            let codigoPuntoVenta = 0;
            //let montoGiftCard = document.getElementById("descuentoGiftCard").value;
            let codigoMetodoPago = this.idtipo_pago;
            let correo = document.getElementById("email").value;

            let cafc = this.scodigorecepcion === 5 || this.scodigorecepcion === 6 || this.scodigorecepcion === 7
                ? document.getElementById("cafc").value
                : null;

            let fechaEmision;
            if (this.scodigorecepcion === 5 || this.scodigorecepcion === 6 || this.scodigorecepcion === 7) {
                let fechaSeleccionada = new Date(this.fecha);
                let tzoffset = fechaSeleccionada.getTimezoneOffset() * 60000;
                fechaEmision = new Date(fechaSeleccionada - tzoffset).toISOString().slice(0, -1);
            } else {
                let tzoffset = (new Date()).getTimezoneOffset() * 60000;
                fechaEmision = new Date(Date.now() - tzoffset).toISOString().slice(0, -1);
            }


            try {
                const response = await axios.get('/factura/obtenerLeyendaAleatoria');
                this.leyendaAl = response.data.descripcionLeyenda;
                console.log("El dato de leyenda llegado es: " + this.leyendaAl);
            } catch (error) {
                console.error(error);
                return '"Ley N° 453: Los servicios deben suministrarse en condiciones de inocuidad, calidad y seguridad."';
            }

            try {
                if (tipoDocumentoIdentidad === '5') {
                    const response = await axios.post('/factura/verificarNit/' + numeroDocumento);
                    if (response.data === 'NIT ACTIVO') {
                        me.codigoExcepcion = 0;
                        //alert("NIT VÁLIDO.");
                    } else {
                        me.codigoExcepcion = 1;
                        //alert("NIT INVÁLIDO.");
                    }
                }
            } catch (error) {
                console.error(error);
                return 'No se pudo verificar el NIT';
            }

            var factura = [];
            factura.push({
                cabecera: {
                    nitEmisor: "8033811015",
                    razonSocialEmisor: "365 SOFT",
                    municipio: "Cochabamba",
                    telefono: "77490451",
                    numeroFactura: numeroFactura,
                    cuf: cuf,
                    cufd: cufd,
                    codigoSucursal: 0,
                    direccion: direccion,
                    codigoPuntoVenta: codigoPuntoVenta,
                    fechaEmision: fechaEmision,
                    nombreRazonSocial: nombreRazonSocial,
                    codigoTipoDocumentoIdentidad: tipoDocumentoIdentidad,
                    numeroDocumento: numeroDocumento,
                    complemento: complemento,
                    codigoCliente: numeroDocumento,
                    codigoMetodoPago: codigoMetodoPago,
                    numeroTarjeta: this.numeroTarjeta,
                    montoTotal: montoTotal,
                    montoTotalSujetoIva: montoTotal,
                    codigoMoneda: 1,
                    tipoCambio: 1,
                    montoTotalMoneda: montoTotal,
                    montoGiftCard: this.descuentoGiftCard,
                    descuentoAdicional: descuentoAdicional,
                    codigoExcepcion: this.codigoExcepcion,
                    cafc: cafc,
                    leyenda: this.leyendaAl,
                    usuario: usuario,
                    codigoDocumentoSector: 1
                }
            })
            me.arrayProductos.forEach(function (prod) {
                factura.push({ detalle: prod })
            })

            var datos = { factura };

            axios.post('/venta/paqueteFactura', {
                factura: datos,
                id_cliente: id_cliente,
                fechaEmision: fechaEmision,
                cufd: cufd,
                cafc: cafc,
                idventa: idventa,
                correo: correo
            })
                .then(function (response) {
                    var data = response.data;
                    me.listarVenta(1, '', 'id');
                    if (data.message === "Factura registrada correctamente") {
                        swal(
                            'FACTURA REGISTRADA',
                            'Correctamente',
                            'success'
                        )
                        me.arrayProductos = [];
                        me.codigoExcepcion = 0;
                        me.idtipo_pago = '';
                        me.descuentoGiftCard = '';
                        me.numeroTarjeta = null;
                        me.recibido = '';
                        me.email = '';
                        me.metodoPago = '';
                        me.cerrarModal2();
                        me.cerrarModal3();
                        me.listarVenta(1, '', 'id');
                        me.mostrarSpinner = false;
                    }
                })
                .catch(function (error) {
                    me.arrayProductos = [];
                    me.codigoExcepcion = 0;
                    me.mostrarSpinner = false;
                    me.idtipo_pago = '';
                    me.numeroTarjeta = null;
                    me.descuentoGiftCard = '';
                    me.recibido = '';
                    me.email = '';
                    me.metodoPago = '';
                    swal({
                        title: 'ERROR AL REGISTRAR LA FACTURA',
                        type: 'warning',
                        showCancelButton: false,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Aceptar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonClass: 'btn btn-success',
                        cancelButtonClass: 'btn btn-danger',
                        buttonsStyling: false,
                        reverseButtons: true
                    })
                });
        },

        mostrarDetalle() {
            let me = this;
            me.selectAlmacen();
            me.listado = 0;

            me.idproveedor = 0;
            me.tipo_comprobante = 'FACTURA';
            me.serie_comprobante = '';
            me.nextNumber();
            //me.num_comprobante = '';
            me.impuesto = 0.18;
            me.total = 0.0;
            me.idarticulo = 0;
            me.articulo = '';
            me.cantidad = 1;
            me.precio = 0;
            me.arrayDetalle = [];
            me.arraySeleccionado = [];
        },

        ocultarDetalle() {
            this.listado = 1;
            this.codigo = null;
            this.arrayArticulo.length = 0;
            this.precioseleccionado = null;
            this.medida = null;
            this.nombreCliente = null;
            this.documento = null;
            this.email = null;

        },

        verVenta(id) {
            let me = this;
            me.listado = 2;

            //Obtener datos del ingreso
            var arrayVentaT = [];
            var url = '/venta/obtenerCabecera?id=' + id;

            axios.get(url).then(function (response) {
                var respuesta = response.data;
                arrayVentaT = respuesta.venta;

                me.cliente = arrayVentaT[0]['nombre'];
                me.tipo_comprobante = arrayVentaT[0]['tipo_comprobante'];
                me.serie_comprobante = arrayVentaT[0]['serie_comprobante'];
                me.num_comprobante = arrayVentaT[0]['num_comprobante'];
                me.impuesto = arrayVentaT[0]['impuesto'];
                me.total = arrayVentaT[0]['total'];
            })
                .catch(function (error) {
                    console.log(error);
                });

            //obtener datos de los detalles
            var url = '/venta/obtenerDetalles?id=' + id;

            axios.get(url).then(function (response) {
                //console.log(response);
                var respuesta = response.data;
                me.arrayDetalle = respuesta.detalles;

            })
                .catch(function (error) {
                    console.log(error);
                });
        },
        cerrarModal() {
            this.modal = 0;
            this.tituloModal = '';
        },
        abrirModal() {
            this.scrollToTop()
            this.listarArticulo('', 'nombre');
            this.listarKits(1, '', 'nombre');

            this.arrayArticulo = [];
            this.modal = 1;
            this.tituloModal = 'Seleccione los articulos que desee';
        },
        advertenciaFechaVencimiento() {
            swal({
                title: 'No Disponible',
                text: 'No puede seleccionar este producto porque esta vencido.',
                type: 'warning',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Aceptar',
                confirmButtonClass: 'btn btn-success',
                buttonsStyling: false,
            }).then(() => {
                this.cerrarModal();
            });
        },

        desactivarVenta(id) {
            swal({
                title: 'Esta seguro de anular esta venta?',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Aceptar!',
                cancelButtonText: 'Cancelar',
                confirmButtonClass: 'btn btn-success',
                cancelButtonClass: 'btn btn-danger',
                buttonsStyling: false,
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    let me = this;

                    axios.put('/venta/desactivar', {
                        'id': id
                    }).then(function (response) {
                        me.listarVenta(1, '', 'num_comprobante');
                        swal(
                            'Anulado!',
                            'La venta ha sido anulado con éxito.',
                            'success'
                        )
                    }).catch(function (error) {
                        console.log(error);
                    });


                } else if (
                    // Read more about handling dismissals
                    result.dismiss === swal.DismissReason.cancel
                ) {

                }
            })
        },
        //-------------OBTENER PRECIOS Y MABRIR_MODAL----------
        listarPrecio() {
            let me = this;
            var url = '/precios';
            axios.get(url).then(function (response) {
                var respuesta = response.data;
                me.arrayPrecios = respuesta.precio.data;
                console.log('PRECIOS', me.arrayPrecios);
                //me.precioCount = me.arrayBuscador.length;
            }).catch(function (error) {
                console.log(error);
            });
        },

        mostrarSeleccion() {
            console.log('Precio seleccionado:', this.precioseleccionado);
        },
        //--------------ABRIR MODAL------------------
        registrarAbrilModal() {
            this.modal2 = 1;
            this.cliente = this.nombreCliente;
            console.log('USUARIO LLEGA:', this.cliente);
            this.tituloModal2 = 'VENTA  AL CONTADO ' + this.cliente; // Usamos '+' para concatenar el nombre del cliente
            this.tipoAccion2 = 1;
            this.idtipo_venta = 1;
            console.log('idtipo_venta LLEGA:', this.idtipo_venta);
            console.log('idtipo_pago LLEGA:', this.idtipo_pago);
        },

        registrarAbrilModal2() {
            this.modal3 = 1;
            this.cliente = this.nombreCliente;
            console.log('USUARIO LLEGA:', this.cliente);
            this.tituloModal3 = 'CREDITOS ' + this.cliente; // Usamos '+' para concatenar el nombre del cliente
            this.tipoAccion3 = 1;
            this.idtipo_venta = 2;
            console.log('idtipo_venta LLEGA:', this.idtipo_venta);
            console.log('idtipo_pago LLEGA:', this.idtipo_pago);
        },

        cerrarModal2() {
            this.modal2 = 0;
            this.tituloModal2 = '';
            this.idtipo_pago = '';
            this.tipoPago = '';
        },
        cerrarModal3() {
            this.modal3 = 0;
            this.tituloModal3 = '';
            this.numero_cuotas = '';
            this.tiempo_diaz = '';
            this.primera_cuota = false;
            this.cuotas = [];
        },
        calcularCambio() {
            // Convierte this.recibido a un número
            // cambio /parseFloat(monedaVenta[0])).toFixed(2)
            const recibidoNumero = parseFloat(this.recibido / parseFloat(this.monedaVenta[0]));
            if (recibidoNumero === 0) {
                this.efectivo = recibidoNumero;
                console.log('EFECTIVO', this.efectivo);
                this.cambio = 0;
                this.faltante = 0;
            } else if (recibidoNumero < this.calcularTotal) {
                this.efectivo = recibidoNumero;
                this.faltante = (this.calcularTotal - this.efectivo).toFixed(2);
            } else if (recibidoNumero === this.calcularTotal) {
                this.efectivo = recibidoNumero;
                this.cambio = 0;
                this.faltante = 0;
            } else {
                this.efectivo = recibidoNumero;
                this.cambio = (this.efectivo - this.calcularTotal).toFixed(2);
                this.faltante = 0;
            }
        },
        generarCuotas() {
            this.cuotas = []; // Limpiamos la lista de cuotas
            const fechaHoy = new Date(); // Obtenemos la fecha de hoy

            // Calcular el monto entero y el monto con decimal
            const montoEntero = Math.floor(this.calcularTotal / this.numero_cuotas);
            const montoDecimal = (this.calcularTotal - montoEntero * (this.numero_cuotas - 1)).toFixed(2);

            let primerPrecioCuota;
            let fechaInicioPago;
            let saldos_total;
            let estadoCuota;
            if (this.primera_cuota) {
                primerPrecioCuota = montoEntero;
                console.log('primerPrecioCuota', primerPrecioCuota);
                fechaInicioPago = fechaHoy;
                saldos_total = (this.calcularTotal - primerPrecioCuota).toFixed(2);
                console.log('saldos_total', saldos_total);
                estadoCuota = 'Pagado';
            } else {
                primerPrecioCuota = 0;
                fechaInicioPago = new Date(fechaHoy.getTime() + this.tiempo_diaz * 24 * 60 * 60 * 1000); // Sumar 7 días
                saldos_total = this.calcularTotal;
                estadoCuota = 'Pendiente';
            }

            for (let i = 0; i < this.numero_cuotas; i++) {
                const fechaPago = new Date(fechaInicioPago.getTime() + i * this.tiempo_diaz * 24 * 60 * 60 * 1000);
                const dia = fechaPago.getDate();
                const mes = fechaPago.getMonth() + 1;
                const año = fechaPago.getFullYear();

                const cuota = {
                    fechaPago: `${año}-${mes}-${dia} ${fechaPago.toLocaleTimeString()}`,
                    precioCuota: i === this.numero_cuotas - 1 ? parseFloat(montoDecimal).toFixed(2) : montoEntero,
                    totalCancelado: i === 0 ? primerPrecioCuota : 0,
                    saldo: saldos_total,//saldo
                    fechaCancelado: i === 0 && this.primera_cuota ? `${año}-${mes}-${dia} ${fechaPago.toLocaleTimeString()}` : null,
                    estadocuocre: i === 0 ? (this.primera_cuota ? 'Pagado' : estadoCuota) : 'Pendiente',
                };

                this.cuotas.push(cuota);
                console.log('LLEGA_ARRAY_CUOTA', this.cuotas);
            }
        },

        preciounid() {

            console.log("precio unid", this.paquni);
        }

    },

    created() {
        this.listarPrecio();
        // Realiza una solicitud AJAX para obtener los datos de sesión
        axios.get('/obtener-datos-sesion')
            .then(response => {
                this.scodigorecepcion = response.data.scodigorecepcion;
                console.log('Valor de scodigorecepcion:', this.scodigorecepcion);
                return axios.get('/ruta-a-tu-endpoint-laravel-para-obtener-ultimo-comprobante');
            })
            .then(response => {
                const lastComprobante = response.data.last_comprobante;
                this.last_comprobante = lastComprobante;
                this.nextNumber();
            })
            .catch(error => {
                console.error('Error al obtener datos de sesión o el último comprobante:', error);
            });
    },


    mounted() {
        this.datosConfiguracion();
        this.listarVenta(1, this.buscar, this.criterio);
        window.addEventListener('keydown', this.atajoButton);
        this.datosConfiguracion();
        this.verificarComunicacion();
        this.cuis();
        this.cufd();
        this.obtenerDatosUsuario();
    }
}
</script>
<style>
.custom-btn {
    width: 100%;
    text-align: center;
    padding: 5px;
    color: #939392;
    border: 1px solid #939392;
    background-color: white;
    margin-left: 5px;
    margin-right: 5px;
    display: flex;
    align-items: center;
    flex-direction: column;
    justify-content: center;
    border-radius: 5%;
    /* Agregando sombra */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
}


.selected {
    background-color: rgba(5, 75, 122, 0.1);
    /* Ajusta el último valor para cambiar la opacidad */
    font-weight: 100;
    color: #054b7a;
    border: 1px solid #054b7a;
    transition: all 0.5s ease;


}

/* #054b7a; */
.efectivo {
    color: #054b7a;
    transition: all 0.3s ease;

}

.qr {
    transition: all 0.3s ease;

    color: #054b7a;
}

.transferencia {
    transition: all 0.3s ease;

    color: #054b7a;
}

.modal-content {
    width: 100% !important;
    position: absolute !important;
}

.mostrar {
    display: list-item !important;
    opacity: 1 !important;
    position: absolute !important;
    background-color: #3c29297a !important;
}

.div-error {
    display: flex;
    justify-content: center;
}

.text-error {
    color: red !important;
    font-weight: bold;
}

@media (min-width: 600px) {
    .btnagregar {
        margin-top: 2rem;
    }
}

.card-img {
    width: auto;
    height: 200px;
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
}

.spinner-container {
    position: relative;
}

.spinner-container>* {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.spinner-message {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translate(-50%, -170%);
    z-index: 1;
}

.refresh-button {
    position: absolute;
    top: 0;
    right: 0;
    margin-top: 10px;
    /* Ajusta el margen superior según sea necesario */
    margin-right: 10px;
    /* Ajusta el margen derecho según sea necesario */
}

.refresh-button button {
    background-color: transparent;
    border: none;
    padding: 0;
    cursor: pointer;
    font-size: 18px;
    /* Ajusta el tamaño del icono según sea necesario */
}



/* .negrita-input {
        font-weight: bold;
    } */
</style>
