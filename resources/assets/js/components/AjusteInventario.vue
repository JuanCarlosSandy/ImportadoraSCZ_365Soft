<template>
  <main class="main">
    <div class="loading-overlay" v-if="isLoading">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">LOADING...</div>
      </div>
    </div>

    <Panel v-if="vistaActual === 'formulario'">
      <template #header>
        <div class="panel-header">
          <i class="pi pi-list mr-2"></i>
          <h4 class="panel-title">AJUSTE DE INVENTARIO</h4>
        </div>
      </template>

      <form @submit.prevent="enviarFormulario">
        <!-- PARTE 1: ALMACÉN Y PROVEEDOR -->
        <div class="row mt-3">
          <div class="col-md-6">
            <div class="form-group">
              <label class="font-weight-bold" for="almacen">
                Almacén <span class="text-danger">*</span>
              </label>
              <Dropdown id="almacen" v-model="idAlmacenSeleccionado" :options="arrayAlmacenes"
                optionLabel="nombre_almacen" optionValue="id" placeholder="Seleccione un almacén" class="form-control"
                @change="limpiarProductosSeleccionados" />
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label class="font-weight-bold">Proveedor</label>
              <div class="input-con-desplegable">
                <div class="p-inputgroup">
                  <input type="text" v-model="proveedorSeleccionado.nombre" @input="buscarProveedores($event)"
                    @keydown.down="moverSeleccionProveedor('abajo')" @keydown.up="moverSeleccionProveedor('arriba')"
                    @keydown.enter="seleccionarProveedorConEnter" placeholder="Buscar proveedor..."
                    class="p-inputtext p-component" />
                  <!-- Botón para limpiar el proveedor seleccionado -->
                  <Button v-if="proveedorSeleccionado.nombre" icon="pi pi-times" class="p-button-danger p-button-sm"
                    @click="limpiarProveedorSeleccionado" style="margin-left: 5px;" />
                </div>
                <ul v-if="mostrarDesplegableProveedor && proveedoresFiltrados.length > 0" class="desplegable-simple">
                  <li v-for="(proveedor, index) in proveedoresFiltrados" :key="proveedor.id"
                    @click="seleccionarProveedor(proveedor)"
                    :class="{ seleccionado: index === indiceSeleccionadoProveedor }">
                    {{ proveedor.nombre }}
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- PARTE 2: MOTIVO DE BAJA Y BOTÓN AGREGAR PRODUCTO -->
        <div class="row mt-3">
          <div class="col-md-8">
            <div class="form-group">
              <label class="font-weight-bold">
                Motivo de Baja <span class="text-danger">*</span>
              </label>
              <div class="p-inputgroup">
                <InputText placeholder="Seleccione un Motivo" v-model="motivoseleccionado.nombre" :disabled="true"
                  class="form-control" />
                <Button icon="pi pi-ellipsis-h" class="p-button-primary" @click="abrirModal2('Motivo')" />
              </div>
            </div>
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <Button label="Agregar Producto" icon="pi pi-plus" class="p-button-success w-100"
              @click="abrirDialogoProductos" :disabled="!idAlmacenSeleccionado" />
          </div>
        </div>

        <!-- Tabla de Productos Seleccionados -->
        <div class="row mt-3" v-if="productosSeleccionados.length > 0">
          <div class="col-md-12">
            <label class="font-weight-bold">Productos Seleccionados para Ajuste</label>
            <DataTable :value="productosSeleccionados" class="p-datatable-sm p-datatable-gridlines"
              responsiveLayout="scroll">
              <Column field="nombre" header="Producto">
                <template #body="slotProps">
                  <div>
                    <strong>{{ slotProps.data.nombre }}</strong>
                    <br>
                    <small class="text-muted">
                      <i class="pi pi-building"></i> {{ slotProps.data.nombre_proveedor || 'Sin proveedor' }}
                    </small>
                  </div>
                </template>
              </Column>

              <Column field="stock_actual" header="Stock Actual">
                <template #body="slotProps">
                  <span class="badge badge-info">

                    <template v-if="slotProps.data.modo_ajuste === 'unidad'">
                      {{ slotProps.data.stock_actual_unidades }} unidades
                    </template>

                    <template v-else>
                      {{ slotProps.data.stock_actual_cajas }} cajas y
                      {{ slotProps.data.stock_actual_unidades_sueltas }} u.
                    </template>

                  </span> </template>
              </Column>
              <Column header="Stock Real">
                <template #body="slotProps">
                  <InputText 
                      type="number" 
                      v-model="slotProps.data.stock_real" 
                      class="form-control text-center font-weight-bold" 
                      placeholder="0"
                      :min="0" 
                      @input="calcularDiferencia(slotProps.data)"
                      @keydown.tab.prevent="moverFoco(slotProps.index, $event, 'stock_real')"
                      :ref="'stock_real-' + slotProps.index" 
                  />
                  <small class="text-muted d-block text-center mt-1">
                      {{ slotProps.data.modo_ajuste === 'unidad' ? 'Unidades' : 'Cajas' }}
                  </small>
                </template>
              </Column>
              <Column header="Diferencia (Ajuste)">
                <template #body="slotProps">
                  <div class="text-center">
                      <span style="font-size: 1.1em; font-weight: bold;"
                            :class="slotProps.data.es_aumento ? 'text-success' : 'text-danger'">
                          {{ slotProps.data.cantidad_ajuste }}
                      </span>
                      
                      <div class="mt-1">
                          <span v-if="slotProps.data.cantidad_ajuste > 0" 
                                class="badge" 
                                :class="slotProps.data.es_aumento ? 'badge-success' : 'badge-danger'">
                              {{ slotProps.data.es_aumento ? 'ENTRADA (+)' : 'SALIDA (-)' }}
                          </span>
                          <span v-else class="badge badge-secondary">Sin cambios</span>
                      </div>
                  </div>
                </template>
              </Column>
              <Column field="stock_restante" header="Stock Ajustado">
                <template #body="slotProps">
                  <span class="badge" :class="slotProps.data.stock_restante < 0 ? 'badge-danger' : 'badge-success'">
                    {{ slotProps.data.stock_restante }}
                  </span>
                </template>
              </Column>
              <Column header="Acciones">
                <template #body="slotProps">
                  <Button icon="pi pi-trash" class="p-button-danger p-button-sm"
                    @click="eliminarProducto(slotProps.index)" title="Eliminar producto" />
                  <Button :label="slotProps.data.modo_ajuste === 'unidad' ? 'Unidad' : 'Caja'" :class="[
                    'p-button-sm',
                    slotProps.data.modo_ajuste === 'unidad'
                      ? 'p-button-info'
                      : 'p-button-success'
                  ]" @click="toggleModoAjuste(slotProps.data)" style="width: 90px; font-weight: bold;" />
                </template>
              </Column>
            </DataTable>

            <!-- Resumen de Totales -->
            <div class="mt-3 p-3" style="background-color: #f8f9fa; border-radius: 5px;">
              <div class="row" style="display: flex; justify-content: space-between;">
                <div class="col-md-4">
                  <strong><i class="pi pi-shopping-cart"></i> Productos: {{ productosSeleccionados.length }}</strong>
                </div>
                <div class="col-md-4">
                  <strong><i class="pi pi-box"></i> Total Unidades: {{ totalUnidadesAjuste }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Botones de Acción -->
        <div class="row mt-3">

          <!-- Izquierda (antes estaba el PDF) -->
          <div class="col-md-6 d-flex justify-content-start">
            <!-- Aquí ya no va nada -->
          </div>

          <!-- Derecha (cancelar y procesar) -->
          <div class="col-md-6 d-flex justify-content-end">
            <Button label="Cancelar" icon="pi pi-times" class="p-button-danger mr-2" @click="confirmarCancelar" />

            <Button label="Procesar Ajuste" icon="pi pi-check" class="p-button-success" @click="enviarFormulario"
              :disabled="!puedeEnviarFormulario()" :loading="isLoading" />
          </div>

        </div>

      </form>
    </Panel>

    <Panel v-if="vistaActual === 'tabla'">
      <template #header>
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
          <div style="display: flex; align-items: center;">
            <i class="pi pi-list mr-2"></i>
            <h4 class="panel-title mb-0">AJUSTE DE INVENTARIO</h4>
          </div>
          <Button label="Nuevo Ajuste" icon="pi pi-plus" class="p-button-success" @click="vistaActual = 'formulario'" />
        </div>
      </template>
      <div class="mt-3">
        <div class="toolbar-container">
          <div class="search-bar">
            <span class="p-input-icon-left">
              <i class="pi pi-search" />
              <InputText v-model="buscar" class="form-control" placeholder="Texto a buscar" />
            </span>
          </div>
          <div class="toolbar">
            <Button label="Reset" icon="pi pi-refresh" @click="resetBusquedaProductos" class="p-button-help p-button-sm"
              title="Limpiar" :disabled="!proveedorSeleccionado || !proveedorSeleccionado.nombre" />
          </div>
        </div>
        <DataTable :value="arrayAjuste" class="p-datatable-sm p-datatable-gridlines" responsiveLayout="scroll">           
          <Column field="nombre_almacen" header="ALMACEN" />

          <Column header="MOVIMIENTO" style="text-align: center; width: 140px;">
              <template #body="slotProps">
                  <span :class="slotProps.data.tipo_movimiento === 'entrada' ? 'badge badge-success' : 'badge badge-danger'"
                        style="font-size: 0.9em; padding: 5px 10px;">
                      
                      <i :class="slotProps.data.tipo_movimiento === 'entrada' ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" 
                        style="margin-right: 5px; font-weight: bold;"></i>
                      
                      {{ slotProps.data.tipo_movimiento === 'entrada' ? 'ENTRADA' : 'SALIDA' }}
                  </span>
              </template>
          </Column>
          <Column field="nombre" header="ARTICULO">
              <template #body="slotProps">
                  {{ slotProps.data.nombre }}
                  <small v-if="slotProps.data.descripcion_fabrica" class="text-muted d-block">
                      {{ slotProps.data.descripcion_fabrica }}
                  </small>
              </template>
          </Column>
          <Column field="cantidad" header="CANTIDAD" style="text-align: center; font-weight: bold;" />
          <Column field="tipo" header="MOTIVO / JUSTIFICACIÓN" /> 
          <Column field="created_at" header="FECHA Y HORA" />
        </DataTable>
        <Paginator :rows="pagination.per_page" :totalRecords="pagination.total"
          :first="(pagination.current_page - 1) * pagination.per_page" @page="onPageChange" />
      </div>
    </Panel>

    <Sidebar :visible.sync="dialogoProductosVisible" position="right" header="Agregar Productos" :style="{
      width: '90vw',
      maxWidth: '800px',
      marginTop: '78px',
      zIndex: 1050    // 👈 aquí lo aumentamos
    }" appendTo="body" @show="onSidebarShow" @hide="limpiarBusqueda">
      <div class="search-and-buttons p-mb-3 p-d-flex p-flex-column p-flex-md-row p-gap-2">
        <div class="search-bar p-flex-grow-1">
          <span class="p-input-icon-left p-w-full">
            <i class="pi pi-search" />
            <InputText ref="inputBusqueda" v-model="buscarA" class="form-control p-w-full" placeholder="Texto a buscar"
              @keyup="filtrarProductos" />
          </span>
        </div>
        <div class="p-d-flex p-gap-2">
          <Button label="Reset" icon="pi pi-refresh" @click="resetBusquedaProductos" class="p-button-help p-button-sm"
            title="Limpiar" :disabled="!buscarA" />
          <Button label="Agregar Todos" icon="pi pi-plus" class="p-button-success p-button-sm"
            @click="agregarTodosProductos" :disabled="!proveedorSeleccionado.id || arrayBuscador.length === 0" />
          <Button label="Escanear Código" icon="pi pi-qrcode" class="p-button-info p-button-sm"
            @click="iniciarEscaneo" />
        </div>
      </div>

      <!-- Tabla de productos -->
      <div class="table-responsive" style="margin-top: 2%">
        <DataTable :value="arrayBuscador" class="p-datatable-sm p-datatable-gridlines" responsiveLayout="scroll">
          <!-- Columnas de la tabla (sin cambios) -->
          <Column header="Seleccionar" style="width: 12%">
            <template #body="slotProps">
              <Button icon="pi pi-plus" class="p-button-primary p-button-sm"
                @click="seleccionarProducto(slotProps.data)"
                :disabled="productosSeleccionados.some(p => p.id === slotProps.data.id)"
                :title="productosSeleccionados.some(p => p.id === slotProps.data.id) ? 'Ya seleccionado' : 'Agregar producto'" />
            </template>
          </Column>
          <Column field="nombre" header="Producto">
            <template #body="slotProps">
              <div>
                <strong>{{ slotProps.data.nombre }}</strong>
                <br>
                <small class="text-muted">{{ slotProps.data.nombre_proveedor || 'Sin proveedor' }}</small>
              </div>
            </template>
          </Column>
          <!-- Cajas + Unidades -->
          <Column header="Stock (Cajas)">
            <template #body="slotProps">
              <span style="
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        background: #DBEAFE;   /* azul claro */
        color: #1E3A8A;        /* azul oscuro texto */
        font-weight: bold;
      ">
                {{ slotProps.data.stock_formateado }}
              </span>
            </template>
          </Column>

          <!-- Solo Unidades -->
          <Column header="Stock (Unidades)">
            <template #body="slotProps">
              <span style="
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        background: #DCFCE7;   /* verde claro */
        color: #065F46;        /* verde oscuro texto */
        font-weight: bold;
      ">
                {{ slotProps.data.stock_total_unidades }}
              </span>
            </template>
          </Column>
        </DataTable>
      </div>

      <!-- Paginador -->
      <Paginator :rows="pagination.per_page" :totalRecords="pagination.total"
        :first="(pagination.current_page - 1) * pagination.per_page" @page="(event) => {
          const page = Math.floor(event.first / event.rows) + 1;
          listarProducto(page, buscarA, criterioA, idAlmacenSeleccionado, false, proveedorSeleccionado ? proveedorSeleccionado.id : null);
        }" class="p-mt-3" />
    </Sidebar>

    <Dialog :visible.sync="dialogoEscaneoVisible" modal header="Escaneo de Código de Barras"
      :style="{ width: '95vw', maxWidth: '500px' }" :closable="true" @hide="cerrarEscaneo" class="responsive-dialog">
      <div style="position: relative; width: 100%; height: 80vh; background-color: #000;">
        <p style="position: absolute; top: 10px; left: 0; right: 0; color: white; text-align: center; z-index: 100;">
          Apunta la cámara al código de barras
        </p>
        <div id="escaneo-camara" style="width: 100%; height: 100%;"></div>
      </div>
    </Dialog>

    <!-- MODAL PARA SELECCIONAR MOTIVOS -->
    <Dialog :visible.sync="modal2" modal :header="tituloModal2" :closable="true" @hide="cerrarModal2"
      class="responsive-dialog" :containerStyle="dialogContainerStyle">
      <!-- Contenido del modal -->
      <div class="toolbar-container">
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText v-model="buscarA" @keyup="listarMotivo(1, buscarA, criterioA)" class="form-control"
              placeholder="Buscar motivo..." />
          </span>
        </div>
        <div class="toolbar">
          <Button label="Reset" icon="pi pi-refresh" @click="resetBusquedaMotivos" class="p-button-help p-button-sm"
            title="Limpiar" />
          <!-- Botón para añadir nuevo motivo -->
          <Button label="Nuevo Motivo" icon="pi pi-plus" class="p-button-secondary p-button-sm"
            @click="abrirModalNuevoMotivo" />
        </div>
      </div>

      <div class="table-responsive">
        <DataTable :value="arrayBuscador" class="p-datatable-sm p-datatable-gridlines" responsiveLayout="scroll">
          <!-- Columnas de la tabla de motivos -->
          <Column header="Seleccionar" style="width: 15%">
            <template #body="slotProps">
              <Button icon="pi pi-check" class="p-button-success p-button-sm" @click="seleccionar(slotProps.data)" />
            </template>
          </Column>
          <Column field="nombre" header="Motivo de Baja" />
        </DataTable>
      </div>

      <Paginator :rows="pagination.per_page" :totalRecords="pagination.total"
        :first="(pagination.current_page - 1) * pagination.per_page" @page="onPageChange" />

      <template #footer>
        <Button label="Cerrar" icon="pi pi-times" class="p-button-danger p-button-sm" @click="cerrarModal2"
          type="button" />
      </template>
    </Dialog>

    <!-- MODAL PARA REGISTRAR NUEVO MOTIVO -->
    <Dialog :visible.sync="modal3" modal :header="tituloModal3" :closable="true" @hide="cerrarModal3"
      class="responsive-dialog" :containerStyle="dialogContainerStyle">
      <!-- Contenido del modal -->
      <div v-if="tituloModal2 !== 'Proveedors'">
        <form class="form-horizontal">
          <div v-if="tituloModal2 !== 'Grupos' && tituloModal2 !== 'Lineas'" class="form-group row">
            <label class="col-md-3 form-control-label" for="text-input">
              Nombre
            </label>
            <div class="col-md-9">
              <InputText type="text" v-model="nombre" class="form-control1" placeholder="Ingrese nombre del motivo" />
            </div>
          </div>
        </form>
      </div>
      <template #footer>
        <Button label="Cerrar" icon="pi pi-times" class="p-button-secondary p-button-sm" @click="cerrarModal3"
          type="button" />
        <Button v-if="tipoAccion2 == 5" class="p-button-primary p-button-sm" label="Guardar" icon="pi pi-check"
          @click="registrarMarca" type="button" />
        <Button v-if="tipoAccion2 == 6" class="p-button-primary p-button-sm" label="Actualizar" icon="pi pi-check"
          @click="actualizarMarca" type="button" />
      </template>
    </Dialog>
  </main>
