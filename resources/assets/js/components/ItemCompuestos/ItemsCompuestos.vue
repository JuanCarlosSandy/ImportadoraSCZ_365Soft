<template>
  <main class="main">
    <div class="loading-overlay" v-if="isLoading">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">LOADING...</div>
      </div>
    </div>
    <Panel>
      <template #header>
        <div class="panel-header">
          <i class="pi pi-bars"></i>
          <h4 class="panel-title">COMBOS / OFERTAS</h4>
        </div>
      </template>

      <div class="toolbar-container">
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText v-model="buscar" placeholder="Texto a buscar" class="p-inputtext-sm" @keyup="buscarArticulo" />
          </span>
        </div>
        <div class="toolbar">
          <Button :label="mostrarLabel ? 'Nuevo' : ''" icon="pi pi-plus" class="p-button-secondary p-button-sm"
            @click="abrirModal('articulo', 'registrar')" />
          <!--<Button :label="mostrarLabel ? 'Reporte' : ''" icon="pi pi-file" class="p-button-success p-button-sm"
            @click="cargarReporte" />-->
        </div>
      </div>

      <DataTable :value="paginatedItems" class="p-datatable-gridlines p-datatable-sm tabla-pro"
        responsiveLayout="scroll">
        <Column v-for="(column, index) in computedColumns" :key="index" :field="column.field" :header="column.header">
          <template #body="slotProps">
            <span v-if="column.type === 'button'">
              <div class="botones-opciones">
                <Button icon="pi pi-pencil" class="btn-icon p-button-warning btn-mini"
                  @click="abrirModal('articulo', 'actualizar', slotProps.data)" v-tooltip.top="'Editar'" />
                <Button v-if="slotProps.data.condicion" icon="pi pi-ban" class="btn-icon p-button-danger btn-mini"
                  @click="desactivarArticulo(slotProps.data.id)" v-tooltip.top="'Desactivar'" />
                <Button v-else icon="pi pi-check-circle" class="btn-icon p-button-success btn-mini"
                  @click="activarArticulo(slotProps.data.id)" v-tooltip.top="'Activar'" />
                <Button icon="pi pi-eye" class="btn-icon p-button-primary btn-mini"
                  @click="verCombosOfertas(slotProps.data.id)" v-tooltip.top="'Ver Combo'" />

              </div>
            </span>
            <span v-else-if="column.type === 'dynamicPrice'">
              {{
                (
                  slotProps.data[column.field] * parseFloat(monedaPrincipal[0])
                ).toFixed(2)
              }}
              {{ monedaPrincipal[1] }}
            </span>
            <span v-else-if="column.type === 'image'">
              <img :src="'img/articulo/' +
                slotProps.data.fotografia +
                '?t=' +
                new Date().getTime()
                " width="50" height="50" v-if="slotProps.data.fotografia" ref="imagen" />
              <img :src="'img/articulo/' + 'defecto.jpg'" width="50" height="50" v-else ref="imagen" />
            </span>
            <span v-else-if="column.type === 'badge'" style="text-align: center;">
              <span v-if="slotProps.data.precio_variable" class="badge badge-success" style="center">Si</span>
              <span v-else class="badge badge-danger" style="center">No</span>
            </span>
            <span v-else>
              {{ slotProps.data[column.field] }}
            </span>
          </template>
        </Column>
      </DataTable>
      <Paginator :rows="rowsPerPage" :totalRecords="arrayArticulo.length" :first="first" @page="onPageChange" />
    </Panel>
    <!-- MODAL REGISTRAR PRODUCTO -->
    <Dialog :visible.sync="dialogVisible" :modal="true" header="FORMULARIO COMBO/OFERTA" :closable="false"
      :closeOnEscape="true" @hide="closeDialog" :containerStyle="{ width: '800px' }">
      <TabView>
        <TabPanel header="Datos">
          <div class="form-group row">
            <div class="col-md-6">
              <div>
                <label for="nombre" class="required-field">
                  <span class="required-icon">*</span>
                  Concepto del Oferta/Combo
                </label>
                <InputText id="nombreProducto" v-model="datosFormulario.nombre" placeholder="Ej. Inyeccion antibiotica"
                  class="form-control p-inputtext-sm input-full" :class="{ 'input-error': errores.nombre }"
                  @input="validarCampo('nombre')" autocomplete="off" />
              </div>
            </div>

            <div class="col-md-6">
              <label for="nombre" class="required-field">
                <span class="required-icon">*</span>
                Categoria
              </label>
              <div class="p-inputgroup">
                <InputText id="linea" v-model="lineaSeleccionado.nombre" placeholder="Seleccione una categoria"
                  class="form-control p-inputtext-sm bold-input input-full" disabled
                  :class="{ 'input-error': errores.idcategoria }" />
                <Button label="..." class="p-button-primary p-button-sm btn-sm" @click="abrirDialogos('Lineas')" />
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
                  placeholder="Ej. Inyeccion antibiotica para infecciones severas" rows="3"
                  class="form-control p-inputtext-sm"" />
              </div>
            </div>
          </div>
        </TabPanel>

        <TabPanel header=" Productos">
              <div class="form-group row">
                <div class="col-md-12">
                  <InputText v-model="busquedaProducto" placeholder="Buscar producto..."
                    class="form-control p-inputtext-sm" @input="filtrarProductos" />
                </div>
              </div>
              <DataTable :value="arrayProductos" class="p-datatable-gridlines p-datatable-sm tabla-pro"
                :loading="productosLoading" responsiveLayout="scroll">
                <Column field="codigo" header="Código" />
                <Column field="nombre" header="Nombre" />
                <Column header="Seleccionar">
                  <template #body="slotProps">
                <Button icon="pi pi-plus" class="p-button-success p-button-sm btn-mini"
                  @click="seleccionarProducto(slotProps.data)" />
              </template>
                </Column>
              </DataTable>
              <Paginator :rows="rowsPerPageProductos" :totalRecords="productosTotal" :first="firstProductos"
                @page="onPageChangeProductos" />
              <h5 class="mt-3">Productos seleccionados</h5>
              <DataTable :value="productosSeleccionados" class="p-datatable-gridlines p-datatable-sm tabla-pro"
                :rows="10" paginator responsiveLayout="scroll">
                <Column header="Quitar">
                  <template #body="slotProps">
                <Button icon="pi pi-times" class="p-button-danger p-button-sm btn-mini"
                  @click="quitarProducto(slotProps.data)" />
              </template>
                </Column>
                <Column field="codigo" header="Código" />
                <Column field="nombre" header="Nombre" />
                <Column header="Categoria">
                  <template #body="{ data }">
                {{ data.nombre_categoria || data.nombre_categoria || '—' }}
              </template>
                </Column>
                <Column field="cantidad" header="Cantidad">
                  <template #body="slotProps">
                <InputNumber v-model="slotProps.data.cantidad" :min="1" :step="1"
                  class="cantidad-input input-number-full" />
              </template>
                </Column>
              </DataTable>
    </TabPanel>

    <TabPanel header="Precios">
      <div class="row">
        <!-- COLUMNA IZQUIERDA -->
        <div class="col-md-6 col-12">
          <div class="panel-titulo">Información de Costos</div>
          <div class="info-box d-flex align-items-center justify-content-between">
            <div>
              <span class="label-bold required-field">Precio Sugerido:</span>
              <span>{{ precioSugeridoVenta.toFixed(2) }}
                {{ monedaPrincipal[1] }}</span>
            </div>
            <div class="precio-sugerido-buttons">
              <Button icon="pi pi-exclamation-circle" class="p-button-info p-button-sm"
                @click="mostrarDetallePrecioSugeridoVenta = true" />
              <Button icon="pi pi-arrow-down" class="p-button-success p-button-sm"
                @click="rellenarPrecioUnoConSugeridoVenta" title="Usar precio sugerido" />
            </div>
          </div>
        </div>

        <!-- COLUMNA DERECHA -->
        <div class="col-md-6 col-12">
          <div class="panel-titulo">Precios de Venta</div>

          <div class="precio-inputgroup">
            <label class="label-bold required-field">
              Precio del combo:
            </label>

            <div class="p-inputgroup">
              <InputNumber placeholder="Precio" v-model="precio_uno" mode="decimal" locale="es-ES" :min="0"
                :minFractionDigits="2" :maxFractionDigits="2" class="p-inputtext-sm"
                style="font-size: 1.2em; font-weight: bold; width: 120px;" @keydown.native="convertirPuntoComa"
                @input="evitarReformateo($event, val => precio_uno = val)" />
              <span class="p-inputgroup-addon addon-precio">
                {{ monedaPrincipal[1] }}
              </span>
            </div>
          </div>

        </div>
      </div>
    </TabPanel>
  </TabView>
  <template #footer>
        <div class="d-flex gap-2 justify-content-end modal-footer-buttons">
          <Button label="Cerrar" icon="pi pi-times" class="p-button-danger p-button-sm" @click="cerrarModal" />
          <Button v-if="tipoAccion == 1" class="p-button-success p-button-sm" label="Guardar" icon="pi pi-check"
            @click="enviarFormulario()" />
          <Button v-if="tipoAccion == 2" class="p-button-warning p-button-sm" label="Actualizar" icon="pi pi-check"
            @click="enviarFormulario()" />
        </div>
      </template>
