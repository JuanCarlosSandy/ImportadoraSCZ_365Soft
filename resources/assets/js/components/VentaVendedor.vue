<template>
    <main class="main">
        <!-- Breadcrumb -->
        <Panel header=" Ventasss">
            <span class="badge bg-secondary" id="comunicacionSiat" style="color: white;" v-show="mostrarElementos">Desconectado</span>
            <span class="badge bg-secondary" id="cuis" v-show="mostrarElementos">CUIS: Inexistente</span>
            <span class="badge bg-secondary" id="cufd" v-show="mostrarElementos">No existe cufd vigente</span>
            <span class="badge bg-secondary" id="direccion" v-show="mostrarDireccion">No hay dirección registrada</span>
            <span class="badge bg-primary" id="cufdValor" v-show="mostrarCUFD">No hay CUFD</span>

            <template>
                <div class="p-d-flex p-jc-between p-ai-center">
                    <span class="p-input-icon-left">
                        <i class="pi pi-search" />
                        <InputText v-model="buscar" @input="buscarVenta" placeholder="Texto a buscar" />
                    </span>
                    <Button @click="abrirTipoVenta" label="Nuevo" icon="pi pi-plus" class="p-button-primary" />
                </div>
            </template>
            <!-- Listado-->
            <template v-if="listado == 1">
                <div>
                    <DataTable responsiveLayout="scroll" class="p-datatable-gridlines p-datatable-sm"
                        :value="arrayVenta" :rows="10">
                            <Column header="Opciones">
                                <template #body="slotProps">
                                    <!-- Botón para ver la venta -->
                                    <Button 
                                        icon="pi pi-eye" 
                                        @click="verVenta(slotProps.data.id)" 
                                        class="p-button-sm p-mr-1"
                                        style="background-color: green; border-color: green; color: white;" 
                                    />

                                    <!-- Botón para desactivar la venta, solo para recibos y estado registrado -->
                                    <template v-if="slotProps.data.estado === 'Registrado' && idrol !== 2">
                                        <Button 
                                            icon="pi pi-trash"                                     
                                            v-if="slotProps.data.tipo_comprobante === 'RESIVO'" 
                                            @click="desactivarVenta(slotProps.data.id)"
                                            class="p-button-sm p-button-danger p-mr-1" 
                                        />
                                    </template>

                                    <!-- Botón para imprimir recibos -->
                                    <Button 
                                        icon="pi pi-print"
                                        v-if="slotProps.data.tipo_comprobante === 'RESIVO'" 
                                        @click="imprimirResivo(slotProps.data.id, slotProps.data.correo)"
                                        class="p-button-sm p-button-primary p-mr-1" 
                                    />

                                    <!-- Botones para acciones de factura -->
                                    <template v-if="slotProps.data.tipo_comprobante === 'FACTURA'">
                                        <Button 
                                            icon="pi pi-check" 
                                            @click="verificarFactura(slotProps.data.cuf, slotProps.data.numeroFactura)" 
                                            class="p-button-sm p-mr-1"
                                            style="background-color: green; border-color: green; color: white;" 
                                        />
                                        <Button 
                                            icon="pi pi-print"
                                            @click="imprimirFactura(slotProps.data.idFactura, slotProps.data.correo)"
                                            class="p-button-sm p-button-primary p-mr-1" 
                                        />
                                        <Button 
                                            icon="pi pi-trash" 
                                            @click="anularFactura(slotProps.data.idFactura, slotProps.data.cuf)"
                                            class="p-button-sm p-button-danger p-mr-1" 
                                        />
                                    </template>
                                </template>
                            </Column>

                            <Column field="usuario" header="Vendedor"></Column>
                            <Column field="razonSocial" header="Cliente"></Column>
                            <Column field="documentoid" header="Documento" class="d-none d-md-table-cell"></Column>
                            <Column field="num_comprobante" header="N° de Factura" class="d-none d-md-table-cell"></Column>
                            <Column field="fecha_hora" header="Fecha y Hora" class="d-none d-md-table-cell"></Column>
                            <Column header="Total">
                                <template #body="slotProps">
                                    {{ (slotProps.data.total * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}
                                </template>
                            </Column>
                            <Column field="estado" header="Estado" class="d-none d-md-table-cell"></Column>
                        </DataTable>


                    <Paginator :rows="5" :totalRecords="pagination.total" :first="(pagination.current_page - 1) * 5"
                        @page="onPageChange" />
                </div>
            </template>



            <!--Fin Listado-->

            <!--Ver ingreso-->
            <template v-else-if="listado == 2">
                <Card class="shadow">
                    <template #content>
                        <div class="p-grid p-fluid border p-3 mb-3">
                            <div class="p-col-12 p-md-9">
                                <div class="p-field">
                                    <label>Cliente</label>
                                    <InputText v-model="cliente" disabled />
                                </div>
                            </div>
                            <div class="p-col-12 p-md-3">
                                <div class="p-field">
                                    <label>Tipo Comprobante</label>
                                    <InputText v-model="tipo_comprobante" disabled />
                                </div>
                            </div>
                            <div class="p-col-12 p-md-3">
                                <div class="p-field">
                                    <label>Número Comprobante</label>
                                    <InputText v-model="num_comprobante" disabled />
                                </div>
                            </div>
                        </div>

                        <DataTable :value="arrayDetalle" class="mb-3">
                            <Column field="articulo" header="Artículo"></Column>
                            <Column header="Precio">
                                <template #body="slotProps">
                                    {{ (slotProps.data.precio * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                        monedaVenta[1] }}
                                </template>
                            </Column>
                            <Column field="cantidad" header="Cantidad"></Column>
                            <Column header="Subtotal">
                                <template #body="slotProps">
                                    {{ ((slotProps.data.precio * slotProps.data.cantidad) *
                                        parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}
                                </template>
                            </Column>
                        </DataTable>

                        <div class="p-text-right p-mb-3">
                            <strong>Total Neto: {{ (total * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1]
                                }}</strong>
                        </div>

                        <div class="p-text-right">
                            <Button label="Cerrar" @click="ocultarDetalle()" class="p-button-secondary" />
                        </div>
                    </template>
                </Card>
            </template>

        </panel>
        <!-- HASTA AQUI DEVOLUCIONES -->
        <template>
            <Dialog :visible.sync="modal2" :containerStyle="{ width: '60vw' }" :modal="true" :closable="false"
                :closeOnEscape="false">
                <template #header>
                    <div class="modal-header">
                        <button class="close-button" @click="modal2 = false">×</button>
                        <h5 class="modal-title">Detalle Ventas</h5>
                    </div>
                </template>

                <div class="p-fluid">
                    <div class="p-field">
                        <div class="step-indicators">
                            <span :class="['step', { 'active': step === 1, 'completed': step > 1 }]">1</span>
                            <span :class="['step', { 'active': step === 2, 'completed': step > 2 }]">2</span>
                        </div>
                    </div>

                    <div v-if="step === 2" class="step-content p-fluid">
                        <div class="p-grid p-formgrid align-items-start">
                            <!-- Columna para Datos del Cliente -->
                            <div class="p-col-12 p-md-6 d-flex flex-column justify-content-start">
                                <h5 class="mb-3" style="font-size: 1.5rem; font-weight: bold; text-align: center;">DATOS DEL CLIENTE</h5>
                                <div style="width: 100%;">
                                    <div class="p-mb-3">
                                        <span class="p-float-label">
                                            <InputText id="documento" v-model="documento" @keyup.enter="buscarClientePorDocumento"style="margin-top: 8px;" />
                                            <label for="documento">Documento <span class="p-error">*</span></label>
                                        </span>
                                    </div>
                                    <div class="p-mb-3">
                                        <span class="p-float-label">
                                            <InputText id="nombreCliente" v-model="nombreCliente" :disabled="!nombreClienteEditable" />
                                            <label for="nombreCliente">Cliente <span class="p-error">*</span></label>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna para las opciones de pago -->
                            <div class="p-col-12 p-md-6 d-flex flex-column justify-content-start">
                                <div v-if="tipoVenta === 'contado'">
                                    <!-- Botones de pago -->
                                    <div class="d-flex justify-content-center mb-1">
                                        <!-- Ajustado margen inferior de botones -->
                                        <div class="form-group">
                                            <div class="btn-group">
                                                <button class="btn btn-primary" @click="opcionPago = 'efectivo'">
                                                    <i class="fa fa-money mr-2" aria-hidden="true"></i>
                                                    Efectivo
                                                </button>
                                                <button class="btn btn-primary" @click="opcionPago = 'qr'">
                                                    <i class="fa fa-qrcode mr-2" aria-hidden="true"></i>
                                                    QR
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sección para pago en efectivo -->
                                    <div v-if="opcionPago === 'efectivo'" style="margin-top: -5px;">
                                        <!-- Primera Card -->
                                        <div class="card mb-2" style="font-size: 0.75rem;">
                                            <div class="card-body d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <label for="montoEfectivo" class="mb-0 mr-2"><i class="fa fa-money mr-2"></i> Monto Recibido:</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">{{ monedaVenta[1] }}</span>
                                                        </div>
                                                        <input type="number" class="form-control" id="montoEfectivo" v-model="recibido" placeholder="Ingrese el monto recibido" />
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center ml-3">
                                                    <label for="cambioRecibir" class="mb-0 mr-2"><i class="fa fa-exchange mr-2"></i> Cambio a Entregar:</label>
                                                    <input type="text" class="form-control" id="cambioRecibir" :value="recibido - calcularTotal * parseFloat(monedaVenta[0])" readonly />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Segunda Card -->
                                        <div class="card" style="font-size: 0.75rem;">
                                            <div class="card-body d-flex justify-content-between align-items-center">
                                                <div class="d-flex flex-column">
                                                    <h5 class="mb-0" style="font-size: 0.95rem;">Detalle de Venta</h5>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fa fa-money mr-2" style="font-size: 0.75rem;"></i>
                                                            <span style="font-size: 0.75rem;">Total a Pagar:&nbsp;&nbsp;</span>
                                                        </div>
                                                        <span class="font-weight-bold h5 mb-0" style="font-size: 0.95rem; line-height: 1;">{{ (calcularTotal * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}</span>
                                                    </div>
                                                </div>

                                                <div class="ml-3">
                                                    <button class="btn btn-light" @click="aplicarDescuentoRecibo(1)">
                                                        <img src="/img/Librerialogin.png" alt="Botón Imagen" class="img-fluid" style="height: 24px;">
                                                    </button>
                                                </div>
                                                <div>
                                                    <button type="button" @click="aplicarDescuento(1)" class="btn btn-success">
                                                        <i class="fa fa-check mr-2"></i> Registrar Pago
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sección para pago con QR -->
                                    <div v-else-if="opcionPago === 'qr'"style="margin-top: -5px;">
                                        <div class="card">
                                            <div class="card-body d-flex justify-content-between align-items-center">
                                            <!--<div class="card-body">-->
                                                <div class="form-group">
                                                    <h5 class="mb-0" style="font-size: 0.95rem;">Detalle de Venta</h5>
                                                    <label for="montoEfectivo">Total a pagar:</label>
                                                    <span class="font-weight-bold">{{ montoEfectivo = (calcularTotal).toFixed(2) }}</span>
                                                </div>
                                                <!--<button class="btn btn-primary mb-2" @click="generarQr">Generar QR</button>
                                                <div v-if="qrImage" class="mb-2 text-center">
                                                    <img :src="qrImage" alt="Código QR" class="img-fluid" />
                                                </div>
                                                <button class="btn btn-secondary mb-2" @click="verificarEstado" v-if="qrImage">Verificar Estado de Pago</button>
                                                <div v-if="estadoTransaccion" class="card p-2">
                                                    <div class="font-weight-bold">Estado Actual:</div>
                                                    <div>
                                                        <span :class="'badge badge-' + badgeSeverity">{{ estadoTransaccion.objeto.estadoActual }}</span>
                                                    </div>
                                                </div>-->
                                                <div class="ml-3">
                                                    <button class="btn btn-light" @click="aplicarDescuentoRecibo(7)">
                                                        <img src="/img/Librerialogin.png" alt="Botón Imagen" class="img-fluid" style="height: 24px;">
                                                    </button>
                                                </div>
                                                <div>
                                                    <button type="button" @click="aplicarDescuento(7)" class="btn btn-success">
                                                        <i class="fa fa-check mr-2"></i> Registrar Pago
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <InputText v-model="idcliente" type="hidden" />
                        <InputText v-model="tipo_documento" type="hidden" />
                        <InputText v-model="complemento_id" type="hidden" />
                        <InputText v-model="usuarioAutenticado" type="hidden" />
                        <InputText v-model="puntoVentaAutenticado" type="hidden" />
                        <InputText v-model="email" type="hidden" />
                        <InputText v-model="num_comprob" type="hidden" disabled />
                    </div>

                    <div v-if="step === 1" class="step-content">
                        <div class="p-fluid p-grid">
                            <div class="p-col-12 p-md-6">
                                <label class="p-d-block">Almacen <span class="p-error">*</span></label>
                                <Dropdown v-model="selectedAlmacen" :options="arrayAlmacenes"
                                    optionLabel="nombre_almacen" optionValue="id" placeholder="Seleccione un almacén"
                                    @change="getAlmacenProductos" />
                            </div>

                            <div class="p-col-12 p-md-6">
                                <label class="p-d-block">Buscar articulo   ATAJO Shift + B</label>
                                <div class="p-inputgroup">
                                    <InputText v-model="codigo" placeholder="Codigo del articulo"
                                        :disabled="!selectedAlmacen" @keyup="buscarArticulo()" />
                                    <Button icon="pi pi-search" :disabled="!selectedAlmacen" @click="abrirModal" />
                                </div>
                            </div>
                        </div>

                        <DataTable :value="arrayDetalle" class="p-mt-3">
                            <Column header="Opciones" style="width: 10%">
                                <template #body="slotProps">
                                    <Button icon="pi pi-trash" class="p-button-danger p-button-sm"
                                        @click="slotProps.data.medida != 'KIT' ? eliminarDetalle(slotProps.data.id) : eliminarKit(slotProps.data.idkit)" />
                                </template>
                            </Column>
                            <Column field="articulo" header="Artículo" style="width: 30%" />
                            <Column field="stock" header="Stock Actual" style="width: 30%" />
                            <Column field="precioUnidad" header="Precio Unidad" style="width: 15%">
                                <template #body="slotProps">
                                    <input
                                        type="text"
                                        v-model="slotProps.data.precioseleccionado"
                                        @input="actualizarDetalle(slotProps.index, slotProps.data.precioseleccionado)"
                                        class="form-control"
                                    />
                                </template>
                            </Column>
                            <Column field="unidades" header="Unidades" style="width: 15%">
                                <template #body="slotProps">
                                    <InputNumber v-model="slotProps.data.cantidad" :min="1"
                                        @input="actualizarDetalle(slotProps.index)" />
                                </template>
                            </Column>
                            <Column field="total" header="Total" style="width: 20%">
                                <template #body="slotProps">
                                    {{ (slotProps.data.precioseleccionado * slotProps.data.cantidad *
                                        parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}
                                </template>
                            </Column>
                        </DataTable>

                        <div class="p-grid p-mt-3">
                            <div class="p-col-12 p-md-8"></div>
                            <div class="p-col-12 p-md-4" style="text-align: right;">
                                <h3>Total Neto: {{ (calcularTotal * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                    monedaVenta[1] }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="buttons d-flex justify-content-center">
                        <button class="btn btn-primary mr-2" @click="prevStep" :disabled="step === 1">Anterior</button>
                        <button class="btn btn-primary" @click="validarYAvanzar" :disabled="step === 2">Siguiente</button>
                    </div>
                </div>
             </Dialog>
        </template>
        
        <template>
            <Dialog :visible="modal" :containerStyle="{ width: '800px' }" style="padding-top: 35px;" :modal="true"
                :closable="false">
                <template #header>
                    <h3>{{ tituloModal }}</h3>
                </template>

                <TabView>
                    <TabPanel header="Articulos">
                        <div class="p-grid">
                            <div class="p-col-12">
                                <div class="p-field" style="width: 100%;">
                                    <label for="buscarA" class="p-text-bold" style="display: block; margin-bottom: 8px;">Buscar:</label>
                                    <InputText 
                                        id="buscarA" 
                                        v-model="buscarA" 
                                        placeholder="Texto a buscar" 
                                        @input="listarArticulo(buscarA)" 
                                        class="p-inputtext-lg" 
                                        style="width: 100%; margin: 0;" 
                                    />
                                </div>
                            </div>
                        </div>

                        <DataTable :value="arrayArticulo" :paginator="true" :rows="10" class="p-mt-2">
                            <Column header="Opciones">
                                <template #body="slotProps">
                                    <Button icon="pi pi-check" class="p-button-success p-button-sm"
                                        @click="agregarDetalleModal(slotProps.data)" />
                                </template>
                            </Column>
                            <Column field="codigo" header="Código" />
                            <Column field="nombre" header="Nombre" />
                            <Column field="nombre_categoria" header="Categoría" />
                            <Column header="Precio Venta">
                                <template #body="slotProps">
                                    {{ (slotProps.data.precio_uno * parseFloat(monedaVenta[0])).toFixed(2) }} {{
                                        monedaVenta[1] }}
                                </template>
                            </Column>
                            <Column field="saldo_stock" header="Stock" />
                            <Column header="Estado">
                                <template #body="slotProps">
                                    <Tag :severity="slotProps.data.condicion ? 'success' : 'danger'"
                                        :value="slotProps.data.condicion ? 'Activo' : 'Desactivado'" />
                                </template>
                            </Column>
                        </DataTable>
                    </TabPanel>
                </TabView>

                <template #footer>
                    <Button label="Cerrar" icon="pi pi-times" @click="cerrarModal" class="p-button-secondary" />
                    <Button v-if="tipoAccion === 1" label="Guardar" icon="pi pi-check" @click="registrarPersona" />
                    <Button v-if="tipoAccion === 2" label="Actualizar" icon="pi pi-check" @click="actualizarPersona" />
                </template>
            </Dialog>
        </template>
    </main>
</template>

<script>
import Dropdown from 'primevue/dropdown';
import Swal from 'sweetalert2';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Panel from 'primevue/panel';
import Steps from 'primevue/steps';
import Dialog from 'primevue/dialog';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import SelectButton from 'primevue/selectbutton';
import InputNumber from 'primevue/inputnumber';



export default {
    components: {
        Dropdown,
        DataTable,
        Column,
        Button,
        Paginator,
        Card,
        InputText,
        Button,
        Panel,
        Steps,
        Message,
        Dialog,
        Tag,
        SelectButton,
        InputNumber,
    },
    data() {
        return {
            opcionesPago: [
        { label: 'Efectivo', value: 'efectivo' },
        { label: 'QR', value: 'qr' }
      ],
      criterioOptions: [
        { label: 'Nombre', value: 'nombre' },
        { label: 'Descripción', value: 'descripcion' },
        { label: 'Código', value: 'codigo' }
      ],
      isDialogVisible: false,
      tipoComprobanteOptions: [
      { name: 'FACTURA', code: 'FACTURA' },
        { name: 'RECIBO', code: 'RESIVO' }
      ],
      opcionPago: 'efectivo',
      tipoVenta: 'contado',
      tipocompro: 'Factura',

      mostrarSpinner: false,
      selectedAlmacen: 1,
      idrol: null,
      step: 1,
      modal2: false,
      modal: false,
      zIndexBase: 1050,
            //qr
            alias: '',
            montoQR: 0,
            qrImage: '',
            aliasverificacion: '',
            estadoTransaccion: null,
            currency: 'BOB', // Define tu moneda
            resivo: "",
            clienteDeudas: 0,
            arrayCuotas: [],
            arraySeleccionado: [],
            cuotaSeleccionada: null,
            modalCuotas: 0,

            tipo_pago: "",
            criterioKit: "nombre",
            buscarKit: "",

            mensajesKit: [],
            arrayArticulosKit: [],
            datosFormularioKit: [],
            modalDetalleKit: 0,
            arrayKit: [],

            arrayPreciosEspeciales: [],
            modalDetalle: 0,
            datosFormularioPE: [],
            arrayArticulosPE: [],

            arrayPromocion: [],
            unidadPaquete: 1,
            tipoVentaOptions: [
                { label: 'Por unidad', value: 0 },
                { label: 'Por paquete', value: 1 }
            ],

            monedaVenta: [],
            permitirDevolucion: "",
            saldosNegativos: 1,
            venta_id: 0,
            idcliente: 0,
            usuarioAutenticado: null,
            puntoVentaAutenticado: null,
            idsucursalAutenticado: null,
            cliente: "",
            email: "",
            nombreCliente: "",
            nombreClienteEditable: false,
            documento: "",
            tipo_documento: "1",
            complemento_id: "",
            descuentoAdicional: 0.0,
            descuentoGiftCard: "",
            tipo_comprobante: "FACTURA",
            serie_comprobante: "",
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
            arrayFactura: [],
            listado: 1,
            tituloModal: "",
            tipoAccion: 0,
            errorVenta: 0,
            errorMostrarMsjVenta: [],
            pagination: {
                total: 0,
                current_page: 1,
                last_page: 0, // Asegúrate de actualizar este valor al obtener datos
            },
            offset: 3,
            criterio: "",
            buscar: "",
            criterioA: "nombre",
            buscarA: "",
            arrayArticulo: [],
            arraySeleccionado: [],

            idarticulo: 0,
            codigo: "",
            articulo: "",
            medida: "",
            codigoClasificador: "",
            codigoProductoSin: "",
            precio: 0,
            unidad_envase: 0,
            cantidad: 1,
            paquni: "",
            precioBloqueado: false,
            descuento: 0,
            descuentoProducto: 0,
            sTotal: 0,
            stock: 0,
            valorMaximoDescuento: "",
            mostrarDireccion: true,

            casosEspeciales: false,
            mostrarCampoCorreo: false,
            leyendaAl: "",
            codigoExcepcion: 0,
            mostrarSpinner: false,
            primer_precio_cuota: 0,
            numeroTarjeta: null,
            metodoPago: "",
            criterioVenta: "ci",
            //almacenes
            arrayAlmacenes: [],
            almacenSeleccionado: null,
            almacenPredeterminadoId: null,
            idAlmacen: 1,
            //-----PRECIOS- AUMENTE 3/OCTUBRE--------
            precioseleccionado: "",
            //precio : '',
            arrayPrecios: [],
            nombre_precio: "",
            precio_uno: "",
            precio_dos: "",
            precio_tres: "",
            precio_cuatro: "",
            //-----MODAL 2---

            tituloModal2: "",
            tipoAccion2: "",

            modal3: 0,
            tituloModal3: "",
            tipoAccion3: "",

            recibido: 0,
            efectivo: 0,
            cambio: 0,
            faltante: 0,
            cantidadClientes: 0,
            idtipo_pago: "",
            idtipo_venta: 1,
            tiempo_diaz: "",
            numero_cuotas: "",
            cuotas: [], //---para almacenar las fechas
            estadocrevent: "activo",
            primera_cuota: "",
            habilitarPrimeraCuota: false,
            tipoPago: "EFECTIVO",
            mostrarElementos: true,
            mostrarCUFD: true,
            idPago: '',
            tiposPago: {
                EFECTIVO: 1,
                TARJETA: 2,
                QR: 7,
            },
        };
    },

    watch: {
        codigo(newValue) {
            if (newValue) {
                this.buscarArticulo();
            }
        },
        documento(newDocumento) {
            this.mostrarCampoCorreo =
                newDocumento === "99002" || newDocumento === "99003";
        },
    },
    computed: {
        calcularStockDisponible() {
            return this.unidadPaquete == 1
                ? this.arraySeleccionado.saldo_stock - this.cantidad
                : this.arraySeleccionado.saldo_stock / this.arraySeleccionado.unidad_envase - this.cantidad;
        },

        resultadoMultiplicacion() {
            if (this.arraySeleccionado) {
                return this.precioseleccionado * this.unidadPaquete * this.cantidad;
            }
        },

        totalCantidades() {
            return this.arrayArticulosKit.reduce((total, articulo) => {
                return total + parseInt(articulo.cantidad);
            }, 0);
        },

        isActived: function () {
            return this.pagination.current_page;
        },

        //Calcula los elementos de la paginación
        pagesNumber() {
            let from = this.pagination.current_page - 2;
            if (from < 1) {
                from = 1;
            }
            let to = from + 4;
            if (to >= this.pagination.last_page) {
                to = this.pagination.last_page;
            }
            let pagesArray = [];
            for (let page = from; page <= to; page++) {
                pagesArray.push(page);
            }
            return pagesArray;
        },

        calcularTotal() {
            var resultado = 0.0;
            for (var i = 0; i < this.arrayDetalle.length; i++) {
                resultado +=
                    this.arrayDetalle[i].precioseleccionado *
                    this.arrayDetalle[i].cantidad -
                    (this.arrayDetalle[i].precioseleccionado *
                        this.arrayDetalle[i].cantidad *
                        this.arrayDetalle[i].descuento) /
                    100;
            }
            resultado -= this.descuentoAdicional;
            //resultado -= this.descuentoGiftCard;
            return resultado;
        },


        badgeSeverity() {
            if (this.estadoTransaccion && this.estadoTransaccion.objeto.estadoActual === 'PENDIENTE') {
                return 'danger'; // Rojo para estado PENDIENTE
            } else if (this.estadoTransaccion && this.estadoTransaccion.objeto.estadoActual === 'PAGADO') {
                return 'success'; // Verde para estado PAGADO
            } else {
                return 'info'; // Otros estados
            }
        }
    },

    methods: {
        handleKeyPress(event) {
            // Detectar Shift + R
            if (event.shiftKey && event.key === "B") {
                this.abrirModal();
            }
        },

        generarCuotas() {
            this.cuotas = [];
            const fechaHoy = new Date();

            const montoEntero = Math.floor(this.calcularTotal / this.numero_cuotas);
            const montoDecimal = (this.calcularTotal - montoEntero * (this.numero_cuotas - 1)).toFixed(2);

            let fechaInicioPago;
            let saldoRestante;
            let estadoCuota;

            if (this.primera_cuota) {
                const primerPago = Number(this.primer_precio_cuota) || 0;
                fechaInicioPago = fechaHoy;
                saldoRestante = (this.calcularTotal - primerPago).toFixed(2);
                estadoCuota = 'Pagado';

                const primeraCuota = {
                    fecha_pago: `${fechaHoy.getFullYear()}-${fechaHoy.getMonth() + 1}-${fechaHoy.getDate()} ${fechaHoy.toLocaleTimeString()}`,
                    precio_cuota: primerPago.toFixed(2),
                    totalCancelado: primerPago.toFixed(2),
                    saldo_restante: saldoRestante,
                    fecha_cancelado: `${fechaHoy.getFullYear()}-${fechaHoy.getMonth() + 1}-${fechaHoy.getDate()} ${fechaHoy.toLocaleTimeString()}`,
                    estado: 'Pagado',
                };

                this.cuotas.push(primeraCuota);

                const montoRestante = this.calcularTotal - primerPago;
                const montoEnteroRestante = Math.floor(montoRestante / (this.numero_cuotas - 1));
                const montoDecimalRestante = (montoRestante - montoEnteroRestante * (this.numero_cuotas - 2)).toFixed(2);

                saldoRestante = montoRestante;
                fechaInicioPago = new Date(fechaHoy.getTime() + this.tiempo_diaz * 24 * 60 * 60 * 1000);
                estadoCuota = 'Pendiente';

                for (let i = 1; i < this.numero_cuotas; i++) {
                    const fechaPago = new Date(fechaInicioPago.getTime() + (i - 1) * this.tiempo_diaz * 24 * 60 * 60 * 1000);
                    const dia = fechaPago.getDate();
                    const mes = fechaPago.getMonth() + 1;
                    const año = fechaPago.getFullYear();

                    const cuota = {
                        fecha_pago: `${año}-${mes}-${dia} ${fechaPago.toLocaleTimeString()}`,
                        precio_cuota: i === this.numero_cuotas - 1 ? parseFloat(montoDecimalRestante).toFixed(2) : montoEnteroRestante,
                        totalCancelado: 0,
                        saldo_restante: saldoRestante,
                        fecha_cancelado: null,
                        estado: 'Pendiente',
                    };

                    saldoRestante -= cuota.precio_cuota;
                    saldoRestante = saldoRestante.toFixed(2);

                    this.cuotas.push(cuota);
                }
            } else {
                fechaInicioPago = new Date(fechaHoy.getTime() + this.tiempo_diaz * 24 * 60 * 60 * 1000);
                saldoRestante = this.calcularTotal;
                estadoCuota = 'Pendiente';

                for (let i = 0; i < this.numero_cuotas; i++) {
                    const fechaPago = new Date(fechaInicioPago.getTime() + i * this.tiempo_diaz * 24 * 60 * 60 * 1000);
                    const dia = fechaPago.getDate();
                    const mes = fechaPago.getMonth() + 1;
                    const año = fechaPago.getFullYear();

                    const cuota = {
                        fecha_pago: `${año}-${mes}-${dia} ${fechaPago.toLocaleTimeString()}`,
                        precio_cuota: i === this.numero_cuotas - 1 ? parseFloat(montoDecimal).toFixed(2) : montoEntero,
                        totalCancelado: 0,
                        saldo_restante: saldoRestante,
                        fecha_cancelado: null,
                        estado: 'Pendiente',
                    };

                    saldoRestante -= cuota.precio_cuota;
                    saldoRestante = saldoRestante.toFixed(2);

                    this.cuotas.push(cuota);
                }
            }
        },
        seleccionarTipoVenta(tipo) {
            this.tipoVenta = 'contado';
            this.idtipo_venta = tipo === 'contado' ? 1 : 2;
            this.opcionPago = 'efectivo'; // Reinicia la opción de pago al cambiar el tipo de venta
        },
        buscarVenta() {
            this.listarVentaF(1, this.buscar);
        },

        validarYAvanzar() {
            const errores = [];

            if (this.step === 2) {
                if (!this.idAlmacen) errores.push('Seleccione un almacén');
            }

            if (errores.length > 0) {
                const mensaje = errores.join('\n');
                swal('Campos incompletos', mensaje, 'warning');
            } else {
                this.nextStep();
            }
        },

        cerrarModal2() {
            this.modal2 = false;
        },
        nextStep() {
            if (this.step < 3) {
                this.step++;
            }
        },
        prevStep() {
            if (this.step > 1) {
                this.step--;
            }
        },

        actualizarFechaHora() {
            const now = new Date();
            this.alias = now.toLocaleString();
        },
        verificarEstado() {
            axios.post('/qr/verificarestado', {
                alias: this.aliasverificacion,
            })
                .then(response => {
                    this.estadoTransaccion = response.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },

        generarQr() {
            this.aliasverificacion = this.alias;
            axios.post('/qr/generarqr', {
                alias: this.alias,
                monto: this.calcularTotal
            })
                .then(response => {
                    const imagenBase64 = response.data.objeto.imagenQr;
                    this.qrImage = `data:image/png;base64,${imagenBase64}`;
                })
                .catch(error => {
                    console.error(error);
                });

            this.alias = '';
            this.montoQR = 0;
        },


        calcularPrecioUnitario(articulo) {
            // Lógica para calcular el precio unitario según el rango total de cantidades
            if (
                this.totalCantidades >= this.datosFormularioPE.rango_inicio_r1 &&
                this.totalCantidades <= this.datosFormularioPE.rango_final_r1
            ) {
                return this.datosFormularioPE.precio_r1;
            } else if (
                this.totalCantidades >= this.datosFormularioPE.rango_inicio_r2 &&
                this.totalCantidades <= this.datosFormularioPE.rango_final_r2
            ) {
                return this.datosFormularioPE.precio_r2;
            } else if (
                this.totalCantidades >= this.datosFormularioPE.rango_inicio_r3 &&
                this.totalCantidades <= this.datosFormularioPE.rango_final_r3
            ) {
                return this.datosFormularioPE.precio_r3;
            } else {
                // Precio predeterminado si no está en ningún rango
                return articulo.precio_costo_unid;
            }
        },
        getClassByCantidad(total) {
            if (
                total >= this.datosFormularioPE.rango_inicio_r1 &&
                total <= this.datosFormularioPE.rango_final_r1
            ) {
                return "rango-1"; // clase para el rango 1
            } else if (
                total >= this.datosFormularioPE.rango_inicio_r2 &&
                total <= this.datosFormularioPE.rango_final_r2
            ) {
                return "rango-2"; // clase para el rango 2
            } else if (
                total >= this.datosFormularioPE.rango_inicio_r3 &&
                total <= this.datosFormularioPE.rango_final_r3
            ) {
                return "rango-3"; // clase para el rango 3
            } else {
                return ""; // clase por defecto si no se cumple ningún rango
            }
        },
        abrirTipoVenta() {
            if (this.idtipo_venta == 1) {
                this.modal2 = true;
                this.cliente = this.nombreCliente;
                this.tipoAccion2 = 1;
                this.scrollToTop();
            }
        },

        seleccionarTipoPago(tipo) {
            console.log("TIPO PAGO ", tipo)
            this.tipoPago = tipo;
            this.tituloModal2 = `TIPO DE PAGO : ${tipo}`;
            this.idtipo_pago = this.tiposPago[tipo];
        },

        agregarKit(kit) {
            if (new Date(kit.fecha_final) < new Date()) {
                swal({
                    type: "error",
                    title: "Error...",
                    text: "Este kit ha expirado!",
                });
                return;
            }
            //   this.GetValidateKit(kit['id'])
            this.GetValidateKit(kit["id"])
                .then(() => {
                    if (this.mensajesKit.length == 0) {
                        const totalKit = this.arrayArticulosKit.reduce(
                            (total, producto) => {
                                return total + producto.cantidad * producto.precio_costo_unid;
                            },
                            0
                        );
                        this.arrayArticulosKit.forEach((producto) => {
                            producto.porcentaje =
                                ((producto.cantidad * producto.precio_costo_unid) / totalKit) *
                                100;
                        });

                        this.arrayArticulosKit.forEach((producto) => {
                            producto.nuevo_precio = (kit.precio * producto.porcentaje) / 100;
                        });
                        console.log("Estos son los articulos: ", this.arrayArticulosKit);
                        this.arrayArticulosKit.forEach((articulo) => {
                            this.arrayDetalle.push({
                                idkit: kit["id"],
                                idarticulo: articulo.id,
                                articulo: articulo.nombre,
                                medida: "KIT",
                                unidad_envase: articulo.unidad_envase,
                                cantidad: articulo.cantidad,
                                cantidad_paquetes: articulo.unidad_envase * articulo.cantidad,
                                precio: articulo.nuevo_precio,
                                descuento: 0,
                                stock: articulo.stock,
                                precioseleccionado: articulo.precio_costo_unid,
                            });
                            let actividadEconomica = 461021;

                            this.arrayProductos.push({
                                actividadEconomica: actividadEconomica,
                                codigoProductoSin: articulo.id,
                                codigoProducto: articulo.codigo,
                                descripcion: articulo.nombre,
                                cantidad: articulo.cantidad,
                                unidadMedida: 25,
                                precioUnitario: parseFloat(
                                    articulo.precio_costo_unid * this.monedaVenta[0]
                                ).toFixed(2),
                                montoDescuento: (
                                    articulo.precio_costo_unid *
                                    articulo.cantidad *
                                    this.monedaVenta[0] -
                                    articulo.nuevo_precio * this.monedaVenta[0]
                                ).toFixed(2),
                                subTotal: parseFloat(
                                    articulo.nuevo_precio * this.monedaVenta[0]
                                ).toFixed(2),
                                numeroSerie: null,
                                numeroImei: null,
                            });
                            this.cerrarModal();
                        });

                    } else {
                        swal({
                            type: "error",
                            title: "Stock insuficiente",
                            text: this.mensajesKit.join("\n\n"),
                        });
                    }
                })
                .catch((error) => {
                    // Maneja el error aquí
                    console.error(error);
                });
        },

        agregarPE(kit) {
            console.log("esto:", kit);
            kit["articulos"] = this.arrayArticulosKit;
            kit["precio"] = kit["precio"] / parseFloat(this.monedaVenta[0]);
            axios.put("/ofertasespeciales/actualizar", kit);

            this.modalDetalle = 0;
            if (new Date(kit.fecha_final) < new Date()) {
                swal({
                    type: "error",
                    title: "Error...",
                    text: "Este kit ha expirado!",
                });
                return;
            }
            console.log("datos formulario agregar PE", kit);
            //   this.GetValidateKit(kit['id'])
            this.GetValidateKit(kit["id"])
                .then(() => {
                    if (this.mensajesKit.length == 0) {
                        const totalKit = this.arrayArticulosKit.reduce(
                            (total, producto) => {
                                return total + producto.cantidad * producto.precio_costo_unid;
                            },
                            0
                        );
                        this.arrayArticulosKit.forEach((producto) => {
                            producto.porcentaje =
                                ((producto.cantidad * producto.precio_costo_unid) / totalKit) *
                                100;
                        });
                        console.log("precio especial ", this.arrayArticulosKit);
                        this.arrayArticulosKit.forEach((producto) => {
                            producto.nuevo_precio =
                                (this.calcularPrecioUnitario(kit) * producto.porcentaje) / 100;
                        });
                        console.log("Estos son los articulos: ", this.arrayArticulosKit);
                        this.arrayArticulosKit.forEach((articulo) => {
                            this.arrayDetalle.push({
                                idkit: kit["id"],
                                idarticulo: articulo.id,
                                articulo: articulo.nombre,
                                medida: "KIT",
                                unidad_envase: articulo.unidad_envase,
                                cantidad: articulo.cantidad,
                                cantidad_paquetes: articulo.unidad_envase * articulo.cantidad,
                                precio: articulo.nuevo_precio,
                                descuento: 0,
                                stock: articulo.stock,
                                precioseleccionado: this.calcularPrecioUnitario(articulo),
                            });
                            let actividadEconomica = 461021;

                            this.arrayProductos.push({
                                actividadEconomica: actividadEconomica,
                                codigoProductoSin: articulo.id,
                                codigoProducto: articulo.codigo,
                                descripcion: articulo.nombre,
                                cantidad: articulo.cantidad,
                                unidadMedida: 25,
                                precioUnitario: parseFloat(
                                    this.calcularPrecioUnitario(articulo) * this.monedaVenta[0]
                                ).toFixed(2),
                                montoDescuento: (
                                    articulo.precio_costo_unid *
                                    articulo.cantidad *
                                    this.monedaVenta[0] -
                                    articulo.nuevo_precio * this.monedaVenta[0]
                                ).toFixed(2),
                                subTotal: parseFloat(
                                    articulo.nuevo_precio * this.monedaVenta[0]
                                ).toFixed(2),
                                numeroSerie: null,
                                numeroImei: null,
                            });
                            this.cerrarModal();
                        });
                    } else {
                        swal({
                            type: "error",
                            title: "Stock insuficiente",
                            text: this.mensajesKit.join("\n\n"),
                        });
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
                id: data["id"],
                nombre: data["nombre"],
                porcentaje: data["porcentaje"],
                codigo: data["codigo"],

                fecha_final: new Date(data["fecha_final"]).toISOString().split("T")[0],
                tipo_promocion: data["tipo_promocion"],
                estado: data["estado"],
                precio: data["precio"],
            };
            this.obtenerDatosKit(data["id"]);
        },

        abrirModalDetalles(data) {
            this.arrayArticulosSeleccionados = [];

            this.modalDetalle = 1;
            this.datosFormularioPE = {
                id: data["id"],
                nombre: data["nombre"],
                precio_r1: data["precio_r1"],
                rango_inicio_r1: data["rango_inicio_r1"],
                rango_final_r1: data["rango_final_r1"],
                precio_r2: data["precio_r2"],
                rango_inicio_r2: data["rango_inicio_r2"],
                rango_final_r2: data["rango_final_r2"],
                precio_r3: data["precio_r3"],
                rango_inicio_r3: data["rango_inicio_r3"],
                rango_final_r3: data["rango_final_r3"],

                fecha_final: new Date(data["fecha_final"]).toISOString().split("T")[0],
                tipo_promocion: data["tipo_promocion"],
                estado: data["estado"],
            };
            this.obtenerDatosKit(data["id"]), console.log(this.datosFormularioPE);
        },

        obtenerDatosKit(idPromocion) {
            return axios
                .get("/ofertas/id", {
                    params: {
                        idPromocion: idPromocion,
                    },
                })
                .then((response) => {
                    const datos = response.data.articulosPorPromocion;
                    this.arrayArticulosKit = datos.map((item) => ({
                        ...item.articulo,
                        cantidad: item.cantidad,
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
                return "#ff0000";
            }
            switch (estado) {
                case "Activo":
                    return "#5ebf5f";
                case "Inactivo":
                    return "#d76868";
                case "Agotado":
                    return "#ce4444";
                default:
                    return "#f9dda6";
            }
        },



        listarOfertaEspecial(page, buscar, criterio) {
            let me = this;
            let url = "/ofertasespeciales";

            axios
                .get(url, {
                    params: {
                        page: page,
                        buscar: buscar,
                        criterio: criterio,
                    },
                })
                .then(function (response) {
                    let respuesta = response.data;
                    me.arrayPreciosEspeciales = response.data.ofertas.data;
                    me.pagination = respuesta.pagination;
                })
                .catch(function (error) {
                    console.log(error);
                });
        },

        scrollToSection() {
            $("html, body").animate(
                {
                    scrollTop: $("#seccionObjetivo").offset().top,
                },
                50
            );
        },
        scrollToTop() {
            $("html, body").animate(
                {
                    scrollTop: 0,
                },
                50
            );
        },
        calcularPrecioConDescuento(precioOriginal, porcentajeDescuento) {
            const descuento = this.precioseleccionado * (this.descuentoProducto / 100);
            const precioConDescuento = this.precioseleccionado - descuento;
            const precioFinal = precioConDescuento * this.unidadPaquete * this.cantidad;
            console.log("precio: ,", precioFinal)
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
            let detalle = this.arrayDetalle[index];
            let producto = this.arrayProductos[index];

            producto.cantidad = detalle.cantidad;
            producto.precioUnitario =  detalle.precioseleccionado;
            producto.subTotal = detalle.cantidad * producto.precioUnitario;

            console.log("Se actualizo el arrayFactura: " + producto);

            if (this.arrayDetalle[index] && typeof this.arrayDetalle[index].precioseleccionado !== 'undefined' && typeof this.arrayDetalle[index].cantidad !== 'undefined') {

                this.arrayDetalle[index].total = (this.arrayDetalle[index].precioseleccionado * this.arrayDetalle[index].cantidad).toFixed(2);

                const productoIndex = this.arrayProductos.findIndex(producto => producto.id === this.arrayDetalle[index].id);
                if (productoIndex !== -1) {
                    this.arrayProductos[productoIndex].precio = parseFloat(this.arrayDetalle[index].precioseleccionado).toFixed(2);
                }
                console.log(this.arrayProductos);
                console.log("Se actualizo el arrayDetalle: ");
                console.log(this.arrayDetalle);
                this.calcularTotal();
            } else {
                console.error('Datos inválidos en actualizarDetalle para el índice:', index);
            }
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

        async verificarComunicacion() {
            try {
            const response = await axios.post('/venta/verificarComunicacion');
            if (response.data.RespuestaComunicacion.transaccion === true) {
                document.getElementById("comunicacionSiat").innerHTML = response.data.RespuestaComunicacion.mensajesList.descripcion;
                document.getElementById("comunicacionSiat").className = "badge bg-success";
            } else {
                document.getElementById("comunicacionSiat").innerHTML = "Desconectado";
                document.getElementById("comunicacionSiat").className = "badge bg-secondary";
            }
            } catch (error) {
            console.log(error);
            }
        },

        async cuis() {
            try {
            const response = await axios.post('/venta/cuis');
            if (response.data.RespuestaCuis.transaccion === false) {
                document.getElementById("cuis").innerHTML = "CUIS: " + response.data.RespuestaCuis.codigo;
                document.getElementById("cuis").className = "badge bg-primary";
            } else {
                document.getElementById("cuis").innerHTML = "CUIS: Inexistente";
                document.getElementById("cuis").className = "badge bg-secondary";
            }
            } catch (error) {
            console.log(error);
            }
        },
        async cufd() {
            try {
            const response = await axios.post('/venta/cufd');
            console.log("Respuesta Cufd: " + response.data);
            if (response.data.transaccion != false) {
                document.getElementById("cufd").innerHTML = "CUFD vigente: " + response.data.fechaVigencia.substring(0, 16);
                document.getElementById("direccion").innerHTML = response.data.direccion;
                document.getElementById("cufdValor").innerHTML = response.data.codigo;
                document.getElementById("cufd").className = "badge bg-info";
            } else {
                document.getElementById("cufd").innerHTML = "No existe CUFD vigente";
                document.getElementById("cufd").className = "badge bg-secondary";
            }
            } catch (error) {
            console.log(error);
            }
        },

        async ejecutarSecuencial() {
            try {
            await this.verificarComunicacion();
            await this.cuis();
            await this.cufd();
            } catch (error) {
            console.log("Error en la ejecución secuencial:", error);
            }
        },

        validarDescuentoGiftCard() {
            if (this.descuentoGiftCard >= this.calcularTotal) {
                this.descuentoGiftCard = 0;
                alert("El descuento Gift Card no puede ser mayor o igual al total.");
            }
        },
        buscarPromocion(idArticulo) {
            // Supongamos que el ID del artículo es 1, ajusta según tus necesidades

            axios
                .get(`/promocion/id?idArticulo=${idArticulo}`)
                .then((response) => {
                    this.arrayPromocion = response.data.promocion;
                })
                .catch((error) => {
                    // Maneja los errores aquí
                    console.error("Error:", error);
                });
        },

        async obtenerDatosUsuario() {
            try {
                const response = await axios.get('/venta');
                this.usuarioAutenticado = response.data.usuario.usuario;
                this.usuario_autenticado = this.usuarioAutenticado;
                this.idrol = response.data.usuario.idrol;
                this.idsucursalAutenticado = response.data.usuario.idsucursal;
                console.log("Obtener Datos Usuario: " + this.idsucursalAutenticado);
                this.puntoVentaAutenticado = response.data.codigoPuntoVenta;
            } catch (error) {
                console.error(error);
            }
        },

        async obtenerDatosSesionYComprobante() {
            try {
                const idsucursal = this.idsucursalAutenticado;
                console.log("El idsucursal es: " + idsucursal);
                const response = await axios.get('/obtener-ultimo-comprobante', {
                    params: {
                        idsucursal: idsucursal
                    }
                });
                const lastComprobante = response.data.last_comprobante;
                this.last_comprobante = lastComprobante;
                this.last_comprobante++;
                this.num_comprob = this.last_comprobante.toString().padStart(5, "0");
                console.log("El ultimo comprobante es: " + this.last_comprobante);
                //this.nextNumber(lastComprobante);
            } catch (error) {
                console.error('Error al obtener el último comprobante:', error);
            }
        },

        async ejecutarFlujoCompleto() {
            await this.obtenerDatosUsuario();
            await this.obtenerDatosSesionYComprobante();
        },

        nextNumber() {
            if (!this.num_comprob || this.num_comprob === "") {
                this.last_comprobante++;
                // Completa con ceros a la izquierda hasta alcanzar 5 dígitos
                this.num_comprob = this.last_comprobante.toString().padStart(5, "0");
            }
        },

        cambiarTipoventa(tipoventa, buscar, criterio) {
            this.tipocompro = tipoventa;
            console.log("estos es:", this.tipocompro);
            this.listarventaReporte(1,  buscar, criterio);
        },

        listarventaReporte(page, buscar, criterio) {
            if (this.tipocompro === "Factura") {
                this.listarVentaF(page, buscar, criterio);
            } else if (this.tipocompro === "Recibo") {
                this.listarVentaR(page, buscar, criterio);
            } else {
                this.listarVenta(page, buscar, criterio);
            }
        },

        listarVenta(page, buscar, criterio) {
            let me = this;
            var url =
                "/venta?page=" + page + "&buscar=" + buscar + "&criterio=" + criterio;
            axios
                .get(url)
                .then(function (response) {
                    var respuesta = response.data;
                    console.log(respuesta);
                    me.arrayVenta = respuesta.ventas.data;
                    me.pagination = respuesta.pagination;
                })
                .catch(function (error) {
                    console.log(error);
                });
        },

        listarVentaF(page, buscar, criterio) {
            let me = this;
            var url = '/ventaReporteFactura?page=' + page + '&buscar=' + buscar + '&criterio=' + criterio;
            axios.get(url).then(function (response) {
                var respuesta = response.data;
                me.arrayVenta = respuesta.ventas.data;
                me.pagination = respuesta.pagination;
                console.log('lista: ', me.arrayVenta);

            })
                .catch(function (error) {
                    console.log(error);
                });
        },

        listarVentaR(page, buscar, criterio) {
            let me = this;
            var url = '/ventaReporte?page=' + page + '&buscar=' + buscar + '&criterio=' + criterio;
            axios.get(url).then(function (response) {
                var respuesta = response.data;
                me.arrayVenta = respuesta.ventas.data;
                me.pagination = respuesta.pagination;
                console.log('lista: ', me.arrayVenta);

            })
                .catch(function (error) {
                    console.log(error);
                });
        },

        resetBuscarCriterio() {
            this.buscar = '';
            this.criterio = '';
            this.tipocompro = '';
            this.arrayVenta = [];
        },
        

        selectCliente(numero) {
            let me = this;
            var url = "/cliente/selectClientePorNumero?numero=" + numero;
            axios
                .get(url)
                .then(function (response) {
                    let respuesta = response.data;
                    q: numero;
                    me.arrayCliente = respuesta.clientes;
                    console.log(me.arrayCliente);
                    me.cantidadClientes = me.arrayCliente.length;
                })
                .catch(function (error) {
                    console.log(error);
                });
        },
        selectClienteNombre(nombre) {
            console.log("nombre ", nombre)
            let me = this;
            var url = "/cliente/selectCliente?filtro=" + nombre;
            axios
                .get(url)
                .then(function (response) {
                    let respuesta = response.data;
                    q: nombre;
                    me.arrayCliente = respuesta.clientes;
                    console.log(me.arrayCliente);
                    me.cantidadClientes = me.arrayCliente.length;
                })
                .catch(function (error) {
                    console.log(error);
                });
        },

        getDatosCliente(val1) {
            let me = this;
            if (val1 !== null) {
                me.loading = true;
                me.idcliente = val1.id;
                //console.log(val1);
                this.email = val1.email;
                this.nombreCliente = val1.nombre;
                this.documento = val1.num_documento;
                this.tipo_documento = val1.tipo_documento;
                this.complemento_id = val1.complemento_id;
                this.clienteDeudas = val1.cantidad_creditos;
            }
            else {
                //console.log(val1);
                this.email = '';
                this.nombreCliente = '';
                this.documento = '';
                this.tipo_documento = '';
                this.complemento_id = '';
                this.clienteDeudas = '';
            }
        },
        getDatosCliente2(val1) {
            let me = this;
            me.loading = true;
            if (val1 !== null) {
                me.loading = true;
                me.idcliente = val1.id;
                //console.log(val1);
                this.email = val1.email;
                this.nombreCliente = val1.nombre;
                this.documento = val1.num_documento;
                this.tipo_documento = val1.tipo_documento;
                this.complemento_id = val1.complemento_id;
                this.clienteDeudas = val1.cantidad_creditos;
            }
            else {
                //console.log(val1);
                this.email = '';
                this.nombreCliente = '';
                this.documento = '';
                this.tipo_documento = '';
                this.complemento_id = '';
                this.clienteDeudas = '';
            }
        },
        buscarArticulo() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                let me = this;
                var url = "/articulo/buscarArticuloVenta?filtro=" + me.codigo + "&idalmacen=" + this.idAlmacen;

                axios
                    .get(url)
                    .then(function (response) {
                        let respuesta = response.data;
                        me.arraySeleccionado = respuesta.articulos[0];
                        me.precioseleccionado = me.arraySeleccionado.precio_uno;
                        console.log(me.arraySeleccionado);
                        me.agregarDetalle();
                    })
                    .catch(function (error) {
                        console.log(error);
                    });
            }, 200); // 100 ms de retraso
        },
        pdfVenta(id) {
            window.open("/venta/pdf/" + id, "_blank");
        },
        onPageChange(event) {
            let page = event.page + 1; // PrimeVue pages are 0-based, while your logic uses 1-based
            this.cambiarPagina(page, this.buscar, this.criterio);
        },
        cambiarPagina(page, buscar, criterio) {
            this.pagination.current_page = page;
            if (this.tipocompro === "Factura") {
                this.listarVentaF(page, buscar, criterio);
            } else if (this.tipocompro === "Recibo") {
                this.listarVentaR(page, buscar, criterio);
            } else {
                this.listarVenta(page, buscar, criterio);
            }
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
        eliminarDetalle(id) {
            const index = this.arrayDetalle.findIndex(item => item.id === id);
            if (index !== -1) {
                this.arrayDetalle.splice(index, 1);
                this.arrayProductos.splice(index, 1);
                this.calcularTotal();
            }
        },
        eliminarKit(id) {
            const indicesEliminar = [];
            for (let i = 0; i < this.arrayDetalle.length; i++) {
                if (this.arrayDetalle[i].idkit === id) {
                    indicesEliminar.push(i);
                }
            }
            indicesEliminar.forEach((index) => {
                this.arrayProductos.splice(index, 1);
            });
            for (let i = indicesEliminar.length - 1; i >= 0; i--) {
                this.arrayDetalle.splice(indicesEliminar[i], 1);
            }
        },

        verificarFactura(cuf, numeroFactura){
            var url = 'https://siat.impuestos.gob.bo/consulta/QR?nit=8033811015&cuf='+cuf+'&numero='+numeroFactura+'&t=2';
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

        agregarDetalle() {
            console.log("Entro a agregarDetalle");
            if (this.encuentra(this.arraySeleccionado.id)) {
                swal({
                    type: "error",
                    title: "Error...",
                    text: "Este Artículo ya se encuentra agregado!",
                });
                return;
            }

            if (this.saldosNegativos === 0 && this.arraySeleccionado.saldo_stock < this.cantidad * this.unidadPaquete) {
                swal({
                    type: "error",
                    title: "Error...",
                    text: "No hay stock disponible!",
                });
                return;
            }

            const precioUnitario = parseFloat(this.precioseleccionado);
            const cantidad = this.cantidad * this.unidadPaquete;
            const descuento = (precioUnitario * cantidad * (this.descuentoProducto / 100)).toFixed(2);
            const total = (precioUnitario * cantidad - descuento).toFixed(2);

            const nuevoDetalle = {
                id: Date.now(),
                idkit: -1,
                idarticulo: this.arraySeleccionado.id,
                articulo: this.arraySeleccionado.nombre,
                medida: this.arraySeleccionado.medida,
                unidad_envase: this.arraySeleccionado.unidad_envase,
                cantidad: cantidad,
                cantidad_paquetes: this.arraySeleccionado.unidad_envase,
                precio: precioUnitario,
                descuento: this.descuentoProducto,
                stock: this.arraySeleccionado.saldo_stock,
                precioseleccionado: precioUnitario,
                total: total
            };

            this.arrayDetalle.push(nuevoDetalle);
            console.log("Para la Venta:", this.arrayDetalle);

            const nuevoProducto = {
                actividadEconomica: this.arraySeleccionado.actividadEconomica,
                codigoProductoSin: this.arraySeleccionado.codigoProductoSin,
                codigoProducto: this.arraySeleccionado.codigo,
                descripcion: this.arraySeleccionado.nombre,
                cantidad: cantidad,
                unidadMedida: this.arraySeleccionado.codigoClasificador,
                precioUnitario: precioUnitario.toFixed(2),
                montoDescuento: descuento,
                subTotal: total,
                numeroSerie: null,
                numeroImei: null,
            };

            this.arrayProductos.push(nuevoProducto);
            console.log("Para la Factura:", this.arrayProductos);

            this.precioBloqueado = true;
            this.arraySeleccionado = [];
            this.cantidad = 1;
            this.unidadPaquete = 1;
            this.codigo = "";
            this.descuentoProducto = 0;

            this.calcularTotal();
        },

        agregarDetalleModal(data) {
            //this.scrollToSection();
            this.codigo = data.codigo;
            console.log("SLECCIONE ESTO:", data);

            this.buscarPromocion(data.id);
            this.precioseleccionado = data.precio_uno;

            this.cerrarModal();
        },
        eliminarSeleccionado() {
            this.codigo = "";
            this.arraySeleccionado = [];
        },
        listarArticulo(buscar, criterio) {
            let me = this;
            var url =
                "/articulo/listarArticuloVenta?buscar=" +
                buscar +
                "&criterio=" +
                criterio +
                "&idAlmacen=" +
                this.idAlmacen;
            axios
                .get(url)
                .then(function (response) {
                    var respuesta = response.data;
                    me.arrayArticulo = respuesta.articulos;
                    console.log("listar articulo", respuesta);
                })
                .catch(function (error) {
                    console.log(error);
                });
        },

        datosConfiguracion() {
            let me = this;
            var url = "/configuracion";

            axios
                .get(url)
                .then(function (response) {
                    let respuesta = response.data;
                    console.log(respuesta);
                    me.saldosNegativos = respuesta.configuracionTrabajo.saldosNegativos;
                    me.permitirDevolucion =
                        respuesta.configuracionTrabajo.permitirDevolucion;
                    me.monedaVenta = [
                        respuesta.configuracionTrabajo.valor_moneda_venta,
                        respuesta.configuracionTrabajo.simbolo_moneda_venta,
                    ];
                })
                .catch(function (error) {
                    console.log(error);
                });
        },

        async selectAlmacen() {
            let me = this;
            let url = "/almacen/listarAlmacen";
            await axios
                .get(url)
                .then(function (response) {
                    let respuesta = response.data;
                    me.arrayAlmacenes = respuesta.almacenes;
                    console.log(me.arrayAlmacenes);
                    me.obtenerAlmacenPredeterminado();
                })
                .catch(function (error) {
                    console.log(error);
                });
        },

        async obtenerAlmacenPredeterminado() {
            try {
                const response = await axios.get('/api/configuracion/almacen-predeterminado');
                this.almacenPredeterminadoId = response.data.almacen_predeterminado_id;
                console.log("El almacen predeterminado es : " + this.almacenPredeterminadoId);

                this.almacenSeleccionado = this.arrayAlmacenes.find(
                    almacen => almacen.id === this.almacenPredeterminadoId
                );
            } catch (error) {
                console.error('Error al obtener el almacén predeterminado:', error);
            }
        },

        getAlmacenProductos(event) {
            this.idAlmacen = event.value;
        },
        validarVenta() {
    let me = this;
    me.errorVenta = 0;
    me.errorMostrarMsjVenta = [];
    let condicion = true;

    // Verificar stock de cada artículo
    me.arrayDetalle.forEach(function (x) {
        if (x.cantidad > x.stock) {
            let art = `${x.articulo}: Stock insuficiente`;
            me.errorMostrarMsjVenta.push(art);
            condicion = false;
        }
    });

    // Verificar si se seleccionó el tipo de comprobante
    if (me.tipo_comprobante == 0) {
        me.errorMostrarMsjVenta.push("Seleccione el Comprobante");
        condicion = false;
    }

    // Verificar si se ingresó el impuesto
    if (!me.impuesto) {
        me.errorMostrarMsjVenta.push("Ingrese el impuesto de compra");
        condicion = false;
    }

    // Verificar si hay detalles en la venta
    if (me.arrayDetalle.length <= 0) {
        me.errorMostrarMsjVenta.push("Ingrese detalles");
        condicion = false;
    }

    // Verificar si hay errores
    if (me.errorMostrarMsjVenta.length > 0) {
        me.errorVenta = 1;

        // Mostrar todos los errores en un solo mensaje de SweetAlert
        swal({
            type: "error",
            title: "Error en la venta",
            text: me.errorMostrarMsjVenta.join("\n"),
        });
    }

    return condicion;
},
aplicarDescuento(idtipopago) {
            this.tipo_comprobante = 'FACTURA';
            var idtipo_pago = idtipopago;

            const descuentoGiftCard = this.descuentoGiftCard;
            const numeroTarjeta = this.numeroTarjeta;

            if (numeroTarjeta && descuentoGiftCard) {
                idtipo_pago = 86;
            } else if (numeroTarjeta && !descuentoGiftCard) {
                idtipo_pago = 10;
            }else if (idtipo_pago == 7) {
                idtipo_pago = 7;
            } else {
                idtipo_pago = descuentoGiftCard ? 35 : 1;
            }

            this.registrarVenta(idtipo_pago);
        },
        aplicarDescuentoRecibo(idtipopago) {
            this.tipo_comprobante = 'RESIVO';
            var idtipo_pago = idtipopago;

            const descuentoGiftCard = this.descuentoGiftCard;
            const numeroTarjeta = this.numeroTarjeta;

            if (numeroTarjeta && descuentoGiftCard) {
                idtipo_pago = 86;
            } else if (numeroTarjeta && !descuentoGiftCard) {
                idtipo_pago = 10;
            }else if (idtipo_pago == 7) {
                idtipo_pago = 7;
            } else {
                idtipo_pago = descuentoGiftCard ? 35 : 1;
            }

            this.registrarVenta(idtipo_pago);
        },
        aplicarCombinacion() {
            const descuentoGiftCard = this.descuentoGiftCard;
            const idtipo_pago = descuentoGiftCard ? 40 : 2;

            this.registrarVenta(idtipo_pago);
        },

        otroMetodo(metodoPago) {
            const idtipo_pago = metodoPago;
            this.registrarVenta(idtipo_pago);
        },
        emitirComprobante() {
            if (!this.tipo_comprobante) {
                alert("Por favor seleccione un tipo de comprobante.");
                return;
            }

            if (this.tipo_comprobante === "RESIVO") {
                this.emitirResivo();
            } else if (this.tipo_comprobante === "FACTURA") {
                this.emitirFactura();
            }
        },
        async emitirResivo(idVentaRecienRegistrada) {
            let me = this;

            let idventa = idVentaRecienRegistrada;
            let numeroResivo = document.getElementById("num_comprobante").value;
            let id_cliente = document.getElementById("idcliente").value;
            let nombreRazonSocial = document.getElementById("nombreCliente").value;
            let numeroDocumento = document.getElementById("documento").value;
            let complemento = document.getElementById("complemento_id").value;
            let tipoDocumentoIdentidad = document.getElementById("tipo_documento")
                .value;
            let montoTotal = (
                this.calcularTotal * parseFloat(this.monedaVenta[0])
            ).toFixed(2);
            let usuario = document.getElementById("usuarioAutenticado").value;

            try {
                const response = await axios.get("/resivo/obtenerLeyendaAleatoria");
                this.leyendaAl = response.data.descripcionLeyenda;
                console.log("El dato de leyenda llegado es: " + this.leyendaAl);
            } catch (error) {
                console.error(error);
                return '"Ley N° 453: Los servicios deben suministrarse en condiciones de inocuidad, calidad y seguridad."';
            }

            var resivo = [];
            resivo.push({
                cabecera: {
                    municipio: "Cochabamba",
                    telefono: "77490451",
                    numeroResivo: numeroResivo,
                    codigoSucursal: 0,
                    direccion: "Av. Ejemplo 123",
                    codigoPuntoVenta: 0,
                    fechaEmision: new Date().toISOString().slice(0, -1),
                    nombreRazonSocial: nombreRazonSocial,
                    codigoTipoDocumentoIdentidad: tipoDocumentoIdentidad,
                    numeroDocumento: numeroDocumento,
                    complemento: complemento,
                    codigoCliente: numeroDocumento,
                    montoTotal: montoTotal,
                    codigoMoneda: 1,
                    tipoCambio: 1,
                    montoTotalMoneda: montoTotal,
                    usuario: usuario,
                    leyenda: this.leyendaAl,
                },
            });
            me.arrayProductos.forEach(function (prod) {
                resivo.push({ detalle: prod });
            });

            var datos = { resivo };

            axios
                .post("/venta/emitirResivo", {
                    resivo: datos,
                    id_cliente: id_cliente,
                    idventa: idventa,
                })
                .then(function (response) {
                    var data = response.data;

                    if (data === "VALIDADA") {
                        swal("RESIVO VALIDADO", "Correctamente", "success");
                        me.arrayProductos = [];
                        me.cerrarModal2();
                        me.cerrarModal3();
                        me.listarVentaF(1, "", "id");
                        me.mostrarSpinner = false;
                    } else {
                        me.arrayProductos = [];
                        me.cerrarModal2();
                        me.cerrarModal3();
                        me.listarVentaF(1, "", "id");
                        me.mostrarSpinner = false;
                        swal("RESIVO VALIDADO", "éxito", "success");
                    }
                })
                .catch(function (error) {
                    me.arrayProductos = [];
                    swal("INTENTE DE NUEVO", "Comunicacion fallida", "error");
                    me.mostrarSpinner = false;
                });
        },

        imprimirResivo(id) {
    swal({
        title: "Selecciona un tamaño para imprimir el recibo",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "CARTA",
        cancelButtonText: "ROLLO",
        reverseButtons: true,
    })
        .then((result) => {
            if (result.value) {
                console.log("Se seleccionó imprimir en CARTA");
                axios
                    .get("/resivo/imprimirCarta/" + id, {
                        responseType: "blob",
                    })
                    .then(function (response) {
                        const url = window.URL.createObjectURL(
                            new Blob([response.data], { type: "application/pdf" })
                        );
                        window.open(url); // Abre el PDF en otra pestaña
                        console.log("Se imprimió el recibo en CARTA correctamente");
                    })
                    .catch(function (error) {
                        console.log(error);
                    });
            } else if (result.dismiss === swal.DismissReason.cancel) {
                console.log("Se seleccionó imprimir en ROLLO");
                axios
                    .get("/resivo/imprimirRollo/" + id, {
                        responseType: "blob",
                    })
                    .then(function (response) {
                        const url = window.URL.createObjectURL(
                            new Blob([response.data], { type: "application/pdf" })
                        );
                        window.open(url); // Abre el PDF en otra pestaña
                        console.log("Se imprimió el recibo en ROLLO correctamente");
                    })
                    .catch(function (error) {
                        console.log(error);
                    });
                    this.opcionPago = 'efectivo';

            }
        })
        .catch((error) => {
            console.error("Error al mostrar el diálogo:", error);
        });
},

        async buscarOCrearCliente() {
            try {
                // Primero, intenta buscar el cliente
                const response = await axios.get(`/api/clientes/existe?documento=${this.documento}`);

                if (response.data.existe) {
                    // Si el cliente existe, usa ese ID y actualiza los datos del cliente
                    this.idcliente = response.data.cliente.id;
                    this.nombreCliente = response.data.cliente.nombre;
                    this.tipo_documento = response.data.cliente.tipo_documento;
                    this.complemento_id = response.data.cliente.complemento_id;
                } else {
                    // Si el cliente no existe, intenta crearlo
                    const nuevoClienteResponse = await axios.post('/cliente/registrar', {
                        'nombre': this.nombreCliente,
                        'num_documento': this.documento,
                        'tipo_documento': '5'
                    });
                    this.idcliente = nuevoClienteResponse.data.id;
                }
            } catch (error) {
                console.error("Error al buscar o crear cliente:", error);
                // Manejar el error adecuadamente
            }
        },

        async registrarVenta(idtipo_pago) {
            if (this.validarVenta()) {
                this.prepararDatosCliente();
                await this.buscarOCrearCliente();
                this.idPago = idtipo_pago;

                if (this.tipo_comprobante === "FACTURA") {
                    await this.obtenerNumeroFactura();
                } else if (this.tipo_comprobante === "RESIVO") {
                    await this.obtenerDatosSesionYComprobante();
                    /*const ultimoNumero = response.data.last_comprobante;
                    this.num_comprob = ultimoNumero + 1;*/
                }

                console.log("El número de comprobante es:" + this.num_comprob);

                const ventaData = this.prepararDatosVenta(idtipo_pago);

                try {
                    this.mostrarSpinner = true;
                    const response = await axios.post("/venta/registrar", ventaData);

                    if (response.data.id > 0) {
                        this.manejarVentaExitosa(response.data.id);
                    } else {
                        this.manejarErrorVenta(response.data);
                    }
                } catch (error) {
                    console.error("Error al registrar venta:", error);
                    this.ejecutarFlujoCompleto();
                } finally {
                    this.mostrarSpinner = false;
                }
            }
        },

        cambiarProducto(index, nuevoProducto) {
            if (index >= 0 && index < this.arrayDetalle.length) {
                this.arrayDetalle[index] = {
                    idarticulo: nuevoProducto.id,
                    articulo: nuevoProducto.nombre,
                    cantidad: 1,
                    precio: nuevoProducto.precio,
                    stock: nuevoProducto.stock,
                    subtotal: nuevoProducto.precio
                };
                this.calcularTotal();
            }
        },



        prepararDatosCliente() {
            if (!this.nombreCliente.trim()) {
                this.nombreCliente = "SIN NOMBRE";
                this.documento = "000000";
            }
        },

        prepararDatosVenta(idtipo_pago) {
            const datosComunes = {
                idcliente: this.idcliente,
                tipo_comprobante: this.tipo_comprobante,
                serie_comprobante: this.serie_comprobante,
                num_comprobante: this.num_comprob,
                impuesto: this.impuesto,
                total: this.calcularTotal,
                idAlmacen: this.idAlmacen,
                idtipo_pago,
                idtipo_venta: this.idtipo_venta,
                data: this.arrayDetalle,
            };

            if (this.tipoVenta === 'credito') {
                const totalCredito = this.primera_cuota
                    ? this.calcularTotal - this.primer_precio_cuota
                    : this.calcularTotal;

                let cuotasActualizadas = [...this.cuotas];
                if (this.primera_cuota) {
                    cuotasActualizadas[0] = {
                        ...cuotasActualizadas[0],
                        totalCancelado: this.primer_precio_cuota,
                        estado: 'Pagado'
                    };
                }
                return {
                    ...datosComunes,
                    idpersona: this.idcliente,
                    numero_cuotasCredito: this.numero_cuotas,
                    tiempo_dias_cuotaCredito: this.tiempo_diaz,
                    totalCredito: this.primera_cuota ? this.calcularTotal - this.cuotas[0].totalCancelado : this.calcularTotal,
                    estadoCredito: "Pendiente",
                    cuotaspago: cuotasActualizadas,
                    primer_precio_cuota: this.primer_precio_cuota,
                    primera_cuota_pagada: this.primera_cuota
                };
            } else if (this.tipo_comprobante === "RESIVO") {
                return { ...datosComunes, resivo: this.resivo };
            } else {
                return { ...datosComunes, idpersona: this.idcliente };
            }
        },

        manejarVentaExitosa(idVenta) {
            this.listado = 1;
            this.obtenerDatosUsuario();
            this.listarVentaF(1, "", "num_comprob");
            this.cerrarModal2();
            this.cerrarModal3();

            if (this.tipo_comprobante === "RESIVO") {
                this.imprimirResivo(idVenta);
                this.arrayProductos = [];
            } else if (this.tipo_comprobante === "FACTURA") {
                this.emitirFactura(idVenta);
            }

            this.reiniciarFormulario();
        },

        async emitirFactura(idVentaRecienRegistrada) {

        let me = this;

        let idventa = idVentaRecienRegistrada;
        //let numeroFactura = document.getElementById("num_comprobante").value;
        let numeroFacturaPrueba = String(this.num_comprob);
        let numeroFactura = numeroFacturaPrueba.padStart(5, '0');
        let cuf = "464646464";
        let cufdValor = document.getElementById("cufdValor");
        console.log("hola aaaa: ", this.cufdValor);
        let numeroTarjeta = this.numeroTarjeta;
        console.log("El numero de tarjeta es: " + numeroTarjeta);
        let cufd = cufdValor.textContent;
        let direccionValor = document.getElementById("direccion");
        let direccion = direccionValor.textContent;
        var tzoffset = (new Date()).getTimezoneOffset() * 60000;
        let fechaEmision = (new Date(Date.now() - tzoffset)).toISOString().slice(0, -1);
        //let id_cliente = document.getElementById("idcliente").value;
        //let nombreRazonSocial = document.getElementById("cliente").value;
        let nombreRazonSocial = this.nombreCliente;
        //let numeroDocumento = document.getElementById("documento").value;
        let numeroDocumento = this.documento;
        //let complemento = document.getElementById("complemento_id").value;
        let complemento = null;
        //let tipoDocumentoIdentidad = document.getElementById("tipo_documento").value;
        let tipoDocumentoIdentidad = 5;
        let montoTotal = (this.calcularTotal.toFixed(2));
        //let descuentoAdicional = document.getElementById("descuentoAdicional").value;
        let descuentoAdicional = this.descuentoAdicional;
        //let usuario = document.getElementById("usuarioAutenticado").value;
        let usuario = this.usuarioAutenticado;
        //let codigoPuntoVenta = document.getElementById("puntoVentaAutenticado").value;
        let codigoPuntoVenta = this.puntoVentaAutenticado;
        //let montoGiftCard = document.getElementById("descuentoGiftCard").value;
        let montoGiftCard = this.descuentoGiftCard;
        let codigoMetodoPago = this.idPago;
        let montoTotalSujetoIva = montoTotal - this.descuentoGiftCard;
        //let correo = document.getElementById("email").value;
        let correo = this.email;


        console.log("El monto de Descuento de Gift Card es: " + this.descuentoGiftCard);
        console.log("El tipo de documento es: " + tipoDocumentoIdentidad);
        console.log("El complemento de documento es: " + complemento);
        console.log("hola monto toal: " + this.calcularTotal.toFixed(2));

        try {
            const response = await axios.get('/factura/obtenerLeyendaAleatoria');
            this.leyendaAl = response.data.descripcionLeyenda;
            console.log("El dato de leyenda llegado es: " + this.leyendaAl);
        } catch (error) {
            console.error(error);
            return '"Ley N° 453: Los servicios deben suministrarse en condiciones de inocuidad, calidad y seguridad."';
        }

        try {
                if (tipoDocumentoIdentidad === 5) {
                    const response = await axios.post('/factura/verificarNit/' + numeroDocumento);
                    if (response.data === 'NIT ACTIVO') {
                        me.codigoExcepcion = 0;
                        //alert("NIT VÁLIDO.");
                    } else {
                        me.codigoExcepcion = 1;
                        //alert("NIT INVÁLIDO.");
                    }
                }else{
                    me.codigoExcepcion = 0;
                }
            } catch (error) {
                console.error(error);
                return 'No se pudo verificar el NIT';
            }

        var factura = [];
        factura.push({
            cabecera: {
                nitEmisor: "8033811015",
                razonSocialEmisor: "MARIBEL QUISPE CHOQUE",
                municipio: "Quillacollo",
                telefono: "77497231",
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
                numeroTarjeta: numeroTarjeta,
                montoTotal: montoTotal,
                montoTotalSujetoIva: montoTotalSujetoIva,
                codigoMoneda: 1,
                tipoCambio: 1,
                montoTotalMoneda: montoTotal,
                montoGiftCard: this.descuentoGiftCard,
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

        axios.post('/venta/emitirFactura', {
            factura: datos,
            id_cliente: this.idcliente,
            idventa: idventa,
            cufd: cufd
        })
            .then(function (response) {
                var data = response.data;
                var mensaje = data.mensaje;
                var idFactura = data.idFactura;
                console.log('Mensaje:', mensaje);
                console.log('ID de la factura:', idFactura);

                if (mensaje === "VALIDADA") {
                    me.visibleDialog = false;
                    me.cambiar_pagina = 0;
                    me.ejecutarFlujoCompleto();
                    //me.obtenerNumeroFactura();
                    
                    swal(
                        'FACTURA VALIDADA',
                        'Correctamente',
                        'success'
                    )
                    //me.imprimirTicket(idVentaRecienRegistrada);
                    //me.imprimirFactura(idFactura, correo);
                    
                    me.listarVentaF(1, "", "num_comprobante");
                    me.imprimirFactura(idFactura, correo);
                    me.arrayProductos = [];
                    me.codigoExcepcion = 0;
                    me.idtipo_pago = '';
                    me.email = '';
                    me.descuentoGiftCard = '';
                    me.numeroTarjeta =  null;
                    me.recibido = '';
                    me.metodoPago = '';
                    me.idcliente = 0;
                    me.cerrarModal2();
                    me.mostrarSpinner = false;
                    me.menu = 49;
                    me.opcionPago = 'efectivo';
                    //me.tipo_comprobante = 'FACTURA';

                } else{
                    me.listarVentaF(1, "", "num_comprobante");
                    me.visibleDialog = false;
                    me.cambiar_pagina = 0;
                    me.arrayProductos = [];
                    me.codigoExcepcion = 0;
                    me.idtipo_pago = '';
                    me.descuentoGiftCard = '';
                    me.numeroTarjeta =  null;
                    me.recibido = '';
                    me.idcliente = 0;
                    me.metodoPago = '';
                    me.last_comprobante = '';
                    me.cerrarModal2();
                    me.mostrarSpinner = false;
                    me.opcionPago = 'efectivo';
                    //me.tipo_comprobante = 'FACTURA';

                    swal(
                        'FACTURA RECHAZADA',
                        data,
                        'warning'
                    );
                    me.eliminarVenta(idVentaRecienRegistrada);
                }
            })
            .catch(function (error) {
                console.error("Este es el error: " + error);
                me.arrayProductos = [];
                me.codigoExcepcion = 0;
                swal(
                    'INTENTE DE NUEVO',
                    'Comunicacion con SIAT fallida',
                    'error');
                me.mostrarSpinner = false;
                me.idtipo_pago = '';
                me.numeroTarjeta =  null;
                me.descuentoGiftCard = '';
                me.recibido = '';
                me.idcliente = 0;
                me.metodoPago = '';
                me.opcionPago = 'efectivo';
                me.eliminarVentaFalloSiat(idVentaRecienRegistrada);
                me.ejecutarFlujoCompleto();
                me.listarVentaF(1, "", "num_comprobante");
            });
        },

        imprimirFactura(id) {
            axios.get('/factura/imprimirRollo/' + id)
                .then(function(response) {
                    const fileURL = response.data.url;
                    const newWindow = window.open(fileURL, '_blank');
                    if (newWindow) {
                        newWindow.focus();
                    } else {
                        console.log("No se pudo abrir una nueva pestaña, asegúrate de que los pop-ups no están bloqueados.");
                    }
                    console.log("Se generó la factura en Rollo correctamente");
                })
                .catch(function(error) {
                    console.log(error);
                });
        },

        eliminarVenta(idVenta) {
            axios.delete('/venta/eliminarVenta/' + idVenta)
                .then(function (response) {
                    console.log('Venta eliminada correctamente:', response);
                })
                .catch(function (error) {
                    console.error('Error al eliminar la venta:', error);
                });
        },

        eliminarVentaFalloSiat(idVenta) {
            axios.delete('/venta/eliminarVentaFalloSiat/' + idVenta)
                .then(function (response) {
                    console.log('Venta eliminada correctamente:', response);
                })
                .catch(function (error) {
                    console.error('Error al eliminar la venta:', error);
                });
        },

        async obtenerNumeroFactura() {
            try {
                const response = await axios.get('/facturas/ultimo-numero');
                const ultimoNumero = response.data.ultimoNumero;
                this.num_comprob = ultimoNumero + 1; 
                console.log("el numero factura es: " + this.num_comprob);
            } catch (error) {
                console.error('Error al obtener el último número de factura:', error);
            }
        },

        manejarErrorVenta(data) {
            if (data.valorMaximo) {
                swal("Aviso", `El valor de descuento no puede exceder el ${data.valorMaximo}`, "warning");
            } else if (data.caja_validado) {
                swal("Aviso", data.caja_validado, "warning");
            } else {
                console.error("Error desconocido al registrar venta:", data);
            }
        },

        reiniciarFormulario() {
            // Restablecer todos los valores del formulario
            Object.assign(this, {
                idproveedor: 0,
                //tipo_comprobante: this.tipo_comprobante === "FACTURA" ? "RESIVO" : "FACTURA",
                //tipo_comprobante: "FACTURA",
                nombreCliente: "",
                //idcliente: 0,
                tipo_documento: 0,
                complemento_id: "",
                documento: "",
                imagen: "",
                serie_comprobante: "",
                //num_comprob: "",
                impuesto: 0.18,
                total: 0.0,
                idarticulo: 0,
                articulo: "",
                cantidad: 1,
                precio: 0,
                stock: 0,
                codigo: "",
                descuento: 0,
                arrayDetalle: [],
                //arrayProductos: [],
                primer_precio_cuota: 0,
                step: 1,
                recibido: 0
            });
        },

        eliminarVenta(idVenta) {
            axios.delete('/venta/eliminarVenta/' + idVenta)
                .then(function (response) {
                    console.log('Venta eliminada correctamente:', response);
                })
                .catch(function (error) {
                    console.error('Error al eliminar la venta:', error);
                });
        },

        mostrarDetalle() {
            let me = this;
            me.selectAlmacen();
            me.listado = 0;

            me.idproveedor = 0;
            //me.tipo_comprobante = 'FACTURA';
            me.serie_comprobante = '';
            //me.nextNumber();
            //me.num_comprobante = '';
            me.impuesto = 0.18;
            me.total = 0.0;
            me.idarticulo = 0;
            me.articulo = '';
            me.cantidad = 1;
            me.precio = 0;
            me.arrayDetalle = [];
            //me.arrayProductos = [];
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
            this.idAlmacen = 1;
            this.arrayProductos = [];
            this.arrayDetalle = [];
            this.precioBloqueado = false;

        },
        verVenta(id) {
            let me = this;
            me.listado = 2;

            //Obtener datos del ingreso
            var arrayVentaT = [];
            var url = "/venta/obtenerCabecera?id=" + id;

            axios
                .get(url)
                .then(function (response) {
                    var respuesta = response.data;
                    arrayVentaT = respuesta.venta;
                    console.log("VIENDO ", respuesta);

                    me.cliente = arrayVentaT[0]["nombre"];
                    me.tipo_comprobante = arrayVentaT[0]["tipo_comprobante"];
                    me.serie_comprobante = arrayVentaT[0]["serie_comprobante"];
                    me.num_comprobante = arrayVentaT[0]["num_comprobante"];
                    me.impuesto = arrayVentaT[0]["impuesto"];
                    me.total = arrayVentaT[0]["total"];
                })
                .catch(function (error) {
                    console.log(error);
                });

            //obtener datos de los detalles
            var url = "/venta/obtenerDetalles?id=" + id;

            axios
                .get(url)
                .then(function (response) {
                    //console.log(response);
                    var respuesta = response.data;
                    me.arrayDetalle = respuesta.detalles;
                    console.log(array);
                })
                .catch(function (error) {
                    console.log(error);
                });
        },
        cerrarModal() {
            this.modal = 0;
            this.tituloModal = "";
            this.buscarA = '';
        },
        abrirModal() {

            this.scrollToTop();
            this.listarArticulo("", "nombre");
            this.selectAlmacen();
            this.arrayArticulo = [];
            this.modal = true;
            this.tituloModal = "Seleccione los articulos que desee";
            console.log("entro siii");
        },
        advertenciaFechaVencimiento() {
            swal({
                title: "No Disponible",
                text: "No puede seleccionar este producto porque esta vencido.",
                type: "warning",
                confirmButtonColor: "#3085d6",
                confirmButtonText: "Aceptar",
                confirmButtonClass: "btn btn-success",
                buttonsStyling: false,
            }).then(() => {
                this.cerrarModal();
            });
        },

        desactivarVenta(id) {
            swal({
                title: "Esta seguro de anular esta venta?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Aceptar!",
                cancelButtonText: "Cancelar",
                confirmButtonClass: "btn btn-success",
                cancelButtonClass: "btn btn-danger",
                buttonsStyling: false,
                reverseButtons: true,
            }).then((result) => {
                if (result.value) {
                    let me = this;

                    axios
                        .put("/venta/desactivar", {
                            id: id,
                        })
                        .then(function (response) {
                            me.listarVentaF(1, "", "num_comprobante");
                            swal(
                                "Anulado!",
                                "La venta ha sido anulado con éxito.",
                                "success"
                            );
                        })
                        .catch(function (error) {
                            console.log(error);
                        });
                } else if (
                    // Read more about handling dismissals
                    result.dismiss === swal.DismissReason.cancel
                ) {
                }
            });
        },
        //-------------OBTENER PRECIOS Y MABRIR_MODAL----------
        listarPrecio() {
            let me = this;
            var url = "/precios";
            axios
                .get(url)
                .then(function (response) {
                    var respuesta = response.data;
                    me.arrayPrecios = respuesta.precio.data;
                    console.log("PRECIOS", me.arrayPrecios);
                    //me.precioCount = me.arrayBuscador.length;
                })
                .catch(function (error) {
                    console.log(error);
                });
        },
        mostrarSeleccion() {
            console.log("Precio seleccionado:", this.precioseleccionado);
        },


        cerrarModal2() {
            this.modal2 = false;
            this.tituloModal2 = "";
            this.idtipo_pago = "";
            this.tipoPago = "";
        },
        cerrarModal3() {
            this.modal3 = 0;
            this.tituloModal3 = "";
            this.numero_cuotas = "";
            this.tiempo_diaz = "";
            this.primera_cuota = false;
            this.cuotas = [];
        },

        calcularCambio() {

            const recibidoNumero = parseFloat(
                this.recibido / parseFloat(this.monedaVenta[0])
            );
            if (recibidoNumero === 0) {
                this.efectivo = recibidoNumero;
                console.log("EFECTIVO", this.efectivo);
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

        buscarClientePorDocumento() {
            axios.get(`/api/clientes?documento=${this.documento}`)
                .then(response => {
                    const cliente = response.data;
                    console.log(cliente);
                    this.nombreCliente = cliente.nombre;
                    this.nombreClienteEditable = false; // Deshabilita el input si se encuentra el cliente
                })
                .catch(error => {
                    if (error.response && error.response.status === 404) {
                        this.nombreCliente = '';
                        this.nombreClienteEditable = true; // Habilita el input si no se encuentra el cliente
                        Swal.fire({
                            title: 'Cliente no encontrado',
                            text: 'No se encontró ningún cliente con el documento proporcionado.',
                            icon: 'warning',
                            confirmButtonText: 'Ok'
                        });
                    } else {
                        console.error('Error al buscar el cliente:', error);
                        this.nombreCliente = '';
                        this.nombreClienteEditable = false; // Asegura que el input esté deshabilitado en caso de error
                        Swal.fire({
                            title: 'Error',
                            text: 'Hubo un problema al buscar el cliente. Por favor, inténtelo de nuevo más tarde.',
                            icon: 'error',
                            confirmButtonText: 'Ok'
                        });
                    }
                });
        },
    },
    created() {
        this.listarPrecio();
    },
    mounted() {
        this.datosConfiguracion();
        this.selectAlmacen();
        this.listarVentaF(1, this.buscar, this.criterio);
        //this.obtenerDatosUsuario();
        this.actualizarFechaHora();
        this.ejecutarFlujoCompleto();
        this.ejecutarSecuencial();
        //this.obtenerNumeroFactura();
        document.addEventListener('keypress', this.handleKeyPress);
        window.addEventListener("keydown", this.handleKeyPress);
    },
    beforeUnmount() {
        window.removeEventListener("keydown", this.handleKeyPress);
    },
    beforeDestroy() {
        document.removeEventListener('keypress', this.handleKeyPress);
    }
};
</script>
<style scoped>
.step-indicators {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.step {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.step.active {
    background-color: #007bff;
    color: white;
}

.step.completed {
    background-color: #28a745;
    color: white;
}

.modal-header {
    display: flex;
    align-items: center;
    padding: 1rem;
}

.close-button {
    border: none;
    background: transparent;
    font-size: 1.5rem;
    cursor: pointer;
    margin-right: 1rem; /* Space between the button and the title */
}

.modal-title {
    margin: 0;
    flex: 1; /* This allows the title to take up remaining space */
}
</style>