</template>


<script>
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Dropdown from "primevue/dropdown";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import Paginator from "primevue/paginator";
import { esquemaArticulos, esquemaInventario } from "../constants/validations";
import VueBarcode from "vue-barcode";
import Panel from "primevue/panel";
import Sidebar from 'primevue/sidebar';
import AutoComplete from 'primevue/autocomplete';

export default {
  components: {
    Button,
    InputText,
    Dropdown,
    DataTable,
    Column,
    Dialog,
    Paginator,
    barcode: VueBarcode,
    Panel,
    Sidebar,
    AutoComplete,
  },
  data() {
    return {
      modo_ajuste: 'unidad',
      mostrarLabel: true,
      buscar: "",
      isLoading: false,
      idAlmacenSeleccionado: null,
      categoriaSeleccionada: "",

      tipo_stock: "paquetes",
      idarticulo: 0,
      fechaVencimientoSeleccion: "0",
      arrayAlmacenes: [],
      AlmacenSeleccionado: null,
      productosSeleccionados: [],
      datosFormulario: {
        cantidad: 0,
        idtipobaja: null,
        producto: null,
        idAlmacenSeleccionado: null,
      },

      stock_actual: 0,
      stock_restante: 0,

      errores: {},
      monedaPrincipal: [],

      criterioA: "nombre",
      buscarA: "",
      tituloModal2: "",
      motivoseleccionado: [],
      industriaseleccionada: [],
      productoseleccionado: [],
      proveedorseleccionada: [],
      gruposeleccionada: [],
      nombre_grupo: "",

      modal2: false,
      idcategoria: 0,
      idmarca: 0,
      idindustria: 0,
      idproveedor: 0,
      idgrupo: 0,
      idmedida: 0,
      nombre_categoria: "",
      nombre_proveedor: "",
      //id:'',//aumente 7 julio
      codigo: "",
      nombre: "",
      nombre_generico: "",
      //validaion para inputs

      lineaseleccionadaVacio: false,
      marcaseleccionadaVacio: false,
      //aumente esto
      unidad_envase: 0,
      precio_costo_unid: 0,
      precio_costo_paq: 0,
      //hasta aqui
      precios: [],
      precio: "", //aumente 5julio

      //aumento 13_junio
      precio_uno: 0,
      precio_dos: 0,
      precio_tres: 0,
      precio_cuatro: 0,
      //hasta aqui

      //--------hasta aqui-----------------
      //--grupo--
      nombre_grupo: "",
      //---hasta aqui
      precio_venta: 0,
      costo_compra: 0,

      stock: 5,
      descripcion: "",
      fotografia: "",
      fotoMuestra: null,
      arrayAjuste: [],
      arrayBuscador: [],
      modal: 0,

      tituloModal: "",
      tipoAccion: 0,
      tipoAccion2: 0,
      //------registro industia, marcas--
      modal3: false,
      tituloModal3: "",
      marca_id: 0,
      condicion: 1,
      nombre_industria: "",
      //--------hasta aqui---
      pagination: this.createPaginationObject(),
      offset: {
        pagination: 3,
      },
      criterio: "articulos.nombre", // Por defecto
      categoria: "", // Nueva categoría seleccionada

      //CONFIGURACIONES
      mostrarSaldosStock: "",
      mostrarProveedores: "",
      mostrarCostos: "",
      rolUsuario: "",

      descripcion_medida: "",
      medidaseleccionada: [],
      // NUEVO REFACTORIZACION AJUSTES
      dialogoProductosVisible: false,
      proveedorSeleccionado: { nombre: '' },
      proveedoresFiltrados: [],
      mostrarDesplegableProveedor: false,
      indiceSeleccionadoProveedor: -1,
      dialogoEscaneoVisible: false,
      indiceFoco: -1,
      vistaActual: 'tabla',
    };
  },
  computed: {
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
    isActived: function () {
      return this.pagination.current_page;
    },
    pagesNumber: function () {
      return this.calculatePages(this.pagination, this.offset.pagination);
    },

    totalUnidadesAjuste() {
      return this.productosSeleccionados.reduce((total, producto) => {
        const cantidad = parseInt(producto.cantidad_ajuste) || 0;
        return total + (cantidad > 0 ? cantidad : 0);
      }, 0);
    },

    hayProductosSeleccionados() {
      return this.productosSeleccionados.length > 0;
    }
  },
  //añadido en fecha 14/03/25
  // Corrección de los watchers
  watch: {
    buscar: {
      handler: function (val) {
        if (this._debounceBuscar) clearTimeout(this._debounceBuscar);
        this._debounceBuscar = setTimeout(() => {
          this.listarAjuste(1, val, "");
        }, 400);
      },
    },
    // Para el producto seleccionado
    productoseleccionado: {
      handler(newVal) {
        if (newVal && newVal.id) {
          // Actualizar el ID del producto en datosFormulario
          this.datosFormulario.producto = newVal.id;
          // Llamar a obtenerStock
          this.obtenerStock();
        }
      },
      deep: true,
    },

    // Para el almacén seleccionado
    idAlmacenSeleccionado: function (newVal) {
      this.AlmacenSeleccionado = newVal; // Sincronizar ambas variables
      this.datosFormulario.idAlmacenSeleccionado = newVal; // Mantener siempre actualizado el id en el formulario

      if (newVal !== this.idAlmacenAnterior) {
        this.limpiarProductosSeleccionados();
        this.idAlmacenAnterior = newVal;
      }

      if (newVal && this.productoseleccionado && this.productoseleccionado.id) {
        this.obtenerStock();
      }
    },

    motivoseleccionado: {
      handler(newVal) {
        this.asignarCampos();
      },
      deep: true,
    },

    // Para la cantidad
    "datosFormulario.cantidad": function (newVal) {
      this.actualizarStock();
    },
  },

  methods: {
    toggleModoAjuste(producto) {
      producto.modo_ajuste =
        producto.modo_ajuste === 'unidad' ? 'caja' : 'unidad';

      // Recalcular todo al cambiar modo
      this.calcularDiferencia(producto);
    },
    resetBusqueda() {
      this.buscar = "";
      this.listarAjuste(1, this.buscar || "", "", this.categoria);
    },

    resetBusquedaProductos() {
      this.buscarA = '';
      let idProveedor = null;
      if (this.proveedorSeleccionado && this.proveedorSeleccionado.id) {
        idProveedor = this.proveedorSeleccionado.id;
      }
      this.listarProducto(1, '', this.criterioA, this.idAlmacenSeleccionado, false, idProveedor);
    },

    resetBusquedaMotivos() {
      this.buscarA = "";
      this.listarMotivo(1, "", this.criterioA);
    },

    enfocarInputBusqueda() {
      this.$nextTick(() => {
        if (this.$refs.inputBusqueda && this.$refs.inputBusqueda.$el) {
          this.$refs.inputBusqueda.$el.focus();
        } else {
          console.error('No se encontró el elemento input');
        }
      });
    },

    onSidebarShow() {
      this.actualizarListaProductos();
      this.enfocarInputBusqueda();
    },

    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    onPageChange(event) {
      const page = Math.floor(event.first / event.rows) + 1;
      this.cambiarPagina(page);
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
    asignarCampos() {
      this.datosFormulario.producto = this.productoseleccionado.id;
      this.datosFormulario.idtipobaja = this.motivoseleccionado.id;
      this.datosFormulario.idAlmacenSeleccionado = this.idAlmacenSeleccionado;
    },
    async validarCampo() {
      this.asignarCampos();
    },

    async enviarFormulario() {
      console.log('Productos seleccionados antes de enviar:', this.productosSeleccionados);

      const productosConStockReal = this.productosSeleccionados.filter(producto => {
        return producto.stock_real !== null && producto.stock_real !== undefined && producto.stock_real !== ''; 
      });

      if (productosConStockReal.length === 0) {
        this.toastError("Debe ingresar el stock real para al menos un producto");
        return;
      }

      const productosInvalidos = this.productosSeleccionados.filter(p => {
        if (!p.es_aumento && p.cantidad_ajuste > p.stock_actual) {
            return true; 
        }
        return false;
      });

      if (productosInvalidos.length > 0) {
        this.toastError("Error: Estás intentando dar de baja más stock del disponible.");
        return;
      }

      if (!this.motivoseleccionado || !this.motivoseleccionado.id) {
        this.toastError("Debe seleccionar un motivo de baja");
        return;
      }

      try {
        this.isLoading = true;

        const ajustesData = {
          almacen_id: this.idAlmacenSeleccionado,
          motivo_id: this.motivoseleccionado.id,
          productos: this.productosSeleccionados.map(producto => {
            const esEntrada = (producto.es_aumento === true); 

            return {
              producto_id: producto.id,
              cantidad: producto.cantidad_ajuste, 
              tipo_movimiento: esEntrada ? 'entrada' : 'salida', 
              
              stock_anterior: producto.stock_actual,
              stock_real: producto.stock_real,
              fecha_vencimiento: producto.fecha_vencimiento_seleccionada ? producto.fecha_vencimiento_seleccionada.fecha_vencimiento : null
            };
          }).filter(p => p.cantidad > 0) 
        };

        console.log('Datos que se envían al backend (JSON Final):', ajustesData);

        await this.registrarAjusteMultiple(ajustesData);

        this.$toast.add({
          severity: 'success',
          summary: 'Éxito',
          detail: 'El ajuste de inventario se realizó correctamente.',
          life: 1500
        });

        this.productosSeleccionados = [];
        this.motivoseleccionado = { nombre: '' };
        this.proveedorSeleccionado = { nombre: '' };
        this.buscarA = '';

        setTimeout(() => {
          this.vistaActual = 'tabla';
        }, 1500);

      } catch (error) {
        console.error("Error al registrar el ajuste múltiple: ", error);
        let mensajeError = 'Error al registrar el ajuste.';
        
        if (error.response) {
          if (error.response.status === 422) {
            console.log('Errores de validación:', error.response.data.errors);
            mensajeError = 'Error de validación: Revise los datos.';
          } else {
            mensajeError = `Error ${error.response.status}: ${error.response.data.message}`;
          }
        }
        
        this.$toast.add({ severity: 'error', summary: 'Error', detail: mensajeError, life: 5000 });
      } finally {
        this.isLoading = false;
      }
    },

    guardarYVolver() {
      this.enviarFormulario();
      this.vistaActual = 'tabla';
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
    createPaginationObject() {
      return {
        total: 0,
        current_page: 0,
        per_page: 0,
        last_page: 0,
        from: 0,
        to: 0,
      };
    },

    //-------------hasta qui calcular -----------
    seleccionar(selected) {
      if (this.tituloModal2 == "Motivo") {
        this.motivoseleccionado = selected;
        this.marcaseleccionadaVacio = false;
      } else if (this.tituloModal2 == "Productos") {
        if (selected.condicion == 1) {
          this.agregarProductoSeleccionado(selected);
        } else if (selected.condicion == 0) {
          this.advertenciaInactiva("Productos");
        }
      }

      this.arrayBuscador = [];
      this.modal2 = false;
    },

    cerrarModal2() {
      this.arrayBuscador = [];
      this.modal2 = false;
      this.buscarA = "";
    },

    confirmarCancelar() {
      this.$swal.fire({
        title: '¿Está seguro que desea cancelar?',
        text: 'Se perderán todos los cambios.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No'
      }).then((result) => {
        if (result.isConfirmed) {
          this.reiniciarFormulario();
        }
      });
    },

    reiniciarFormulario() {
      this.idAlmacenSeleccionado = null;
      this.proveedorSeleccionado = { nombre: '' };
      this.motivoseleccionado = {};
      this.productosSeleccionados = [];
      this.buscarA = '';
      this.arrayBuscador = [];
      this.vistaActual = 'tabla';
    },

    listarProducto(page, buscar, criterio, idAlmacen, todos, idProveedor) {
      let me = this;
      let url = `/articuloAjusteInven?page=${page}&buscar=${buscar}&criterio=${criterio}&idAlmacen=${idAlmacen}`;
      if (idProveedor) {
        url += `&idProveedor=${idProveedor}`;
      }
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayBuscador = respuesta.articulos.data;
          me.pagination = respuesta.pagination;
        })
        .catch(function (error) {
          console.log("Error al listar los productos:", error);
        });
    },

    actualizarListaProductos() {
      const page = 1;
      const buscar = this.buscarA || "";
      const criterio = this.criterioA || "nombre";
      const idAlmacen = this.idAlmacenSeleccionado;
      const idProveedor = this.proveedorSeleccionado ? this.proveedorSeleccionado.id : null;

      this.listarProducto(page, buscar, criterio, idAlmacen, false, idProveedor);
    },

    seleccionarCategoria(categoria) {
      this.categoriaSeleccionada = categoria; // Almacenar la categoría seleccionada
      this.listarProducto(
        1,
        this.buscarA,
        this.criterioA,
        this.idAlmacenSeleccionado
      ); // Actualizar la lista con la nueva categoría
    },

    selectAlmacen() {
      let me = this;
      var url = "/almacen/selectAlmacen";
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayAlmacenes = respuesta.almacenes;
        })
        .catch(function (error) {
          console.log(error);
        });
    },


    abrirDialogoProductos() {
      this.dialogoProductosVisible = true;
      let idProveedor = null;
      if (this.proveedorSeleccionado && this.proveedorSeleccionado.id) {
        idProveedor = this.proveedorSeleccionado.id;
      }
      this.listarProducto(1, this.buscarA, this.criterioA, this.idAlmacenSeleccionado, false, idProveedor);
    },


    //FALTA CODIGO PARA QUE FUNCIONE EL ESCANEO DEL CODIGO DE BARRAS

    esDispositivoMovil() {

    },


    iniciarEscaneo() {

    },

    iniciarQuagga() {

    },

    onDetected(result) {

    },

    cerrarEscaneo() {

    },

    abrirModal2(titulo) {
      if (titulo === "Motivo") {
        this.listarMotivo(1, "", "nombre");
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.marcaseleccionadaVacio = false;
      } else if (titulo === "Productos") {
        if (!this.idAlmacenSeleccionado) {
          swal({
            icon: "warning",
            title: "Almacén no seleccionado",
            text: "Primero seleccione un almacén válido",
          });
          return;
        }
        this.listarProducto(
          1,
          this.buscarA,
          this.criterioA,
          this.idAlmacenSeleccionado
        );
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.lineaseleccionadaVacio = false;
      }
    },

    async agregarProductoSeleccionado(producto) {
      const productoExistente = this.productosSeleccionados.find(p => p.id === producto.id);

      if (productoExistente) {
        this.toastError("Este producto ya está en la lista");
        return;
      }

      try {
        const stockData = await this.obtenerStockProducto(producto.id, this.idAlmacenSeleccionado);

        const productoConStock = {
          ...producto,
          stock_actual: stockData.stock_actual || 0,
          cantidad_ajuste: 0,
          stock_restante: stockData.stock_actual || 0
        };

        this.productosSeleccionados.push(productoConStock);

      } catch (error) {
        console.error("Error al obtener stock del producto:", error);
        this.toastError("Error al obtener información del producto");
      }
    },

    async obtenerStockProducto(productoId, almacenId) {
      try {
        const response = await axios.get("/ajuste-inventario/obtenerStock", {
          params: {
            producto: productoId,
            almacen: almacenId,
          },
        });
        return response.data;
      } catch (error) {
        console.error("Error al obtener stock:", error);
        return { stock_actual: 0 };
      }
    },

    actualizarStockRestante(producto) {
      const cantidad = parseInt(producto.cantidad_ajuste) || 0;
      producto.stock_restante = Math.max(0, producto.stock_actual - cantidad);
    },

    eliminarProducto(index) {
      const producto = this.productosSeleccionados[index];
      this.productosSeleccionados.splice(index, 1);
    },

    limpiarProductosSeleccionados() {
      if (this.productosSeleccionados.length > 0) {
        this.productosSeleccionados = [];
      }
    },

    puedeEnviarFormulario() {
      if (!this.idAlmacenSeleccionado) return false;
      if (!this.motivoseleccionado.id) return false;
      if (this.productosSeleccionados.length === 0) return false;

      // Al menos un producto debe tener cantidad > 0
      const tieneProductosConCantidad = this.productosSeleccionados.some(producto => {
        const cantidad = parseInt(producto.cantidad_ajuste) || 0;
        return cantidad > 0;
      });

      return tieneProductosConCantidad;
    },

    validarFormularioMultiple() {
      if (!this.idAlmacenSeleccionado) return false;
      if (!this.motivoseleccionado.id) return false;
      if (this.productosSeleccionados.length === 0) return false;

      // TODOS los productos deben tener cantidad > 0 y válida
      return this.productosSeleccionados.every(producto => {
        const cantidad = parseInt(producto.cantidad_ajuste) || 0;
        return cantidad > 0 && cantidad <= producto.stock_actual;
      });
    },

    async registrarAjusteMultiple(datosParaEnviar) {
        try {
          this.isLoading = true;
          
          const response = await axios.post("/ajuste/registrar-multiple", datosParaEnviar, {
            headers: {
              "Content-Type": "application/json",
            },
          });

          this.cerrarModal();
          await this.listarAjuste(1, "", "");
          this.toastSuccess("Ajuste de Inventario registrado correctamente");            
        } catch (error) {
          console.error("Error:", error);
          throw error; 
        } finally {
          this.isLoading = false;
        }
    },

    formatearPrecio(precio) {
      if (!precio) return "0.00";
      return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB'
      }).format(precio);
    },

    almacenSeleccionado() { },

    listarAlmacenes(page, buscar, criterio) {
      let me = this;
      var url = `/almacen?page=${page}&buscar=${buscar}&criterio=${criterio}`;
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayBuscador = respuesta.almacenes.data;
          me.pagination = respuesta.pagination;
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    async listarAjuste(page, buscar, criterio) {
      try {
        let me = this;
        var url = `/ajusteinv?page=${page}&buscar=${buscar || ""}&categoria=${this.categoria
          }`;

        const response = await axios.get(url);
        var respuesta = response.data;
        me.arrayAjuste = respuesta.ajuste.data;
        me.pagination = respuesta.pagination;
      } catch (error) {
        console.error("Error al listar artículos:", error);
      }
    },

    filtrarProductos() {
      this.listarProducto(1, this.buscarA, this.criterioA, this.idAlmacenSeleccionado, false, this.proveedorSeleccionado ? this.proveedorSeleccionado.id : null);
    },

    limpiarBusqueda() {
      this.buscarA = "";
      this.resetBusquedaProductos();
    },

    filtrarPorCategoria(categoria) {
      this.categoria = categoria;
      this.listarAjuste(1, this.buscar, "");
    },

    listarMarca(page, buscar, criterio) {
      let me = this;
      var url =
        "/marca?page=" + page + "&buscar=" + buscar + "&criterio=" + criterio;

      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;

          me.arrayBuscador = respuesta.marcas.data;
          me.pagination = respuesta.pagination;
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    listarMotivo(page, buscar, criterio) {
      let me = this;
      var url =
        "/motivos?page=" + page + "&buscar=" + buscar + "&criterio=" + criterio;
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          console.log(respuesta);
          me.arrayBuscador = respuesta.motivos.data;
          me.pagination = respuesta.pagination;
        })
        .catch(function (error) {
          console.log("ERRORES", error);
        });
    },

    buscarProveedores(event) {
      const query = event.target.value;
      if (!query.trim().length) {
        this.proveedoresFiltrados = [];
        this.mostrarDesplegableProveedor = false;
        return;
      }
      axios.get(`/proveedor/selectNombreProveedor?filtro=${query}`)
        .then(response => {
          this.proveedoresFiltrados = response.data.proveedores;
          this.mostrarDesplegableProveedor = this.proveedoresFiltrados.length > 0;
        })
        .catch(error => {
          console.error("Error al buscar proveedores:", error);
        });
    },

    moverSeleccionProveedor(direccion) {
      if (!this.mostrarDesplegableProveedor || this.proveedoresFiltrados.length === 0) return;
      if (direccion === 'abajo') {
        this.indiceSeleccionadoProveedor = (this.indiceSeleccionadoProveedor + 1) % this.proveedoresFiltrados.length;
      } else if (direccion === 'arriba') {
        this.indiceSeleccionadoProveedor = (this.indiceSeleccionadoProveedor - 1 + this.proveedoresFiltrados.length) % this.proveedoresFiltrados.length;
      }
    },

    seleccionarProveedorConEnter() {
      if (this.indiceSeleccionadoProveedor >= 0 && this.indiceSeleccionadoProveedor < this.proveedoresFiltrados.length) {
        this.seleccionarProveedor(this.proveedoresFiltrados[this.indiceSeleccionadoProveedor]);
      }
    },

    seleccionarProveedor(proveedor) {
      this.proveedorSeleccionado = { id: proveedor.id, nombre: proveedor.nombre };
      this.mostrarDesplegableProveedor = false;
    },

    limpiarProveedorSeleccionado() {
      this.proveedorSeleccionado = { id: null, nombre: '' };
      this.mostrarDesplegableProveedor = false;
      this.actualizarListaProductos();
    },

    resetBusquedaProductos() {
      this.buscarA = '';
      let idproveedor = null;
      if (this.proveedorSeleccionado && this.proveedorSeleccionado.id) {
        idproveedor = this.proveedorSeleccionado.id;
      }
      this.listarProducto(1, '', this.criterioA, this.idAlmacenSeleccionado, false, idproveedor);
    },

    resetBusquedaMotivos() {
      this.buscarA = '';
      this.listarMotivo(1, '', this.criterioA);
    },

    agregarTodosProductos() {
      const productosFiltrados = this.arrayBuscador.filter(
        producto => !this.productosSeleccionados.some(p => p.id === producto.id)
      );

      productosFiltrados.forEach(producto => {
        // Obtener la fecha de vencimiento más cercana
        const fechaVencimientoMasCercana = producto.fechas_vencimiento && producto.fechas_vencimiento.length > 0
          ? producto.fechas_vencimiento.reduce((prev, curr) =>
            new Date(prev.fecha_vencimiento) < new Date(curr.fecha_vencimiento) ? prev : curr
          )
          : null;

        this.productosSeleccionados.push({
          ...producto,
          fecha_vencimiento_seleccionada: fechaVencimientoMasCercana,
          cantidad_ajuste: 0,
          stock_restante: producto.stock_total,
          stock_actual: producto.stock_total,
          stock_real: 0
        });
      });

      this.$toast.add({ severity: 'success', summary: 'Éxito', detail: 'Productos agregados' });
    },

    seleccionarProducto(producto) {
      // Obtener la fecha de vencimiento más cercana (si existe)
      const fechaVencimientoMasCercana =
        producto.fechas_vencimiento && producto.fechas_vencimiento.length > 0
          ? producto.fechas_vencimiento[0]
          : null;

      // Verificar si el producto ya está seleccionado
      const productoExistente = this.productosSeleccionados.find(p => p.id === producto.id);

      if (!productoExistente) {

        this.productosSeleccionados.push({
          ...producto,
          fecha_vencimiento_seleccionada: fechaVencimientoMasCercana,
          cantidad_ajuste: 0,

          // STOKS
          stock_actual_unidades: producto.stock_total_unidades,
          stock_actual_cajas: producto.stock_total_cajas,
          stock_actual_unidades_sueltas: producto.stock_total_unidades_sueltas,
          stock_formateado: producto.stock_formateado,

          // Modo de ajuste (nuevo)
          modo_ajuste: 'unidad',

          stock_restante: producto.stock_total_unidades,
          stock_real: null
        });

        this.$toast.add({
          severity: 'success',
          summary: 'Éxito',
          detail: 'Producto agregado'
        });
      }
    },



    cambiarPagina(page) {
      let me = this;
      //Actualiza la página actual
      me.pagination.current_page = page;
      //Envia la petición para visualizar la data de esa página
      me.listarAjuste(page, me.buscar || "", "", me.categoria);
    },

    cambiarPaginaMarca(page, buscar, criterio) {
      let me = this;
      //Actualiza la página actual
      me.pagination.current_page = page;
      me.listarMotivo(page, buscar, criterio);
      //Envia la petición para visualizar la data de esa página
    },
    cambiarPaginaLinea(page, buscar, criterio, idAlmacenSeleccionado) {
      let me = this;
      //Actualiza la página actual
      me.pagination.current_page = page;
      me.listarProducto(page, buscar, criterio, idAlmacenSeleccionado);
      //Envia la petición para visualizar la data de esa página
    },

    // Método para obtener el stock actual 14/03/25
    async obtenerStock() {
      try {
        this.isLoading = true; // Activar loading
        const productoId =
          this.datosFormulario.producto ||
          (this.productoseleccionado ? this.productoseleccionado.id : null);
        const almacenId =
          this.idAlmacenSeleccionado || this.AlmacenSeleccionado;

        if (!productoId || !almacenId) {
          this.stock_actual = 0;
          this.stock_restante = 0;
          return;
        }

        const response = await axios.get("/ajuste-inventario/obtenerStock", {
          params: {
            producto: productoId,
            almacen: almacenId,
          },
        });

        this.stock_actual = response.data.stock_actual;
        this.actualizarStock();
      } catch (error) {
        console.error("Error al obtener el stock:", error);
        console.error(
          "Detalles del error:",
          error.response ? error.response.data : error.message
        );
        this.stock_actual = 0;
        this.stock_restante = 0;
      } finally {
        setTimeout(() => {
          this.isLoading = false;
        }, 500);
      }
    },
    actualizarStock() {
      const cantidad = parseInt(this.datosFormulario.cantidad) || 0;
      this.stock_restante = Math.max(0, this.stock_actual - cantidad);
    },

    calcularDiferencia(producto) {
        if (producto.stock_real === undefined || producto.stock_real === null || producto.stock_real === '') {
            this.$set(producto, 'cantidad_ajuste', 0);
            return;
        }

        const envase = Number(producto.unidad_envase) || 1;
        const inputUsuario = Number(producto.stock_real);
        const stockActualUnidades = Number(producto.stock_actual_unidades); 

        let stockFisicoEnUnidades = 0;

        if (producto.modo_ajuste === 'caja') {
            stockFisicoEnUnidades = inputUsuario * envase;
        } else {
            stockFisicoEnUnidades = inputUsuario;
        }

        const diferencia = stockFisicoEnUnidades - stockActualUnidades;

        if (diferencia >= 0) {
            this.$set(producto, 'es_aumento', true);
            this.$set(producto, 'cantidad_ajuste', diferencia);
        } else {
            this.$set(producto, 'es_aumento', false);
            this.$set(producto, 'cantidad_ajuste', Math.abs(diferencia));
        }

        this.$set(producto, 'stock_restante', stockFisicoEnUnidades);
    },

    moverFoco(index, event, tipoCampo) {
      event.preventDefault();
      const totalProductos = this.productosSeleccionados.length;
      let nuevoIndice = index + 1;

      if (nuevoIndice >= totalProductos) {
        nuevoIndice = 0;
      }

      this.$nextTick(() => {
        let inputRef;
        if (tipoCampo === 'stock_real') {
          inputRef = this.$refs[`cantidad_ajuste-${nuevoIndice}`];
        } else if (tipoCampo === 'cantidad_ajuste') {
          inputRef = this.$refs[`stock_real-${nuevoIndice}`];
        }

        if (inputRef && inputRef[0]) {
          const inputElement = inputRef[0].$el.querySelector('input');
          if (inputElement) {
            inputElement.focus();
          }
        }
      });
    },

    calcularPrecioValorMoneda(precio) {
      return (precio * parseFloat(this.monedaPrincipal)).toFixed(2);
    },

    async registrarAjuste(data) {
      let me = this;
      try {
        this.isLoading = true; // Activar loading
        var formulario = new FormData();
        for (var key in data) {
          if (data.hasOwnProperty(key)) {
            formulario.append(key, data[key]);
          }
        }

        const response = await axios.post("/ajuste/registrar", formulario, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        });

        me.idarticulo = response.data.id;
        me.cerrarModal();
        await me.listarAjuste(1, "", "");
      } catch (error) {
        console.error(error);
        me.toastError("Error al registrar el ajuste de inventario");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },

    advertenciaInactiva(nombre) {
      swal({
        title: "Opción Inactiva",
        text: "No puede seleccionar esta opción porque está inactiva.",
        type: "warning",
        confirmButtonColor: "#3085d6",
        confirmButtonText: "Aceptar",
        confirmButtonClass: "btn btn-success",
        buttonsStyling: false,
      }).then(() => {
        this.abrirModal2(nombre);
      });
    },

    //#################registro industria############
    registrarMarca() {
      let me = this;

      axios
        .post("/motivo/registrar", {
          nombre: this.nombre,
        })
        .then(function (response) {
          me.cerrarModal3();
          //me.modal3=0;
          me.listarMotivo(1, "", "id");
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    cerrarModal() {
      this.modal = 0;
      this.tituloModal = "";
      this.cantidad = 0;
      this.idtipobaja = "";
      this.producto = "";
      this.buscarA = "";
      this.productosSeleccionados = [];
    },

    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "articulo": {
          switch (accion) {
            case "registrar": {
              this.modal = 1;
              this.tituloModal = "AJUSTE DE INVENTARIO";
              this.tipoAccion = 1;
              this.fotografia = "";

              this.datosFormulario = {
                cantidad: 0,
                idtipobaja: null,
                producto: null,
                idAlmacenSeleccionado: null,
              };

              this.idAlmacenSeleccionado = null;
              this.categoriaSeleccionada = "";
              this.productoseleccionado = [];
              this.motivoseleccionado = [];
              this.buscarA = "";

              this.productosSeleccionados = [];

              this.errores = {};
              break;
            }
          }
        }
      }
    },

    cerrarModal3() {
      this.modal3 = 0;
      this.tituloModal3 = "";
      this.nombre = "";
    },

    abrirModal3(modelo3, accion3, data = []) {
      switch (modelo3) {
        case "Marca": {
          switch (accion3) {
            case "registrarMar": {
              this.modal3 = 1;
              this.tituloModal3 = "Registrar Motivo de Bajass";
              this.nombre = "";
              this.tipoAccion2 = 5;
              break;
            }
            case "actualizar": {
              this.modal3 = 1;
              this.tituloModal3 = "Actualizar marca";
              this.tipoAccion2 = 6;
              this.marca_id = data["id"];
              this.nombre = data["nombre"];
              this.condicion = data["condicion"];
              break;
            }
          }
        }
      }
    },

    abrirModalNuevoMotivo() {
      this.tituloModal3 = "Registrar Nuevo Motivo";
      this.tipoAccion2 = 5;
      this.nombre = "";
      this.modal3 = true;
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
    recuperarIdRol() {
      this.rolUsuario = window.userData.rol;
    },

    exportarPDF() {
      this.isLoading = true;

      const productosParaPDF = this.productosSeleccionados.map(producto => {
        const stockReal = producto.stock_real !== undefined ? parseInt(producto.stock_real) : 0;
        const stockActual = producto.stock_actual !== undefined ? parseInt(producto.stock_actual) : 0;
        const cantidadAjuste = stockActual - stockReal;
        const stockRestante = stockReal;

        return {
          ...producto,
          stock_real: stockReal,
          cantidad_ajuste: cantidadAjuste,
          stock_restante: stockRestante,
        };
      });

      axios.post('/ajustes-inventario/exportar-pdf', {
        productos: productosParaPDF,
        almacen: this.idAlmacenSeleccionado,
        motivo: this.motivoseleccionado.nombre
      }, {
        responseType: 'blob'
      })
        .then(response => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', 'ajuste_inventario.pdf');
          document.body.appendChild(link);
          link.click();
          this.$toast.add({ severity: 'success', summary: 'Éxito', detail: 'PDF generado correctamente' });
        })
        .catch(error => {
          console.error("Error al generar el PDF:", error);
          this.$toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo generar el PDF' });
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
  async mounted() {
    this.handleResize();
    window.addEventListener("resize", this.handleResize);
    try {
      await Promise.all([
        this.selectAlmacen(),
        this.recuperarIdRol(),
        this.datosConfiguracion(),
        this.obtenerConfiguracionTrabajo(),
        this.listarAjuste(1, this.buscar, ""),
      ]);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
      swal("Error", "Error al cargar los datos iniciales", "error");
    }
  },
  beforeUnmount() {
    window.removeEventListener("resize", this.handleResize);
  },
};
</script>
<style scoped>
.p-d-flex .p-button-sm {
  margin: 0 1%;
}

.form-control input {
  width: 100%;
  padding: 0.375rem 0.75rem;
  font-size: 1rem;
  line-height: 1.5;
  color: #495057;
  background-color: #fff;
  border: 1px solid #ced4da;
  border-radius: 0.25rem;
}

.form-control input:focus {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.mr-2 {
  margin-right: 10px;
}

#escaneo-camara {
  width: 100%;
  height: 400px;
  background-color: #000;
}

.input-con-desplegable {
  position: relative;
  width: 100%;
}

.input-con-desplegable {
  position: relative;
  width: 100%;
}

.desplegable-simple {
  position: absolute;
  background: white;
  width: 100%;
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #ddd;
  border-radius: 4px;
  z-index: 1000;
  list-style: none;
  padding: 0;
  margin: 0;
}

.desplegable-simple li {
  padding: 8px 10px;
  cursor: pointer;
}

.desplegable-simple li:hover,
.desplegable-simple li.seleccionado {
  background-color: #f0f0f0;
}

/* Estilos para el botón "Agregar Producto" */
.d-flex.align-items-end {
  display: flex;
  align-items: flex-end;
  padding-bottom: 0.5rem;
}

/* Estilos para el Panel */
.panel-header {
  padding: 1rem;
}


.p-sidebar {
  position: fixed !important;
  top: 0 !important;
  bottom: 0 !important;
  right: 0 !important;
  height: 100vh !important;
  width: auto !important;
  max-width: 800px !important;
  background: var(--surface-overlay, #fff);
  box-shadow: -2px 0 12px rgba(0, 0, 0, 0.3);
  z-index: 9992 !important;
  /* 👈 encima del modal (9990) */
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.p-sidebar-mask {
  position: fixed !important;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.55);
  display: flex;
  justify-content: flex-end;
  align-items: stretch;
  pointer-events: auto;
  z-index: 9991 !important;
  transition: background 0.3s ease;
}

.p-sidebar-mask.p-component-overlay {
  pointer-events: auto;
}

body.p-overflow-hidden {
  overflow: hidden !important;
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

.panel-header {
  display: flex;
  align-items: center;
}

.panel-title {
  margin: 0;
  padding-left: 5px;
}

/* Responsive Dialog Styles */
.responsive-dialog>>>.p-dialog {
  margin: 0.75rem;
  max-height: 90vh;
  overflow-y: auto;
}

.responsive-dialog>>>.p-dialog-content {
  overflow-x: auto;
  padding: 0.75rem 1rem;
}

.responsive-dialog>>>.p-dialog-header {
  padding: 0.75rem 1.5rem;
  font-size: 1.1rem;
}

.responsive-dialog>>>.p-dialog-footer {
  padding: 0.5rem 1.5rem;
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

/* Formulario compacto - Reducir espacios entre campos */
.form-compact>>>.p-field {
  margin-bottom: 0.25rem !important;
}

>>>.p-fluid .p-field {
  margin-bottom: 0.25rem;
}

/* Estilos para campos obligatorios */
.required-field {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-weight: 600;
  color: #2c3e50;
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
  align-items: center;
  gap: 0.4rem;
  font-weight: 500;
  color: #6c757d;
}

.optional-icon {
  color: #17a2b8;
  font-size: 0.8rem;
}

/* DataTable Responsive */
>>>.p-datatable {
  font-size: 0.9rem;
}

>>>.p-datatable .p-datatable-tbody>tr>td {
  padding: 0.5rem;
  word-break: break-word;
  text-align: left;
}

>>>.p-datatable .p-datatable-thead>tr>th {
  padding: 0.75rem 0.5rem;
  font-size: 0.85rem;
}

/* Form Grid Responsive */
>>>.p-formgrid.p-grid {
  margin: 0;
}

>>>.p-formgrid .p-field {
  padding: 0.5rem;
}

/* Tablet Styles */
@media (max-width: 1024px) {
  .responsive-dialog>>>.p-dialog {
    margin: 0.5rem;
    max-height: 95vh;
  }

  >>>.p-datatable {
    font-size: 0.85rem;
  }

  >>>.p-formgrid .p-field.p-col-12.p-md-6 {
    width: 100% !important;
    flex: 0 0 100% !important;
  }
}

/* Mobile Styles */
@media (max-width: 768px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog>>>.p-dialog {
    margin: 0.25rem;
    max-height: 98vh;
  }

  .responsive-dialog>>>.p-dialog-content {
    padding: 0.5rem 0.75rem;
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.5rem 1rem;
    font-size: 1rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.4rem 1rem;
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

  >>>.p-formgrid .p-field {
    padding: 0.25rem;
    margin-bottom: 0.4rem !important;
  }

  >>>.p-formgrid .p-field label {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
  }

  /* Ajustar iconos en móviles */
  .required-icon {
    font-size: 0.8rem;
  }

  .optional-icon {
    font-size: 0.6rem;
  }

  >>>.p-inputtext,
  >>>.p-dropdown,
  >>>.p-inputnumber-input,
  >>>.p-multiselect,
  >>>.p-inputtextarea {
    font-size: 0.9rem;
    padding: 0.5rem;
  }

  >>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }

  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  .search-bar .p-inputtext-sm {
    padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
    font-size: 0.85rem !important;
  }

  .p-sidebar {
    width: 100vw !important;
    max-width: 100% !important;
  }

  /* Buscador */
  .search-bar {
    width: 100%;
    margin-bottom: 1rem;
  }

  .search-bar .p-input-icon-left {
    width: 100%;
  }

  .search-bar .form-control {
    width: 100%;
  }

  /* Botones del toolbar */
  .toolbar {
    flex-direction: column;
    gap: 0.75rem;
  }

  .toolbar>div {
    width: 100%;
    display: flex;
    justify-content: space-between;
  }

  .toolbar .p-d-flex.p-gap-2 {
    justify-content: flex-end;
  }

  /* Botones de Exportar PDF, Cancelar y Procesar Ajuste */
  .row.mt-3 [class*="col-"] {
    flex: 0 0 100%;
    max-width: 100%;
    margin-bottom: 0.75rem;
  }

  .row.mt-3 .d-flex.justify-content-start,
  .row.mt-3 .d-flex.justify-content-end {
    justify-content: center !important;
  }

  .row.mt-3 .p-button {
    width: 100%;
    margin-bottom: 0.5rem;
  }

  .search-and-buttons {
    flex-direction: column !important;
  }

  .search-and-buttons .p-d-flex.p-gap-2 {
    width: 100%;
    justify-content: space-between;
  }

  .search-and-buttons .p-button {
    flex: 1;
  }
}

/* Extra Small Mobile */
@media (max-width: 480px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog>>>.p-dialog {
    margin: 0.1rem;
    max-height: 99vh;
  }

  .responsive-dialog>>>.p-dialog-content {
    padding: 0.4rem 0.5rem;
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.4rem 0.75rem;
    font-size: 0.95rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.3rem 0.75rem;
    justify-content: flex-end;
  }

  .responsive-dialog>>>.p-dialog-footer .p-button {
    width: auto;
    margin-bottom: 0.25rem;
  }

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

  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

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

  >>>.p-formgrid .p-field {
    padding: 0.2rem;
    margin-bottom: 0.3rem !important;
  }

  >>>.p-formgrid .p-field label {
    font-size: 0.85rem;
  }

  .required-icon {
    font-size: 0.7rem;
  }

  .optional-icon {
    font-size: 0.55rem;
  }

  >>>.p-inputtext,
  >>>.p-dropdown,
  >>>.p-inputnumber-input,
  >>>.p-multiselect,
  >>>.p-inputtextarea {
    font-size: 0.85rem;
    padding: 0.4rem;
  }

  >>>.p-tag {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
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

body,
html {
  transform: none !important;
  perspective: none !important;
  filter: none !important;
}
</style>