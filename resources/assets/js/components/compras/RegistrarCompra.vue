<template>
  <div class="main-container">
    <div class="loading-overlay" v-if="isLoading">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">LOADING...</div>
      </div>
    </div>
    <Toast :breakpoints="{ '920px': { width: '100%', right: '0', left: '0' } }" style="padding-top: 10px;"
      appendTo="body" :baseZIndex="99999"></Toast>
    <Panel class="ingreso-panel">
      <template #header>
        <div class="panel-header header-flex">
          <div class="header-title-icon">
            <i class="pi pi-shopping-cart panel-icon"></i>
            <h4 class="panel-title">DETALLE COMPRA</h4>
          </div>
          <div class="linear-stepper compact-stepper">
            <div class="step-container">
              <div class="step" :class="{ active: step === 1, completed: step > 1 }">
                <span class="step-number" v-if="step > 1">✔</span>
                <span class="step-number" v-else>1</span>
              </div>
              <div class="step-line" v-if="step >= 2"></div>
            </div>
            <div class="step-container">
              <div class="step" :class="{ active: step === 2, completed: step > 2 }">
                <span class="step-number" v-if="step > 2">✔</span>
                <span class="step-number" v-else>2</span>
              </div>
            </div>
          </div>
        </div>
      </template>
      <div v-show="step === 1" class="step-content">
        <Panel header="Datos de Comprobante y Almacén">
          <div class="p-fluid p-formgrid p-grid">
            <div class="p-field p-col-12 p-md-4">
              <label for="tipoComprobante" class="font-weight-bold">
                Tipo comprobante
                <Tag severity="danger" class="obligatorio-rojo">OBLIG</Tag>
              </label>
              <Dropdown id="tipoComprobante" v-model="tipo_comprobante" :options="tipoComprobanteOptions"
                optionLabel="label" optionValue="value" placeholder="Seleccione" class="w-full" />
            </div>
            <div class="p-field p-col-12 p-md-4">
              <label for="numComprobante" class="font-weight-bold">
                N° Comprobante
                <Tag severity="danger" class="obligatorio-rojo">OBLIG</Tag>
              </label>
              <InputText id="numComprobante" v-model="num_comprobante" placeholder="000xx" ref="numeroComprobanteRef"
                class="w-full" />
              <small>Shift + E</small>
            </div>
            <div class="p-field p-col-12 p-md-4">
              <label for="almacen" class="font-weight-bold">
                Almacen Destino
                <Tag severity="danger" class="obligatorio-rojo">OBLIG</Tag>
              </label>
              <Dropdown id="almacen" v-model="AlmacenSeleccionado" :options="arrayAlmacenes"
                optionLabel="nombre_almacen" optionValue="id" placeholder="Seleccione" :disabled="idrolUsuario != 4" />
            </div>
          </div>
        </Panel>
      </div>
      <div v-show="step === 2" class="step-content">
        <Panel header="Detalle de Compra">
          <div class="almacen-busqueda-flex">
            <div class="buscador-container">
              <input type="text" v-model="buscarA" @input="listarArticuloDebounced(buscarA, criterioA)"
                class="form-control buscador-input" placeholder="Texto a buscar" />
              <button type="button" class="reset-buscar-btn" @click="resetBuscarA" title="Limpiar búsqueda">
                <i class="pi pi-times"></i>
              </button>
            </div>
            <div style="display: flex; align-items: flex-end;">
              <Button :label="mostrarLabel ? 'Nuevo' : ''" icon="pi pi-plus" class="p-button-secondary p-button-sm"
                @click="abrirModal('articulo', 'registrar')" title="Nuevo Medicamento" />
            </div>
          </div>

          <div class="modal-body">
            <DataTable :value="arrayArticulo" responsiveLayout="scroll" stripedRows size="small" class="p-datatable-sm"
              paginator :rows="5">
              <Column header="Opciones">
                <template #body="slotProps">
                  <Button icon="pi pi-check" class="p-button-success p-button-sm"
                    style="padding: 0.3rem 0.4rem; font-size: 0.75rem; width: auto; min-width: unset;"
                    @click="agregarDetalleModal(slotProps.data)" />
                </template>
              </Column>

              <Column field="codigo" header="Código" />
              <Column field="nombre" header="Nombre comercial" />
              <Column field="nombre_proveedor" header="Proveedor" />

              <Column field="precio_costo_unid" header="Costo unit" />
              <Column field="precio_costo_paq" header="Costo Paquete" />
              <Column field="precio_uno" header="Precio Venta">
                <template #body="slotProps">
                  {{ Number(slotProps.data.precio_uno).toFixed(2) }}
                </template>
              </Column>
            </DataTable>
          </div>
          <div class="p-col-12">
            <DataTable :value="arrayDetalle" responsiveLayout="scroll" class="p-datatable-sm">

              <Column header="Acciones" style="width: 50px">
                <template #body="slotProps">
                  <Button icon="pi pi-trash" class="p-button-danger p-button-sm"
                    style="padding: 0.3rem 0.4rem; width: auto;" @click="eliminarDetalle(slotProps.index)" />
                </template>
              </Column>

              <Column field="articulo" header="Producto" />

              <!--<Column header="Modo Compra" headerStyle="justify-content: flex-start" bodyClass="text-center">
                <template #body="slotProps">
                  <div style="display: flex; flex-direction: column; align-items: center;">
                    <InputSwitch v-model="slotProps.data.es_paquete" />
                    <small
                      :style="{ color: slotProps.data.es_paquete ? '#2196F3' : '#689F38', fontWeight: 'bold', marginTop: '5px' }">
                      {{ slotProps.data.es_paquete ? 'POR CAJA' : 'POR UNIDAD' }}
                    </small>
                  </div>
                </template>
              </Column>-->

              <Column field="unidad_x_paquete" header="Unid. x Caja" headerStyle="justify-content: flex-start"
                bodyClass="text-center">
              </Column>

              <Column header="Costo" headerStyle="justify-content: flex-start">
                <template #body="slotProps">
                  <div style="display: flex; flex-direction: column;">
                    <InputNumber v-if="slotProps.data.es_paquete" v-model="slotProps.data.precio_paquete"
                      :style="{ width: '120px' }" :min="0" :step="0.01" locale="es-ES" :minFractionDigits="2"
                      :maxFractionDigits="2" class="p-inputtext-sm text-center"
                      @input="sincronizarPrecios(slotProps.data, 'paquete')" />
                    <InputNumber v-else v-model="slotProps.data.precio" :style="{ width: '120px' }" :min="0" :step="0.01"
                      locale="es-ES" :minFractionDigits="2" :maxFractionDigits="2" class="p-inputtext-sm text-center"
                      @input="sincronizarPrecios(slotProps.data, 'unidad')" />
                    <small
                      :style="{ color: slotProps.data.es_paquete ? '#2196F3' : '#689F38', fontWeight: 'bold', marginTop: '5px', marginLeft: '60px' }">
                      {{ slotProps.data.es_paquete ? 'Costo Caja' : 'Costo Unit.' }}
                    </small>
                  </div>
                </template>
              </Column>

              <Column header="Cantidad">
                <template #body="slotProps">
                  <InputNumber v-model="slotProps.data.cantidad" class="inputnumber-compact" :min="1"
                    :style="{ width: '160px' }" />
                  <div class="text-center mt-1">
                    <span class="badge" :class="slotProps.data.es_paquete ? 'bg-primary' : 'bg-success'"
                      :style="{ marginRight: '50px' }">
                      {{ slotProps.data.es_paquete ? 'Cajas' : 'Unidades' }}
                    </span>
                  </div>
                </template>
              </Column>

              <Column header="Subtotal" headerStyle="justify-content: flex-end">
                <template #body="slotProps">
                  <span style="font-weight: bold; font-size: 1.1em;">
                    {{ calcularSubtotalItem(slotProps.data) }} {{ monedaCompra[1] }}
                  </span>
                </template>
              </Column>

            </DataTable>
            <div class="p-d-flex p-jc-end p-mt-2">
              <b>Total Neto:</b>
              <span class="p-ml-2">{{ (calcularTotal * parseFloat(monedaCompra[0])).toFixed(2) }}
                {{ monedaCompra[1] }}</span>
            </div>
          </div>
          <div class="p-d-flex p-jc-end p-mt-3 compra-action-buttons-row">
            <!-- Botón Cerrar siempre visible -->
            <Button label="Cerrar" class="p-button-danger p-button-sm compra-btn-custom" @click="cerrarFormulario()" />

            <!-- Botón Registrar Compra (nuevo) -->
            <Button v-if="!isEditing" label="Registrar Compra" class="p-button-success p-button-sm compra-btn-custom"
              @click="confirmarRegistroCompra" />

            <!-- Botón Actualizar Compra (editar) -->
            <Button v-else label="Actualizar Compra" class="p-button-primary p-button-sm compra-btn-custom"
              @click="actualizarIngreso" />
          </div>

        </Panel>
      </div>
      <div class="buttons p-d-flex p-jc-center p-mt-4 step-buttons-row">
        <Button label="Anterior" class="p-button-secondary p-button-sm step-btn-custom" @click="prevStep"
          :disabled="step === 1" />
        <Button label="Siguiente" class="p-button-primary p-button-sm step-btn-custom" @click="validarYAvanzar"
          :disabled="step === 2" />
      </div>
    </Panel>
    <!-- MODAL REGISTRAR PRODUCTO -->
    <Dialog :visible.sync="dialogVisible" :modal="true" header="Datos del Producto" :closable="false"
      :closeOnEscape="true" @hide="closeDialog" :containerStyle="dialogContainerStyle" class="responsive-dialog">
      <form>
        <TabView v-model:activeIndex="activeTab">
          <!-- 🟢 TAB 1: DATOS DEL producto -->
          <TabPanel header="Datos del producto">

            <div class="form-group row">
              <div class="col-md-6">
                <div>
                  <label for="nombre" class="required-field">
                    <span class="required-icon">*</span>
                    Nombre del Producto
                  </label>
                  <InputText id="nombreProducto" v-model="datosFormulario.nombre" placeholder="Ej. Pelota de Futbol"
                    class="form-control p-inputtext-sm input-full" :class="{ 'input-error': errores.nombre }"
                    @input="validarCampo('nombre')" autocomplete="off" />
                </div>
              </div>
              <div class="col-md-6">
                <label for="descripcion" class="optional-field">
                  <i class="pi pi-info-circle optional-icon"></i>
                  Codigo de Barras
                  <span class="p-tag p-tag-secondary tag-opcional">Opcional</span>
                </label>
                <InputText id="codigo" v-model="datosFormulario.codigo_alfanumerico" placeholder="Ej. 1000001101011"
                  class="form-control p-inputtext-sm input-full" autocomplete="off" />
                <div class="barcode-container mt-4">
                  <barcode :value="datosFormulario.codigo_alfanumerico" :options="{ format: 'EAN-13' }"></barcode>
                </div>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-12">
                <div>
                  <label for="nombre" class="required-field">
                    <span class="required-icon">*</span>
                    Codigo del Producto
                  </label>
                  <InputText id="codigo" v-model="datosFormulario.codigo" placeholder="Ej. 309C"
                    class="form-control p-inputtext-sm input-full" :class="{ 'input-error': errores.codigo }"
                    @input="validarCampo('codigo')" autocomplete="off" />
                </div>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-12">
                <div>
                  <label for="descripcion" class="optional-field">
                    <i class="pi pi-info-circle optional-icon"></i>
                    Descripción
                    <span class="p-tag p-tag-secondary tag-opcional">Opcional</span>
                  </label>
                  <Textarea id="descripcion" v-model="datosFormulario.descripcion"
                    placeholder="Ej. 1 docena de Pelotas de Futbol" rows="3" class="form-control p-inputtext-sm" />
                </div>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label for="nombre" class="required-field">
                  <span class="required-icon">*</span>
                  Proveedor
                </label>
                <div class="p-inputgroup ">
                  <InputText id="proveedor" v-model="proveedorSeleccionado.nombre" placeholder="Seleccione un proveedor"
                    class="form-control p-inputtext-sm bold-input input-full" disabled
                    :class="{ 'input-error': errores.idproveedor }" @input="validarCampo('codigo')" />
                  <Button label="..." class="p-button-primary p-button-sm btn-sm"
                    @click="abrirDialogos('Proveedores')" />
                </div>
              </div>

              <div class="col-md-6">
                <label for="nombre" class="required-field">
                  <span class="required-icon">*</span>
                  Categoria
                </label>
                <div class="p-inputgroup">
                  <InputText id="linea" v-model="lineaSeleccionado.nombre" placeholder="Seleccione una Categoria"
                    class="form-control p-inputtext-sm bold-input input-full" disabled
                    :class="{ 'input-error': errores.idcategoria }" />
                  <Button label="..." class="p-button-primary p-button-sm btn-sm" @click="abrirDialogos('Lineas')" />
                </div>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <div>
                  <label for="nombre" class="required-field">
                    <span class="required-icon">*</span>
                    Medida de Producto
                  </label>

                  <InputText id="nombreProducto" v-model="datosFormulario.descripcion_fabrica"
                    placeholder="Ej. Unid (Unidades)" class="form-control p-inputtext-sm input-full"
                    :class="{ 'input-error': errores.descripcion_fabrica }" @input="validarCampo('descripcion_fabrica')"
                    autocomplete="off" />
                </div>
              </div>

              <div class="col-md-6">
                <label for="unidadEnvase" class="required-field">
                  <span class="required-icon">*</span>

                  <!-- 🔥 Aquí el label dinámico -->
                  {{ datosFormulario.descripcion_fabrica
                    ? datosFormulario.descripcion_fabrica + ' x Caja'
                    : 'Unidades x Caja' }}

                </label>

                <div class="p-inputgroup">
                  <InputNumber id="unidadEnvase" v-model="datosFormulario.unidad_envase" placeholder="Ej: 24"
                    class="p-inputtext-sm input-number-full" :class="{ 'input-error': errores.unidad_envase }"
                    @input="validarCampo('unidad_envase')" autocomplete="off" />
                </div>
              </div>

            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label for="nombre" class="required-field">
                  <span class="required-icon">*</span>
                  Costo Compra
                </label>

                <div class="p-inputgroup">
                  <InputNumber id="preciounitario" v-model="datosFormulario.precio_costo_unid" placeholder="Ej: 12.50"
                    locale="es-ES" class="p-inputtext-sm bold-input input-number-full" mode="decimal"
                    :minFractionDigits="2" :maxFractionDigits="2" :class="{ 'input-error': errores.precio_costo_unid }"
                    @value-change="onPrecioPaqChange" @keydown.native="convertirPuntoComa" autocomplete="off" />

                  <!-- 🔥 Aquí se agrega el texto “Bs” al final -->
                  <span class="p-inputgroup-addon addon-precio">Bs</span>
                </div>
              </div>


              <div class="col-md-6">
                <label for="nombre" class="required-field">
                  <span class="required-icon">*</span>
                  Stock Minimo (Unidades)
                </label>
                <div class="p-inputgroup">
                  <InputNumber id="stockMinimo" v-model="datosFormulario.stock" placeholder="Ej: 10"
                    class="p-inputtext-sm input-number-full" :class="{ 'input-error': errores.stock }"
                    @input="validarCampo('stock')" autocomplete="off" />
                </div>
              </div>
            </div>

            <div class="p-col-12" style="margin-top: 20px; margin-bottom: 10px;">
              <h5 style="font-weight: bold; color: #495057; border-bottom: 1px solid #dee2e6; padding-bottom: 8px;">
                <i class="pi pi-tags"></i> Precios de Venta Unitarios
              </h5>
            </div>

            <div v-for="(precio, index) in precios" :key="precio.id" class="p-grid p-ai-start p-mb-3 mobile-responsive">
              <div class="p-col-12">
                <label class="required-field">{{ precio.nombre_precio }}:</label>
              </div>

              <div class="p-col-12">
                <div class="p-grid">
                  <div class="p-col-12 p-md-6">
                    <div class="p-inputgroup" style="width: 100%;">
                      <InputNumber v-model.number="precio.valor" placeholder="Precio" mode="decimal" locale="es-ES"
                        :min="0" :useGrouping="false" :allowEmpty="true" :minFractionDigits="2" :maxFractionDigits="2"
                        class="p-inputtext-sm w-full input-number-full" @keydown.native="convertirPuntoComa"
                        @input="onPrecioChange(precio)" :class="{ 'input-error': precioError(precio) }" />
                      <span class="p-inputgroup-addon addon-precio">{{ monedaPrincipal[1] }}</span>
                    </div>
                  </div>

                  <div class="p-col-12 p-md-6">
                    <div class="p-inputgroup" style="width: 100%;">

                      <InputNumber v-model.number="precio.porcentaje" mode="decimal" :min="0" :max="1000"
                        :useGrouping="false" :allowEmpty="true" :minFractionDigits="2" :maxFractionDigits="2"
                        placeholder="0.00" class="p-inputtext-sm w-full input-number-full"
                        @focus="onPorcentajeFocus(precio)" @input="onPorcentajeInput(precio)"
                        @keydown.native="convertirPuntoComa" />

                      <span class="p-inputgroup-addon addon-precio">%</span>
                    </div>
                  </div>
                </div>

                <div v-if="precio.errorVenta" class="p-error-precio"
                  style="display: block; margin-top: 4px; font-size: 0.85rem;">
                  ⚠️ El precio de venta es menor al costo de compra.
                </div>
              </div>
            </div>
          </TabPanel>
        </TabView>
      </form>
      <template #footer>
        <Button label="Cerrar" icon="pi pi-times" class="p-button-danger p-button-sm" @click="cerrarModal" />
        <Button v-if="tipoAccion == 1" class="p-button-success p-button-sm" label="Guardar" icon="pi pi-check"
          @click="enviarFormulario()" />
        <Button v-if="tipoAccion == 2" class="p-button-warning p-button-sm" label="Actualizar" icon="pi pi-check"
          @click="enviarFormulario()" />
      </template>
    </Dialog>
    <!-- MODALES DINÁMICOS -->
    <DialogProveedores v-if="mostrarDialogoProveedores" :visible.sync="mostrarDialogoProveedores"
      @close="cerrarDialogos('Proveedores')" @proveedor-seleccionado="manejarProveedorSeleccionado" />
    <DialogLineas v-if="mostrarDialogoLineas" :visible.sync="mostrarDialogoLineas" @close="cerrarDialogos('Lineas')"
      @linea-seleccionado="manejarLineaSeleccionado" />
    <DialogMarcas v-if="mostrarDialogoMarcas" :visible.sync="mostrarDialogoMarcas" @close="cerrarDialogos('Marcas')"
      @marca-seleccionado="manejarMarcaSeleccionado" />
    <DialogIndustrias v-if="mostrarDialogoIndustrias" :visible.sync="mostrarDialogoIndustrias"
      @close="cerrarDialogos('Industrias')" @industria-seleccionado="manejarIndustriaSeleecionado" />
    <DialogGrupos v-if="mostrarDialogoGrupos" :visible.sync="mostrarDialogoGrupos" @close="cerrarDialogos('Grupos')"
      @grupo-seleccionado="manejarGrupoSeleccionado" />
    <DialogMedidas v-if="mostrarDialogoMedidas" :visible.sync="mostrarDialogoMedidas" @close="cerrarDialogos('Medidas')"
      @medida-seleccionado="manejarMedidaSeleccionado" />
    <DialogAlmacenes v-if="mostrarDialogoAlmacen" :visible.sync="mostrarDialogoAlmacen"
      @close="cerrarDialogos('Almacen')" @almacen-seleccionado="manejarAlmacenSeleccionado" />
  </div>