</Dialog>

<Dialog :visible.sync="mostrarDetallePrecioSugeridoVenta" :modal="true" header="Detalle de Precio Sugerido (Venta)"
  :closable="false" :closeOnEscape="false" :containerStyle="{ width: '400px' }">
  <!-- Contenido del dialog -->
  <div v-if="productosSeleccionados.length">
    <ul style="list-style: none; padding: 0;">
      <li v-for="item in productosSeleccionados" :key="item.id" class="mb-2">
        <span class="font-weight-bold">{{ item.nombre }}</span>
        <span class="ml-2">- {{ parseFloat(item.precio_uno || 0).toFixed(2) }}
          {{ monedaPrincipal[1] }}</span>
      </li>
    </ul>
  </div>
  <div v-else>No hay productos seleccionados.</div>

  <!-- Botón para cerrar -->
  <div style="text-align: right; margin-top: 1rem;">
    <Button label="Cerrar" icon="pi pi-times" class="p-button-danger p-button-sm"
      @click="mostrarDetallePrecioSugeridoVenta = false" />
  </div>
</Dialog>

<Dialog :visible.sync="dialogVerCombo" :modal="true" :closable="false" :containerStyle="{ width: '800px' }"
  class="dialog-combo">
  <!-- HEADER PERSONALIZADO -->
  <template #header>
    <div class="dialog-header">
      <i class="pi pi-box icon-header"></i>
      <div>
        <h3 class="title">{{ nombreComboActual }}</h3>
        <span class="subtitle">Detalle de productos incluidos</span>
      </div>
    </div>
  </template>

  <!-- CONTENIDO -->
  <div class="dialog-content">
    <DataTable :value="productosSeleccionados" responsiveLayout="scroll" class="p-datatable-sm p-datatable-striped">
      <Column field="nombre" header="Producto"></Column>
      <Column field="nombre_categoria" header="Categoría"></Column>
      <Column field="cantidad" header="Cantidad" style="width: 120px; text-align:center">
        <template #body="slotProps">
          <span class="cantidad-badge">
            {{ slotProps.data.cantidad }}
          </span>
        </template>
      </Column>
    </DataTable>
  </div>

  <!-- FOOTER -->
  <template #footer>
    <div class="dialog-footer">
      <Button
        label="Cerrar"
        icon="pi pi-times"
        class="p-button-danger p-button-sm"
        @click="dialogVerCombo = false"
      />
    </div>
  </template>
