<template>
    <main class="main">
        <!-- Breadcrumb -->
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"></a></li>
        </ol>
        <div class="container-fluid">
            <!-- Ejemplo de tabla Listado -->
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-align-justify"></i> Ventas Institucionales
                    <button type="button" @click="ejecutarFunciones" class="btn btn-secondary">
                        <i class="icon-plus"></i>&nbsp;Nueva Venta
                    </button>
                    <button v-if="permitirDevolucion === 1" type="button" @click="listado = 3" class="btn btn-secondary">Devoluciones
                    </button>
                    <span class="badge bg-secondary" id="comunicacionSiat" style="color: white;">Desconectado</span>
                    <span class="badge bg-secondary" id="cuis">CUIS: Inexistente</span>
                    <span class="badge bg-secondary" id="cufd">No existe cufd vigente</span>
                    <span class="badge bg-secondary" id="direccion" v-show="mostrarDireccion">No hay dirección
                        registrada</span>
                    <span class="badge bg-primary" id="cufdValor" v-show="mostrarCUFD">No hay CUFD</span>
                </div>
                <!-- Listado-->
                <template v-if="listado == 1">
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <select class="form-control col-md-3" v-model="criterio">
                                        <option value="tipo_comprobante">Tipo Comprobante</option>
                                        <option value="num_comprobante">Número Comprobante</option>
                                        <option value="fecha_hora">Fecha-Hora</option>
                                    </select>
                                    <input type="text" v-model="buscar" @keyup="listarVenta(1, buscar, criterio)"
                                        class="form-control" placeholder="Texto a buscar">
                                    <!--button type="submit" @click="listarVenta(1, buscar, criterio)" class="btn btn-primary"><i
                                            class="fa fa-search"></i> Buscar</button-->
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Opciones</th>
                                        <th>ID Venta</th>
                                        <th>Fecha</th>
                                        <th>Razón Social</th>
                                        <th>Documento</th>
                                        <th>Total</th>
                                        <th>Descuento</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <tr v-for="factura in arrayFactura" :key="factura.id">
                                    <td v-text="factura.idventainstitucional "></td>
                                    <td v-text="factura.numeroFactura"></td>
                                    <td v-text="factura.fechaEmision"></td>
                                    <td v-text="factura.razonSocial"></td>
                                    <td v-text="factura.documentoid"></td>
                                    <td v-text="factura.montoTotal"></td>
                                    <td v-text="factura.descuentoAdicional"></td>
                                    <td>
                                        <a @click="verificarFactura(factura.cuf, factura.numeroFactura)" target="_blank" class="btn btn-info"><i class="icon-note"></i></a>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary" type="button" @click="imprimirFactura(factura.id, factura.idventainstitucional)"><i class="icon-printer"></i></button>
                                        <button class="btn btn-danger" type="button" @click="anularFactura(factura.id, factura.cuf)"><i class="icon-close"></i></button>
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
                                        <!-- <span class="font-weight-normal text-sm text-secondary">(Opcional)</span> -->
                                    </label>

                                    <v-select :on-search="selectCliente" label="nombre" :options="arrayCliente"
                                        placeholder="Buscar Clientes..." :onChange="getDatosCliente">
                                    </v-select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label for="" class="font-weight-bold">Razon social
                                    <span class="text-danger">*</span>
                                    <!-- <span class="font-weight-normal text-sm text-secondary">(Opcional)</span> -->
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
                            <input type="hidden" id="usuarioAutenticado" class="form-control" v-model="usuarioAutenticado"
                                readonly>

                                <div class="col-md-3">
                                <label for="" class="font-weight-bold">Documento
                                    <span class="text-danger">*</span>
                                    <!-- <span class="font-weight-normal text-sm text-secondary">(Opcional)</span> -->
                                </label>

                                <input type="text" id="documento" class="form-control" v-model="documento"
                                    ref="documentoRef" readonly>
                            </div>

                            <div class="col-md-3">
                                <label for="" class="font-weight-bold">Correo electronico
                                    <span class="text-danger">*</span>
                                    <!-- <span class="font-weight-normal text-sm text-secondary">(Opcional)</span> -->
                                </label>

                                <input type="text" id="email" class="form-control" v-model="email" ref="emailRef" readonly>
                            </div>

                            <div class="col-md-3">
                                <label for="" class="font-weight-bold">Almacen
                                    <span class="text-danger">*</span>
                                    <!-- <span class="font-weight-normal text-sm text-secondary">(Opcional)</span> -->
                                </label>
                                <v-select label="nombre_almacen" :options="arrayAlmacenes"
                                    placeholder="Seleccione un almacen" :onChange="getAlmacenProductos"></v-select>
                            </div>

                            <div class="col-md-3">
                                <label for="" class="font-weight-bold">Tipo de comprobante
                                    <span class="text-danger">*</span>
                                    <!-- <span class="font-weight-normal text-sm text-secondary">(Opcional)</span> -->
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
                                    <!-- <span class="font-weight-normal text-sm text-secondary">(Opcional)</span> -->
                                </label>
                                <input type="text" id="num_comprobante" class="form-control" v-model="num_comprob" disabled>
                            </div>
                            
                            <div class="col-md-12">
                                <div v-show="errorVenta" class="form-group row div-error">
                                    <div class="text-center text-error">
                                        <div v-for="error in errorMostrarMsjVenta" :key="error" v-text="error">

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row border">
                            <div class="col-md-3">
                                <label for="" class="font-weight-bold">Agregar Productos</label>
                                <div class="input-group mb-3">
                                    <input :disabled="!idAlmacen" type="text" class="form-control" v-model="codigo"
                                        placeholder="Codigo del articulo" aria-label="Codigo del articulo"
                                        @keyup="buscarArticulo()">
                                    <button :disabled="!idAlmacen" class="btn btn-primary" type="button"
                                        @click="abrirModal()">...</button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row border">
                            <div class="table-responsive col-md-12">
                                <table class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Opciones</th>
                                            <th style="display: none;">ID Variable</th>
                                            <th style="display: none;">Variable</th>
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
                                                <button @click="eliminarDetalle(index, detalle.idArtVariable)" type="button" class="btn btn-danger btn-sm">
                                                    <i class="icon-close"></i>
                                                </button>
                                                <button @click="verVariable(detalle.idVar)" type="button" class="btn btn-success btn-sm">
                                                    <i class="icon-eye"></i>
                                                </button>
                                            </td>
                                            <td style="display: none;" v-text="detalle.idArtVariable"></td>
                                            <td style="display: none;" v-text="detalle.idVar"></td>
                                            <td v-text="detalle.articulo">
                                            </td>
                                            <td v-text="detalle.medida">
                                            </td>
                                            <td>{{ detalle.unidad_envase }}
                                            </td>
                                            <td>
                                                {{ (detalle.precioseleccionado * parseFloat(monedaVenta[0])).toFixed(2) }}
                                                {{
                                                    monedaVenta[1] }}

                                            </td>
                                            <td>
                                                {{ detalle.cantidad }}
                                            </td> 
    
                                            <td> {{ (detalle.cantidad / detalle.unidad_envase).toFixed(2) }} </td>
                                             
                                            
                                            <td>
                                            {{ ((detalle.precioseleccionado * detalle.cantidad)
                                                * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}
                                            </td>
                                            
                                        
                                            <td v-text="detalle.descuento">
                                            </td>
                                                <!--<span style="color:red;"
                                                    v-show="detalle.descuento > (detalle.precioseleccionado * detalle.cantidad)">Descuento
                                                    superior</span>
                                                <input v-model="detalle.descuento" type="number" class="form-control">-->


                                                <td>
                                                {{ (((detalle.precioseleccionado * detalle.cantidad) -
                                                    (detalle.precioseleccionado * detalle.cantidad * detalle.descuento / 100))
                                                    * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}

                                            </td>
                                        </tr>
                                        <tr style="background-color: #CEECF5;">
                                            <td colspan="9" align="right"><strong>Sub Total: </strong></td>
                                            <td id="subTotal">
                                                {{ ((totalParcial = (calcularSubTotal))
                                                    * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}

                                            </td>
                                        </tr>
                                        <tr style="background-color: #CEECF5;">
                                            <td colspan="9" align="right"><strong>Descuento Adicional: </strong></td>
                                            <input id="descuentoAdicional" v-model="descuentoAdicional" type="number"
                                                class="form-control">
                                        </tr>
                                        <tr style="background-color: #CEECF5;">
                                            <td colspan="9" align="right"><strong>Total Neto: </strong></td>
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
                        <div class="form-group row">
                            <label class="col" for=""><strong>Forma de Pago</strong></label>
                            <div class="col-md-12">
                                <button type="button" @click="ocultarDetalle()" class="btn btn-secondary">Cerrar</button>
                                <button type="button" class="btn btn-primary"
                                    @click="registrarAbrilModal()">Contado</button>
                                <button type="button" class="btn btn-primary"
                                    @click="registrarAbrilModal2()">Credito</button>
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
                                            <td v-text="detalle.medida">
                                            </td>
                                            <td v-text="detalle.precio">
                                            </td>
                                            <td v-text="detalle.cantidad">
                                            </td>
                                            <td v-text="detalle.descuento">
                                            </td>
                                            <td>
                                                {{ detalle.precio * detalle.cantidad - detalle.descuento }}
                                            </td>
                                        </tr>
                                        <tr style="background-color: #CEECF5;">
                                            <td colspan="4" align="right"><strong>Total Parcial:</strong></td>
                                            <td>$ {{ totalParcial=(total - totalImpuesto).toFixed(2) }}</td>
                                        </tr>
                                        <tr style="background-color: #CEECF5;">
                                            <td colspan="4" align="right"><strong>Total Impuesto:</strong></td>
                                            <td>$ {{ totalImpuesto=(total * impuesto).toFixed(2) }}</td>
                                        </tr>
                                        <tr style="background-color: #CEECF5;">
                                            <td colspan="4" align="right"><strong>Total Neto:</strong></td>
                                            <td>$ {{ total }}</td>
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
                                <button type="button" @click="ocultarDetalle()" class="btn btn-secondary">Cerrar</button>
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
        
        <!-- Modal para agregar productos Variable Temporal -->
        <div class="modal fade" tabindex="-1" :class="{'mostrar': modalActivo}" role="dialog" aria-labelledby="myModalLabel"
            style="display: none;" aria-hidden="true">
            <div class="modal-dialog modal-primary modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">AGREGAR PRODUCTOS</h4>
                        <button type="button" class="close" @click="cerrarModal()" aria-label="Close">
                        <span aria-hidden="true"></span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group row border">
                            <div class="col-md-6">
                                <label for="nombreVariableTemporal"><strong>Nombre del Producto:</strong></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="nombreVariableTemporal" v-model="nombreVariableTemporal">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" @click="crearVariableTemporal">Crear</button>
                                    </div>
                                </div>
                                <input type="text" id="idVariableTemporal " class="form-control" v-model="idVariableTemporal " ref="idVariableTemporal "
                                readonly>
                            </div>
                            <div v-if="mostrarTabla">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Articulo</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unidad</th>
                                        <th>Descuento</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="articuloVariable in arrayArticuloVar" :key="articuloVariable.id">
                                        <td>{{ articuloVariable.articulo }}</td>
                                        <td>{{ articuloVariable.cantidad }}</td>
                                        <td>{{ articuloVariable.precio }}</td>
                                        <td>{{ articuloVariable.descuento }}</td>
                                        <td>
                                        {{ ((articuloVariable.precio * articuloVariable.cantidad) - (articuloVariable.precio * articuloVariable.cantidad * articuloVariable.descuento / 100)).toFixed(2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <select class="form-control col-md-3" v-model="criterioA">
                                        <option value="nombre">Nombre</option>
                                        <option value="descripcion">Descripción</option>
                                        <option value="codigo">Código</option>
                                    </select>
                                    <input type="text" v-model="buscarA" @keyup="listarArticulo(buscarA, criterioA)"
                                        class="form-control" placeholder="Texto a buscar">
                                    <!--button type="submit" @click="listarArticulo(buscarA, criterioA)"
                                        class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button-->
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Seleccionar</th>
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
                                            <button type="button" @click="agregarProductoVariable(articulo)"
                                            class="btn btn-success btn-sm">
                                                <i class="icon-check"></i>
                                            </button>
                                        </td>
                                        <td v-text="articulo.codigo"></td>
                                        <td v-text="articulo.nombre"></td>
                                        <td v-text="articulo.nombre_categoria"></td>
                                        <td>
                                            {{ ((articulo.precio_venta) * parseFloat(monedaVenta[0])).toFixed(2) }} {{
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
                        
                        <div class="form-group row border">
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
                                            arrayPromocion.porcentaje) * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                            monedaVenta[1] }}</b>
                                        <s style="font-size:15px" class="lead">{{
                                            (resultadoMultiplicacion * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                            monedaVenta[1] }}</s>
                                    </h3>

                                    <h3 v-else style="display:flex;align-items:center;margin:0px;">
                                        <b>{{ (resultadoMultiplicacion * parseFloat(monedaVenta[0])).toFixed(2) }} {{
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
                                <img v-else src="img/productoSinImagen.png" alt="Imagen del Card"
                                    class="card-img">


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
                                        <!-- <span class="font-weight-normal text-sm text-secondary">(Opcional)</span> -->
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
                                        <!-- <span class="font-weight-normal text-sm text-secondary">(Opcional)</span> -->
                                    </label>
                                    <select class="form-control" placeholder="Seleccione" v-model="precioseleccionado"
                                        @change="mostrarSeleccion" :disabled="precioBloqueado">
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
                                        <!-- <span class="font-weight-normal text-sm text-secondary">(Opcional)</span> -->
                                    </label>
                                    <input type="number" id="cantidad" value="1" class="form-control"
                                        v-model="cantidad">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group d-flex">
                                    <button @click="agregar()" class="btn btn-success flex-fill btnagregar">
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" @click="cerrarModal()">Guardar</button>
                        <button type="button" class="btn btn-danger" @click="cerrarModal()">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
        <!--Fin del Modal Variable Temporal-->

        <!-- Modal para Ver los Productos de la Variable Temporal-->
        <div class="modal fade" tabindex="-1" :class="{'mostrar': modalVerVariable}" role="dialog" aria-labelledby="myModalLabel"
            style="display: none;" aria-hidden="true">
            <div class="modal-dialog modal-primary modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Variable</h4>
                        <button type="button" class="close" @click="cerrarModalVerVariable()" aria-label="Close">
                        <span aria-hidden="true"></span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group row border">
                            <div class="col-md-6">
                                <label for="nombreVariableTemp"><strong>Nombre del Producto:</strong></label>
                                <span id="nombreVariableTemp"></span>
                                <input type="text" id="idVariableTemporal2 " class="form-control" v-model="idVariableTemporal2" ref="idVariableTemporal2"
                                readonly>
                            </div>
                            <div>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Articulo</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unidad</th>
                                        <th>Descuento</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="articuloVariable in arrayArticuloVariable" :key="articuloVariable.id">
                                        <td>{{ articuloVariable.articulo }}</td>
                                        <td>{{ articuloVariable.cantidad }}</td>
                                        <td>{{ articuloVariable.precio }}</td>
                                        <td>{{ articuloVariable.descuento }}</td>
                                        <td>
                                        {{ ((articuloVariable.precio * articuloVariable.cantidad) - (articuloVariable.precio * articuloVariable.cantidad * articuloVariable.descuento / 100)).toFixed(2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        </div>
                        <div v-if="mostrarSeleccionProductos">
                            <div class="form-group row">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <select class="form-control col-md-6" v-model="criterioA" style="height: 38px;">
                                            <option value="nombre"><strong>Nombre</strong></option>      
                                            <option value="descripcion"><strong>Descripción</strong></option>
                                            <option value="codigo"><strong>Código</strong></option>
                                        </select>
                                        <input type="text" v-model="buscarA" @keyup="listarArticulo(buscarA, criterioA)"
                                            class="form-control" placeholder="Texto a buscar" style="height: 38px;">
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Seleccionar</th>
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
                                                <button type="button" @click="agregarProductoVariable(articulo)"
                                                class="btn btn-success btn-sm">
                                                    <i class="icon-check"></i>
                                                </button>
                                            </td>
                                            <td v-text="articulo.codigo"></td>
                                            <td v-text="articulo.nombre"></td>
                                            <td v-text="articulo.nombre_categoria"></td>
                                            <td v-text="articulo.precio_venta"></td>
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
                            <div class="form-group row border">
                                <div class="col-md-6">
                                    <input type="hidden" id="nombre_producto" readonly class="form-control" v-model="articulo">
                                    <div class="card-body" v-if="articulo !== ''">
                                        <h3>{{articulo }}</h3>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <img
                                        v-if="arrayArticulo.length > 0 && arrayArticulo[0].fotografia"
                                        :src="'img/articulo/' + arrayArticulo[0].fotografia + '?t=' + new Date().getTime()"
                                        width="50" height="50" ref="imagen" class="card-img"/>
                                    <img v-else src="https://www.bicifan.uy/wp-content/uploads/2016/09/producto-sin-imagen.png"  alt="Imagen del Card" class="card-img">
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label><strong>Total Stock</strong></label>
                                        <input type="text" id="stock" style="color: red;" class="form-control" v-model="stock" readonly>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><strong>Precios(*)</strong></label>
                                        <select class="form-control" v-model="precioseleccionado" @change="mostrarSeleccion" :disabled="precioBloqueado">
                                            <option>Selecciona un precio: </option>
                                            <option :value="precio_uno" v-if="arrayPrecios[0]">{{ arrayPrecios[0].nombre_precio }}</option>
                                            <option :value="precio_dos" v-if="arrayPrecios[1]">{{arrayPrecios[1].nombre_precio}}</option>
                                            <option :value="precio_tres" v-if="arrayPrecios[2]">{{arrayPrecios[2].nombre_precio}}</option>
                                            <option :value="precio_cuatro" v-if="arrayPrecios[3]">{{arrayPrecios[3].nombre_precio}}</option>
                                        </select>
                                    </div>
                                </div>

                                <input type="hidden" id="codigo" class="form-control" v-model="codigo" readonly>
                                <input type="hidden" id="codigoProductoSin" class="form-control" v-model="codigoProductoSin"
                                    readonly>
                                <input type="hidden" id="medida" class="form-control" v-model="medida" readonly>
                                <input type="hidden" id="codigoClasificador" class="form-control" v-model="codigoClasificador" readonly>
                                    
                                <div class="col-md-2">
                                    <div class="form-group"> 
                                        <label><strong>Precio</strong></label> 
                                        <input type="number" id="precio" value="0"  class="form-control" v-model="precioFinal">
                                        
                                        </div> 
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label><strong>Cantidad</strong><span style="color: red;" v-show="cantidad == 0"></span></label>
                        
                                        <input type="number" id="cantidad" value="0" class="form-control" v-model="cantidad"
                                            ref="cantidadRef"> 
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><strong>Unidad Medida</strong><span style="color: red;" v-show="cantidad == 0"></span></label>
                                    
                                        <select class="form-control" v-model="paquni" @change="preciounid">
                                            <option>Selecciona una Medida: </option>
                                            <option :value="unidad_envase" >paquete</option>
                                            <option :value="1" >unidad</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label><strong>Descuento %</strong></label>
                                        <input type="number" id="descuento" value="0" class="form-control" v-model="descuento"
                                            ref="descuentoRef">
                                    </div>
                                </div>
                                <label id="sTotal" style="display: none;">{{ ((precioseleccionado * cantidad * paquni) - (precioseleccionado * cantidad * paquni * descuento / 100)).toFixed(2) }}
                                </label>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <button @click="agregar2()" class="btn btn-success form-control btnagregar"><i
                                                class="icon-plus"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                    <div class="modal-footer">
                        <button @click="mostrarSeleccionProductos = true" class="btn btn-primary">Agregar Más Productos</button>
                        <button type="button" class="btn btn-secondary" @click="cerrarModalVerVariable()">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <!--Fin del Modal Variable Temporal-->

        <!--Inicio del modal agregar/actualizar-->
        <div class="modal fade" tabindex="-1" :class="{ 'mostrar': modal }" role="dialog" aria-labelledby="myModalLabel"
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
                        <div class="form-group row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <select class="form-control col-md-3" v-model="criterioA">
                                        <option value="nombre">Nombre</option>      
                                        <option value="descripcion">Descripción</option>
                                        <option value="codigo">Código</option>
                                    </select>
                                    <input type="text" v-model="buscarA" @keyup="listarArticulo(buscarA, criterioA)"
                                        class="form-control" placeholder="Texto a buscar">
                                    <!--button type="submit" @click="listarArticulo(buscarA, criterioA)"
                                        class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button-->
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
                                        <td v-text="articulo.precio_venta"></td>
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
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" v-text="tituloModal2"></h4>
                        <button type="button" class="close" @click="cerrarModal2()" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-control-label" for="text-input">Recibido
                                        {{ monedaVenta[1] }}(*)</label>
                                    <div class="input-group">
                                        <input type="number" v-model="recibido">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-control-label" for="text-input">Calcular</label>
                                    <div class="input-group">
                                        <button type="button" class="btn btn-primary"
                                            @click="calcularCambio()">Calcular</button>
                                    </div>
                                </div>
                            </div>


                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-control-label" for="text-input">ToTal(*)</label>
                                    <div class="input-group">
                                        <input type="number"
                                            :value="((calcularTotal) * parseFloat(monedaVenta[0])).toFixed(2)"
                                            class="font-weight-bold">
                                        <!-- <input type="number"  v-model="totalprue" class="negrita-input"> -->
                                    </div>
                                </div>
                                <!-- {{((articulo.precio_venta) *parseFloat(monedaVenta[0])).toFixed(2)}} {{ monedaVenta[1] }} -->

                                <div class="form-group">
                                    <label class="form-control-label" for="text-input">Efec(*)</label>
                                    <div class="input-group">
                                        <input type="number" disabled
                                            :value="(efectivo * parseFloat(monedaVenta[0])).toFixed(2)">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-control-label" for="text-input">Cambio(*)</label>
                                    <div class="input-group">
                                        <input type="number" disabled
                                            :value="(cambio * parseFloat(monedaVenta[0])).toFixed(2)">
                                    </div>

                                </div>
                                <div class="form-group">
                                    <label class="form-control-label" for="text-input">Faltante(*)</label>
                                    <div class="input-group">
                                        <input type="number" disabled
                                            :value="(faltante * parseFloat(monedaVenta[0])).toFixed(2)">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="cerrarModal2()">Volver</button>
                        <button type="button" v-if="tipoAccion2 == 1" class="btn btn-primary"
                            @click="registrar()">Cobrar</button>
                        <!-- <button type="button" v-if="tipoAccion==2" class="btn btn-primary" @click="actualizarSucursal()">Actualizar</button> -->
                    </div>
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
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-control-label" for="text-input">Numero Cuotas</label>
                                    <div class="input-group">
                                        <input type="number" v-model="numero_cuotas">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-control-label" for="text-input">Tiempo Días</label>
                                    <div class="input-group">
                                        <input type="number" v-model="tiempo_diaz">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-control-label" for="text-input">Generar Cuota</label>
                                    <button @click="generarCuotas">GENERAR_CUOTA</button>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-control-label" for="text-input">Primera Cuota</label>
                                    <div class="input-group">
                                        <div class="input-group-append">
                                            <div class="input-group-text">
                                                <input type="checkbox" v-model="primera_cuota" id="primera_cuota">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--################################-listado de cuotas-#######################-->
                        <div class="form-group row border">
                            <div class="table-responsive col-md-12">
                                <table class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <tr>
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
                                            <td>{{ cuota.fechaPago }}</td>
                                            <td>
                                                {{ (cuota.precioCuota * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                                    monedaVenta[1] }}

                                            </td>
                                            <td>
                                                {{ (cuota.totalCancelado * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                                    monedaVenta[1] }}

                                            </td>
                                            <td>
                                                {{ (cuota.saldo * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                                    monedaVenta[1] }}

                                            </td>
                                            <td>{{ cuota.fechaCancelado }}</td>
                                            <td>{{ cuota.estadocuocre }}</td>
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
                            @click="registrar()">Registrar</button>
                    </div>
                </div>
            </div>
        </div>  
        <!--#################-------HASTA AQUI MODAL CREDITOS------#################-->
    </main>
</template>

<script>
import vSelect from 'vue-select';
export default {
    data() {
        return {
            arrayPromocion: [],
            unidadPaquete: 1,

            monedaVenta: [],
            permitirDevolucion: '',
            saldosNegativos: 1,
            venta_id: 0,
            idcliente: 0,
            idVentaInstitucional: null,
            usuarioAutenticado: null,
            puntoVentaAutenticado: null,
            cliente: '',
            email: '',
            nombreCliente: '',
            documento: '',
            tipo_documento: '',
            complemento_id: '',
            descuentoAdicional: 0.00,
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
            arrayArticuloVariable: [],
            arrayArticuloVar: [],
            arraySeleccionado: [],
            arrayFactura : [],
            listado: 1,
            modal: 0,
            tituloModal: '',
            tipoAccion: 0,
            errorVenta: 0,
            errorMostrarMsjVenta: [],
            errorVariable: 0,
            errorMostrarMsjVariable: [],
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
            idarticulo: 0,
            codigo: '',
            articulo: '',
            medida: '',
            codigoClasificador: '',
            codigoProductoSin: '',
            precio: 0,
            unidad_envase:0,
            cantidad: 1,
            paquni:'',
            precioBloqueado: false,
            descuento: 0,
            sTotal: 0,
            stock: 0,
            valorMaximoDescuento: '',
            mostrarDireccion: true,
            mostrarCUFD: true,
            leyendaAl: '',
            codigoExcepcion: 0,

            //almacenes
            arrayAlmacenes: [],
            idAlmacen: null,
            //-----PRECIOS- AUMENTE 3/OCTUBRE--------
            precioseleccionado :'',
            //precio : '',
            arrayPrecios :[],
            nombre_precio :'',
            precio_uno: '',
            precio_dos : '',
            precio_tres : '',
            precio_cuatro : '',
            //-----MODAL 2---
            modal2:0,
            tituloModal2: '',
            tipoAccion2: '',
            //-----MODAL 3---
            modal3:0,
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
            faltante : 0,
            //-------DEStE AQUI 13-OCTUBRE--
            idtipo_pago :'',
            idtipo_venta:'',
            tiempo_diaz : '',
            numero_cuotas : '',
            cuotas: [],//---para almacenar las fechas
            estadocrevent : 'activo',
            primera_cuota : '',
            habilitarPrimeraCuota : false,
            modalActivo: false,
            nombreVariableTemporal: '',
            mostrarTabla: false,
            idVariableTemporal: '',
            modalVerVariable : false,
            tituloModalVerVariable: '',
            mostrarSeleccionProductos: false,
            idVariableTemporal2: '',
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
        scrollToSection() {
            // Utiliza jQuery para animar el desplazamiento hacia la sección objetivo
            $('html, body').animate({
                scrollTop: $('#seccionObjetivo').offset().top
            }, 50); // El valor 1000 es la duración de la animación en milisegundos
        },
        scrollToTop() {
            // Utiliza jQuery para animar el desplazamiento hacia arriba
            $('html, body').animate({
                scrollTop: 0
            }, 50); // El valor 1000 es la duración de la animación en milisegundos
        },
        calcularPrecioConDescuento(precioOriginal, porcentajeDescuento) {
            const descuento = precioOriginal * (porcentajeDescuento / 100);
            const precioConDescuento = precioOriginal - descuento;
            return precioConDescuento; // Ajusta a la cantidad de decimales que desees
        },
        calcularDiasRestantes(fechaFinal) {
            const fechaActual = new Date();
            const fechaObjetivo = new Date(fechaFinal);
            const diferenciaEnMilisegundos = fechaObjetivo - fechaActual;
            const diasRestantes = Math.ceil(diferenciaEnMilisegundos / (1000 * 60 * 60 * 24));
            return diasRestantes;
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
        ejecutarFunciones() {
            let me = this;
            
            axios.post('/variables/registrar-venta-institucional')
                .then(response => {
                    let idVentaInstitucional = response.data.idVentaInstitucional;

                    me.idVentaInstitucional = idVentaInstitucional;

                    me.mostrarDetalle();
                })
                .catch(error => {
                    console.error('Error al registrar la venta institucional:', error);
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

        verificarFactura(cuf, numeroFactura){
                var url = 'https://siat.impuestos.gob.bo/consulta/QR?nit=8033811015&cuf='+cuf+'&numero='+numeroFactura+'&t=2';
                window.open(url);
                
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
        nextNumber() {
            if (!this.num_comprob || this.num_comprob === "") {
                this.last_comprobante++;
                // Completa con ceros a la izquierda hasta alcanzar 5 dígitos
                this.num_comprob = this.last_comprobante.toString().padStart(5, "0");
            }
        },

        listarVenta(page, buscar, criterio) {
            let me = this;
            var url = '/venta?page=' + page + '&buscar=' + buscar + '&criterio=' + criterio;
            axios.get(url).then(function (response) {
                var respuesta = response.data;
                me.arrayVenta = respuesta.ventas.data;
                me.pagination = respuesta.pagination;
            })
                .catch(function (error) {
                    console.log(error);
                });
        },

        listarFactura (page){
                let me=this;
                var url= '/variables/facturaInstitucional?page=' + page;
                axios.get(url).then(function (response) {
                    var respuesta= response.data;
                    me.arrayFactura = respuesta.facturas.data;
                    me.pagination= respuesta.pagination;
                })
                .catch(function (error) {
                    console.log(error);
                });
            },

        listarArticulosVariable() {
            let me = this;
            let idvariable = this.idVariableTemporal;
            
            axios.get('/variables/listarArticulosVariable?idvariable=' + idvariable)
                .then(function (response) {
                    me.arrayArticuloVar = response.data.articulos;
                    console.log(me.arrayArticuloVar);
                })
                .catch(function (error) {
                    console.log(error);
                });
        },



        selectCliente(search, loading) {
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
        },
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
                    var respuesta = response.data;
                    me.arraySeleccionado = respuesta.articulos[0];
                    console.log(me.arraySeleccionado);
                    /*if (me.arrayArticulo.length > 0) {
                        me.fecha_vencimiento = me.arrayArticulo[0]['fecha_vencimiento'];
                    }
                    const fechaActual = new Date();
                    const año = fechaActual.getFullYear();
                    let mes = fechaActual.getMonth() + 1;
                    mes = mes < 10 ? '0' + mes : mes; 
                    let dia = fechaActual.getDate();
                    dia = dia < 10 ? '0' + dia : dia;
                    const fechaVencimiento = me.fecha_vencimiento;
                    if (fechaVencimiento <= `${año}-${mes}-${dia}`) {
                        console.log('VENCIDO', fechaVencimiento);
                        me.arrayArticulo.length = 0;
                        me.precioseleccionado = null;
                        me.advertenciaFechaVencimiento();
                        me.codigo = null;
                    } else {
                        if (me.arrayArticulo.length > 0) {
                        me.articulo = me.arrayArticulo[0]['nombre'];
                        me.idarticulo = me.arrayArticulo[0]['id'];
                        me.codigo = me.arrayArticulo[0]['codigo'];
                        me.precio = me.arrayArticulo[0]['precio_venta'];
                        me.stock = me.arrayArticulo[0]['saldo_stock'];
                        me.medida = me.arrayArticulo[0]['medida'];
                        me.codigoClasificador = me.arrayArticulo[0]['codigoClasificador'];
                        me.codigoProductoSin = me.arrayArticulo[0]['codigoProductoSin'];

                        //----precios---
                        me.unidad_envase= me.arrayArticulo[0]['unidad_envase'];
                        me.precio_uno = me.arrayArticulo[0]['precio_uno'];
                        me.precio_dos = me.arrayArticulo[0]['precio_dos'];
                        me.precio_tres = me.arrayArticulo[0]['precio_tres'];
                        me.precio_cuatro = me.arrayArticulo[0]['precio_cuatro'];
                        me.fotografia = me.arrayArticulo[0]['fotografia'];
                        }
                        else {
                            me.articulo = 'No existe este articulo';
                            me.idarticulo = 0;
                        }
                        me.codigo = me.codigo;
                        console.log('SIRVE', fechaVencimiento);
                    }
                    // console.log('fechaDeVencimiento:', me.fecha_vencimiento);
                    console.log('Fecha Actual:', fechaActual);*/
                })
                    .catch(function (error) {
                        console.log(error);
                    });
            }, 1000);
        },
        pdfVenta(id) {
            window.open('/venta/pdf/' + id, '_blank');
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

        eliminarDetalle(index, idArticuloVariable){
               swal({
                title: 'Esta seguro de quitar el articulo?',
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
                    axios.delete('/variables/excluirArticulo', { params: { id: idArticuloVariable } })
                    .then(function (response) {
                        me.arrayDetalle.splice(index, 1);
                        me.arrayProductos.splice(index, 1);
                        swal(
                        'Excluido!',
                        'El artículo ha sido excluido.',
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

        agregar() {
            this.registrarArticuloVariable();
        },

        agregar2() {
            this.registrarArticuloVariable2();
        },

        registrarArticuloVariable() {
        let me = this;

        if (!me.idVariableTemporal) {
            swal("Vacío", "No hay una Variable Temporal seleccionada", "info");
            return;
        }

        let formData = new FormData();

        formData.append('idvariable', me.idVariableTemporal);
        formData.append('idarticulo', me.arraySeleccionado.id);
        formData.append('cantidad', me.cantidad);
        formData.append('precio', me.precioseleccionado);
        formData.append('descuento', me.descuento);

        // Realiza la solicitud al backend
        axios.post('/variables/registrarArticuloVariable', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        }).then(function (response) {
            let idArticuloVariable = response.data.idArticuloVariable;
            let idVariable = response.data.idVariable;

            console.log(idArticuloVariable);
            console.log(idVariable);

            swal("Artículo Añadido", "Se añadió el Artículo a la Variable Creada", "success");

            me.agregarDetalle(idArticuloVariable, idVariable);
            me.listarArticulosVariable();
            me.precioBloqueado = false;
            me.precioseleccionado = 0;
            me.arraySeleccionado = [];
            me.cantidad = 1;
            me.unidadPaquete = 1;
            me.codigo = '';
            this.idVariableTemporal= '';
        }).catch(function (error) {
            console.log(error);
        });
    },

    registrarArticuloVariable2() {
        let me = this;

        if (!me.idVariableTemporal) {
            swal("Vacío", "No hay una Variable Temporal seleccionada", "info");
            return;
        }

        let formData = new FormData();

        formData.append('idvariable', me.idVariableTemporal2);
        formData.append('idarticulo', me.idarticulo);
        formData.append('cantidad', me.cantidad);
        formData.append('precio', me.precioseleccionado);
        formData.append('descuento', me.descuento);

        axios.post('/variables/registrarArticuloVariable2', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        }).then(function (response) {
            let idArticuloVariable = response.data.idArticuloVariable;
            let idVariable = response.data.idVariable;

            console.log(idArticuloVariable);
            console.log(idVariable);

            swal("Artículo Añadido", "Se añadió el Artículo a la Variable Creada", "success");

            me.agregarDetalle(idArticuloVariable, idVariable);
            me.verVariable(me.idVariableTemporal2);
        }).catch(function (error) {
            console.log(error);
        });
    },



        agregarDetalle(idArticuloVariable, idVariable) {
            let me = this;

            let actividadEconomica = 474110;
            /*let codigoProductoSin = document.getElementById("codigoProductoSin").value;
            let codigoProducto = document.getElementById("codigo").value;
            let descripcion = document.getElementById("nombre_producto").value;
            let cantidad = document.getElementById("cantidad").value;
            let unidadMedida = document.getElementById("codigoClasificador").value;
            let precioUnitario = document.getElementById("precio").value;
            let montoDescuento = document.getElementById("descuento").value;
            let subTotalValor = document.getElementById("sTotal");
            let subTotal = subTotalValor.textContent;*/
            let numeroSerie = null;
            let numeroImei = null;          
            let idArtVariable = idArticuloVariable;
            let idVar = idVariable;

            console.log("El id llegado es :" + idArtVariable);

            if (this.encuentra(this.arraySeleccionado.id)) {
                swal({
                    type: 'error',
                    title: 'Error...',
                    text: 'Este Artículo ya se encuentra agregado!',
                })
            } else {
                if (me.encuentra(me.idarticulo)) {
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
                         
                        const precioArticulo= this.resultadoMultiplicacion;
                
                        //const precioArticulo = this.calcularPrecioConDescuento(this.resultadoMultiplicacion, this.arrayPromocion ? this.arrayPromocion.porcentaje : 0);
                        console.log("El precio del articulo es: " + precioArticulo);
                            me.arrayDetalle.push({
                                idarticulo: this.arraySeleccionado.id,
                                articulo: this.arraySeleccionado.nombre,
                                medida: this.arraySeleccionado.medida,
                                unidad_envase: this.arraySeleccionado.unidad_envase,
                                cantidad: this.cantidad * this.unidadPaquete,
                                cantidad_paquetes: this.arraySeleccionado.unidad_envase,
                                precio: precioArticulo,
                                descuento: this.arrayPromocion && this.arrayPromocion.porcentaje !== undefined ? this.arrayPromocion.porcentaje : 0,
                                stock: this.arraySeleccionado.saldo_stock,
                                precioseleccionado: this.precioseleccionado,
                                idArtVariable: idArtVariable,
                                idVar: idVar

                            });
                            console.log("ARRAY_AGREGADO",me.arrayDetalle);

                            me.arrayProductos.push({
                                actividadEconomica: actividadEconomica,
                                codigoProductoSin: this.arraySeleccionado.codigoProductoSin,
                                codigoProducto: this.arraySeleccionado.codigo,
                                descripcion: this.arraySeleccionado.nombre,
                                cantidad: this.cantidad * this.unidadPaquete,
                                unidadMedida: this.arraySeleccionado.codigoClasificador,
                                precioUnitario: parseFloat(this.precioseleccionado).toFixed(2),
                                montoDescuento: this.arrayPromocion && this.arrayPromocion.porcentaje ? ((this.arrayPromocion.porcentaje / this.resultadoMultiplicacion) * 100).toFixed(2) : 0,
                                subTotal: precioArticulo.toFixed(2),
                                numeroSerie: numeroSerie,
                                numeroImei: numeroImei
                            });
                            console.log("Array Productos: ",me.arrayProductos);
                    
                            this.precioBloqueado = false;
                            this.arraySeleccionado = [];
                            this.cantidad = 1;
                            this.unidadPaquete = 1;
                            this.codigo = '';
                        }
                    } 
        },

        agregarProductoVariable(data) {
            this.codigo = data.codigo;
            console.log('Producto Seleccionado:', this.codigo);
        },
        agregarDetalleModal(data) {
            this.scrollToSection();
            this.codigo = data.codigo;
            console.log('SLECCIONE ESTO:', data);

            this.buscarPromocion(data.id);
            this.precioseleccionado = data.precio_uno;

            this.cerrarModal();
        },
        listarArticulo(buscar, criterio) {
            let me = this;
            var url = '/articulo/listarArticuloVenta?buscar=' + buscar + '&criterio=' + criterio + '&idAlmacen=' + this.idAlmacen;
            axios.get(url).then(function (response) {
                var respuesta = response.data;
                me.arrayArticulo = respuesta.articulos;
                console.log('listar articulo',respuesta);
            })
                .catch(function (error) {
                    console.log(error);
                });
        },
        eliminarSeleccionado() {
            this.codigo = '';
            this.arraySeleccionado = []
        },
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
        registrar() {
            this.registrarVenta();
            this.emitirFactura();
            //this.num_comprob;
        },
        //-------------REGISTRARAR VENTA ------
        registrarVenta() {
            if (this.validarVenta()) {
                return;
            }

            let me = this;

            for (let i = 0; i < me.cuotas.length; i++) {
                // console.log('INvENtARIO',me.cuotas[i].idinventario);
                // console.log('ARtICULOID',me.cuotas[i].idarticulo);
                // console.log(me.cuotas[i].cantidad_traspaso);                 
                console.log('LLEGA ARRAYDATA!',me.cuotas[i]);
            }

            axios.post('/venta/registrar', {
                'idcliente': this.idcliente,
                'tipo_comprobante': this.tipo_comprobante,
                'serie_comprobante': this.serie_comprobante,
                'num_comprobante': this.num_comprob,
                'impuesto': this.impuesto,
                'total': this.total,
                'idAlmacen': this.idAlmacen,
                'idtipo_pago': this.idtipo_pago,
                'idtipo_venta':this.idtipo_venta,
                //----creditos Ventas----
                'idpersona' : this.idcliente,
                'numero_cuotas': this.numero_cuotas,
                'tiempo_dias_cuota': this.tiempo_diaz,
                'estadocrevent': this.estadocrevent,
                 //-----hasta aqui-------
                //----Cuotas Credito----
                'cuotaspago': this.cuotas,
                //-----hasta aqui-------
                'data': this.arrayDetalle

            }).then(function (response) {
                //console.log(response.data.id);
                if (response.data.id > 0) {
                    let idVentaRecienRegistrada = response.data.id;
                    axios.post('/variables/ventas_institucionales', {
                        idventa: idVentaRecienRegistrada,
                        id: me.idVentaInstitucional
                    }).then(function (responseModificacion) {
                        me.listarFactura(1, '');
                        console.log("Venta Institucional regisstrada correctamente");
                    }).catch(function (errorModificacion) {
                        console.error(errorModificacion);
                    });

                    me.listado = 1;
                    me.listarFactura(1, '');
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
                    me.idVentaInstitucional = '';
                    //window.open('/factura/imprimir/' + response.data.id);
                } else {
                    if (response.data.valorMaximo) {
                        //alert('El valor de descuento no puede exceder el '+ response.data.valorMaximo);
                        swal(
                            'Aviso',
                            'El valor de descuento no puede exceder el ' + response.data.valorMaximo,
                            'warning'
                        )
                        return;
                    } else {
                        //alert(response.data.caja_validado); 
                        swal(
                            'Aviso',
                            response.data.caja_validado,
                            'warning'
                        )
                        return;
                    }
                    //console.log(response.data.valorMaximo)
                }

            }).catch(function (error) {
                console.log(error);
            });
        },
        //--------HASTA AQUI--------------------
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

        async emitirFactura() {
            let me = this;

            let numeroFactura = document.getElementById("num_comprobante").value;
            let cuf = "464646464";
            let cufdValor = document.getElementById("cufdValor");
            let cufd = cufdValor.textContent;
            let direccionValor = document.getElementById("direccion");
            let direccion = direccionValor.textContent;
            var tzoffset = (new Date()).getTimezoneOffset() * 60000;
            let fechaEmision = (new Date(Date.now() - tzoffset)).toISOString().slice(0, -1);
            let id_cliente = document.getElementById("idcliente").value;
            let nombreRazonSocial = document.getElementById("nombreCliente").value;
            let tipoDocumentoIdentidad = document.getElementById("tipo_documento").value;
            let numeroDocumento = document.getElementById("documento").value;
            let complemento = document.getElementById("complemento_id").value;
            let montoTotal = (this.calcularTotal * parseFloat(this.monedaVenta[0])).toFixed(2);
            let descuentoAdicional = document.getElementById("descuentoAdicional").value;
            let leyenda = "Ley N° 453: El proveedor de servicios debe habilitar medios e instrumentos para efectuar consultas y reclamaciones.";
            let usuario = document.getElementById("usuarioAutenticado").value;
            //let codigoPuntoVenta = this.puntoVentaAutenticado;
            let codigoPuntoVenta = 0;
            let idventainstitucional = this.idVentaInstitucional;

            console.log("El tipo de documento es: " + tipoDocumentoIdentidad);
            console.log("El monto total es: " + montoTotal);
            console.log("El id de la venta es: " + idventainstitucional);

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
                            alert("NIT VÁLIDO.");
                        } else {
                            me.codigoExcepcion = 1;
                            alert("NIT INVÁLIDO.");
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
                    codigoMetodoPago: 1,
                    numeroTarjeta: null,
                    montoTotal: montoTotal,
                    montoTotalSujetoIva: montoTotal,
                    codigoMoneda: 1,
                    tipoCambio: 1,
                    montoTotalMoneda: montoTotal,
                    montoGiftCard: null,
                    descuentoAdicional: descuentoAdicional,
                    codigoExcepcion: this.codigoExcepcion,
                    cafc: null,
                    leyenda: this.leyendaAl,
                    usuario: usuario,
                    codigoDocumentoSector: 1
                }
            })
            me.arrayProductos.forEach(function (prod) {
                factura.push({ detalle: prod })
            })

            var datos = { factura };
            
            axios.post('/variables/emitirFacturaInstitucional', {
                factura: datos,
                id_cliente: id_cliente,
                idventainstitucional: idventainstitucional
            })
                .then(function (response) {
                    var data = response.data;
                    console.log(data);
                    if (data === "VALIDADA") {
                        swal(
                            'FACTURA ' + data,
                            'Correctamente',
                            'success'
                        )
                        me.arrayProductos = [];
                        me.codigoExcepcion = 0;
                        me.cerrarModal2();
                        me.cerrarModal3();
                        me.listarVenta(1, '', 'id');
                    }else{
                        if (Array.isArray(data)) {
                    let errorMessage = 'Errores al emitir la Factura:<br>';

                    data.forEach((descripcion, index) => {
                        errorMessage += `${index + 1}: ${descripcion}<br>`;
                    });

                    swal({
                        title: 'FACTURA RECHAZADA',
                        html: errorMessage,
                        type: 'warning',
                        showCloseButton: true,
                        allowOutsideClick: false,
                    });
                    }
                    me.arrayProductos = [];
                    me.codigoExcepcion = 0;
                    }
                })
                .catch(function (error) {
                    me.arrayProductos = [];
                    swal(
                        'INTENTE DE NUEVO',
                        'Comunicacion con SIAT fallida',
                        'error');
                });
        },

        imprimirFactura(id, idventainstitucional) {
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
                    axios.get('/variables/imprimirCarta/' + id + '/' + idventainstitucional, { responseType: 'blob' })
                        .then(function (response) {
                            window.location.href = "docs/facturaCarta.pdf";
                            console.log("Se generó el factura en Carta correctamente");
                        })
                        .catch(function (error) {
                            console.log(error);
                        });
                } else if (result.dismiss === swal.DismissReason.cancel) {
                    console.log("El boton ROLLO fue presionado");
                    axios.get('/variables/imprimirRollo/' + id, { responseType: 'blob' })
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
                    .then(function(response) {
                    var respuesta = response.data;
                    me.arrayMotivosAnulacion = respuesta.motivo_anulaciones;
                    
                    console.log('Motivos obtenidos:', me.arrayMotivosAnulacion);

                    let options = {};
                    me.arrayMotivosAnulacion.forEach(function(motivo) {
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
                        axios.get('/factura/anular/' + cuf +"/" + motivoSeleccionado)
                            .then(function(response) {
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
                            .catch(function(error) {
                            console.log(error);
                            });
                        }
                    });
                    })
                    .catch(function(error) {
                    console.log(error);
                    });
                }
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
            me.cantidad = 0;
            me.precio = 0;
            me.arrayDetalle = [];
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

        abrirModal() {
            console.log('Abriendo modal');
            if (this.idAlmacen == 0) {
                swal("Ningún Almacén Seleccionado", "Debe seleccionar un almacén para continuar", "info");
                return;
            }
            this.listarArticulo('', 'nombre');
            this.arrayArticulo = [];
            this.modalActivo = true;
        },

        cerrarModal() {
            this.nombreVariableTemporal = '';
            this.arrayArticuloVariable = [];
            this.arrayArticuloVar = [];
            this.precioBloqueado = false;
            this.arraySeleccionado = [];
            this.cantidad = 1;
            this.unidadPaquete = 1;
            this.codigo = '';
            this.idVariableTemporal= '';
            this.modalActivo = false;
        },
        cerrarModalVerVariable() {
            this.mostrarSeleccionProductos = false;
            this.modalVerVariable = false;
        },
        guardarVariableYProductos() {
            this.cerrarModal();
        },

        crearVariableTemporal() {
            console.log('Creando variable temporal:', this.nombreVariableTemporal);
            
            if (this.validarVariable()){
                return;
            }
        
            let me = this;
            let formData = new FormData();

            formData.append('idventainstitucional', this.idVentaInstitucional);
            formData.append('nombre', this.nombreVariableTemporal);

            axios.post('/variables/registrarVariable', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }

            }).then(function (response) {
                const variableTemporalId = response.data.id;
                console.log('ID de la VariableTemporal:', variableTemporalId);
                me.guardarIdVariableTemporal(variableTemporalId);
                swal("Exito", "Variable Creada Correctamente", "success");
                me.mostrarTabla = true;
                //me.listarArticulosVariable();
            }).catch(function (error) {
                console.log(error);
            });
        },

        verVariable(idVar) {
        this.modalVerVariable = true;

        let me = this;
        let variable = idVar;

        axios.get('/variables/listarArticulosVariable?idvariable=' + idVar)
            .then(function (response) {
                console.log(response);
                let nombreVariable = response.data.nombreVariable;
                let arrayArticuloVariable = response.data.articulos;
                
                document.getElementById('nombreVariableTemp').textContent = nombreVariable;
                me.idVariableTemporal2 = idVar;

                console.log('Nombre de la Variable:', nombreVariable);
                console.log('Artículos de la Variable:', arrayArticuloVariable);

                me.arrayArticuloVariable = arrayArticuloVariable;
            })
            .catch(function (error) {
                console.log(error);
            });
    },



        guardarIdVariableTemporal(id) {
            this.idVariableTemporal = id;
            console.log('ID de la VariableTemporal:', id);
        },

        validarVariable(){
            this.errorVariable=0;
            this.errorMostrarMsjVariable =[];
            console.log(this.nombreVariableTemporal);
            if (this.nombreVariableTemporal=="") this.errorMostrarMsjVariable.push("El nombre de la variable no puede estar vacío.");
            
            if (this.errorMostrarMsjVariable.length) this.errorVariable = 1;

            return this.errorVariable;
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
                console.log('PRECIOS',me.arrayPrecios);
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
            this.tituloModal2 = 'PAGO AL CONTADO ' + this.cliente; // Usamos '+' para concatenar el nombre del cliente
            this.tipoAccion2 = 1;
            this.idtipo_pago = 1;
            this.idtipo_venta= 1;
            
            console.log('idtipo_venta LLEGA:', this.idtipo_venta);
            console.log('idtipo_pago LLEGA:', this.idtipo_pago);
        },

        registrarAbrilModal2() {
            this.modal3 = 1;
            this.cliente = this.nombreCliente;
            console.log('USUARIO LLEGA:', this.cliente);
            this.tituloModal3 = 'CREDITOS ' + this.cliente; // Usamos '+' para concatenar el nombre del cliente
            this.tipoAccion3 = 1;
            this.idtipo_pago = 1; 
            this.idtipo_venta= 2;
            
            console.log('idtipo_venta LLEGA:', this.idtipo_venta);
            console.log('idtipo_pago LLEGA:', this.idtipo_pago);
        },
        cerrarModal2() {
            this.modal2 = 0;
            this.tituloModal2 = '';
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
    
    },

    created() {
        this.listarPrecio();
        axios.get('/ruta-a-tu-endpoint-laravel-para-obtener-ultimo-comprobante')
            .then(response => {
                const lastComprobante = response.data.last_comprobante;

                this.last_comprobante = lastComprobante;

                this.nextNumber();
            })
            .catch(error => {
                console.error('Error al obtener el último comprobante:', error);
            });
    },



    mounted() {
        this.datosConfiguracion();
        this.listarVenta(1, this.buscar, this.criterio);
        this.listarArticulo(1, this.buscar, this.criterio);
        window.addEventListener('keydown', this.atajoButton);
        this.datosConfiguracion();
        this.verificarComunicacion();
        this.cuis();
        this.cufd();
        this.obtenerDatosUsuario();
        this.listarFactura();
    }
}
</script>
<style>    
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
        width: 120px;
        height: auto;
        border-top-right-radius: 8px;
        border-bottom-right-radius: 8px;
    }
    /* .negrita-input {
        font-weight: bold;
    } */
</style>
  