</template>

<script>
import Dropdown from "primevue/dropdown";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Button from "primevue/button";
import Tag from "primevue/tag";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Panel from "primevue/panel";
import Swal from "sweetalert2";
import debounce from "lodash/debounce";
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import Tooltip from 'primevue/tooltip';
import InputSwitch from "primevue/inputswitch";
import Dialog from "primevue/dialog";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import VueBarcode from "vue-barcode";
import { esquemaArticulos, esquemaInventario } from "../../constants/validations";
import DialogProveedores from "../modales/DialogProveedores.vue";
import DialogLineas from "../modales/DialogLineas.vue";
import DialogMarcas from "../modales/DialogMarcas.vue";
import DialogIndustrias from "../modales/DialogIndustrias.vue";
import DialogGrupos from "../modales/DialogGrupos.vue";
import DialogMedidas from "../modales/DialogMedidas.vue";
import DialogAlmacenes from "../modales/DialogAlmacenes.vue";

export default {
  components: {
    Dropdown,
    InputText,
    InputNumber,
    Button,
    Tag,
    DataTable,
    Column,
    Panel,
    Swal,
    ToastService,
    Toast,
    InputSwitch,
    Dialog,
    TabView,
    TabPanel,
    barcode: VueBarcode,
    DialogProveedores,
    DialogLineas,
    DialogMarcas,
    DialogIndustrias,
    DialogGrupos,
    DialogMedidas,
    DialogAlmacenes,

  }, directives: {
    'tooltip': Tooltip
  },
  props: {
    monedaPrincipal: {
      type: Array,
      required: true,
    },
    arrayArticuloSeleccionado: {
      type: Object,
      required: false,
    },
    arrayPedidoSeleccionado: {
      type: Object,
      required: false,
    },
    arrayDetallePedido: {
      type: Array,
      required: false,
    },
    monedaCompra: {
      type: Array,
      required: true,
    },
    editarIngresoData: {
      type: Object,
      default: null
    }
  },
  created() {
    this.selectAlmacen();
    this.articuloSeleccionadoLocal = { ...this.arrayArticuloSeleccionado };
    if (this.arrayDetallePedido) {
      this.arrayDetalle = [...this.arrayDetallePedido];

      this.AlmacenSeleccionado = this.arrayPedidoSeleccionado.idalmacen;
      this.proveedorSeleccionado = this.arrayPedidoSeleccionado.nombre_proveedor;
      this.idproveedor = this.arrayPedidoSeleccionado.idproveedor;
    }
  },
  data() {
    return {
      isEditing: false, // se activa cuando cargamos datos para editar
      tipoComprobanteOptions: [
        { label: "Seleccione", value: "0" },
        { label: "DIM", value: "DIM" },
        { label: "Boleta", value: "BOLETA" },
        { label: "Factura", value: "FACTURA" },
      ],
      editarPrecioOptions: [
        { label: "Costo unitario", value: "1" },
        { label: "Costo paquete", value: "0" },
      ],
      isLoading: false,
      editarPrecios: "1",
      fechavencimiento: "",

      nuevoPrecio: 0,
      nuevoCostoUnidad: 0,
      nuevoCostoPaquete: 0,
      precios: [],
      precio_uno: 0,
      precio_dos: 0,
      precio_tres: 0,
      precio_cuatro: 0,
      step: 1,
      proveedorSeleccionado: "",
      tipoUnidadSeleccionada: "Unidades",
      arrayArticuloSeleccionadoLocal: {},
      AlmacenSeleccionado: null,
      idrolUsuario: null,
      arrayAlmacenes: [],
      idproveedor: 0,
      tipo_comprobante: "DIM",
      num_comprobante: "",
      impuesto: 0.18,
      total: 0.0,
      arrayDetalle: [],
      listado: 1,
      modal: 0,
      errorIngreso: 0,
      errorMostrarMsjIngreso: [],
      criterio: "num_comprobante",
      buscar: "",
      criterioA: "nombre",
      buscarA: "",
      arrayArticulo: [],
      idarticulo: 0,
      codigo: "",
      articulo: "",
      precio: 0,
      cantidad: 1,
      es_paquete: false,

            buscarComprobante: '',
      comprobanteSeleccionado: null,
      listaComprobantes: [],

      windowWidth: window.innerWidth,
      limpiandoDescuento: false,
      activeTab: 0,
      dialogDetallesVisible: false,
      mostrarLabel: true,
      articuloSeleccionado: null,
      idrol: null,
      isLoading: false,
      intentoEnviar: false,
      criterio: "nombre",
      buscar: "",
      arrayArticulo: [], // Datos del producto
      dialogVisible: false,
      agregarStock: false,
      fechaVencimientoSeleccion: false,
      fechaVencimientoAlmacen: '2099-12-31',
      unidadStock: null,
      datosFormulario: {
        descuento: null,
        fecha_venc_descuento: null,
        nombre: "",
        descripcion: "",
        nombre_generico: "",
        unidad_envase: 0,
        precio_costo_unid: null,
        precio_costo_paq: null,
        precio_venta: null,
        precio_uno: null,
        precio_dos: null,
        precio_tres: null,
        precio_cuatro: null,
        stock: 0,
        costo_compra: 0,
        codigo: "",
        codigo_alfanumerico: "",
        descripcion_fabrica: "",
        idcategoria: null,
        idmarca: null,
        idindustria: null,
        idgrupo: null,
        idproveedor: null,
        idmedida: null,
      },
      datosFormularioInventario: {
        AlmacenSeleccionado: null,
        fechaVencimientoAlmacen: '2099-12-31',
        unidadStock: null,
      },
      errores: {},
      erroresinventario: {},
      tipo_stock: "unidades",
      tipoEnvase: [
        { valor: "paquetes", etiqueta: "Paquetes" },
        { valor: "unidades", etiqueta: "Unidades" },
      ],
      mostrarDialogoProveedores: false,
      mostrarDialogoLineas: false,
      mostrarDialogoMarcas: false,
      mostrarDialogoIndustrias: false,
      mostrarDialogoGrupos: false,
      mostrarDialogoMedidas: false,
      mostrarDialogoAlmacen: false,
      mostrarDialogoImportar: false,
      proveedorSeleccionado: [],
      lineaSeleccionado: [],
      marcaSeleccionado: [],
      industriaSeleccionado: [],
      grupoSeleccionado: [],
      medidaSeleccionado: [],
      almacenSeleccionado: "Almacen Principal",
      precios: [
        { id: 1, nombre_precio: 'por Unidad', valor: null, porcentaje: null, errorVenta: false },
        { id: 2, nombre_precio: 'por Docena', valor: null, porcentaje: null, errorVenta: false },
        { id: 3, nombre_precio: 'por Paquete', valor: null, porcentaje: null, errorVenta: false },
        //{ id: 4, nombre_precio: 'Especial',    valor: null, porcentaje: null, errorVenta: false }, 
      ],
      precio_uno: null,
      precio_dos: null,
      precio_tres: null,
      precio_cuatro: null,
      monedaPrincipal: [],

      //CONFIGURACIONES
      mostrarSaldosStock: "",
      mostrarProveedores: "",
      mostrarCostos: "",
      articulo_id: 0,
      idcategoria: 0,
      idmarca: 0,
      idindustria: 0,
      idproveedor: 0,
      idgrupo: 0,
      codigoProductoSin: 0,
      idmedida: 0,
      nombreLinea: "",
      nombre_categoria: "",
      nombre_proveedor: "",
      //id:'',//aumente 7 julio
      codigo: "",
      nombre: "",
      nombre_producto: "",
      nombre_generico: "",
      nombreProductoVacio: false,
      codigoVacio: false,
      unidad_envaseVacio: false,
      nombre_genericoVacio: false,
      precio_costo_unidVacio: false,
      precio_costo_paqVacio: false,
      precio_ventaVacio: false,
      costo_compraVacio: false,
      stockVacio: false,
      descripcionVacio: false,
      fotografiaVacio: false,
      lineaseleccionadaVacio: false,
      marcaseleccionadaVacio: false,
      industriaseleccionadaVacio: false,
      proveedorseleccionadaVacio: false,
      gruposeleccionadaVacio: false,
      medidaseleccionadaVacio: false,
      unidad_envase: 0,
      precio_costo_unid: 0,
      precio_costo_paq: 0,
      fotoMuestra: null,
      tipoAccion: 0,
      minDate: null,
      idarticulo: 0,
      pagination: {
        total: 0,
        current_page: 1,
        per_page: 10,
        last_page: 0,
        from: 0,
        to: 0,
      },
      offset: 3,
      headers: [
        { field: "acciones", header: "Acciones", type: "button" },
        { field: "codigo", header: "CODIGO" },
        { field: "nombre", header: "NOMBRE PRODUCTO" },
        { field: "nombre_proveedor", header: "PROVEEDOR" },
        { field: "nombre_categoria", header: "CATEGORIA" },
        { field: "precio_costo_unid", header: "COSTO COMPRA" },
        { field: "detalles", header: "Detalles", type: "button" },
      ],
    };
  },
  watch: {
    editarIngresoData: {
      handler(newVal) {
        if (newVal) {
          this.cargarDatosEdicion(newVal);
        }
      },
      immediate: true, // se ejecuta también al montar si ya viene con valor
      deep: true
    },
    codigo(newValue) {
      if (newValue) {
        this.buscarArticulo();
      }
    },
    AlmacenSeleccionado(newVal) {
      // Cuando cambia el almacén, actualizar todos los detalles existentes
      if (newVal && this.arrayDetalle && this.arrayDetalle.length > 0) {
        this.arrayDetalle.forEach((detalle) => {
          detalle.idalmacen = newVal;
        });
      }
    },
    arrayDetalle: {
      deep: true,
      handler: function (newVal) {
        if (Array.isArray(newVal)) {
          this.total = newVal.reduce((acc, detalle) => {
            return acc + detalle.cantidad * detalle.precio;
          }, 0);
        } else {
          console.error("arrayDetalle no es un array:", newVal);
        }
      },
    },
  },
  computed: {
    /*fechaPorDefecto() {
      const today = new Date();
      const year = today.getFullYear();
      const month = String(today.getMonth() + 1).padStart(2, "0");
      const day = String(today.getDate()).padStart(2, "0");
      this.fechavencimiento = `${year}-${month}-${day}`;

      return this.fechavencimiento;
    },*/
    fechaPorDefecto() {
      this.fechavencimiento = "2099-12-31";
      return this.fechavencimiento;
    },

    calcularTotal() {
      let resultado = 0.0;
      if (this.arrayDetalle) {
        for (let item of this.arrayDetalle) {
          resultado += this.calcularSubtotalItem(item, true);
        }
      }
      return resultado;
    },
        dialogContainerStyle() {
      if (window.innerWidth <= 480) {
        return { width: "95vw", maxWidth: "95vw", margin: "0 auto" };
      } else if (window.innerWidth <= 768) {
        return { width: "90vw", maxWidth: "90vw", margin: "0 auto" };
      } else if (window.innerWidth <= 1024) {
        return { width: "85vw", maxWidth: "900px", margin: "0 auto" };
      } else {
        return { width: "800px", maxWidth: "90vw", margin: "0 auto" };
      }
    },
  },
  async mounted() {
    try {
      this.isLoading = true; // Activar loading
      await Promise.all([
        this.listarPrecio(),
        this.datosConfiguracion(),
        this.listarArticulo("", ""),
      ]);
      window.addEventListener("keydown", this.handleKeyPress);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
      swal("Error", "Error al cargar datos iniciales", "error");
    } finally {
      setTimeout(() => {
        this.isLoading = false; // Desactivar loading
      }, 500);
    }
  },
  beforeUnmount() {
    window.removeEventListener("keydown", this.handleKeyPress);
  },
  methods: {
    async abrirModal(modelo, accion, data = []) {
      if (modelo === 'articulo' && (accion === 'actualizar' || accion === 'registrar')) {
        if (!this.validarPermisoVendedor()) return;
      }

      switch (modelo) {
        case "articulo": {
          switch (accion) {
            case "registrar": {
              this.cerrarModal();

              this.$nextTick(async () => {
                this.dialogVisible = true;
                this.tituloModal = "Registrar producto";
                this.agregarStock = false;
                this.tipoAccion = 1;
                this.fotografia = "";

                const preciosGlobales = await this.cargarPreciosGlobales();

                this.precios = [
                  {
                    id: 1,
                    nombre_precio: "por Unidad",
                    valor: null,
                    porcentaje: parseFloat(preciosGlobales.venta1) || null,
                    errorVenta: false,
                  },
                  {
                    id: 2,
                    nombre_precio: "por Docena",
                    valor: null,
                    porcentaje: parseFloat(preciosGlobales.venta2) || null,
                    errorVenta: false,
                  },
                  {
                    id: 3,
                    nombre_precio: "por Paquete",
                    valor: null,
                    porcentaje: parseFloat(preciosGlobales.venta3) || null,
                    errorVenta: false,
                  },

                ];

                this.datosFormulario = {
                  nombre: "",
                  descripcion: "",
                  nombre_generico: "",
                  unidad_envase: null,
                  precio_costo_unid: null,
                  precio_costo_paq: null,
                  precio_venta: null,
                  precio_uno: null,
                  precio_dos: null,
                  precio_tres: null,
                  precio_cuatro: null,
                  stock: null,
                  costo_compra: null,
                  codigo: "",
                  codigo_alfanumerico: "",
                  descripcion_fabrica: "",
                  idcategoria: null,
                  idmarca: null,
                  idindustria: null,
                  idgrupo: null,
                  idproveedor: null,
                  idmedida: null,
                };

                this.errores = {};
                this.fechaVencimientoSeleccion = false;
              });

              break;
            }
            case "actualizar": {
              this.cerrarModal();

              // ✅ Recargamos datos frescos desde backend
              axios.get(`/articulo/detalle/${data.id}`).then((response) => {
                const articulo = response.data;

                this.$nextTick(() => {
                  console.log("DATA ACTUALIZAR (refrescado)", articulo);
                  this.agregarStock = false;
                  this.dialogVisible = true;
                  this.tituloModal = "Actualizar producto";
                  this.tipoAccion = 2;

                  this.datosFormulario = {
                    nombre: articulo.nombre,
                    descripcion: articulo.descripcion,
                    nombre_generico: articulo.nombre_generico,
                    unidad_envase: articulo.unidad_envase,
                    descuento: articulo.descuento > 0 ? articulo.descuento : null,
                    fecha_venc_descuento: articulo.fecha_venc_descuento
                      ? articulo.fecha_venc_descuento.split("T")[0]
                      : null,

                    precio_costo_unid: this.calcularPrecioValorMoneda(articulo.precio_costo_unid),
                    precio_costo_paq: this.calcularPrecioValorMoneda(articulo.precio_costo_paq),
                    precio_venta: this.calcularPrecioValorMoneda(articulo.precio_venta),
                    precio_uno: 0,
                    precio_dos: 0,
                    precio_tres: 0,
                    precio_cuatro: 0,
                    stock:
                      this.tipo_stock == "paquetes" && articulo.unidad_envase > 0
                        ? articulo.stock / articulo.unidad_envase
                        : articulo.stock,
                    costo_compra: this.calcularPrecioValorMoneda(articulo.costo_compra),
                    codigo: articulo.codigo,
                    codigo_alfanumerico: articulo.codigo_alfanumerico || "",
                    descripcion_fabrica: articulo.descripcion_fabrica || "",
                    idcategoria: null,
                    idmarca: null,
                    idindustria: null,
                    idgrupo: null,
                    idproveedor: null,
                    idmedida: articulo.idmedida,
                    id: articulo.id,
                  };

                  this.precio_uno = Number(this.calcularPrecioValorMoneda(articulo.precio_uno)) || 0;
                  this.precio_dos = Number(this.calcularPrecioValorMoneda(articulo.precio_dos)) || 0;
                  this.precio_tres = Number(this.calcularPrecioValorMoneda(articulo.precio_tres)) || 0;
                  this.precio_cuatro = Number(this.calcularPrecioValorMoneda(articulo.precio_cuatro)) || 0;

                  this.errores = {};
                  this.idmedida = articulo.idmedida;
                  this.fotografia = articulo.fotografia;
                  this.fotoMuestra = articulo.fotografia
                    ? "img/articulo/" + articulo.fotografia
                    : null;

                  // Asignación de objetos seleccionados (Selects)
                  this.industriaSeleccionado = { nombre: articulo.nombre_industria, id: articulo.idindustria };
                  this.lineaSeleccionado = { nombre: articulo.nombre_categoria, id: articulo.idcategoria };
                  this.marcaSeleccionado = { nombre: articulo.nombre_marca, id: articulo.idmarca };
                  this.proveedorSeleccionado = { nombre: articulo.nombre_proveedor, id: articulo.idproveedor };
                  this.grupoSeleccionado = { nombre_grupo: articulo.nombre_grupo, id: articulo.idgrupo };
                  this.medidaSeleccionado = { descripcion_medida: articulo.descripcion_medida, id: articulo.idmedida };

                  this.precios = [
                    {
                      id: 1,
                      nombre_precio: "por Unidad",
                      valor: this.precio_uno,
                      porcentaje: this.calcularPorcentaje(this.precio_uno),
                      errorVenta: false,
                    },
                    {
                      id: 2,
                      nombre_precio: "por Docena",
                      valor: this.precio_dos,
                      porcentaje: this.calcularPorcentaje(this.precio_dos),
                      errorVenta: false,
                    },
                    {
                      id: 3,
                      nombre_precio: "por Paquete",
                      valor: this.precio_tres,
                      porcentaje: this.calcularPorcentaje(this.precio_tres),
                      errorVenta: false,
                    }
                  ];

                  if (this.precio_cuatro > 0) {
                    this.precios.push({
                      id: 4,
                      nombre_precio: "Especial",
                      valor: this.precio_cuatro,
                      porcentaje: this.calcularPorcentaje(this.precio_cuatro),
                      errorVenta: false,
                    });
                  }

                  this.$forceUpdate();
                  this.fechaVencimientoSeleccion = articulo.vencimiento === 1;
                });
              });

              break;
            }
            case "registrarInd": {
              this.modal = 1;
              this.tituloModal = "Registrar Industria";
              this.nombre = "";
              this.tipoAccion = 3;
              break;
            }
          }
        }
      }
    },
    cerrarModal() {
      this.dialogVisible = false;
      this.tituloModal = "";
      this.codigo = "";
      this.nombre_producto = "";
      this.nombre_generico = "";
      this.precio_venta = "";
      this.precio_costo_unid = "";
      this.precio_costo_paq = "";
      this.stock = "";
      this.descripcion = "";
      this.fotografia = ""; //Pasando el valor limpio de la referencia
      this.fotoMuestra = null;
      this.lineaSeleccionado = [];
      this.marcaSeleccionado = [];
      this.industriaSeleccionado = [];
      this.proveedorSeleccionado = [];
      this.grupoSeleccionado = [];
      this.medidaSeleccionado = [];
      this.fechaVencimientoSeleccion = false;
      this.errorArticulo = 0;
      this.idmedida = 0;
      this.costo_compra = "";
      this.precio_uno = "";
      this.precio_dos = "";
      this.precio_tres = "";
      this.precio_cuatro = "";
      this.descuento = 0;
      this.fecha_venc_descuento = null;
      this.intentoEnviar = false;
    },
    validarPermisoVendedor() {
      if (this.idrol === 2) {
        this.$toast.add({
          severity: 'warn',
          summary: 'Acceso Denegado',
          detail: 'Esta acción solo está permitida para Administradores.',
          life: 3000
        });
        return false;
      }
      return true;
    },
    async cargarPreciosGlobales() {
      try {
        const response = await axios.get("/configuracion/porcentajes");
        const preciosConfig = response.data;

        const venta1 = preciosConfig.find((p) => p.nombre_precio === "VENTA 1");
        const venta2 = preciosConfig.find((p) => p.nombre_precio === "VENTA 2");
        const venta3 = preciosConfig.find((p) => p.nombre_precio === "VENTA 3");

        return {
          venta1: venta1 ? venta1.porcentage : 0,
          venta2: venta2 ? venta2.porcentage : 0,
          venta3: venta3 ? venta3.porcentage : 0,
        };
      } catch (error) {
        console.error("❌ Error al cargar precios globales:", error);
        return { venta1: 0, venta2: 0, venta3: 0 };
      }
    },
    calcularPrecioValorMoneda(precio) {
      const tasa = Array.isArray(this.monedaPrincipal)
        ? parseFloat(this.monedaPrincipal[0]) || 1
        : parseFloat(this.monedaPrincipal) || 1;

      const valor = parseFloat(precio) || 0;
      return Number((valor * tasa).toFixed(2));
    },
    calcularPorcentaje(precioVenta) {
      const costo = Number(this.datosFormulario.precio_costo_unid) || 0;
      const venta = Number(precioVenta);

      if (costo <= 0 || isNaN(venta) || venta <= costo) return 0;

      const porcentaje = ((venta - costo) / costo) * 100;
      return parseFloat(porcentaje.toFixed(2));
    },
    async actualizarIngreso() {
      if (this.validarIngreso()) {
        swal("Error", this.errorMostrarMsjIngreso.join("\n"), "error");
        return;
      }

      if (this.arrayDetalle.length === 0) {
        swal("Error", "Debe agregar productos", "error");
        return;
      }

      try {
        this.isLoading = true;

        const response = await axios.post("/ingreso/actualizar", {
          id: this.ingresoSeleccionado.id, // <--- Aquí debe estar el ID
          idproveedor: this.idproveedor,
          idalmacen: this.AlmacenSeleccionado,
          tipo_comprobante: this.tipo_comprobante,
          serie_comprobante: this.serie_comprobante,
          num_comprobante: this.num_comprobante,
          impuesto: this.impuesto,
          total: this.total,
          data: this.arrayDetalle
        });

        if (!response.data.success) {
          swal("Error", response.data.message, "error");
          return;
        }

        await this.listarIngreso(1, "", "num_comprobante");

        swal({
          type: "success",
          title: "Éxito",
          text: "Compra actualizada correctamente"
        });
        this.isEditing = false; // ⚡ Modo edición
        this.$emit("cerrar");

      } catch (error) {
        swal("Error", "No se pudo actualizar la compra", "error");
      } finally {
        this.isLoading = false;
      }
    },
    cargarDatosEdicion(data) {
      console.log("cargarDatosEdicion llamado con data:", data);
      this.isEditing = true; // ⚡ Modo edición
      this.listado = 0;

      this.idproveedor = data.idproveedor || 0;
      this.tipo_comprobante = data.tipo_comprobante || "DIM";
      this.serie_comprobante = data.serie_comprobante || "";
      this.num_comprobante = data.num_comprobante || "";
      this.impuesto = parseFloat(data.impuesto) || 0.18;
      this.total = parseFloat(data.total) || 0;
      this.AlmacenSeleccionado = data.idalmacen || null;

      // Detalles: si viene vacío, inicializamos con array vacío
      this.arrayDetalle = data.detalles || [];

      // Guardar id del ingreso para usar en actualización
      this.ingresoSeleccionado = { id: data.id };

      // Recalcular totales si existe función
      if (typeof this.actualizarTotales === "function") {
        this.actualizarTotales();
      }
    },

    convertirPuntoComa(event) {
      if (event.key !== '.') return;

      event.preventDefault();

      // 🔥 SI ES INPUTNUMBER → obtener input interno
      let input = event.target;
      if (input.tagName !== 'INPUT') {
        input = input.querySelector('input');
      }

      if (!input) return;

      // Si ya tiene coma, solo mover cursor
      if (input.value.includes(',')) {
        const pos = input.value.indexOf(',') + 1;
        input.setSelectionRange(pos, pos);
        return;
      }

      const start = input.selectionStart;
      const end = input.selectionEnd;

      // Insertar coma
      const nuevoValor =
        input.value.substring(0, start) + ',' + input.value.substring(end);

      input.value = nuevoValor;

      input.setSelectionRange(start + 1, start + 1);

      // 🔥 Aquí está la clave:
      // Disparamos input para que PrimeVue actualice event.value correctamente
      input.dispatchEvent(new Event("input", { bubbles: true }));
    },
    evitarReformateo(event, callback) {

      // 🔥 SI ES INPUTNUMBER → usar input interno
      let input = event.target;
      if (input && input.tagName !== 'INPUT') {
        input = input.querySelector('input');
      }

      const valor = input ? input.value : String(event.value || '');

      // Si está en estado intermedio "10," o "10,."
      if (valor.endsWith(',') || valor.endsWith(',.')) {
        return;
      }

      callback(event);
    },
    /*
    evitarReformateo(event, callback) {
      const valor = event.target.value;

      // Si el valor está en un estado intermedio como "10," o "10,."
      if (valor.endsWith(',') || valor.endsWith(',.')) {
        return; // ← No ejecutar validaciones
      }

      // Ejecutar la validación original
      callback();
    },
    convertirPuntoComa(event) {
      if (event.key === '.') {
        event.preventDefault();
        const input = event.target;

        // Si ya tiene coma, solo mover cursor a la parte decimal
        if (input.value.includes(',')) {
          const pos = input.value.indexOf(',') + 1;
          input.setSelectionRange(pos, pos);
          return;
        }

        const start = input.selectionStart;
        const end = input.selectionEnd;

        // Insertar una sola coma SI no existe
        const nuevoValor =
          input.value.substring(0, start) + ',' + input.value.substring(end);

        input.value = nuevoValor;

        // Colocar cursor después de la coma
        input.setSelectionRange(start + 1, start + 1);

        // Actualizar modelo
        input.dispatchEvent(new Event("input"));
      }
    },*/
    async confirmarRegistroCompra() {
      const result = await Swal.fire({
        title: '¿Seguro que quiere registrar la compra?',
        text: '¿Verificó bien los datos?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Registrar',
        cancelButtonText: 'Volver',
        customClass: {
          confirmButton: 'custom-swal-confirm',
          cancelButton: 'custom-swal-cancel'
        },
        reverseButtons: true
      });
      if (result.isConfirmed) {
        this.registrarIngreso();
      }
    },
    resetBuscarA() {
      this.buscarA = "";
      this.listarArticuloDebounced("", this.criterioA);
    },
    validarInput(event, campo, item) {
      // 1. Obtener el valor de manera compatible (sin Optional Chaining '?.')
      let valor;

      if (typeof event === 'string' || typeof event === 'number') {
        valor = String(event);
      } else if (event && event.target && event.target.value !== undefined) {
        valor = event.target.value;
      } else {
        valor = '';
      }

      // 2. Limpieza inicial: Permitimos números (0-9), coma (,) Y PUNTO (.)
      valor = valor.replace(/[^0-9.,]/g, '');

      // 3. Lógica para limitar a un solo separador
      const separador = valor.includes(',') ? ',' : '.';
      const partes = valor.split(separador);

      if (partes.length > 1) {
        let parteDecimalLimpia = partes.slice(1).join('').replace(/[.,]/g, '');
        parteDecimalLimpia = parteDecimalLimpia.substring(0, 2);
        valor = partes[0] + separador + parteDecimalLimpia;
      }

      // 4. Asegurar que no haya separador al inicio (ej: ,50 -> 0,50)
      if (valor.startsWith(',') || valor.startsWith('.')) {
        valor = '0' + valor;
      }

      // 5. Asignar el valor limpio de vuelta al item (para que v-model se actualice con el valor limpio)
      if (item && item[campo] !== undefined) {
        item[campo] = valor;
      } else if (this[campo] !== undefined) {
        this[campo] = valor;
      }
    },

    async onPrecioUnitarioInput(valor, item) {
      console.log("onPrecioUnitarioInput llamado con valor:", valor, "y item:", item);
      let precioUnitario = this.getNumero(valor);
      let precioUnitarioLimitado = precioUnitario.toFixed(2);
      item.precio = precioUnitarioLimitado;

      let precioVenta = this.getNumero(item.precio_uno);

      if (precioVenta < precioUnitarioLimitado) {
        item.errorPrecioVenta = true;

        this.$toast.add({
          severity: 'warn',
          summary: 'Advertencia',
          detail: 'El precio de venta no puede ser menor al costo unitario.',
          life: 3500
        });

        return;
      }

      item.errorPrecioVenta = false;

      let unidades = this.getNumero(item.unidad_x_paquete);
      if (unidades > 0) {
        item.precio_paquete = (precioUnitarioLimitado * unidades).toFixed(2);
      }

      await this.cambiarPrecios(
        item.precio,
        item.precio_paquete,
        "Costo unitario",
        item.idarticulo
      );

      this.listarArticulo(this.buscarA, this.criterioA);
    },

    async onPrecioPaqueteInput(valor, item) {
      console.log("onPrecioPaqueteInput llamado con valor:", valor, "y item:", item);
      // 1. Sanitizar y limitar el valor (forzando 2 decimales para el cálculo)
      let precioPaquete = this.getNumero(valor);

      // 2. Aplicar el límite de 2 decimales antes del cálculo
      let precioPaqueteLimitado = precioPaquete.toFixed(2);

      // Actualiza precio paquete
      item.precio_paquete = precioPaqueteLimitado; // Asignamos el valor limpio y limitado

      let unidades = this.getNumero(item.unidad_x_paquete);

      if (unidades !== 0) {
        // 3. Recalcula precio unitario
        let resultadoUnitario = this.getNumero(precioPaqueteLimitado) / unidades;
        item.precio = resultadoUnitario.toFixed(2);
      }

      // Llama a la función para actualizar en backend
      await this.cambiarPrecios(
        item.precio,
        item.precio_paquete,
        "Costo paquete",
        item.idarticulo
      );
      // Refresca la tabla de productos
      this.listarArticulo(this.buscarA, this.criterioA);
    },
    async onPrecioUnoInput(valor, item) {
      let precioVenta = this.getNumero(valor);
      let precioVentaLimitado = precioVenta.toFixed(2);
      item.precio_uno = precioVentaLimitado;

      let costoUnitario = this.getNumero(item.precio);

      // ⚠️ Mostrar advertencia pero permitir guardar
      if (precioVenta < costoUnitario) {
        item.errorPrecioVenta = true;

        this.$toast.add({
          severity: 'warn',
          summary: 'Advertencia',
          detail: 'El precio de venta es menor que el costo unitario.',
          life: 3500
        });

        // ❗ NO HACEMOS return → se permite guardar igual
      } else {
        // ✔️ Precio válido → quitar borde rojo
        item.errorPrecioVenta = false;
      }

      // 🔥 Siempre actualizar en la base de datos
      await this.cambiarPrecioVenta(item.idarticulo, precioVentaLimitado);

      // 🔄 Refrescar tabla
      this.listarArticulo(this.buscarA, this.criterioA);
    },
    getNumero(valor) {
      if (!valor) return 0;
      return parseFloat(String(valor).replace(',', '.')) || 0;
    },
    async cambiarPrecioVenta(idArticulo, precioVenta) {
      try {
        await axios.post("/articulo/actualizarPrecioVenta", {
          id: idArticulo,
          precio_uno: precioVenta,
        });
      } catch (error) {
        console.error("Error al actualizar precio de venta:", error);
      }
    },

    sincronizarPrecios(item, origen) {
      let unidades = parseFloat(item.unidad_x_paquete) || 1;

      if (origen === 'paquete') {
        let nuevoPrecioUnit = parseFloat(item.precio_paquete) / unidades;
        item.precio = nuevoPrecioUnit;
      } else {
        let nuevoPrecioPaq = parseFloat(item.precio) * unidades;
        item.precio_paquete = nuevoPrecioPaq;
      }
    },

    calcularSubtotalItem(item, raw = false) {
      let precioBase = 0;
      if (item.es_paquete) {
        precioBase = parseFloat(item.precio_paquete) || 0;
      } else {
        precioBase = parseFloat(item.precio) || 0;
      }

      let cantidad = parseInt(item.cantidad) || 0;
      let subtotal = precioBase * cantidad;

      return raw ? subtotal : subtotal.toFixed(2);
    },

    listarArticulo(buscar, criterio) {
      let me = this;
      var url =
        "/articulo/listarArticulo?buscar=" + buscar + "&criterio=" + criterio;
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayArticulo = respuesta.articulos.map(art => ({
            ...art,

            // Normalizar todos los campos numéricos
            precio_uno: me.normalizarNumero(art.precio_uno),
          }));
        })
        .catch(function (error) { });
    },
    normalizarNumero(valor) {
      if (valor === null || valor === undefined) return null;

      // Elimina comas por si acaso y convierte "10.0000" -> 10
      const num = parseFloat(String(valor).replace(',', '.'));

      return isNaN(num) ? null : num;
    },

    listarArticuloDebounced: debounce(function (buscar, criterio) {
      this.listarArticulo(buscar, criterio);
    }, 200), // espera 500ms

    handleKeyPress(event) {
      if (event.shiftKey && event.key === "T") {
        this.editarPrecio();
      }
    },

    validarYAvanzar() {
      const errores = [];
      if (this.step === 1) {
        if (this.tipo_comprobante === "0")
          errores.push("Seleccione un tipo de comprobante");
      } else if (this.step === 2) {
        if (this.AlmacenSeleccionado === "0" || this.AlmacenSeleccionado === 0)
          errores.push("Seleccione un almacén");
        if (!this.arrayArticuloSeleccionado.id)
          errores.push("Seleccione un producto");
        if (this.cantidad === 0) errores.push("Ingrese una cantidad válida");
      }
      if (errores.length > 0) {
        const mensaje = errores.join("\n");
        swal("Campos incompletos", mensaje, "warning");
      } else {
        this.nextStep();
      }
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

    async buscarArticulo() {
      if (!this.AlmacenSeleccionado || this.AlmacenSeleccionado === 0) {
        Swal.fire({
          icon: "warning",
          title: "Almacén no seleccionado",
          text: "Debe seleccionar un almacén antes de buscar productos.",
        });
        return;
      }
      try {
        if (this.searchTimeout) {
          clearTimeout(this.searchTimeout);
        }
        this.searchTimeout = setTimeout(async () => {
          this.isLoading = true; // Activar loading
          let me = this;
          var url =
            "/articulo/listarArticulo?buscar=" +
            me.codigo +
            "&criterio=codigo&idProveedor=" +
            this.idproveedor;
          try {
            const response = await axios.get(url);
            let respuesta = response.data;
            me.arrayArticuloSeleccionado = respuesta.articulos.data[0];
          } catch (error) {
            swal("Error", "No se pudo buscar el artículo", "error");
          } finally {
            setTimeout(() => {
              this.isLoading = false; // Desactivar loading
            }, 500);
          }
        }, 1000);
      } catch (error) {
        console.error(error);
        this.isLoading = false;
      }
    },

    eliminarDetalle(index) {
      let me = this;
      me.arrayDetalle.splice(index, 1);
    },

    cerrarFormulario() {
      this.arrayDetalle = [];
      this.isEditing = false; // ⚡ Modo edición
      this.$emit("cerrar");
    },

    editarEstado() {
      const datos = {
        pedido: this.arrayPedidoSeleccionado,
        detalles: this.arrayDetallePedido,
      };
      this.$emit("editarEstadoPedido", datos);
    },

    listarIngreso(page, buscar, criterio) {
      const datos = {
        page: page,
        buscar: buscar,
        criterio: criterio,
      };
      this.$emit("listarIngreso", datos);
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

    selectAlmacen() {
      axios
        .get("/almacen/almaceneslista")
        .then((response) => {
          this.arrayAlmacenes = response.data.almacenes;
          this.idrolUsuario = response.data.idrol;
          if (this.arrayAlmacenes.length === 1) {
            this.AlmacenSeleccionado = this.arrayAlmacenes[0].id;
          }
        })
        .catch((error) => {
          console.error(error);
        });
    },

    async editarPrecio() {
      try {
        this.isLoading = true; // Activar loading
        let me = this;
        if (me.editarPrecios == "1") {
          me.nuevoCostoPaquete = (
            me.nuevoPrecio * me.arrayArticuloSeleccionado.unidad_envase
          ).toFixed(2);
          me.arrayArticuloSeleccionado.precio_costo_paq = me.nuevoCostoPaquete;
          me.arrayArticuloSeleccionado.precio_costo_unid = me.nuevoPrecio;
          await this.cambiarPrecios(
            me.arrayArticuloSeleccionado.precio_costo_unid,
            me.nuevoCostoPaquete,
            "Costo unitario"
          );
        }
        if (me.editarPrecios == "0") {
          me.nuevoCostoUnidad = (
            me.nuevoPrecio / me.arrayArticuloSeleccionado.unidad_envase
          ).toFixed(2);
          me.arrayArticuloSeleccionado.precio_costo_unid = me.nuevoCostoUnidad;
          me.arrayArticuloSeleccionado.precio_costo_paq = me.nuevoPrecio;
          await this.cambiarPrecios(
            me.nuevoCostoUnidad,
            me.arrayArticuloSeleccionado.precio_costo_paq,
            "Costo paquete"
          );
        }
      } catch (error) {
        console.error("Error al editar precio:", error);
        swal("Error", "No se pudo actualizar el precio", "error");
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },

    calcularPrecio(precio, index, preciounidad) {
      const margen_ganancia =
        parseFloat(preciounidad) * (parseFloat(precio.porcentage) / 100);
      const precio_publico = parseFloat(preciounidad) + margen_ganancia;

      if (index === 0) {
        this.precio_uno = precio_publico.toFixed(2);
      } else if (index === 1) {
        this.precio_dos = precio_publico.toFixed(2);
      } else if (index === 2) {
        this.precio_tres = precio_publico.toFixed(2);
      } else if (index === 3) {
        this.precio_cuatro = precio_publico.toFixed(2);
      }
    },

    async cambiarPrecios(
      precioUnidad,
      precioPaquete,
      editarPrecios,
      idArticulo = null
    ) {
      let me = this;
      // Si se pasa idArticulo, usarlo, si no, usar el seleccionado
      const articuloId =
        idArticulo ||
        (me.arrayArticuloSeleccionado && me.arrayArticuloSeleccionado.id);
      me.precios.forEach((precio, index) => {
        me.calcularPrecio(precio, index, precioUnidad);
      });
      // No mostrar swal de confirmación para edición directa en tabla
      try {
        await axios.post("/articulo/actualizarPrecios", {
          id: articuloId,
          precio_costo_paquete: precioPaquete,
          precio_costo_unidad: precioUnidad,
          precio_uno: me.precio_uno,
          precio_dos: me.precio_dos,
          precio_tres: me.precio_tres,
          precio_cuatro: me.precio_cuatro,
        });
      } catch (error) {
        console.error(error);
      }
    },

    listarPrecio() {
      let me = this;
      var url = "/precios";
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.precios = respuesta.precio.data;
        })
        .catch(function (error) { });
    },

    async registrarIngreso() {
      if (this.validarIngreso()) {
        swal("Error", this.errorMostrarMsjIngreso.join("\n"), "error");
        return;
      }

      if (this.arrayDetalle.length === 0) {
        swal("Error", "Debe agregar productos", "error");
        return;
      }

      try {
        this.isLoading = true;

        const response = await axios.post("/ingreso/registrar", {
          idproveedor: this.idproveedor,
          idalmacen: this.AlmacenSeleccionado,
          tipo_comprobante: this.tipo_comprobante,
          serie_comprobante: this.serie_comprobante,
          num_comprobante: this.num_comprobante,
          impuesto: this.impuesto,
          total: this.total,
          data: this.arrayDetalle
        });

        if (!response.data.success) {
          swal("Error", response.data.message, "error");
          return;
        }

        await this.listarIngreso(1, "", "num_comprobante");

        swal({
          type: "success",
          title: "Éxito",
          text: "Compra registrada correctamente"
        });

        this.$emit("cerrar");

      } catch (error) {
        swal("Error", "No se pudo registrar la compra", "error");
      } finally {
        this.isLoading = false;
      }
    },

    validarIngreso() {
      this.errorIngreso = 0;
      this.errorMostrarMsjIngreso = [];
      if (
        !this.AlmacenSeleccionado ||
        this.AlmacenSeleccionado === 0 ||
        this.AlmacenSeleccionado === "0"
      ) {
        this.errorMostrarMsjIngreso.push("Seleccione un almacén");
      }
      if (this.tipo_comprobante === "0" || !this.tipo_comprobante) {
        this.errorMostrarMsjIngreso.push("Seleccione el tipo de comprobante");
      }
      if (!this.num_comprobante || this.num_comprobante.trim() === "") {
        this.errorMostrarMsjIngreso.push("Ingrese el número de comprobante");
      }
      if (!this.impuesto) {
        this.errorMostrarMsjIngreso.push("Ingrese el impuesto de compra");
      }
      if (this.arrayDetalle.length <= 0) {
        this.errorMostrarMsjIngreso.push(
          "Agregue al menos un detalle de producto"
        );
      }
      if (this.errorMostrarMsjIngreso.length) this.errorIngreso = 1;
      return this.errorIngreso;
    },

    agregarDetalle() {
      let me = this;
      if (
        me.arrayArticuloSeleccionado.length == 0 ||
        me.cantidad == 0 ||
        me.AlmacenSeleccionado == 0
      ) {
      } else if (me.fechavencimiento == null) {
        swal({
          type: "error",
          title: "Error...",
          text: "No se ingresó fecha de vencimiento!",
        });
      } else {
        if (me.encuentra(me.arrayArticuloSeleccionado.id)) {
          swal({
            type: "error",
            title: "Error...",
            text: "Este Artículo ya se encuentra agregado!",
          });
        } else {
          if (me.tipoUnidadSeleccionada == "Paquetes") {
            me.arrayDetalle.push({
              idarticulo: producto.id,
              articulo: producto.nombre,
              codigo: producto.codigo,
              // Precios
              precio: parseFloat(producto.precio_costo_unid),
              precio_paquete: parseFloat(producto.precio_costo_paq),
              unidad_x_paquete: parseInt(producto.unidad_envase),

              fecha_vencimiento: me.fechavencimiento || me.fechaPorDefecto,
              cantidad: 1,

              es_paquete: false,
            });
          } else {
            me.arrayDetalle.push({
              idarticulo: me.arrayArticuloSeleccionado.id,
              idalmacen: me.AlmacenSeleccionado,
              codigo: me.arrayArticuloSeleccionado.codigo,
              articulo: me.arrayArticuloSeleccionado.nombre,
              precio: me.arrayArticuloSeleccionado.precio_costo_unid,
              precio_paquete: me.arrayArticuloSeleccionado.precio_costo_paq,
              fecha_vencimiento: me.fechavencimiento,
              unidad_x_paquete: me.arrayArticuloSeleccionado.unidad_envase,
              cantidad: me.cantidad,
            });
          }
          swal({
            type: "success",
            title: "Éxito!",
            text: "Artículo agregado correctamente!",
          });
          me.arrayArticuloSeleccionadoLocal = {};
          me.codigo = "";
          me.idarticulo = 0;
          me.articulo = "";
          me.cantidad = 1;
          me.fechavencimiento = null;
          me.precio = 0;
        }
      }
    },

    agregarDetalleModal(producto) {
      let me = this;
      // Evitar duplicados
      if (me.encuentra(producto.id)) {
        swal({
          type: "error",
          title: "Error...",
          text: "Este Artículo ya se encuentra agregado!",
        });
        return;
      }
      me.arrayDetalle.push({
        idarticulo: producto.id,
        articulo: producto.nombre,
        codigo: producto.codigo,
        //Precios
        precio: parseFloat(producto.precio_costo_unid),
        precio_paquete: parseFloat(producto.precio_costo_paq),
        unidad_x_paquete: parseInt(producto.unidad_envase),

        fecha_vencimiento: me.fechavencimiento || me.fechaPorDefecto,
        cantidad: 1,

        es_paquete: false,
      });
      console.log('arrayDetalle:', me.arrayDetalle);
      console.log('producto:', producto);
    },

    async obtenerComprobantes() {
      try {
        const response = await axios.get('/ingresos/comprobantes-dim');
        this.listaComprobantes = response.data;
      } catch (error) {
        console.error(error);
      }
    },
    async buscarPorComprobante() {
      this.isLoading = true;

      await this.listarArticulo(1, this.buscar, 'comprobante');

      this.isLoading = false;
    },
    onPrecioPaqChange(e) {
      this.datosFormulario.precio_costo_paq = e.value;

      // limpiamos o activamos error
      this.validarCampo("precio_costo_paq");
    },
    precioError(precio) {
      // Solo mostrar error si se intentó enviar el formulario
      if (!this.intentoEnviar) return false;
      return (
        precio.valor === null ||
        precio.valor === undefined ||
        precio.valor === ''
      );
    },
    mostrarDescripcion(valor) {
      if (!valor || valor === "null" || valor === null) {
        return {
          texto: 'No registrado',
          clase: 'no-registrado'
        };
      }
      return { texto: valor, clase: '' };
    },
    evitarReformateo(event, callback) {
      const valor = event.target.value;

      // Si el valor está en un estado intermedio como "10," o "10,."
      if (valor.endsWith(',') || valor.endsWith(',.')) {
        return; // ← No ejecutar validaciones
      }

      // Ejecutar la validación original
      callback();
    },
    convertirPuntoComa(event) {
      if (event.key === '.') {
        event.preventDefault();
        const input = event.target;

        // Si ya tiene coma, solo mover cursor a la parte decimal
        if (input.value.includes(',')) {
          const pos = input.value.indexOf(',') + 1;
          input.setSelectionRange(pos, pos);
          return;
        }

        const start = input.selectionStart;
        const end = input.selectionEnd;

        // Insertar una sola coma SI no existe
        const nuevoValor =
          input.value.substring(0, start) + ',' + input.value.substring(end);

        input.value = nuevoValor;

        // Colocar cursor después de la coma
        input.setSelectionRange(start + 1, start + 1);

        // Actualizar modelo
        input.dispatchEvent(new Event("input"));
      }
    },

    async guardarDescuento() {
      try {
        const { descuento, fecha_venc_descuento, id } = this.datosFormulario;

        // ⚠️ VALIDACIONES (TOAST DE ADVERTENCIA)
        if (descuento <= 0) {
          this.$toast.add({
            severity: "warn",
            summary: "Atención",
            detail: "El descuento debe ser mayor a 0%.",
            life: 3000,
          });
          return;
        }

        if (!fecha_venc_descuento) {
          this.$toast.add({
            severity: "warn",
            summary: "Atención",
            detail: "Debe seleccionar una fecha de vencimiento.",
            life: 3000,
          });
          return;
        }

        const payload = { id, descuento, fecha_venc_descuento };

        await axios.put(`/articulo/actualizar-descuento/${id}`, payload);

        // 🟢 TOAST DE ÉXITO
        this.$toast.add({
          severity: "success",
          summary: "Descuento guardado",
          detail: "El descuento fue aplicado correctamente.",
          life: 2000,
        });

        // 🔄 Refrescar tabla
        this.listarArticulo(
          this.pagination.current_page,
          this.buscar || "",
          this.criterio || "nombre"
        );

      } catch (error) {
        console.error(error);

        // 🔴 TOAST DE ERROR
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo guardar el descuento.",
          life: 3500,
        });
      }
    },

    async limpiarDescuento() {
      try {
        this.limpiandoDescuento = true;

        const payload = {
          id: this.datosFormulario.id,
          descuento: 0,
          fecha_venc_descuento: null,
        };

        await axios.put(`/articulo/actualizar-descuento/${payload.id}`, payload);

        this.datosFormulario.descuento = 0;
        this.datosFormulario.fecha_venc_descuento = null;

        // 🔵 TOAST DE ÉXITO
        this.$toast.add({
          severity: "success",
          summary: "Descuento eliminado",
          detail: "El descuento se eliminó correctamente.",
          life: 2500,
        });

        this.limpiandoDescuento = false;

        this.listarArticulo(
          this.pagination.current_page,
          this.buscar || "",
          this.criterio || "nombre"
        );

      } catch (error) {
        console.error("❌ Error al limpiar descuento:", error);
        this.limpiandoDescuento = false;

        // 🔴 TOAST DE ERROR
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo eliminar el descuento.",
          life: 4000,
        });
      }
    },

    calcularVentasDesdeCosto() {
      if (this.datosFormulario.precio_costo_unid && this.datosFormulario.precio_costo_unid > 0) {
        const costo = parseFloat(this.datosFormulario.precio_costo_unid);

        // 🔹 Tomamos los porcentajes actuales (ya cargados)
        const venta1 = this.precios.find((p) => p.id === 1);
        const venta2 = this.precios.find((p) => p.id === 2);

        // 🔹 Calculamos los precios de venta según el porcentaje
        if (venta1) {
          venta1.valor = parseFloat((costo + (costo * venta1.porcentaje / 100)).toFixed(2));
        }
        if (venta2) {
          venta2.valor = parseFloat((costo + (costo * venta2.porcentaje / 100)).toFixed(2));
        }
      }
    },
    

    calcularPrecio(porcentaje) {
      const costo = Number(this.datosFormulario.precio_costo_unid) || 0;
      const porc = Number(porcentaje);

      if (isNaN(porc) || porc < 0) return costo; // 👈 si es inválido o negativo, retorna el costo
      return parseFloat((costo + (costo * porc / 100)).toFixed(2));
    },

    onPorcentajeFocus(precio, event) {
      if (!precio.porcentaje || precio.porcentaje === 0) {
        precio.porcentaje = null;
      }
      else {
        if (event && event.target) {
          event.target.select();
        }
      }
    },

    onPorcentajeInput(precio) {
      precio.valor = this.calcularPrecio(precio.porcentaje);
      const costo = Number(this.datosFormulario.precio_costo_unid) || 0;
      precio.errorVenta = precio.valor < costo;
      this.sincronizarPrecios(precio);
    },

    onPrecioChange(precio) {
      const costo = Number(this.datosFormulario.precio_costo_unid) || 0;
      precio.porcentaje = this.calcularPorcentaje(precio.valor);
      precio.errorVenta = precio.valor < costo;
      this.sincronizarPrecios(precio);
    },

    sincronizarPrecios(precio) {
      const valorNumerico = Number(precio.valor);

      switch (precio.id) {
        case 1:
          this.precio_uno = valorNumerico;
          this.datosFormulario.precio_uno = valorNumerico;
          break;
        case 2:
          this.precio_dos = valorNumerico;
          this.datosFormulario.precio_dos = valorNumerico;
          break;
        case 3:
          this.precio_tres = valorNumerico;
          this.datosFormulario.precio_tres = valorNumerico;
          break;
        case 4:
          this.precio_cuatro = valorNumerico;
          this.datosFormulario.precio_cuatro = valorNumerico;
          break;
      }
    },

    onCostoChange() {
      this.validarCampo('precio_costo_unid');

      const costo = parseFloat(this.datosFormulario.precio_costo_unid || 0);
      if (costo <= 0) return;

      const venta1 = this.precios.find(p => p.id === 1); // Unidad
      const venta2 = this.precios.find(p => p.id === 2); // Docena
      const venta3 = this.precios.find(p => p.id === 3); // Paquete 

      // --- 1. RECALCULAR COSTOS ---

      // Unidad
      if (venta1 && venta1.porcentaje > 0 && (!venta1.valor || venta1.valor === 0)) {
        venta1.valor = parseFloat((costo + (costo * venta1.porcentaje / 100)).toFixed(2));
      }
      // Docena
      if (venta2 && venta2.porcentaje > 0 && (!venta2.valor || venta2.valor === 0)) {
        venta2.valor = parseFloat((costo + (costo * venta2.porcentaje / 100)).toFixed(2));
      }
      // Paquete 
      if (venta3 && venta3.porcentaje > 0 && (!venta3.valor || venta3.valor === 0)) {
        venta3.valor = parseFloat((costo + (costo * venta3.porcentaje / 100)).toFixed(2));
      }

      // --- 2. RECALCULAR PORCENTAJES ---

      // Unidad
      if (venta1 && venta1.valor > 0) {
        let porc1 = ((venta1.valor - costo) / costo) * 100;
        venta1.porcentaje = parseFloat((porc1 < 0 ? 0 : porc1).toFixed(2));
        venta1.errorVenta = venta1.valor < costo;
      }

      // Docena
      if (venta2 && venta2.valor > 0) {
        let porc2 = ((venta2.valor - costo) / costo) * 100;
        venta2.porcentaje = parseFloat((porc2 < 0 ? 0 : porc2).toFixed(2));
        venta2.errorVenta = venta2.valor < costo;
      }

      // Paquete 
      if (venta3 && venta3.valor > 0) {
        let porc3 = ((venta3.valor - costo) / costo) * 100;
        venta3.porcentaje = parseFloat((porc3 < 0 ? 0 : porc3).toFixed(2));
        venta3.errorVenta = venta3.valor < costo;
      }

      console.log("✅ Costo:", costo, "| UNIDAD:", venta1.valor, "| DOCENA:", venta2.valor, "| PAQUETE:", venta3.valor);
    },
    mostrarDetalles(articulo) {
      this.articuloSeleccionado = articulo;
      this.dialogDetallesVisible = true;
    },
    cerrarDialogDetalles() {
      this.dialogDetallesVisible = false;
      this.articuloSeleccionado = null;
    },
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    handleDateChange(date) {
      // Verifica si date es un objeto Date válido
      if (date instanceof Date && !isNaN(date)) {
        this.fechaVencimientoAlmacen = this.formatDateToYMD(date);
        console.log("fecha ", this.fechaVencimientoAlmacen);
      } else {
        console.error("La fecha seleccionada no es válida:", date);
      }
    },
    formatDateToYMD(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0"); // Los meses son indexados desde 0
      const day = String(date.getDate()).padStart(2, "0");
      return `${year}-${month}-${day}`;
    },
    closeDialog() {
      this.dialogVisible = false;
    },
    obtenerFotografia(event) {
      let file = event.target.files[0];

      let fileType = file.type;
      // Validar si el archivo es una imagen en formato PNG o JPG
      if (fileType !== "image/png" && fileType !== "image/jpeg") {
        alert("Por favor, seleccione una imagen en formato PNG o JPG.");
        return;
      }

      this.fotografia = file;
      this.mostrarFoto(file);
    },
    mostrarFoto(file) {
      let reader = new FileReader();
      reader.onload = (file) => {
        this.fotoMuestra = file.target.result;
      };
      reader.readAsDataURL(file);
    },
    manejarProveedorSeleccionado(proveedor) {
      this.proveedorSeleccionado = proveedor;
      this.validarCampo("idproveedor");
    },
    manejarLineaSeleccionado(linea) {
      this.lineaSeleccionado = linea;
      this.validarCampo("idcategoria");
    },
    manejarMarcaSeleccionado(marca) {
      this.marcaSeleccionado = marca;
      this.validarCampo("idmarca");
    },
    manejarIndustriaSeleecionado(industria) {
      this.industriaSeleccionado = industria;
      this.validarCampo("idindustria");
    },
    manejarGrupoSeleccionado(grupo) {
      this.grupoSeleccionado = grupo;
      this.validarCampo("idgrupo");
    },
    manejarMedidaSeleccionado(medida) {
      this.medidaSeleccionado = medida;
      this.validarCampo("idmedida");
    },
    manejarAlmacenSeleccionado(almacen) {
      this.almacenSeleccionado = almacen;
      if (this.agregarStock == true) {
        this.validarCampoInventario("AlmacenSeleccionado");
      }
    },

    validarPermisoImportar() {
      const rolUsuario = parseInt(this.idrol);

      if (rolUsuario === 1 || rolUsuario === 4) {
        this.abrirDialogos('Importar');
      } else {
        this.$toast.add({
          severity: 'error',
          summary: 'Acceso Denegado',
          detail: 'Esta acción solo está permitida para Administradores.',
          life: 3000
        });
      }
    },

    abrirDialogos(dialogo) {
      switch (dialogo) {
        case "Proveedores":
          this.mostrarDialogoProveedores = true;
          break;
        case "Lineas":
          this.mostrarDialogoLineas = true;
          break;
        case "Marcas":
          this.mostrarDialogoMarcas = true;
          break;
        case "Industrias":
          this.mostrarDialogoIndustrias = true;
          break;
        case "Grupos":
          this.mostrarDialogoGrupos = true;
          break;
        case "Medidas":
          this.mostrarDialogoMedidas = true;
          break;
        case "Almacen":
          this.mostrarDialogoAlmacen = true;
          this.dialogVisible = false;
          break;
        case "Importar":
          this.mostrarDialogoImportar = true;
          break;
      }
      this.dialogVisible = false;
    },
    cerrarDialogos(dialogo) {
      switch (dialogo) {
        case "Proveedores":
          this.mostrarDialogoProveedores = false;
          this.dialogVisible = true;
          break;
        case "Lineas":
          this.mostrarDialogoLineas = false;
          this.dialogVisible = true;
          break;
        case "Marcas":
          this.mostrarDialogoMarcas = false;
          this.dialogVisible = true;
          break;
        case "Industrias":
          this.mostrarDialogoIndustrias = false;
          this.dialogVisible = true;
          break;
        case "Grupos":
          this.mostrarDialogoGrupos = false;
          this.dialogVisible = true;
          break;
        case "Medidas":
          this.mostrarDialogoMedidas = false;
          this.dialogVisible = true;
          break;
        case "Almacen":
          this.mostrarDialogoAlmacen = false;
          this.dialogVisible = true;
          break;
        case "Importar":
          this.mostrarDialogoImportar = false;
          this.listarArticulo(1, this.buscar);
          break;
      }
    },
    listarPrecio() {
      // Los nombres de precios están definidos en data() y no se sobrescriben
      // Esta función ya no es necesaria para cargar nombres desde el backend
      // Los porcentajes se cargan en cargarPreciosGlobales() cuando se abre el modal
    },
    async buscarArticulo() {
      try {
        if (this.searchTimeout) {
          clearTimeout(this.searchTimeout);
        }

        this.searchTimeout = setTimeout(async () => {
          this.isLoading = true;

          let criterio = '';

          // 🔥 PRIORIDAD: comprobante
          if (this.comprobanteSeleccionado) {
            criterio = 'comprobante';
          }

          await this.listarArticulo(
            1,
            this.buscar,
            criterio
          );

          setTimeout(() => {
            this.isLoading = false;
          }, 500);

        }, 300);

      } catch (error) {
        console.error("Error en la búsqueda:", error);
        this.isLoading = false;
      }
    },
    asignarCamposPrecios() {
      const precioUnitario = this.precios.find(p => p.id === 1);
      const precioDocena = this.precios.find(p => p.id === 2);
      const precioPaquete = this.precios.find(p => p.id === 3);
      const precioEspecial = this.precios.find(p => p.id === 4);

      this.datosFormulario.precio_uno = this.convertDolar(precioUnitario ? precioUnitario.valor : 0);
      this.datosFormulario.precio_dos = this.convertDolar(precioDocena ? precioDocena.valor : 0);

      this.datosFormulario.precio_tres = this.convertDolar(precioPaquete ? precioPaquete.valor : 0);

      this.datosFormulario.precio_cuatro = this.convertDolar(precioEspecial ? precioEspecial.valor : 0);
      this.datosFormulario.precio_costo_unid = this.convertDolar(this.datosFormulario.precio_costo_unid);

      if (!this.datosFormulario.precio_costo_paq || this.datosFormulario.precio_costo_paq == 0) {
        this.datosFormulario.precio_costo_paq = this.datosFormulario.precio_costo_unid * this.datosFormulario.unidad_envase;
      } else {
        this.datosFormulario.precio_costo_paq = this.convertDolar(this.datosFormulario.precio_costo_paq);
      }

      this.datosFormulario.precio_venta = this.datosFormulario.precio_uno; // Normalmente el precio venta base es el precio 1
      this.datosFormulario.costo_compra = this.convertDolar(this.datosFormulario.costo_compra);
    },
    asignarCamposInventario() {
      this.datosFormularioInventario.AlmacenSeleccionado = this.almacenSeleccionado.id;
      this.datosFormularioInventario.unidadStock = this.unidadStock;
      this.datosFormularioInventario.fechaVencimientoAlmacen = this.fechaVencimientoAlmacen;
    },
    convertDolar(precio) {
      return precio / parseFloat(this.monedaPrincipal);
    },
    asignarCampos() {
      this.datosFormulario.idcategoria = this.lineaSeleccionado.id;
      this.datosFormulario.idproveedor = this.proveedorSeleccionado.id;
      this.datosFormulario.idmedida = this.medidaSeleccionado ? this.medidaSeleccionado.id : 1;

      if (this.fechaVencimientoSeleccion == false) {
        this.datosFormulario.fechaVencimientoSeleccion = "0";
      } else {
        this.datosFormulario.fechaVencimientoSeleccion = "1";
      }
    },
    async validarCampo(campo) {
      this.asignarCampos();
      try {
        await esquemaArticulos.validateAt(campo, this.datosFormulario);
        this.errores[campo] = null;
      } catch (error) {
        this.errores[campo] = error.message;
      }
    },
    async validarCampoInventario(campo) {
      this.asignarCamposInventario();
      try {
        await esquemaInventario.validateAt(
          campo,
          this.datosFormularioInventario
        );
        this.erroresinventario[campo] = null;
      } catch (error) {
        this.erroresinventario[campo] = error.message;
      }
    },
    validarDescuento() {
      if (this.datosFormulario.descuento === null || this.datosFormulario.descuento === '' || this.datosFormulario.descuento === undefined) {
        this.datosFormulario.descuento = null;
        return;
      }
      const valor = Number(this.datosFormulario.descuento);
      if (isNaN(valor) || valor === 0) {
        this.datosFormulario.descuento = null;
      } else if (valor < 0) {
        this.datosFormulario.descuento = 0;
      } else if (valor > 100) {
        this.datosFormulario.descuento = 100;
      }
    },
    async enviarFormulario() {
      try {
        this.isLoading = true; // Activar loading
        this.intentoEnviar = true; // Marcar que se intentó enviar
        this.asignarCampos();
        this.asignarCamposPrecios();

        if (this.agregarStock === true) {
          this.asignarCamposInventario();
        }

        let validacionExitosa = true;
        let validacionInventarioExitosa = true;
        let listaErrores = [];

        try {
          await esquemaArticulos.validate(this.datosFormulario, {
            abortEarly: false,
          });
        } catch (error) {
          validacionExitosa = false;
          const erroresValidacion = {};
          error.inner.forEach((e) => {
            erroresValidacion[e.path] = e.message;
            listaErrores.push(e.message);
          });
          this.errores = erroresValidacion;
        }

        if (this.tipoAccion != 2 && this.agregarStock == true) {
          try {
            await esquemaInventario.validate(this.datosFormularioInventario, {
              abortEarly: false,
            });
          } catch (error) {
            validacionInventarioExitosa = false;
            const erroresValidacionInventario = {};
            error.inner.forEach((e) => {
              erroresValidacionInventario[e.path] = e.message;
              listaErrores.push(e.message);
            });
            this.erroresinventario = erroresValidacionInventario;
          }
        }

        // Mostrar Swal si hay errores de validación
        if (!validacionExitosa || (this.agregarStock && !validacionInventarioExitosa)) {
          const mensajeHtml = `
            <div style="text-align: left; max-height: 300px; overflow-y: auto;">
              <p style="margin-bottom: 10px; font-weight: 500;">Por favor complete los siguientes campos:</p>
              <ul style="padding-left: 20px; margin: 0;">
                ${listaErrores.map(err => `<li style="margin-bottom: 5px; color: #dc3545;">${err}</li>`).join('')}
              </ul>
            </div>
          `;

          Swal.fire({
            icon: 'warning',
            title: 'Campos incompletos',
            html: mensajeHtml,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#3085d6',
          });

          this.isLoading = false;
          return;
        }

        if (this.tipoAccion == 2) {
          await this.actualizarArticulo(this.datosFormulario);
        } else if (validacionExitosa && (this.agregarStock ? validacionInventarioExitosa : true)) {
          if (this.tipo_stock == "paquetes") {
            this.datosFormulario.stock =
              this.datosFormulario.unidad_envase * this.datosFormulario.stock;
          }
          await this.registrarArticulo(this.datosFormulario);
        }
      } catch (error) {
        console.error("Error en el envío del formulario:", error);
        this.toastError("Hubo un error al procesar el formulario");
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },
    obtenerConfiguracionTrabajo() {
      // Utiliza Axios para realizar la solicitud al backend
      axios
        .get("/configuracion")
        .then((response) => {
          console.log(response);
        })
        .catch((error) => {
          console.error("Error al obtener configuración de trabajo:", error);
        });
    },
    datosConfiguracion() {
      let me = this;
      var url = "/configuracion";

      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.mostrarSaldosStock =
            respuesta.configuracionTrabajo.mostrarSaldosStock;
          me.mostrarCostos = respuesta.configuracionTrabajo.mostrarCostos;
          me.mostrarProveedores =
            respuesta.configuracionTrabajo.mostrarProveedores;

          me.monedaPrincipal = [
            respuesta.configuracionTrabajo.valor_moneda_principal,
            respuesta.configuracionTrabajo.simbolo_moneda_principal,
          ];
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    calculatePages: function (paginationObject, offset) {
      if (!paginationObject.to) {
        return [];
      }

      var from = paginationObject.current_page - offset;
      if (from < 1) {
        from = 1;
      }

      var to = from + offset * 2;
      if (to >= paginationObject.last_page) {
        to = paginationObject.last_page;
      }

      var pagesArray = [];
      while (from <= to) {
        pagesArray.push(from);
        from++;
      }
      return pagesArray;
    },
    calcularPrecioCostoUnid() {
      if (
        this.datosFormulario.unidad_envase &&
        this.datosFormulario.precio_costo_paq
      ) {
        this.datosFormulario.precio_costo_unid =
          this.datosFormulario.precio_costo_paq /
          this.datosFormulario.unidad_envase;
        this.datosFormulario.precio_costo_unidVacio = false;
        this.validarCampo("precio_costo_unid");
      }
    },
    calcularPrecioCostoPaq() {
      if (
        this.datosFormulario.unidad_envase &&
        this.datosFormulario.precio_costo_unid
      ) {
        this.datosFormulario.precio_costo_paq =
          this.datosFormulario.precio_costo_unid *
          this.datosFormulario.unidad_envase;
        this.datosFormulario.precio_costo_paqVacio = false;
        this.validarCampo("precio_costo_paq");
      }
    },
    calcularPrecioP(precio_costo_unid, porcentage) {
      const margenG = precio_costo_unid * (porcentage / 100);
      const precioP = parseFloat(precio_costo_unid) + parseFloat(margenG);
      return precioP.toFixed(2);
    },
    cambiarPagina(page, buscar, criterio) {
      // Actualiza la página actual
      this.pagination.current_page = page;
      // Envía la petición para visualizar la data de esa página
      this.listarArticulo(page, buscar, criterio);
    },
    onPageChange(event) {
      const page = Math.floor(event.first / this.pagination.per_page) + 1;
      this.cambiarPagina(page, this.buscar, this.criterio);
    },
    async descargarArchivoReporte(url, nombreArchivo) {
      try {
        Swal.fire({
          title: 'Generando reporte...',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        const response = await axios.get(url, {
          responseType: 'blob'
        });

        const blob = new Blob([response.data]);
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = nombreArchivo;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        Swal.close();
      } catch (error) {
        Swal.close();
        Swal.fire('ERROR AL GENERAR EL REPORTE', '', 'error');
      }
    },
    async descargarReporteExcel() {
      if (!this.validarPermisoVendedor()) return;

      const fecha = new Date().toISOString().slice(0, 10);
      const buscarQuery = this.buscar ? `?buscar=${encodeURIComponent(this.buscar)}` : '';
      const url = `/articulo/reporteExcel${buscarQuery}`;
      const nombreArchivo = `MisProductos_${fecha}.xlsx`;

      this.isLoading = true;
      try {
        const response = await axios.get(url, {
          responseType: 'blob',
          timeout: 600000 // 10 minutos
        });

        // Verificar si la respuesta es realmente un Excel y no un error JSON
        const contentType = response.headers['content-type'];
        if (contentType && contentType.includes('application/json')) {
          // El blob contiene JSON de error
          const text = await new Response(response.data).text();
          const error = JSON.parse(text);
          this.$toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.message || 'No se pudo generar el reporte Excel',
            life: 5000
          });
          this.isLoading = false;
          return;
        }

        const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = nombreArchivo;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(link.href);

        this.$toast.add({ severity: 'success', summary: 'Éxito', detail: 'Reporte Excel descargado', life: 3000 });
      } catch (error) {
        console.error('Error al descargar Excel:', error);

        // Detectar errores de permiso específicamente
        if (error.response && error.response.status === 403) {
          this.$toast.add({
            severity: 'warn',
            summary: 'Acceso Denegado',
            detail: 'Esta acción solo está permitida para Administradores.',
            life: 5000
          });
        } else if (error.response && error.response.data) {
          // Intenta obtener mensaje de error del servidor
          try {
            const text = await new Response(error.response.data).text();
            const errorData = JSON.parse(text);
            this.$toast.add({
              severity: 'error',
              summary: 'Error',
              detail: errorData.message || 'No se pudo generar el reporte Excel',
              life: 5000
            });
          } catch (error) {
            this.$toast.add({
              severity: 'error',
              summary: 'Error',
              detail: 'No se pudo generar el reporte Excel',
              life: 5000
            });
          }
        } else {
          this.$toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.message || 'No se pudo generar el reporte Excel',
            life: 5000
          });
        }
      } finally {
        this.isLoading = false;
      }
    },

    async descargarReportePDF() {
      if (!this.validarPermisoVendedor()) return;

      const fecha = new Date().toISOString().slice(0, 10);
      const buscarQuery = this.buscar ? `?buscar=${encodeURIComponent(this.buscar)}` : '';
      const url = `/articulo/reportePDF${buscarQuery}`;
      const nombreArchivo = `MisProductos_${fecha}.pdf`;

      this.isLoading = true;
      try {
        const response = await axios.get(url, {
          responseType: 'blob',
          timeout: 600000
        });

        // Verificar si la respuesta es realmente un PDF y no un error JSON
        const contentType = response.headers['content-type'];
        if (contentType && contentType.includes('application/json')) {
          // El blob contiene JSON de error
          const text = await new Response(response.data).text();
          const error = JSON.parse(text);
          this.$toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.message || 'No se pudo generar el reporte PDF',
            life: 5000
          });
          this.isLoading = false;
          return;
        }

        const blob = new Blob([response.data], { type: 'application/pdf' });
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = nombreArchivo;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(link.href);

        this.$toast.add({ severity: 'success', summary: 'Éxito', detail: 'Reporte PDF descargado', life: 3000 });
      } catch (error) {
        console.error('Error al descargar PDF:', error);

        // Detectar errores de permiso específicamente
        if (error.response && error.response.status === 403) {
          this.$toast.add({
            severity: 'warn',
            summary: 'Acceso Denegado',
            detail: 'Esta acción solo está permitida para Administradores.',
            life: 5000
          });
        } else if (error.response && error.response.data) {
          // Intenta obtener mensaje de error del servidor
          try {
            const text = await new Response(error.response.data).text();
            const errorData = JSON.parse(text);
            this.$toast.add({
              severity: 'error',
              summary: 'Error',
              detail: errorData.message || 'No se pudo generar el reporte PDF',
              life: 5000
            });
          } catch (error) {
            this.$toast.add({
              severity: 'error',
              summary: 'Error',
              detail: 'No se pudo generar el reporte PDF',
              life: 5000
            });
          }
        } else {
          this.$toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.message || 'No se pudo generar el reporte PDF',
            life: 5000
          });
        }
      } finally {
        this.isLoading = false;
      }
    },
    cambiarPagina(page, buscar, criterio) {
      let me = this;
      me.pagination.current_page = page;
      me.listarArticulo(page, buscar, criterio);
    },
   