</Dialog>

<!-- MODALES DINÁMICOS -->
<DialogProveedores v-if="mostrarDialogoProveedores" :visible.sync="mostrarDialogoProveedores"
  @close="cerrarDialogos('Proveedores')" @proveedor-seleccionado="manejarProveedorSeleccionado" />
<DialogCategoria v-if="mostrarDialogoLineas" :visible.sync="mostrarDialogoLineas" @close="cerrarDialogos('Lineas')"
  @linea-seleccionado="manejarLineaSeleccionado" />
<DialogMarcas v-if="mostrarDialogoMarcas" :visible.sync="mostrarDialogoMarcas" @close="cerrarDialogos('Marcas')"
  @marca-seleccionado="manejarMarcaSeleccionado" />
<DialogIndustrias v-if="mostrarDialogoIndustrias" :visible.sync="mostrarDialogoIndustrias"
  @close="cerrarDialogos('Industrias')" @industria-seleccionado="manejarIndustriaSeleecionado" />
<DialogGrupos v-if="mostrarDialogoGrupos" :visible.sync="mostrarDialogoGrupos" @close="cerrarDialogos('Grupos')"
  @grupo-seleccionado="manejarGrupoSeleccionado" />
<DialogMedidas v-if="mostrarDialogoMedidas" :visible.sync="mostrarDialogoMedidas" @close="cerrarDialogos('Medidas')"
  @medida-seleccionado="manejarMedidaSeleccionado" />
<DialogAlmacenes v-if="mostrarDialogoAlmacen" :visible.sync="mostrarDialogoAlmacen" @close="cerrarDialogos('Almacen')"
  @almacen-seleccionado="manejarAlmacenSeleccionado" />
<ImportarExcelNewView v-if="mostrarDialogoImportar" :visible.sync="mostrarDialogoImportar"
  @cerrar="cerrarDialogos('Importar')" />
</main>
</template>