registrarArticulo(data) {
  let me = this;
  var formulario = new FormData();

  for (var key in data) {
    if (data.hasOwnProperty(key)) {
      formulario.append(key, data[key]);
    }
  }

  axios
    .post("/articulo/registrar", formulario, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    })
    .then(function (response) {

      var respuesta = response.data;
      me.idarticulo = respuesta.idArticulo;

      me.$toast.add({
        severity: "success",
        summary: "Registrado",
        detail: "El producto fue registrado correctamente.",
        life: 2500,
      });

      me.cerrarModal();

      // 🔥 COMPATIBLE VUE 2 (SIN OPTIONAL CHAINING)
      var criterio = '';
      var num = '';

      if (me.comprobanteSeleccionado !== null && me.comprobanteSeleccionado !== '') {
        criterio = 'comprobante';
        num = me.comprobanteSeleccionado;
      }

      me.listarArticulo(1, me.buscar, criterio, num);

      // 🔵 Inventario
      if (me.agregarStock === true) {
        let arrayArticulos = [
          {
            idarticulo: me.idarticulo,
            idalmacen: me.almacenSeleccionado.id,
            cantidad: me.unidadStock,
            fecha_vencimiento: me.fechaVencimientoAlmacen,
          },
        ];

        return axios.post("/inventarios/registrar", {
          inventarios: arrayArticulos,
        });
      }

    })
    .then(function (response) {
      if (response) {
        console.log("Inventario registrado:", response.data);
      }
    })
    .catch(function (error) {

      if (
        error.response &&
        error.response.status === 409 &&
        error.response.data &&
        error.response.data.message
      ) {
        me.$toast.add({
          severity: "error",
          summary: "Error",
          detail: error.response.data.message,
          life: 3000,
        });
      } else {
        me.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "Hubo un error al registrar el producto o el inventario.",
          life: 3000,
        });
      }

      console.log(error);
    });
},

   actualizarArticulo(data) {
  var formulario = new FormData();
  let me = this;

  for (var key in data) {
    if (data.hasOwnProperty(key)) {
      formulario.append(key, data[key]);
    }
  }

  axios
    .post("/articulo/actualizar", formulario, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    })
    .then(function (response) {

      me.$toast.add({
        severity: "success",
        summary: "Actualizado",
        detail: "El producto fue actualizado correctamente.",
        life: 2500,
      });

      me.cerrarModal();

      // 🔥 IMPORTANTE: mantener filtro activo
      let criterio = me.comprobanteSeleccionado ? 'comprobante' : '';
      let num = me.comprobanteSeleccionado || '';

      me.listarArticulo(1, me.buscar, criterio, num);

    })
    .catch(function (error) {
      console.log(error);

      me.$toast.add({
        severity: "error",
        summary: "Error",
        detail: "No se pudo actualizar el producto.",
        life: 3000,
      });
    });
},

    async desactivarArticulo(id) {
  if (!this.validarPermisoVendedor()) return;

  try {
    const result = await Swal.fire({
      title: "¿Está seguro de ELIMINAR este producto?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#22c55e",
      cancelButtonColor: "#ef4444",
      confirmButtonText: "Aceptar!",
      cancelButtonText: "Cancelar",
      reverseButtons: true,
      customClass: {
        confirmButton: "swal2-confirm-articulonew",
        cancelButton: "swal2-cancel-articulonew",
      },
    });

    // ⚠️ VUE 2 usa result.value
    if (result.value) {

      this.isLoading = true;

      await axios.put("/articulo/desactivar", { id: id });

      // 🔥 MANTENER FILTRO (DIM o normal)
      var criterio = '';
      var num = '';

      if (this.comprobanteSeleccionado !== null && this.comprobanteSeleccionado !== '') {
        criterio = 'comprobante';
        num = this.comprobanteSeleccionado;
      }

      await this.listarArticulo(1, this.buscar, criterio, num);

      this.$toast.add({
        severity: "success",
        summary: "Eliminado",
        detail: "El producto fue eliminado correctamente.",
        life: 2500,
      });
    }

  } catch (error) {
    console.error("Error al desactivar:", error);

    this.$toast.add({
      severity: "error",
      summary: "Error",
      detail: "No se pudo eliminar el producto.",
      life: 3500,
    });

  } finally {
    this.isLoading = false;
  }
},

    activarArticulo(id) {
      if (!this.validarPermisoVendedor()) return;
      Swal.fire({
        title: "¿Está seguro de activar este producto?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#22c55e",
        cancelButtonColor: "#ef4444",
        confirmButtonText: "Aceptar!",
        cancelButtonText: "Cancelar",
        reverseButtons: true,
        customClass: {
          confirmButton: "swal2-confirm-articulonew",
          cancelButton: "swal2-cancel-articulonew",
        },
      }).then((result) => {
        if (result.value) {
          let me = this;

          axios
            .put("/articulo/activar", {
              id: id,
            })
            .then(function (response) {
              me.listarArticulo(1, me.buscar);
              me.toastSuccess("El registro ha sido activado con éxito.");
            })
            .catch(function (error) {
              console.log(error);
            });
        } else if (
          // Read more about handling dismissals
          result.dismiss === Swal.DismissReason.cancel
        ) {
        }
      });
    },
    validarArticulo() {
      this.errorArticulo = 0;
      this.errorMostrarMsjArticulo = [];
      if (!this.unidad_envase) this.errorMostrarMsjArticulo.push("");
      if (!this.codigo) this.errorMostrarMsjArticulo.push("");
      if (!this.nombre_producto) this.errorMostrarMsjArticulo.push("");
      if (!this.nombre_generico) this.errorMostrarMsjArticulo.push("");
      if (!this.precio_costo_unid) this.errorMostrarMsjArticulo.push("");
      if (!this.precio_costo_paq) this.errorMostrarMsjArticulo.push("");
      if (!this.descripcion) this.errorMostrarMsjArticulo.push("");
      if (!this.stock) this.errorMostrarMsjArticulo.push("");
      if (!this.precio_venta) this.errorMostrarMsjArticulo.push("");
      if (!this.costo_compra) this.errorMostrarMsjArticulo.push("");
      if (!this.fotografia) this.errorMostrarMsjArticulo.push("");

      if (this.errorMostrarMsjArticulo.length) this.errorArticulo = 1;

      return this.errorArticulo;
    },
    
    calcularPrecio(porcentaje) {
      const costo = Number(this.datosFormulario.precio_costo_unid) || 0;
      const porc = Number(porcentaje);
      if (isNaN(porc) || porc < 0) return costo;

      const precioVenta = costo + (costo * porc / 100);
      return parseFloat(precioVenta.toFixed(2));
    },
  },
};
</script>

<style scoped>
.step-content {
  margin-top: 0.2rem;
  padding-top: 0;
}

>>>.p-panel .p-panel-content {
  padding-top: 0.5rem !important;
}

.linear-stepper {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 2px 0;
  position: relative;
}

.step-container {
  display: flex;
  align-items: center;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin: 0 10px;
  opacity: 0.5;
  position: relative;
}

.step.active,
.step.completed {
  opacity: 1;
}

.step-number {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background-color: #ccc;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 18px;
  z-index: 1;
}

.step.active .step-number {
  background-color: #007bff;
}

.step.completed .step-number {
  background-color: #34bc9b;
}

.step-line {
  height: 3px;
  width: 40px;
  background-color: #ccc;
  transition: background-color 0.3s;
  z-index: 0;
}

.step.completed+.step-line {
  background-color: #34bc9b;
}

.step.active+.step-line {
  background-color: #007bff;
}

.step-name {
  margin-top: 10px;
}

/* Estilos del loader */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  background-color: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(5px);
  padding: 30px;
  border-radius: 15px;
}

.spinner {
  width: 80px;
  height: 80px;
  border: 4px solid rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  border-top: 4px solid rgba(255, 255, 255, 0.9);
  animation: spin 1s linear infinite;
}

.loading-text {
  margin-top: 20px;
  color: rgba(255, 255, 255, 0.9);
  letter-spacing: 3px;
  font-size: 14px;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

.obligatorio-rojo {
  background-color: #c53e3e;
  font-size: 0.6em;
}

/* Panel Content Spacing */
>>>.p-panel .p-panel-content {
  padding: 1rem;
}

>>>.p-panel .p-panel-header {
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
}

>>>.p-panel .p-panel-header .p-panel-title {
  font-weight: 600;
}

.header-flex {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.header-title-icon {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.compact-stepper.linear-stepper {
  margin: 0;
  min-width: 120px;
  max-width: 180px;
  justify-content: flex-end;
}

.compact-stepper .step {
  margin: 0 2px;
}

.compact-stepper .step-number {
  width: 20px;
  height: 20px;
  font-size: 12px;
}

.compact-stepper .step-name {
  font-size: 0.7em;
  margin-top: 2px;
}

.compact-stepper .step-line {
  width: 20px;
  height: 2px;
}

.almacen-busqueda-flex {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
}

.almacen-select-container {
  min-width: 220px;
  max-width: 350px;
  flex: 1 1 220px;
}

.almacen-dropdown {
  min-width: 180px;
  max-width: 320px;
  width: 100%;
}

.buscador-container {
  flex: 0 0 auto;
  display: flex;
  align-items: flex-end;
  justify-content: flex-start;
  margin-left: 0;
  margin-bottom: 0;
  /* Eliminar espacio extra abajo */
}

.buscador-input {
  width: 100%;
  max-width: 500px;
  min-width: 250px;
  padding: 0.35rem 0.75rem;
  font-size: 1rem;
  border-radius: 6px;
  box-sizing: border-box;
  margin-bottom: 0 !important;
  /* Eliminar margen inferior */
}

/* Eliminar margen superior de la tabla si existe */
.modal-body {
  margin-top: 0 !important;
  padding-top: 0 !important;
}

@media (max-width: 900px) {
  .almacen-busqueda-flex {
    flex-direction: column;
    align-items: stretch;
    gap: 0 !important;
  }

  .almacen-select-container {
    min-width: 0 !important;
    max-width: 100% !important;
    width: 100% !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
  }

  .almacen-select-container label,
  .almacen-select-container .p-dropdown {
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
  }

  .buscador-container {
    width: 100%;
    margin-left: 0 !important;
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
    justify-content: flex-start;
  }

  .buscador-input {
    max-width: 100%;
    min-width: 120px;
    font-size: 0.95rem;
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
  }
}

@media (max-width: 600px) {
  .almacen-select-container {
    min-width: 0 !important;
    max-width: 100% !important;
    width: 100% !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
  }

  .almacen-select-container label,
  .almacen-select-container .p-dropdown {
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
  }

  .buscador-input {
    max-width: 100%;
    min-width: 100px;
    font-size: 0.9rem;
    padding: 0.3rem 0.5rem;
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
  }
}

/* === RESPONSIVE DESIGN igual que ArticuloNewView.vue y LineaNewView.vue === */
@media (max-width: 1024px) {
  .responsive-dialog>>>.p-dialog {
    margin: 0.5rem;
    max-height: 95vh;
  }

  >>>.p-datatable {
    font-size: 0.85rem;
  }

  .panel-header,
  .header-flex {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .almacen-busqueda-flex {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .almacen-select-container,
  .buscador-container {
    min-width: 0;
    max-width: 100%;
    width: 100%;
  }

  .buscador-input {
    min-width: 0;
    max-width: 100%;
    width: 100%;
  }
}

@media (max-width: 768px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog>>>.p-dialog {
    margin: 0.25rem;
    max-height: 98vh;
  }

  .responsive-dialog>>>.p-dialog-content {
    padding: 0.75rem;
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.75rem 1rem;
    font-size: 1rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.5rem 1rem;
    justify-content: flex-end;
  }

  .panel-header,
  .header-flex {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.3rem;
  }

  .header-title-icon {
    gap: 0.3rem;
  }

  .compact-stepper.linear-stepper {
    min-width: 80px;
    max-width: 120px;
  }

  .almacen-busqueda-flex {
    flex-direction: column;
    gap: 0.5rem;
    align-items: stretch;
  }

  .almacen-select-container,
  .buscador-container {
    min-width: 0;
    max-width: 100%;
    width: 100%;
  }

  .buscador-input {
    min-width: 0;
    max-width: 100%;
    width: 100%;
    font-size: 0.95rem;
    padding: 0.4rem 0.6rem;
  }

  >>>.p-panel .p-panel-content {
    padding: 0.5rem !important;
  }

  >>>.p-panel .p-panel-header {
    padding: 0.5rem 0.7rem;
    font-size: 1rem;
  }

  >>>.p-datatable {
    font-size: 0.8rem;
  }

  >>>.p-datatable .p-datatable-tbody>tr>td {
    padding: 0.4rem 0.3rem;
  }

  >>>.p-datatable .p-datatable-thead>tr>th {
    padding: 0.5rem 0.3rem;
    font-size: 0.75rem;
  }

  >>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }

  .step-buttons-row {
    flex-direction: row !important;
    gap: 0.5rem !important;
    align-items: center !important;
    justify-content: center !important;
  }

  .buttons.p-d-flex {
    flex-direction: column !important;
    gap: 0.5rem;
    align-items: stretch !important;
  }

  .p-d-flex.p-jc-end {
    flex-direction: column !important;
    align-items: flex-end !important;
    gap: 0.3rem;
  }
}

@media (max-width: 480px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog>>>.p-dialog {
    margin: 0.1rem;
    max-height: 99vh;
  }

  .responsive-dialog>>>.p-dialog-content {
    padding: 0.5rem;
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.5rem 0.75rem;
    font-size: 0.95rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.5rem 0.75rem;
    justify-content: flex-end;
  }

  .responsive-dialog>>>.p-dialog-footer .p-button {
    width: auto;
    margin-bottom: 0.25rem;
  }

  .panel-header,
  .header-flex {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.2rem;
  }

  .header-title-icon {
    gap: 0.2rem;
  }

  .compact-stepper.linear-stepper {
    min-width: 60px;
    max-width: 80px;
  }

  .almacen-busqueda-flex {
    flex-direction: column;
    gap: 0.3rem;
    align-items: stretch;
  }

  .almacen-select-container,
  .buscador-container {
    min-width: 0;
    max-width: 100%;
    width: 100%;
  }

  .buscador-input {
    min-width: 0;
    max-width: 100%;
    width: 100%;
    font-size: 0.9rem;
    padding: 0.3rem 0.4rem;
  }

  >>>.p-panel .p-panel-content {
    padding: 0.3rem !important;
  }

  >>>.p-panel .p-panel-header {
    padding: 0.3rem 0.5rem;
    font-size: 0.95rem;
  }

  >>>.p-datatable {
    font-size: 0.75rem;
  }

  >>>.p-datatable .p-datatable-tbody>tr>td {
    padding: 0.3rem 0.2rem;
  }

  >>>.p-datatable .p-datatable-thead>tr>th {
    padding: 0.4rem 0.2rem;
    font-size: 0.7rem;
  }

  >>>.p-button-sm {
    font-size: 0.7rem !important;
    padding: 0.3rem 0.4rem !important;
    min-width: auto !important;
  }

  /* Scroll horizontal para tablas */
  >>>.p-datatable-wrapper {
    overflow-x: auto;
  }
}

.compra-action-buttons-row {
  flex-direction: row !important;
  gap: 0.5rem !important;
  align-items: center !important;
  justify-content: flex-end !important;
}

@media (max-width: 768px) {
  .compra-action-buttons-row {
    flex-direction: row !important;
    gap: 0.5rem !important;
    align-items: center !important;
    justify-content: flex-end !important;
  }
}

.step-buttons-row>.p-button {
  margin-left: 0.5rem;
  margin-right: 0.5rem;
}
</style>

<style scoped>
.reset-buscar-btn {
  margin-left: 0.5rem;
  background: #f5f5f5;
  border: 1px solid #ccc;
  border-radius: 4px;
  padding: 0.35rem 0.6rem;
  cursor: pointer;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  height: 2.2rem;
  transition: background 0.2s;
}

.reset-buscar-btn i.pi {
  font-size: 1.1rem !important;
  line-height: 1 !important;
}

.reset-buscar-btn:hover {
  background: #e0e0e0;
}
</style>

<style scoped>
.tag-extra {
  display: inline-block;
  background: #e0f2ff;
  color: #0369a1;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 10px;
}

/* Estilo uniforme para Dropdown (igual que InputText) */
.dropdown-full {
  width: 100% !important;
  font-size: 0.8rem;
  border-radius: 6px;
  box-sizing: border-box;
}

/* Input dentro del dropdown */
.dropdown-full>>>.p-dropdown-label {
  padding: 6px 8px !important;
  font-size: 0.8rem;
}

/* Flecha del dropdown */
.dropdown-full>>>.p-dropdown-trigger {
  width: 2rem !important;
}

/* Borde al focus */
.dropdown-full>>>.p-dropdown {
  border: 1px solid #ccc;
  transition: border 0.2s;
}

.dropdown-full>>>.p-dropdown.p-focus {
  border-color: #0ea5e9;
  box-shadow: 0 0 0 0.15rem rgba(14, 165, 233, 0.25);
}

/* 🔹 Opciones del panel (lista desplegable) */
.dropdown-full>>>.p-dropdown-panel .p-dropdown-item {
  font-size: 0.8rem !important;
  padding: 6px 10px !important;
  min-height: auto !important;
  /* evita que queden muy grandes */
}

/* 🔹 Input principal (Buscar Producto) */
.input-full {
  width: 100%;
  font-size: 0.8rem;
  padding: 6px 8px;
  border-radius: 6px 0 0 6px;
  box-sizing: border-box;
}

/* Ajuste para InputText de PrimeVue */
.input-full>>>.p-inputtext {
  width: 100% !important;
  font-size: 0.8rem;
  padding: 6px 8px;
  border-radius: 6px 0 0 6px;
}

/* 🔹 Estilo especial para InputNumber */
.input-number-full {
  width: 100%;
}

.input-number-full>>>.p-inputtext {
  width: 100% !important;
  font-size: 0.8rem;
  padding: 6px 8px;
  box-sizing: border-box;
}

.input-date-full {
  width: 100%;
  padding: 6px 8px;
  font-size: 0.85rem;
  border-radius: 6px;
  border: 1px solid #ced4da;
  box-sizing: border-box;
}

.input-date-full:focus {
  border-color: #6c9ffe;
  outline: none;
}

/* 🔹 Addon al mismo tamaño del input */
.addon-precio {
  font-size: 0.8rem;
  /* igual al input */
  padding: 6px 8px;
  /* igual al input */
  border-radius: 0 6px 6px 0;
  /* borde derecho redondeado */
  height: 100%;
  /* igualar altura */
  display: flex;
  align-items: center;
  /* centrar verticalmente */
  box-sizing: border-box;
}

/* Asegurar que el contenedor no se rompa */
.p-inputgroup-addon {
  min-width: 40px;
  /* opcional, para que no se comprima demasiado */
}

.p-inputgroup-addon {
  padding: 0 !important;
  /* eliminamos padding por defecto */
}

.addon-precio {
  padding: 3px 12px !important;
  /* igual al input interno */
  height: 33px;
  /* si tu input termina midiendo 38px (común en PrimeVue) */
}

.tabla-pro {
  width: 100%;
  white-space: nowrap;
  overflow-x: auto;
}

.tabla-pro .p-datatable-wrapper {
  overflow-x: auto;
}

.tabla-pro th,
.tabla-pro td {
  text-align: center;
  vertical-align: middle;
  font-size: 0.85rem;
  padding: 0.5rem;
}

.tabla-pro img {
  border-radius: 4px;
  object-fit: contain;
}

.required-field {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 4px;
}

.required-icon {
  color: #e74c3c;
  font-size: 1rem;
  font-weight: bold;
  margin-right: 0.2rem;
}

/* Estilos para campos opcionales */
.optional-field {
  display: flex;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 4px;
  gap: 0.4rem;
  font-weight: 500;
  color: #6c757d;
}

.optional-icon {
  color: #17a2b8;
  font-size: 0.7rem;
}

/* Arreglar icono de lupa - Centrado perfecto */
.search-bar .p-input-icon-left {
  position: relative;
  width: 100%;
}

.search-bar .p-input-icon-left i {
  position: absolute;
  left: 0.75rem;
  top: 0;
  bottom: 0;
  margin: auto 0;
  height: 1rem;
  z-index: 2;
  color: #6c757d;
  pointer-events: none;
  display: flex;
  align-items: center;
  line-height: 1;
}

.search-bar .p-input-icon-left .p-inputtext {
  padding-left: 2.5rem !important;
  width: 100%;
}

>>>.p-datatable.p-datatable-gridlines .p-datatable-tbody>tr>td {
  text-align: center;
}

.bold-input {
  font-weight: bold;
}

/*Panel*/
.ingreso-panel {
  margin-bottom: 1rem;
}

.panel-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
}

.panel-icon {
  color: #000000;
  font-size: 1.2rem;
}

.panel-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
}

/* Panel Content Spacing */
>>>.p-panel .p-panel-content {
  padding: 1rem;
}

>>>.p-panel .p-panel-header {
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
}

>>>.p-panel .p-panel-header .p-panel-title {
  font-weight: 600;
}

/* Responsive Dialog Styles */
.responsive-dialog>>>.p-dialog {
  margin: 0.75rem;
  max-height: 90vh;
  overflow-y: auto;
}

.responsive-dialog>>>.p-dialog-content {
  overflow-x: auto;
  padding: 0.5rem;
}

.responsive-dialog>>>.p-dialog-header {
  padding: 0.5rem 0.9rem;
  font-size: 1.1rem;
}

.responsive-dialog>>>.p-dialog-footer {
  padding: 0.75rem 1.5rem;
  gap: 0.5rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

/* Toolbar Responsive - Mantener en una línea */
.toolbar-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  gap: 0.75rem;
  flex-wrap: nowrap;
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  flex-shrink: 0;
}

.search-bar {
  flex-grow: 1;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  min-width: 0;
  margin-right: 1rem;
}

.form-group {
  margin-bottom: 15px;
}

>>>.p-dropdown .p-dropdown-trigger {
  width: 2rem;
}

.switch-container {
  display: flex;
  align-items: center;
  justify-content: space-evenly;
}

.custom-precios {
  display: flex;
  justify-content: space-evenly;
  align-items: center;
}

/* Estilos para el código de barras */
.barcode-container {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  overflow-x: auto;
}

/* En desktop, mantener el código de barras a la derecha */
@media (min-width: 769px) {
  .barcode-container {
    justify-content: flex-start;
    width: 250px;
  }
}

/* En móvil, centrar el código de barras */
@media (max-width: 768px) {
  .barcode-container {
    justify-content: center;
    margin: 1rem auto;
    width: 100%;
  }
}

/* DataTable Responsive */
>>>.p-datatable {
  font-size: 0.75rem;
}

>>>.p-datatable .p-datatable-tbody>tr>td {
  padding: 0.4rem;
  word-break: break-word;
  text-align: left;
}

>>>.p-datatable .p-datatable-thead>tr>th {
  padding: 0.35rem 0.4rem;
  font-size: 0.75rem;
}

/* Tablet Styles */
@media (max-width: 1024px) {
  .responsive-dialog>>>.p-dialog {
    margin: 0.5rem;
    max-height: 90vh;
  }

  >>>.p-datatable {
    font-size: 0.85rem;
  }
}

/* Mobile Styles */
@media (max-width: 768px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog>>>.p-dialog {
    margin: 0.25rem;
    max-height: 90vh;
  }

  .responsive-dialog>>>.p-dialog-content {
    padding: 0.75rem;
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.75rem 1rem;
    font-size: 1rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.5rem 1rem;
    justify-content: flex-end;
  }

  .toolbar-container {
    gap: 0.5rem;
  }

  >>>.p-datatable {
    font-size: 0.8rem;
  }

  >>>.p-datatable .p-datatable-tbody>tr>td {
    padding: 0.4rem 0.3rem;
  }

  >>>.p-datatable .p-datatable-thead>tr>th {
    padding: 0.5rem 0.3rem;
    font-size: 0.75rem;
  }

  /* Ajustar botones en móviles */
  >>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }

  /* Ajustar botón "Nuevo" para que coincida con otros botones */
  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  /* Reducir altura del input buscador */
  .search-bar .p-inputtext-sm {
    padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
    font-size: 0.85rem !important;
  }
}

/* Extra Small Mobile */
@media (max-width: 480px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog>>>.p-dialog {
    margin: 0.1rem;
    max-height: 90vh;
  }

  .responsive-dialog>>>.p-dialog-content {
    padding: 0.5rem;
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.5rem 0.75rem;
    font-size: 0.95rem;
  }

  /* Footer mantiene botones alineados a la derecha, no ocupan todo el ancho */
  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.5rem 0.75rem;
    justify-content: flex-end;
  }

  .responsive-dialog>>>.p-dialog-footer .p-button {
    width: auto;
    margin-bottom: 0.25rem;
  }

  /* Toolbar mantiene elementos en una línea */
  .toolbar-container {
    gap: 0.4rem;
    flex-wrap: nowrap;
  }

  .toolbar {
    flex-shrink: 0;
    min-width: auto;
  }

  .search-bar {
    flex: 1;
    min-width: 0;
  }

  /* Ajustar botones para que coincidan */
  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  /* Reducir más la altura del input buscador en móviles pequeños */
  .search-bar .p-inputtext-sm {
    padding: 0.3rem 0.5rem 0.3rem 2.5rem !important;
    font-size: 0.8rem !important;
  }

  >>>.p-datatable {
    font-size: 0.75rem;
  }

  >>>.p-datatable .p-datatable-tbody>tr>td {
    padding: 0.3rem 0.2rem;
  }

  >>>.p-datatable .p-datatable-thead>tr>th {
    padding: 0.4rem 0.2rem;
    font-size: 0.7rem;
  }
}