<script>
import Panel from "primevue/panel";
import Paginator from "primevue/paginator";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Dialog from "primevue/dialog";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Textarea from "primevue/textarea";
import InputNumber from "primevue/inputnumber";
import ImagePreview from "primevue/imagepreview";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import DialogProveedores from "../../components/modales/DialogProveedores.vue";
import DialogCategoria from "../../components/modales/DialogCategoriaServicio.vue";
import DialogMarcas from "../../components/modales/DialogMarcas.vue";
import DialogIndustrias from "../../components/modales/DialogIndustrias.vue";
import DialogGrupos from "../../components/modales/DialogGrupos.vue";
import DialogMedidas from "../../components/modales/DialogMedidas.vue";
import DialogAlmacenes from "../../components/modales/DialogAlmacenes.vue";
import Dropdown from "primevue/dropdown";
import InputSwitch from "primevue/inputswitch";
import Calendar from "primevue/calendar";
import VueBarcode from "vue-barcode";
import {
  esquemaArticulos,
  esquemaInventario,
} from "../../constants/validations";
import ImportarExcelNewView from "../../components/Servicios/ImportarExcelServicio.vue";
import Swal from "sweetalert2";
import Tooltip from 'primevue/tooltip';
export default {
  components: {
    TabView,
    TabPanel,
    Panel,
    Button,
    InputText,
    Dialog,
    DataTable,
    Column,
    Textarea,
    InputNumber,
    ImagePreview,
    Dropdown,
    InputSwitch,
    Calendar,
    Paginator,
    barcode: VueBarcode,
    DialogProveedores,
    DialogCategoria,
    DialogMarcas,
    DialogIndustrias,
    DialogGrupos,
    DialogMedidas,
    DialogAlmacenes,
    ImportarExcelNewView,
  },
  directives: {
    'tooltip': Tooltip
  },
  onPageChangeProductos(event) {
    this.firstProductos = event.first;
    this.rowsPerPageProductos = event.rows;
    const page = Math.floor(event.first / event.rows) + 1;
    this.obtenerProductos(page, this.busquedaProducto, event.rows);
  },
  data() {
    return {
      dialogVerCombo: false,
      nombreComboActual: '',

      mostrarLabel: true,
      arrayProductos: [],
      productosSeleccionados: [],
      busquedaProducto: "",
      productosTotal: 0,
      productosPage: 1,
      rowsPerPageProductos: 5,
      productosLoading: false,
      firstProductos: 0,
      isLoading: false,
      criterio: "nombre",
      buscar: "",
      arrayArticulo: [], // Datos del artículo
      dialogVisible: false,
      agregarStock: false,
      fechaVencimientoAlmacen: null,
      unidadStock: null,
      mostrarDetallePrecioSugerido: false, // Para el modal del detalle de costo
      mostrarDetallePrecioSugeridoVenta: false, // Para el modal del detalle de venta
      datosFormulario: {
        precio_variable: false,
        nombre: "",
        descripcion: "",
        unidad_envase: 0,
        precio_costo_unid: 0,
        precio_costo_paq: 0,
        precio_venta: 0,
        precio_uno: 0,
        precio_dos: 0,
        precio_tres: 0,
        precio_cuatro: 0,
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
        fechaVencimientoAlmacen: null,
        unidadStock: null,
      },
      errores: {},
      erroresinventario: {},
      tipo_stock: "",
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
      precios: [],
      precio_uno: null,
      precio_dos: null,
      precio_tres: null,
      precio_cuatro: null,
      monedaPrincipal: [],

      //CONFIGURACIONES
      mostrarSaldosStock: "",
      mostrarProveedores: "",
      mostrarCostos: "",
      rolUsuario: "",
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
      nombreProductoVacio: false,
      codigoVacio: false,
      unidad_envaseVacio: false,
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
      // Paginación frontend
      first: 0,
      rowsPerPage: 10,
      offset: 3,
      headers: [
        { field: "acciones", header: "Acciones", type: "button" },
        { field: "codigo", header: "CODIGO" },
        { field: "nombre", header: "DESCRIPCION" },
        { field: "nombre_categoria", header: "CATEGORIA" },
      ],
    };
  },
  computed: {
    imagen() {
      return this.fotoMuestra;
    },
    paginatedItems() {
      // Obtener elementos a mostrar en la página actual (frontend pagination)
      return this.arrayArticulo.slice(
        this.first,
        this.first + this.rowsPerPage
      );
    },
    precioSugerido() {
      // Suma de los costos unitarios de los productos seleccionados considerando la cantidad
      return this.productosSeleccionados.reduce(
        (acc, item) =>
          acc +
          (parseFloat(item.precio_costo_unid) || 0) *
          (parseInt(item.cantidad) || 1),
        0
      );
    },
    precioSugeridoVenta() {
      // Suma de los precios de venta (precio_uno) de los productos seleccionados considerando la cantidad
      return this.productosSeleccionados.reduce(
        (acc, item) =>
          acc +
          (parseFloat(item.precio_uno) || 0) * (parseInt(item.cantidad) || 1),
        0
      );
    },
    computedColumns() {

      const dynamicColumns = this.precios.slice(0, 1).map((precio, index) => ({
        field: "precio_uno",
        header: "Precio del Combo",
        type: "dynamicPrice",
      }));

      const index =
        this.headers.findIndex((header) => header.field === "nombre_categoria") + 1;

      const result = [
        ...this.headers.slice(0, index),
        ...dynamicColumns,
        ...this.headers.slice(index),
      ];

      console.log("RESULTS COMPUTED ", index);
      return result;
    }

  },
  methods: {
    verCombosOfertas(id) {
      axios.get(`/itemcompuesto/${id}`).then((res) => {
        this.nombreComboActual = res.data.nombre_compuesto || 'Detalle del Combo';

        this.productosSeleccionados = (res.data.items || []).map(item => ({
          nombre: item.nombre,
          nombre_categoria: item.nombre_categoria || 'SIN CATEGORIA',
          cantidad: parseInt(item.cantidad || 1)
        }));

        this.dialogVerCombo = true;
      });
    },

    getNumero(valor) {
      if (!valor) return 0;
      // La coma (,) la reemplazamos por punto (.) para que parseFloat lo reconozca
      let str = String(valor).replace(',', '.');
      let num = parseFloat(str);
      return isNaN(num) ? 0 : num;
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
    rellenarPrecioUnoConSugeridoVenta() {
      this.precio_uno = this.precioSugeridoVenta;
    },
    async obtenerProductos(page = 1, search = "", rows = null) {
      this.productosLoading = true;
      try {
        const perPage = rows || this.rowsPerPageProductos || 5;
        const response = await axios.get("/articulo", {
          params: {
            page: page,
            per_page: perPage,
            buscar: search,
            criterio: "nombre",
          },
        });
        // Suponiendo que la respuesta es { articulos: { data: [...], total, current_page, per_page } }
        const articulos = response.data.articulos;
        this.arrayProductos = articulos.data || [];
        this.productosTotal = articulos.total || this.arrayProductos.length;
        this.productosPage = articulos.current_page || 1;
        this.rowsPerPageProductos = articulos.per_page || perPage;
        this.firstProductos =
          ((articulos.current_page || 1) - 1) * (articulos.per_page || perPage);
      } catch (error) {
        console.error("Error al obtener productos:", error);
      } finally {
        this.productosLoading = false;
      }
    },
    onPageChangeProductos(event) {
      this.firstProductos = event.first;
      this.rowsPerPageProductos = event.rows;
      const page = Math.floor(event.first / event.rows) + 1;
      this.obtenerProductos(page, this.busquedaProducto, event.rows);
    },
    filtrarProductos() {
      // Reiniciar a la primera página y buscar en el backend
      this.productosPage = 1;
      this.firstProductos = 0;
      this.obtenerProductos(
        1,
        this.busquedaProducto,
        this.rowsPerPageProductos
      );
    },
    seleccionarProducto(producto) {
      if (!this.productosSeleccionados.some((p) => p.id === producto.id)) {
        // Agregar cantidad por defecto 1
        this.productosSeleccionados.push({ ...producto, cantidad: 1 });
      }
      console.log("items: ", this.productosSeleccionados);
      //this.rellenarPrecioUnoConSugeridoVenta();
    },
    quitarProducto(producto) {
      this.productosSeleccionados = this.productosSeleccionados.filter(
        (p) => p.id !== producto.id
      );
      //this.rellenarPrecioUnoConSugeridoVenta();
    },
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    cargarReporte() {
      Swal.fire({
        title: "Selecciona el tipo de reporte",
        icon: "question",
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: "PDF",
        cancelButtonText: "EXCEL",
        denyButtonText: "VOLVER",
        customClass: {
          confirmButton: "swal2-cancel-lineanew",
          cancelButton: "swal2-confirm-lineanew",
          denyButton: "swal2-volver-lineanew",
        },
        buttonsStyling: false,
      }).then((result) => {
        if (result.isConfirmed) {
          window.open("/ItemsCompuestos/pdf", "_blank"); // PDF
        } else if (result.dismiss === Swal.DismissReason.cancel) {
          window.open("/ItemsCompuestos/excel", "_blank"); // Excel
        }
      });
    },
    toastSuccess(mensaje) {
      this.$toasted.show(
        `
    <div style="height: 60px;font-size:16px;">
        <br>
        ` +
        mensaje +
        `.<br>
    </div>`,
        {
          type: "success",
          position: "bottom-right",
          duration: 4000,
        }
      );
    },
    toastError(mensaje) {
      this.$toasted.show(
        `
    <div style="height: 60px;font-size:16px;">
        <br>
        ` +
        mensaje +
        `<br>
    </div>`,
        {
          type: "error",
          position: "bottom-right",
          duration: 4000,
        }
      );
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
          break;
        case "Lineas":
          this.mostrarDialogoLineas = false;
          break;
        case "Marcas":
          this.mostrarDialogoMarcas = false;
          break;
        case "Industrias":
          this.mostrarDialogoIndustrias = false;
          break;
        case "Grupos":
          this.mostrarDialogoGrupos = false;
          break;
        case "Medidas":
          this.mostrarDialogoMedidas = false;
          break;
        case "Almacen":
          this.mostrarDialogoAlmacen = false;
          break;
        case "Importar":
          this.mostrarDialogoImportar = false;
          this.listarItemCompuesto(1, "", "nombre");
          break;
      }
      this.dialogVisible = true;
    },
    listarPrecio() {
      let me = this;
      me.isLoading = true; // Activar loader

      var url = "/precios";
      return axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.precios = respuesta.precio.data;
        })
        .catch(function (error) {
          console.log(error);
        })
        .finally(() => {
          me.isLoading = false; // Desactivar loader
        });
    },
    buscarArticulo() {
      this.listarItemCompuesto(1, this.buscar);
    },
    asignarCampos() {
      this.datosFormulario.idcategoria = this.lineaSeleccionado.id;
      this.datosFormulario.idmedida = this.medidaSeleccionado.id;
    },
    asignarCamposPrecios() {
      this.datosFormulario.precio_venta = this.convertDolar(
        this.datosFormulario.precio_venta
      );

      this.datosFormulario.precio_uno = this.convertDolar(this.precio_uno);
      this.datosFormulario.precio_dos = this.convertDolar(this.precio_dos);
      this.datosFormulario.precio_tres = this.convertDolar(this.precio_tres);
      this.datosFormulario.precio_cuatro = this.convertDolar(
        this.precio_cuatro
      );
    },

    convertDolar(precio) {
      return precio / parseFloat(this.monedaPrincipal);
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
    async enviarFormulario() {
      this.asignarCampos();
      this.asignarCamposPrecios();
      // Agregar productos seleccionados (IDs y cantidades)
      this.datosFormulario.productos = this.productosSeleccionados.map((p) => ({
        id: p.id,
        cantidad: p.cantidad || 1,
      }));
      console.log("UNIDAD STOCK ", this.unidadStock);
      console.log("ALMACEN ", this.AlmacenSeleccionado);
      console.log("agregar ", this.agregarStock);

      if (this.agregarStock === true) {
        console.log("Asignando valores adicionales al formulario");
      }

      console.log("DATOS FORMULARIO ANTES DE VALIDAR: ", this.datosFormulario);
      console.log(
        "DATOS FORMULARIO ANTES DE VALIDAR: ",
        this.datosFormularioInventario
      );

      console.log("Formulario>: ", this.datosFormulario);
      const camposFaltantes = [];
      if (!this.datosFormulario.nombre)
        camposFaltantes.push("Nombre del Item Compuesto");
      if (!this.datosFormulario.idcategoria)
        camposFaltantes.push("Categoria del Item Compuesto");
      if (!this.datosFormulario.precio_uno) camposFaltantes.push("Precio 1");
      if (this.datosFormulario.productos.length == 0)
        camposFaltantes.push("Productos");

      let validacionExitosa = true;
      let validacionInventarioExitosa = true;

      try {
        await esquemaArticulos.validate(this.datosFormulario, {
          abortEarly: false,
        });
        console.log("Validación de esquemaArticulos exitosa");
      } catch (error) {
        validacionExitosa = false;
        const erroresValidacion = {};
        error.inner.forEach((e) => {
          erroresValidacion[e.path] = e.message;
        });
        this.errores = erroresValidacion;
        console.log("Errores en esquemaArticulos: ", this.errores);
      }
      if (camposFaltantes.length > 0) {
        Swal.fire({
          title: "¡Campos obligatorios!",
          html: `Debe completar los siguientes campos:<br><ul style="text-align:left;">${camposFaltantes
            .map((c) => `<li>${c}</li>`)
            .join("")}</ul>`,
          icon: "warning",
        });
        return;
      }

      if (this.tipoAccion != 2 && this.agregarStock == true) {
        try {
          await esquemaInventario.validate(this.datosFormularioInventario, {
            abortEarly: false,
          });
          console.log("Validación de esquemaInventario exitosa");
        } catch (error) {
          validacionInventarioExitosa = false;
          const erroresValidacionInventario = {};
          error.inner.forEach((e) => {
            erroresValidacionInventario[e.path] = e.message;
          });

          this.erroresinventario = erroresValidacionInventario;
          console.log("Errores en esquemaInventario: ", this.erroresinventario);
        }
      }

      if (this.tipoAccion == 2) {
        // Actualización del artículo
        try {
          this.datosFormulario.fotografia = this.fotografia;
          if (this.tipo_stock == "paquetes") {
            this.datosFormulario.stock =
              this.datosFormulario.unidad_envase * this.datosFormulario.stock;
            console.log("paquetes ", this.datosFormulario.stock);
          }
          await this.actualizarArticulo(this.datosFormulario);
          console.log("Actualización de artículo exitosa");
        } catch (error) {
          console.error("Error al actualizar el artículo: ", error);
        }
      } else if (validacionExitosa || validacionInventarioExitosa) {
        // Registro del artículo
        console.log("TIPO STOCK ", this.tipo_stock);
        this.datosFormulario.fotografia = this.fotografia;
        if (this.tipo_stock == "paquetes") {
          this.datosFormulario.stock =
            this.datosFormulario.unidad_envase * this.datosFormulario.stock;
          console.log("paquetes ", this.datosFormulario.stock);
        }

        try {
          await this.registrarArticulo(this.datosFormulario);
          console.log("Registro de artículo exitoso", this.datosFormulario);
        } catch (error) {
          console.error("Error al registrar el artículo: ", error);
        }
      }
    },
    obtenerConfiguracionTrabajo() {
      let me = this;
      me.isLoading = true; // Activar loader

      return axios
        .get("/configuracion")
        .then((response) => {
          console.log(response);
        })
        .catch((error) => {
          console.error("Error al obtener configuración de trabajo:", error);
        })
        .finally(() => {
          me.isLoading = false; // Desactivar loader
        });
    },
    datosConfiguracion() {
      let me = this;
      me.isLoading = true; // Activar loader

      return axios
        .get("/configuracion")
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
        })
        .finally(() => {
          me.isLoading = false; // Desactivar loader
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
    listarItemCompuesto(page, buscar, criterio) {
      let me = this;
      me.isLoading = true; // Activar loader

      var url = "/itemcompuesto?buscar=" + buscar + "&criterio=" + criterio;
      return axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayArticulo = respuesta.articulos;
          me.first = 0; // Reiniciar a la primera página
        })
        .catch(function (error) {
          console.log(error);
        })
        .finally(() => {
          me.isLoading = false; // Desactivar loader
        });
    },
    onPageChange(event) {
      this.first = event.first;
      this.rowsPerPage = event.rows;
    },
    calcularPrecioValorMoneda(precio) {
      return Number((precio * parseFloat(this.monedaPrincipal)).toFixed(2));
    },
    registrarArticulo(data) {
      let me = this;
      var formulario = new FormData();
      for (var key in data) {
        if (data.hasOwnProperty(key)) {
          if (key === "productos") {
            formulario.append("productos", JSON.stringify(data.productos));
          } else {
            formulario.append(key, data[key]);
          }
        }
      }

      axios
        .post("/itemcompuesto/registrar", formulario, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })
        .then(function (response) {
          var respuesta = response.data;
          me.idarticulo = respuesta.idArticulo;
          console.log("respuesta = ", me.idarticulo);
          me.cerrarModal();
          me.listarItemCompuesto(1, "", "nombre");
          me.toastSuccess("Articulo registrado correctamente");
          console.log("stock ???", me.agregarStock);
          if (me.agregarStock == true) {
            let arrayArticulos = [
              {
                idarticulo: me.idarticulo,
                idalmacen: 1,
                cantidad: me.unidadStock,
                fecha_vencimiento: me.fechaVencimientoAlmacen,
              },
            ];
            console.log("registrar inventario qefqe", arrayArticulos);
            return axios.post("/inventarios/registrar", {
              inventarios: arrayArticulos,
            });
          }
        })
        .then(function (response) {
          if (response) {
            console.log(response.data);
          }
        })
        .catch(function (error) {
          console.error(error);
          me.toastError("Hubo un error al registrar el articulo o inventario");
        });
    },
    actualizarArticulo(data) {
      var formulario = new FormData();
      let me = this;
      for (var key in data) {
        if (data.hasOwnProperty(key)) {
          if (key === "productos") {
            formulario.append("productos", JSON.stringify(data.productos));
          } else {
            formulario.append(key, data[key]);
          }
        }
      }

      axios
        .post("/itemcompuesto/actualizar", formulario, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })
        .then(function (response) {
          //alert("Datos actualizados con éxito");
          //console.log("datos actuales",formData);
          var respuesta = response.data;
          console.log("respuesta = ", respuesta);
          console.log("foto ", data);
          me.cerrarModal();
          me.listarItemCompuesto(1, "", "nombre");
          me.toastSuccess("Articulo actualizado correctamente");
        })
        .catch(function (error) {
          console.log(error);
          me.toastError("No se puedo actualizar el articulo");
        });
    },
    desactivarArticulo(id) {
      Swal.fire({
        title: "¿Está seguro de ELIMINAR este artículo?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Aceptar!",
        cancelButtonText: "Cancelar",
        customClass: {
          confirmButton: "swal2-confirm-lineanew",
          cancelButton: "swal2-cancel-lineanew",
        },
        reverseButtons: true,
      }).then((result) => {
        if (result.isConfirmed) {
          let me = this;
          axios
            .put("/articulo/desactivar", {
              id: id,
            })
            .then(function (response) {
              me.listarItemCompuesto(1, "", "nombre");
              Swal.fire(
                "Desactivado!",
                "El registro ha sido desactivado con éxito.",
                "success"
              );
            })
            .catch(function (error) {
              console.log(error);
            });
        }
      });
    },
    activarArticulo(id) {
      Swal.fire({
        title: "¿Está seguro de activar este artículo?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Aceptar!",
        cancelButtonText: "Cancelar",
        reverseButtons: true,
      }).then((result) => {
        if (result.isConfirmed) {
          let me = this;
          axios
            .put("/articulo/activar", {
              id: id,
            })
            .then(function (response) {
              me.listarItemCompuesto(1, "", "nombre");
              Swal.fire(
                "Activado!",
                "El registro ha sido activado con éxito.",
                "success"
              );
            })
            .catch(function (error) {
              console.log(error);
            });
        }
      });
    },
    validarArticulo() {
      this.errorArticulo = 0;
      this.errorMostrarMsjArticulo = [];
      if (!this.nombre_producto) this.errorMostrarMsjArticulo.push("");
      if (!this.precio_venta) this.errorMostrarMsjArticulo.push("");

      if (this.errorMostrarMsjArticulo.length) this.errorArticulo = 1;

      return this.errorArticulo;
    },
    cerrarModal() {
      this.dialogVisible = false;
      this.tituloModal = "";
      this.codigo = "";
      this.nombre_producto = "";
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
    },
    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "articulo": {
          switch (accion) {
            case "registrar": {
              this.dialogVisible = true;
              this.tituloModal = "Registrar Servicio";
              this.agregarStock = false;
              this.tipoAccion = 1;
              this.fotografia = "";

              this.datosFormulario = {
                precio_variable: false,
                nombre: "",
                descripcion: "",
                precio_venta: null,
                precio_uno: null,
                precio_dos: null,
                precio_tres: null,
                precio_cuatro: null,
                codigo: "",
                idcategoria: null,
              };
              this.productosSeleccionados = []; // Limpiar productos seleccionados
              this.errores = {};
              break;
            }
            case "actualizar": {
              console.log("DATA ACTUALIZAR", data);
              this.agregarStock = false;
              this.dialogVisible = true;
              this.tituloModal = "Actualizar Artículo";
              this.tipoAccion = 2;
              this.datosFormulario = {
                nombre: data["nombre"],
                descripcion: data["descripcion"],
                precio_venta: this.calcularPrecioValorMoneda(
                  data["precio_venta"]
                ),
                precio_variable: data["precio_variable"] == 1,
                precio_uno: 0,
                precio_dos: 0,
                precio_tres: 0,
                precio_cuatro: 0,
                codigo: data["codigo"],
                idcategoria: null,
                idmedida: data["idmedida"],
                id: data["id"],
              };
              this.errores = {};
              this.idmedida = data["idmedida"];

              this.lineaSeleccionado = {
                nombre: data["nombre_categoria"],
                id: data["idcategoria"],
              };

              this.medidaSeleccionado = {
                descripcion_medida: data["descripcion_medida"],
                id: data["idmedida"],
              };

              this.precio_uno = this.calcularPrecioValorMoneda(
                data["precio_uno"]
              );
              this.precio_dos = this.calcularPrecioValorMoneda(
                data["precio_dos"]
              );
              this.precio_tres = this.calcularPrecioValorMoneda(
                data["precio_tres"]
              );
              this.precio_cuatro = this.calcularPrecioValorMoneda(
                data["precio_cuatro"]
              );

              // Obtener productos compuestos asociados
              this.productosSeleccionados = [];

              if (data["id"]) {
                axios
                  .get(`/itemcompuesto/${data["id"]}`)
                  .then((res) => {

                    // Guardar nombre del combo (si lo necesitas aquí)
                    this.nombreComboActual = res.data.nombre_compuesto || 'Detalle del Combo';

                    // Mapear SOLO los items
                    this.productosSeleccionados = (res.data.items || []).map((item) => ({
                      ...item,
                      precio_costo_unid: parseFloat(item.precio_costo_unid || 0),
                      precio_uno: parseFloat(item.precio_uno || 0),
                      cantidad: parseInt(item.cantidad || 1),
                    }));

                    console.log(
                      "productos seleccionados",
                      this.productosSeleccionados
                    );
                  })
                  .catch(() => {
                    this.productosSeleccionados = [];
                    this.nombreComboActual = 'Detalle del Combo';
                  });
              }
              break;
            }
          }
        }
      }
    },
    calcularPrecio(precio, index) {
      if (
        isNaN(this.datosFormulario.precio_costo_unid) ||
        isNaN(parseFloat(precio.porcentage))
      ) {
        return;
      }
      const margen_ganancia =
        parseFloat(this.datosFormulario.precio_costo_unid) *
        (parseFloat(precio.porcentage) / 100);
      const precio_publico =
        parseFloat(this.datosFormulario.precio_costo_unid) + margen_ganancia;
      console.log("precio publico", typeof precio_publico);
      if (index === 0) {
        this.precio_uno = Number(parseFloat(precio_publico).toFixed(2));
      } else if (index === 1) {
        this.precio_dos = Number(parseFloat(precio_publico).toFixed(2));
      } else if (index === 2) {
        this.precio_tres = Number(parseFloat(precio_publico).toFixed(2));
      } else if (index === 3) {
        this.precio_cuatro = Number(parseFloat(precio_publico).toFixed(2));
      }
    },
    recuperarIdRol() {
      let me = this;
      me.isLoading = true; // Activar loader

      return new Promise((resolve) => {
        this.rolUsuario = window.userData.rol;
        me.isLoading = false; // Desactivar loader
        resolve();
      });
    },
  },

  mounted() {
    this.handleResize();
    window.addEventListener("resize", this.handleResize);
    this.isLoading = true; // Activar loader al inicio
    Promise.all([
      this.recuperarIdRol(),
      this.datosConfiguracion(),
      this.obtenerConfiguracionTrabajo(),
      this.listarItemCompuesto(1, this.buscar, this.criterio),
      this.listarPrecio(),
      this.obtenerProductos(1, "", 5),
    ])
      .catch((error) => {
        console.error("Error en la inicialización:", error);
      })
      .finally(() => {
        this.isLoading = false; // Desactivar loader
        let today = new Date();
        let tomorrow = new Date(today);
        tomorrow.setDate(today.getDate() + 1);
        this.minDate = tomorrow;
      });
  },
};
</script>