/* Paginator Responsive */
@media (max-width: 768px) {
  >>>.p-paginator {
    flex-wrap: wrap !important;
    justify-content: center;
    font-size: 0.85rem;
    padding: 0.5rem;
  }

  >>>.p-paginator .p-paginator-page,
  >>>.p-paginator .p-paginator-next,
  >>>.p-paginator .p-paginator-prev,
  >>>.p-paginator .p-paginator-first,
  >>>.p-paginator .p-paginator-last {
    min-width: 32px !important;
    height: 32px !important;
    font-size: 0.85rem !important;
    padding: 0 6px !important;
    margin: 2px !important;
  }
}

@media (max-width: 480px) {
  >>>.p-paginator {
    font-size: 0.8rem;
    padding: 0.4rem;
  }

  >>>.p-paginator .p-paginator-page,
  >>>.p-paginator .p-paginator-next,
  >>>.p-paginator .p-paginator-prev,
  >>>.p-paginator .p-paginator-first,
  >>>.p-paginator .p-paginator-last {
    min-width: 28px !important;
    height: 28px !important;
    font-size: 0.8rem !important;
    padding: 0 4px !important;
    margin: 1px !important;
  }
}

/* Action Buttons in DataTable */
>>>.p-datatable .p-button {
  margin-right: 0.25rem;
}

@media (max-width: 768px) {
  >>>.p-datatable .p-button {
    margin-right: 0.15rem;
    margin-bottom: 0.15rem;
  }
}

/* Estilos del loader */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  background-color: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(5px);
  padding: 30px;
  border-radius: 15px;
}

.spinner {
  width: 80px;
  height: 80px;
  border: 4px solid rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  border-top: 4px solid rgba(255, 255, 255, 0.9);
  animation: spin 1s linear infinite;
}

.loading-text {
  margin-top: 20px;
  color: rgba(255, 255, 255, 0.9);
  letter-spacing: 3px;
  font-size: 14px;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

.p-dialog-mask {
  z-index: 9990 !important;
}

.p-dialog {
  z-index: 9990 !important;
}

.swal-zindex {
  z-index: 9995 !important;
}
</style>

<style>
.swal2-confirm-articulonew {
  background-color: #22c55e !important;
  border-color: #22c55e !important;
  color: #fff !important;
}

.swal2-cancel-articulonew {
  background-color: #ef4444 !important;
  border-color: #ef4444 !important;
  color: #fff !important;
}

.detalle-value {
  color: #1e293b;
  font-weight: 500;
  word-break: break-word;
}

.detalle-dialog .p-dialog {
  width: 450px !important;
  max-width: 450px;
  height: 580px !important;
  max-height: 580px;
  border-radius: 10px;
}

.detalle-dialog .p-dialog-content {
  height: calc(100% - 60px);
  overflow-y: auto;
}

.detalle-articulo-dialog {
  height: 100%;
  overflow: hidden;
}

.detalle-articulo-card {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 10px 20px;
}

.detalle-header {
  display: flex;
  align-items: center;
  gap: 8px;
  border-bottom: 1px solid #ddd;
  padding-bottom: 5px;
  margin-bottom: 10px;
}

.icon-header {
  font-size: 1.3rem;
  color: #007ad9;
}

.detalle-titulo {
  font-weight: bold;
  font-size: 1.2rem;
}

.detalle-body {
  flex: 1;
  overflow-y: auto;
  padding-right: 10px;
}

.detalle-row {
  margin: 10px 0;
}

/* --- STOCK MÍNIMO --- */
.detalle-stock {
  display: flex;
  align-items: center;
  gap: 8px;
}

.detalle-label {
  font-weight: bold;
  color: #555;
}

.footer-center {
  display: flex;
  justify-content: center;
  padding-top: 10px;
}

.p-error-precio {
  color: #ddc239;
  /* rojo elegante */
  display: block;
  margin-top: 4px;
  font-size: 0.85rem;
}

.modern-date {
  border: 1px solid #0d6efd;
  border-radius: 0.6rem;
  padding: 8px 12px;
  font-size: 0.95rem;
  color: #212529;
  background-color: #f8faff;
  transition: all 0.25s ease;
  box-shadow: 0 1px 3px rgba(13, 110, 253, 0.15);
}

/* Efecto al enfocar */
.modern-date:focus {
  border-color: #0b5ed7;
  outline: none;
  box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.25);
  background-color: #fff;
}