<style scoped>
  .dialog-combo .p-dialog-header {
  padding: 0;
}

.dialog-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 1rem 1.25rem;
}

.icon-header {
  font-size: 2rem;
  color: #3f51b5;
}

.title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.subtitle {
  font-size: 0.85rem;
  color: #6c757d;
}

.dialog-content {
  padding: 0 1.25rem 1rem;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  padding: 0.75rem 1.25rem;
}

.cantidad-badge {
  background: #eef2ff;
  color: #3f51b5;
  padding: 4px 10px;
  border-radius: 12px;
  font-weight: 600;
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

.panel-titulo {
  font-weight: bold;
  font-size: 1.1rem;
  margin-bottom: 1rem;
  border-bottom: 2px solid #007bff;
  padding-bottom: 0.5rem;
}

.info-box {
  background: #f8f9fa;
  border-left: 4px solid #007bff;
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1rem;
}

.precio-sugerido-buttons .p-button {
  margin-left: 0.4rem;
}

.precio-inputgroup {
  background-color: #ffffff;
  border-radius: 0.4rem;
  padding: 0.8rem 1rem;
  border: 1px solid #dee2e6;
  margin-bottom: 1rem;
}

.label-bold {
  font-weight: 600;
  margin-bottom: 0.2rem;
  display: inline-block;
}

.input-wrapper {
  margin-bottom: 1.2rem;
}

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