/* 🔹 Ícono del calendario (Chrome, Edge, Safari) */
.modern-date::-webkit-calendar-picker-indicator {
  color: #0d6efd;
  background: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%230d6efd' viewBox='0 0 24 24'%3E%3Cpath d='M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2zm0 16H5V9h14v11zM7 11h5v5H7v-5z'/%3E%3C/svg%3E") no-repeat center;
  background-size: 1rem;
  opacity: 0.7;
  cursor: pointer;
  transition: 0.3s ease;
}

.modern-date::-webkit-calendar-picker-indicator:hover {
  opacity: 1;
  transform: scale(1.1);
}

/* 🔹 Borde rojo si hay error (por ejemplo, con clase .is-invalid) */
.modern-date.is-invalid {
  border-color: #dc3545 !important;
  box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.25);
}

.text-danger-fecha {
  color: #dc3545;
  font-weight: 500;
}

.modern-date {
  border-radius: 8px !important;
  border: 1px solid #d1d5db !important;
  transition: 0.2s;
}

.modern-date:focus {
  border-color: #0d6efd !important;
  box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.25);
}

.detalle-label {
  font-weight: 600;
  color: #555;
  margin-right: 6px;
}

.detalle-value {
  color: #222;
}

.badge-stock {
  background: #e0f3ff;
  color: #0d6efd;
  font-weight: 600;
  border-radius: 50px;
  padding: 4px 10px;
  font-size: 0.85rem;
  display: inline-block;
}

.badge-medida {
  background: #f8f4c3;
  color: #fdb10d;
  font-weight: 600;
  border-radius: 50px;
  padding: 4px 10px;
  font-size: 0.85rem;
  display: inline-block;
}

.badge-uxc {
  background: #ffe0fa;
  color: #fd0df1;
  font-weight: 600;
  border-radius: 50px;
  padding: 4px 10px;
  font-size: 0.85rem;
  display: inline-block;
}

.alert-warning {
  background-color: #fff3cd;
  color: #856404;
  border-radius: 8px;
  border: 1px solid #ffeeba;
}

.codigo-descuento {
  background-color: #53d070 !important;
  color: #fff !important;
  padding: 4px 8px;
  border-radius: 8px;
  font-weight: 600;
  display: inline-block;
  /* evita deformaciones */
  white-space: nowrap;
  /* impide que el texto salte de línea */
  text-overflow: ellipsis;
  /* corta con "..." si es muy largo */
  overflow: hidden;
  /* oculta el exceso de texto */
  max-width: 110px;
  /* ajusta el ancho visible */
  text-align: center;
  /* centra el contenido */
  vertical-align: middle;
}

.codigo-descuento:hover {
  background-color: #40b75e !important;
}

.codigo-descuento[title] {
  cursor: pointer;
}
</style>


<style>
.custom-swal-confirm {
  background-color: #28a745 !important;
  color: #fff !important;
  border: none !important;
  box-shadow: none !important;
}

.custom-swal-cancel {
  background-color: #d33 !important;
  color: #fff !important;
  border: none !important;
  box-shadow: none !important;
  margin-right: 0.5rem;
}

.swal2-popup .swal2-styled:focus {
  box-shadow: 0 0 0 2px #28a74555 !important;
}

.input-error {
  border: 2px solid #dc3545 !important;
  background: #ffe6e6 !important;
}
</style>
