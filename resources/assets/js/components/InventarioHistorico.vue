<template>
  <main class="main">
    <Toast :breakpoints="{ '920px': { width: '100%', right: '0', left: '0' } }" style="padding-top: 10px;"
      appendTo="body" :baseZIndex="99999"></Toast>
    <div class="loading-overlay" v-if="isLoading">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">CARGANDO...</div>
      </div>
    </div>
    <Panel>
      <template #header>
        <div class="panel-header">
          <div class="panel-title-section">
            <i class="pi pi-history"></i>
            <h4 class="panel-title">INVENTARIO HISTÓRICO</h4>
          </div>
        </div>
      </template>

      <div class="info-tip">
        <i class="pi pi-info-circle"></i>
        <span>
          Seleccione una fecha y un almacén para consultar las existencias. Puede exportar los resultados en Excel o
          PDF.
        </span>
      </div>

      <div class="filters-container">
        <div class="filter-row">

          <!-- Fecha de consulta -->
          <div class="filter-group-almacen">
            <label for="fechaConsulta" class="filter-label">
              FECHA DE CONSULTA
            </label>

            <input id="fechaConsulta" type="date" v-model="fechaConsulta" :max="fechaActual"
              @change="cambiarFechaConsulta" class="p-inputtext p-component p-inputtext-sm input-date-full" />
          </div>

          <!-- Almacén -->
          <div class="filter-group-almacen">
            <label class="filter-label">
              ALMACÉN DE TRABAJO
            </label>

            <Dropdown v-model="AlmacenSeleccionado" :options="arrayAlmacenes" optionLabel="nombre_almacen"
              optionValue="id" placeholder="Seleccione un almacén" @change="getDatosAlmacen" class="dropdown-full" />
          </div>

          <!-- Ordenar inventario -->
          <div class="filter-group-almacen">
            <label class="filter-label">
              ORDENAR PRODUCTOS
            </label>

            <Dropdown v-model="ordenInventario" :options="opcionesOrden" optionLabel="label" optionValue="value"
              placeholder="Ordenar por" @change="cambiarOrdenInventario" class="dropdown-full" />
          </div>

          <!-- Acciones -->
          <div class="filter-group-actions">
            <label class="filter-label">ACCIONES</label>

            <div class="panel-actions">
              <Button label="Exportar" icon="pi pi-download" @click="exportarInventario"
                :disabled="isLoading || !consultaRealizada || Number(idrol) === 2"
                class="p-button-info p-button-sm btn-sm-input" />
            </div>
          </div>

        </div>
      </div>

      <div class="toolbar-container">
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText v-model="buscar" placeholder="Buscar producto..." class="input-full" @input="buscarInventario" />
          </span>
        </div>
        <div class="toolbar">
          <Button :label="mostrarLabel ? 'Limpiar' : ''" icon="pi pi-refresh" @click="resetBusqueda"
            class="p-button-warning p-button-sm btn-sm-input" />
        </div>
      </div>
      <!-- DataTable para vista por Item -->

      <div v-if="consultaRealizada && tipoConsultaHistorica" class="info-inventario-historico">
        <div class="info-historico-detalle">

          <i class="pi pi-calendar"></i>

          <strong>
            Inventario al {{ fechaConsultaFormateada }}
          </strong>

          <span v-if="tipoConsultaHistorica === 'ACTUAL'" class="estado-historico estado-actual">
            Stock actual
          </span>

          <span v-else-if="tipoConsultaHistorica === 'HISTORICO_REGISTRADO'" class="estado-historico estado-registrado">
            Stock histórico registrado
          </span>

          <span v-else-if="tipoConsultaHistorica === 'HISTORICO_ESTIMADO'" class="estado-historico estado-estimado">
            Stock histórico estimado
          </span>

        </div>

        <small v-if="mensajeHistorial" class="mensaje-historico">
          {{ mensajeHistorial }}
        </small>
        <small v-if="fechaCorteHistorial" class="mensaje-historico">Inicio del historial registrado: {{
          fechaCorteHistorial
        }}</small>
      </div>

      <!-- Inventario histórico por producto -->
      <DataTable v-if="consultaRealizada" :value="arrayInventario"
        class="p-datatable-sm p-datatable-gridlines tabla-pro" responsiveLayout="scroll">

        <!-- Código -->
        <Column field="codigo" header="Código"></Column>

        <!-- Producto -->
        <Column field="nombre_producto" header="Producto"></Column>

        <!-- Stock del almacén seleccionado -->
        <Column field="saldo_stock_total" :header="tituloColumnaStock">
          <template #body="slotProps">

            <span v-if="stockDesconocido(slotProps.data.saldo_stock_total)" class="stock-desconocido">Sin datos</span>
            <span v-else-if="Number(slotProps.data.saldo_stock_total) === 0" style="color: #dc2626; font-weight: bold;">
              <i class="pi pi-exclamation-triangle" style="margin-right: 5px;"></i>
              Sin Stock
            </span>

            <span v-else>
              {{ slotProps.data.saldo_stock_total }}
              {{ slotProps.data.descripcion_fabrica }}
            </span>

          </template>
        </Column>

        <!-- Stock del depósito -->
        <Column v-if="Number(AlmacenSeleccionado) !== 2" field="stock_deposito" header="Stock Depósito">
          <template #body="slotProps">

            <span v-if="stockDesconocido(slotProps.data.stock_deposito)" class="stock-desconocido">Sin datos</span>
            <span v-else-if="Number(slotProps.data.stock_deposito) === 0" style="color: #dc2626; font-weight: 600;">
              Sin Stock
            </span>

            <span v-else>
              {{ slotProps.data.stock_deposito }}
              {{ slotProps.data.descripcion_fabrica }}
            </span>

          </template>
        </Column>

        <!-- Columnas dinámicas de otros almacenes -->
        <Column v-for="almacen in arrayAlmacenesColumnas" :key="almacen.id" :field="'stock_almacen_' + almacen.id"
          :header="almacen.nombre_almacen">
          <template #body="slotProps">

            <span v-if="stockDesconocido(slotProps.data['stock_almacen_' + almacen.id])" class="stock-desconocido">Sin
              datos</span>
            <span v-else-if="Number(slotProps.data['stock_almacen_' + almacen.id]) === 0"
              style="color: #dc2626; font-weight: 600;">
              Sin Stock
            </span>

            <span v-else>
              {{ slotProps.data['stock_almacen_' + almacen.id] }}
              {{ slotProps.data.descripcion_fabrica }}
            </span>

          </template>
        </Column>

        <!-- Acciones -->
        <Column header="Acciones" :headerStyle="{ width: '85px', textAlign: 'center' }"
          :bodyStyle="{ textAlign: 'center' }">

          <template #body="slotProps">

            <Button icon="pi pi-history" class="btn-mini btn-historial" title="Ver historial de movimientos"
              @click="abrirDetalleMovimientos(slotProps.data)" />

          </template>

        </Column>

        <!-- Sin resultados -->
        <template #empty>
          <div class="text-center p-3">
            No se encontraron productos para la consulta seleccionada.
          </div>
        </template>

      </DataTable>

      <!-- Paginación manual del servidor -->
      <div v-if="consultaRealizada && pagination.total > 0" class="barra-paginacion">
        <span class="contador-registros">Mostrando {{ pagination.from }} - {{ pagination.to }} de {{ pagination.total }}
          productos</span>
        <Paginator :rows="pagination.per_page" :totalRecords="pagination.total"
          :first="(pagination.current_page - 1) * pagination.per_page" @page="onPageChange" />
      </div>

      <Dialog :visible.sync="dialogMovimientos" :modal="true" :closable="true" :dismissableMask="false"
        :containerStyle="{ width: '95vw', maxWidth: '1400px' }" class="dialog-historial-movimientos"
        @hide="cerrarDetalleMovimientos">

        <!-- Encabezado -->
        <template #header>

          <div class="historial-dialog-header">

            <div class="historial-header-icon">
              <i class="pi pi-history"></i>
            </div>

            <div>
              <div class="historial-header-subtitle">
                INVENTARIO · TRAZABILIDAD
              </div>

              <h3>Historial de movimientos</h3>
            </div>

          </div>

        </template>

        <div v-if="productoHistorial">

          <!-- Información del producto -->
          <div class="historial-info-producto">

            <div class="historial-info-item">
              <span>Código</span>
              <strong>{{ productoHistorial.codigo }}</strong>
            </div>

            <div class="historial-info-item">
              <span>Producto</span>
              <strong>{{ productoHistorial.nombre_producto }}</strong>
            </div>

            <div class="historial-info-item">
              <span>Unidad de medida</span>
              <strong>
                {{ productoHistorial.descripcion_fabrica || '-' }}
              </strong>
            </div>

            <div class="historial-info-item">
              <span>Fecha del inventario consultado</span>
              <strong>{{ fechaReferenciaHistorial }}</strong>
            </div>

          </div>

          <!-- Filtros -->
          <div class="historial-filtros">

            <div class="historial-filtro">
              <label>Almacén</label>

              <Dropdown v-model="almacenHistorial" :options="almacenesHistorial" optionLabel="nombre_almacen"
                optionValue="id" placeholder="Seleccione almacén" :disabled="cargandoMovimientos"
                @change="listarMovimientos(1)" />
            </div>

            <div class="historial-filtro">
              <label>Fecha desde</label>

              <input type="date" v-model="fechaDesdeHistorial" :max="fechaHastaHistorial"
                :disabled="cargandoMovimientos" class="p-inputtext p-component" />
            </div>

            <div class="historial-filtro">
              <label>Fecha hasta</label>

              <input type="date" v-model="fechaHastaHistorial" :min="fechaDesdeHistorial" :max="fechaLimiteHistorial"
                :disabled="cargandoMovimientos" class="p-inputtext p-component" />
            </div>

            <div class="historial-filtro">
              <label>Tipo de movimiento</label>

              <Dropdown v-model="tipoMovimientoHistorial" :options="tiposMovimientosHistorial" optionLabel="label"
                optionValue="value" :disabled="cargandoMovimientos" />
            </div>

            <div class="historial-filtro historial-filtro-boton">

              <Button label="Consultar" icon="pi pi-search" class="p-button-sm" :disabled="cargandoMovimientos"
                @click="listarMovimientos(1)" />

            </div>

          </div>

          <!-- Cargando -->
          <div v-if="cargandoMovimientos" class="historial-loading">

            <ProgressSpinner style="width: 40px; height: 40px;" strokeWidth="4" />

            <span>Consultando movimientos de inventario...</span>

          </div>

          <div v-else-if="errorMovimientos" class="historial-error">

            <i class="pi pi-exclamation-triangle"></i>

            {{ errorMovimientos }}

          </div>

          <div v-else-if="consultaMovimientosRealizada">

            <!-- Indicador del historial -->
            <div class="historial-indicador" :class="claseTipoHistorial">

              <i class="pi pi-info-circle"></i>

              <div>
                <strong>{{ tituloTipoHistorial }}</strong>
                <p>{{ mensajeDetalleHistorial }}</p>
              </div>

            </div>

            <!-- Resumen -->
            <div class="historial-resumen">

              <!-- Stock inicial -->
              <div class="historial-resumen-card">

                <div class="historial-resumen-label">
                  <span>Stock inicial</span>
                  <i class="pi pi-box"></i>
                </div>

                <div class="historial-resumen-valor">
                  {{ formatearCantidadHistorial(resumenHistorial.stock_inicial) }}
                  <small>{{ productoHistorial.descripcion_fabrica }}</small>
                </div>

                <span class="historial-resumen-descripcion">
                  Existencias al inicio del período
                </span>

              </div>

              <!-- Entradas -->
              <div class="historial-resumen-card resumen-entrada">

                <div class="historial-resumen-label">
                  <span>Total entradas</span>
                  <i class="pi pi-arrow-down-left"></i>
                </div>

                <div class="historial-resumen-valor">
                  {{ formatearCantidadHistorial(resumenHistorial.total_entradas) }}
                  <small>{{ productoHistorial.descripcion_fabrica }}</small>
                </div>

                <span class="historial-resumen-descripcion">
                  Ingresos de mercadería
                </span>

              </div>

              <!-- Salidas -->
              <div class="historial-resumen-card resumen-salida">

                <div class="historial-resumen-label">
                  <span>Total salidas</span>
                  <i class="pi pi-arrow-up-right"></i>
                </div>

                <div class="historial-resumen-valor">
                  {{ formatearCantidadHistorial(resumenHistorial.total_salidas) }}
                  <small>{{ productoHistorial.descripcion_fabrica }}</small>
                </div>

                <span class="historial-resumen-descripcion">
                  Retiros de mercadería
                </span>

              </div>

              <!-- Stock final -->
              <div class="historial-resumen-card resumen-final">

                <div class="historial-resumen-label">
                  <span>Stock final</span>
                  <i class="pi pi-check-circle"></i>
                </div>

                <div class="historial-resumen-valor">
                  {{ formatearCantidadHistorial(resumenHistorial.stock_final) }}
                  <small>{{ productoHistorial.descripcion_fabrica }}</small>
                </div>

                <span class="historial-resumen-descripcion">
                  Existencias al cierre del período
                </span>

              </div>

            </div>

            <!-- Tabla de movimientos -->
            <div class="historial-tabla-contenedor">

              <div class="historial-tabla-titulo">

                <i class="pi pi-list"></i>

                Detalle cronológico de movimientos

              </div>

              <DataTable :value="arrayMovimientosHistorial"
                class="p-datatable-sm p-datatable-gridlines tabla-pro tabla-movimientos">

                <!-- Fecha y hora -->
                <Column field="fecha_movimiento" header="Fecha y hora">

                  <template #body="slotProps">

                    <span class="historial-fecha">
                      {{ formatearFechaMovimiento(slotProps.data.fecha_movimiento) }}
                    </span>

                  </template>

                </Column>

                <!-- Tipo de movimiento -->
                <Column field="tipo_movimiento" header="Movimiento">

                  <template #body="slotProps">

                    <span class="historial-tag" :class="claseMovimientoHistorial(slotProps.data)">
                      {{ nombreMovimientoHistorial(slotProps.data.tipo_movimiento) }}
                    </span>

                  </template>

                </Column>

                <!-- Referencia -->
                <Column header="Referencia">

                  <template #body="slotProps">

                    <span class="historial-referencia">
                      {{ slotProps.data.referencia || '-' }}
                    </span>

                  </template>

                </Column>

                <!-- Entrada -->
                <Column header="Entrada">

                  <template #body="slotProps">

                    <span v-if="esEntradaHistorial(slotProps.data)" class="historial-cantidad-entrada">
                      +{{ formatearCantidadHistorial(slotProps.data.cantidad) }}
                    </span>

                    <span v-else class="historial-guion">—</span>

                  </template>

                </Column>

                <!-- Salida -->
                <Column header="Salida">

                  <template #body="slotProps">

                    <span v-if="esSalidaHistorial(slotProps.data)" class="historial-cantidad-salida">
                      -{{ formatearCantidadHistorial(Math.abs(Number(slotProps.data.cantidad))) }}
                    </span>

                    <span v-else class="historial-guion">—</span>

                  </template>

                </Column>

                <!-- Stock anterior -->
                <Column field="stock_anterior" header="Stock anterior">

                  <template #body="slotProps">

                    {{ formatearCantidadHistorial(slotProps.data.stock_anterior) }}

                  </template>

                </Column>

                <!-- Stock resultante -->
                <Column field="stock_actual" header="Stock resultante">

                  <template #body="slotProps">

                    <span class="historial-stock-resultante" :class="{
                      'historial-stock-negativo':
                        Number(slotProps.data.stock_actual) < 0
                    }">
                      {{ formatearCantidadHistorial(slotProps.data.stock_actual) }}
                    </span>

                  </template>

                </Column>

                <!-- Usuario -->
                <Column field="usuario" header="Usuario">

                  <template #body="slotProps">

                    {{ slotProps.data.usuario || '-' }}

                  </template>

                </Column>

                <!-- Origen de la información -->
                <Column header="Origen">

                  <template #body="slotProps">

                    <span v-if="slotProps.data.es_estimado" class="historial-tag tag-estimado">
                      Estimado
                    </span>

                    <span v-else class="historial-tag tag-registrado">
                      Registrado
                    </span>

                  </template>

                </Column>

                <!-- Observación -->
                <Column field="observacion" header="Observación">

                  <template #body="slotProps">

                    <span class="historial-observacion">
                      {{ slotProps.data.observacion || '-' }}
                    </span>

                  </template>

                </Column>

                <template #empty>

                  <div class="historial-empty">

                    <i class="pi pi-inbox"></i>

                    <span>
                      No se encontraron movimientos para el período seleccionado.
                    </span>

                  </div>

                </template>

              </DataTable>

            </div>

            <!-- Paginación -->
            <div v-if="paginationMovimientos.total > 0" class="barra-paginacion">

              <span class="contador-registros">
                Mostrando {{ paginationMovimientos.from }}
                - {{ paginationMovimientos.to }}
                de {{ paginationMovimientos.total }} movimientos
              </span>

              <Paginator :rows="paginationMovimientos.per_page" :totalRecords="paginationMovimientos.total"
                :first="(paginationMovimientos.current_page - 1) * paginationMovimientos.per_page"
                @page="onPageMovimientos" />

            </div>

          </div>

        </div>

        <!-- Footer -->
        <template #footer>

          <Button label="Cerrar" icon="pi pi-times" class="p-button-secondary p-button-sm"
            @click="dialogMovimientos = false" />

        </template>

      </Dialog>
    </Panel>
    <!-- Fin ejemplo de tabla Listado -->
  </main>
</template>

<script>
import Toast from "primevue/toast";
import Panel from "primevue/panel";
import Swal from "sweetalert2";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import InputText from "primevue/inputtext";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Paginator from "primevue/paginator";
import Dialog from "primevue/dialog";
import ProgressSpinner from "primevue/progressspinner";

const paginacionInicial = () => ({ total: 0, current_page: 1, per_page: 15, last_page: 1, from: 0, to: 0 });

export default {
  components: { Toast, Panel, Button, Dropdown, InputText, DataTable, Column, Paginator, Dialog, ProgressSpinner },
  data() {
    return {
      // ==========================================
      // DETALLE DE MOVIMIENTOS DE INVENTARIO
      // ==========================================

      dialogMovimientos: false,
      cargandoMovimientos: false,
      consultaMovimientosRealizada: false,
      errorMovimientos: "",

      productoHistorial: null,

      almacenHistorial: null,
      almacenesHistorial: [],

      fechaReferenciaHistorial: "",
      fechaLimiteHistorial: "",
      fechaDesdeHistorial: "",
      fechaHastaHistorial: "",

      tipoMovimientoHistorial: "TODOS",

      tipoDetalleHistorial: null,
      mensajeDetalleHistorial: "",

      arrayMovimientosHistorial: [],

      solicitudMovimientosActual: 0,
      filtrosMovimientosConsultados: null,

      resumenHistorial: {
        stock_inicial: null,
        total_entradas: null,
        total_salidas: null,
        stock_final: null
      },

      paginationMovimientos: {
        total: 0,
        current_page: 1,
        per_page: 15,
        last_page: 1,
        from: 0,
        to: 0
      },

      tiposMovimientosHistorial: [
        { label: "Todos los movimientos", value: "TODOS" },

        { label: "Stock inicial", value: "STOCK_INICIAL" },

        { label: "Compras", value: "COMPRA" },
        { label: "Anulación de compra", value: "ANULACION_COMPRA" },

        {
          label: "Actualización compra - Entrada",
          value: "ACTUALIZACION_COMPRA_ENTRADA"
        },
        {
          label: "Actualización compra - Salida",
          value: "ACTUALIZACION_COMPRA_SALIDA"
        },

        { label: "Ventas", value: "VENTA" },
        { label: "Anulación de venta", value: "ANULACION_VENTA" },

        { label: "Ajuste de entrada", value: "AJUSTE_ENTRADA" },
        { label: "Ajuste de salida", value: "AJUSTE_SALIDA" },

        { label: "Traspaso de entrada", value: "TRASPASO_ENTRADA" },
        { label: "Traspaso de salida", value: "TRASPASO_SALIDA" },

        {
          label: "Anulación traspaso de entrada",
          value: "ANULACION_TRASPASO_ENTRADA"
        },
        {
          label: "Anulación traspaso de salida",
          value: "ANULACION_TRASPASO_SALIDA"
        }
      ],
      tipoConsultaHistorica: null, fechaCorteHistorial: null, mensajeHistorial: "",
      consultaRealizada: false, fechaConsultada: "", filtrosConsultados: null,
      fechaConsulta: "", fechaActual: "", ordenInventario: "stock_asc",
      opcionesOrden: [
        { label: "Nombre del producto (A - Z)", value: "nombre_asc" },
        { label: "Menor a mayor stock", value: "stock_asc" },
        { label: "Mayor a menor stock", value: "stock_desc" }
      ],
      arrayInventario: [], arrayAlmacenes: [], arrayAlmacenesColumnas: [],
      AlmacenSeleccionado: null, mostrarLabel: true, isLoading: false,
      idrol: null, criterio: "nombre", buscar: "", searchTimeout: null,
      solicitudActual: 0, pagination: paginacionInicial()
    };
  },
  computed: {
    fechaConsultaFormateada() { return this.fechaConsultada.split("-").reverse().join("/"); },
    nombreAlmacenSeleccionado() {
      const almacen = this.arrayAlmacenes.find(a => Number(a.id) === Number(this.AlmacenSeleccionado));
      return almacen ? almacen.nombre_almacen : "Sin almacén";
    },
    tituloColumnaStock() { return this.tipoConsultaHistorica === "ACTUAL" ? "Stock Actual" : "Stock Histórico"; },
    tituloTipoHistorial() {
      const titulos = {
        HISTORICO_REGISTRADO: "Historial registrado",
        HISTORICO_ESTIMADO: "Historial estimado",
        HISTORICO_MIXTO: "Historial mixto",
        ACTUAL: "Movimientos registrados"
      };

      return titulos[this.tipoDetalleHistorial] || "Historial de movimientos";
    },

    claseTipoHistorial() {
      const clases = {
        HISTORICO_REGISTRADO: "indicador-registrado",
        HISTORICO_ESTIMADO: "indicador-estimado",
        HISTORICO_MIXTO: "indicador-mixto",
        ACTUAL: "indicador-registrado"
      };

      return clases[this.tipoDetalleHistorial] || "indicador-neutral";
    },
  },
  methods: {
    toastSuccess(detail) { this.$toast.add({ severity: "success", summary: "Éxito", detail, life: 2000 }); },
    toastError(detail) { this.$toast.add({ severity: "error", summary: "Error", detail, life: 3000 }); },
    toastWarning(detail) { this.$toast.add({ severity: "warn", summary: "Advertencia", detail, life: 3000 }); },
    stockDesconocido(valor) { return valor === null || valor === undefined || valor === "" || !Number.isFinite(Number(valor)); },
    obtenerFechaActual() {
      const partes = new Intl.DateTimeFormat("en-GB", { timeZone: "America/La_Paz", year: "numeric", month: "2-digit", day: "2-digit" }).formatToParts(new Date());
      return ["year", "month", "day"].map(tipo => partes.find(p => p.type === tipo).value).join("-");
    },
    inicializarFechaConsulta() { this.fechaActual = this.obtenerFechaActual(); this.fechaConsulta = this.fechaActual; },
    obtenerIdrol() { if (window.userData && window.userData.rol !== undefined) this.idrol = Number(window.userData.rol); },
    handleResize() { this.mostrarLabel = window.innerWidth > 768; },
    async selectAlmacen() {
      this.isLoading = true;
      try {
        const { data } = await axios.get("/almacen/selectAlmacen");
        this.arrayAlmacenes = Array.isArray(data.almacenes) ? data.almacenes : [];
        if (!this.arrayAlmacenes.length) { this.limpiarResultados(); this.toastWarning("No existen almacenes disponibles."); return; }
        this.AlmacenSeleccionado = this.arrayAlmacenes[0].id;
        await this.getDatosAlmacen();
      } catch (error) { console.error(error); this.toastError("No se pudieron cargar los almacenes."); }
      finally { this.isLoading = false; }
    },
    cancelarBusquedaPendiente() { clearTimeout(this.searchTimeout); this.searchTimeout = null; },
    limpiarResultados() {
      this.arrayInventario = []; this.arrayAlmacenesColumnas = []; this.tipoConsultaHistorica = null;
      this.fechaCorteHistorial = null; this.mensajeHistorial = ""; this.fechaConsultada = "";
      this.filtrosConsultados = null; this.consultaRealizada = false; this.pagination = paginacionInicial();
    },
    async getDatosAlmacen() { this.cancelarBusquedaPendiente(); return this.listarInventario(1); },
    async listarInventario(page = 1, buscar = this.buscar, criterio = this.criterio) {
      const solicitud = ++this.solicitudActual;
      this.consultaRealizada = false;
      if (!this.AlmacenSeleccionado || !this.fechaConsulta) { this.limpiarResultados(); this.isLoading = false; return false; }
      const params = { idAlmacen: this.AlmacenSeleccionado, buscar: buscar || "", criterio: criterio || "", orden: this.ordenInventario, fecha_consulta: this.fechaConsulta, page };
      this.isLoading = true;
      try {
        const { data } = await axios.get("/inventariohistorico/listar/item", { params });
        if (solicitud !== this.solicitudActual) return false;
        this.arrayInventario = data.inventarios.data;
        this.pagination = data.pagination;
        this.arrayAlmacenesColumnas = Array.isArray(data.almacenes_columnas) ? data.almacenes_columnas : [];
        this.tipoConsultaHistorica = data.tipo_consulta || null;
        this.fechaCorteHistorial = data.fecha_corte || null;
        this.mensajeHistorial = data.mensaje_historial || "";
        this.fechaConsultada = params.fecha_consulta;
        this.filtrosConsultados = Object.assign({}, params);
        this.consultaRealizada = true;
        return true;
      } catch (error) {
        if (solicitud !== this.solicitudActual) return false;
        console.error(error); this.limpiarResultados();
        this.toastError(error.response && error.response.data && error.response.data.message || "No se pudo cargar el inventario histórico.");
        return false;
      } finally { if (solicitud === this.solicitudActual) this.isLoading = false; }
    },
    async cambiarFechaConsulta() {
      this.fechaActual = this.obtenerFechaActual();
      if (!this.fechaConsulta) this.fechaConsulta = this.fechaActual;
      if (this.fechaConsulta > this.fechaActual) { this.fechaConsulta = this.fechaActual; this.toastWarning("No puede consultar fechas futuras."); }
      await this.getDatosAlmacen();
    },
    async cambiarOrdenInventario() { this.cancelarBusquedaPendiente(); await this.listarInventario(1); },
    buscarInventario() {
      this.cancelarBusquedaPendiente(); this.solicitudActual++; this.consultaRealizada = false;
      this.isLoading = false;
      this.searchTimeout = setTimeout(() => { this.searchTimeout = null; this.listarInventario(1); }, 400);
    },
    async resetBusqueda() { this.cancelarBusquedaPendiente(); this.buscar = ""; await this.listarInventario(1); },
    async onPageChange(event) {
      if (!event.rows || !this.consultaRealizada) return;
      this.cancelarBusquedaPendiente(); await this.listarInventario(Math.floor(event.first / event.rows) + 1);
    },
    async exportarInventario() {
      if (Number(this.idrol) === 2) { this.toastWarning("Esta acción solo está permitida para Administradores."); return; }
      if (!this.consultaRealizada || this.isLoading || !this.filtrosConsultados) { this.toastWarning("Debe completar la consulta antes de exportar."); return; }
      const filtros = Object.assign({}, this.filtrosConsultados); delete filtros.page;
      const nombre = this.nombreAlmacenSeleccionado.normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-zA-Z0-9_-]/g, "_").replace(/_+/g, "_");
      const fecha = filtros.fecha_consulta.split("-").reverse().join("_");
      const result = await Swal.fire({ title: "Exportar inventario histórico", text: `Inventario al ${this.fechaConsultaFormateada}`, icon: "question", showCancelButton: true, showDenyButton: true, confirmButtonText: '<i class="pi pi-file-excel"></i> Excel', denyButtonText: '<i class="pi pi-file-pdf"></i> PDF', cancelButtonText: "Cancelar", customClass: { confirmButton: "swal2-confirm-excel", denyButton: "swal2-deny-pdf", cancelButton: "swal2-cancel-cancelar" } });
      if (!result.isConfirmed && !result.isDenied) return;
      const params = new URLSearchParams(Object.assign({ modo: "item" }, filtros));
      const formato = result.isConfirmed ? "excel" : "pdf";
      const extension = result.isConfirmed ? "xlsx" : "pdf";
      await this.descargarArchivoReporte(`/inventariohistorico/exportar-${formato}?${params.toString()}`, `InventarioHistorico_${nombre}_${fecha}.${extension}`);
    },
    async descargarArchivoReporte(url, nombreArchivo) {
      this.isLoading = true;
      try {
        const response = await axios.get(url, { responseType: "blob" });
        const tipo = response.headers["content-type"] || "application/octet-stream";
        if (tipo.includes("application/json") || tipo.includes("text/html")) throw new Error("Archivo inválido");
        const objectUrl = window.URL.createObjectURL(new Blob([response.data], { type: tipo }));
        const link = document.createElement("a"); link.href = objectUrl; link.download = nombreArchivo;
        document.body.appendChild(link); link.click(); document.body.removeChild(link);
        setTimeout(() => window.URL.revokeObjectURL(objectUrl), 1000);
        this.toastSuccess("Reporte generado correctamente.");
      } catch (error) { console.error(error); this.toastError("No se pudo generar el reporte histórico."); }
      finally { this.isLoading = false; }
    },
    async abrirDetalleMovimientos(producto) {

      if (!producto || !producto.id) {
        this.toastWarning("No se pudo identificar el producto.");
        return;
      }

      if (!this.filtrosConsultados) {
        this.toastWarning("Debe realizar una consulta de inventario.");
        return;
      }

      this.limpiarDetalleMovimientos();

      this.productoHistorial = Object.assign({}, producto);

      const idAlmacenConsultado = Number(
        this.filtrosConsultados.idAlmacen
      );

      this.almacenHistorial = idAlmacenConsultado;

      /*
      |--------------------------------------------------------------------------
      | Preparar almacenes disponibles
      |--------------------------------------------------------------------------
      */

      const idsPermitidos = [
        idAlmacenConsultado,
        2,
        ...this.arrayAlmacenesColumnas.map(
          almacen => Number(almacen.id)
        )
      ];

      const almacenesDisponibles = [
        ...this.arrayAlmacenes,
        ...this.arrayAlmacenesColumnas
      ];

      const almacenesUnicos = {};

      almacenesDisponibles.forEach(almacen => {

        const id = Number(almacen.id);

        if (idsPermitidos.includes(id)) {

          almacenesUnicos[id] = {
            id: id,
            nombre_almacen: almacen.nombre_almacen
          };

        }

      });

      // Conservar almacén seleccionado
      if (!almacenesUnicos[idAlmacenConsultado]) {

        almacenesUnicos[idAlmacenConsultado] = {
          id: idAlmacenConsultado,
          nombre_almacen: this.nombreAlmacenSeleccionado
        };

      }

      this.almacenesHistorial = Object.values(almacenesUnicos);

      /*
      |--------------------------------------------------------------------------
      | Configurar fechas iniciales
      |--------------------------------------------------------------------------
      */

      const fechaConsulta = this.filtrosConsultados.fecha_consulta;

      this.fechaReferenciaHistorial = fechaConsulta
        .split("-")
        .reverse()
        .join("/");

      this.fechaLimiteHistorial = fechaConsulta;

      // Primer día del mes consultado
      this.fechaDesdeHistorial = fechaConsulta.substring(0, 7) + "-01";

      this.fechaHastaHistorial = fechaConsulta;

      this.tipoMovimientoHistorial = "TODOS";

      this.dialogMovimientos = true;

      await this.listarMovimientos(1);
    },
    async listarMovimientos(page = 1, usarFiltrosConsultados = false) {

      if (!this.productoHistorial || !this.almacenHistorial) {
        return;
      }

      let filtros;

      if (usarFiltrosConsultados && this.filtrosMovimientosConsultados) {

        filtros = Object.assign(
          {},
          this.filtrosMovimientosConsultados
        );

      } else {

        filtros = {
          idarticulo: this.productoHistorial.id,
          idAlmacen: this.almacenHistorial,
          fecha_desde: this.fechaDesdeHistorial,
          fecha_hasta: this.fechaHastaHistorial,
          tipo_movimiento: this.tipoMovimientoHistorial
        };

      }

      if (!filtros.fecha_desde || !filtros.fecha_hasta) {

        this.toastWarning("Debe seleccionar ambas fechas.");
        return;

      }

      if (filtros.fecha_desde > filtros.fecha_hasta) {

        this.toastWarning(
          "La fecha inicial no puede ser mayor a la fecha final."
        );

        return;
      }

      if (filtros.fecha_hasta > this.fechaLimiteHistorial) {

        this.toastWarning(
          "La fecha final no puede superar la fecha del inventario consultado."
        );

        return;
      }

      const solicitud = ++this.solicitudMovimientosActual;

      this.cargandoMovimientos = true;
      this.consultaMovimientosRealizada = false;
      this.errorMovimientos = "";

      try {

        const response = await axios.get(
          "/inventariohistorico/movimientos",
          {
            params: Object.assign({}, filtros, {
              page: page
            })
          }
        );

        // Ignorar respuestas de consultas anteriores
        if (
          solicitud !== this.solicitudMovimientosActual ||
          !this.dialogMovimientos
        ) {
          return;
        }

        const data = response.data;

        this.arrayMovimientosHistorial =
          data.movimientos.data || [];

        this.paginationMovimientos = data.pagination;

        this.resumenHistorial = data.resumen;

        this.tipoDetalleHistorial = data.tipo_consulta;

        this.mensajeDetalleHistorial =
          data.mensaje_historial || "";

        this.filtrosMovimientosConsultados =
          Object.assign({}, filtros);

        this.consultaMovimientosRealizada = true;

      } catch (error) {

        if (solicitud !== this.solicitudMovimientosActual) {
          return;
        }

        console.error(error);

        this.arrayMovimientosHistorial = [];

        this.errorMovimientos =
          error.response &&
            error.response.data &&
            error.response.data.message
            ? error.response.data.message
            : "No se pudieron consultar los movimientos de inventario.";

        this.toastError(this.errorMovimientos);

      } finally {

        if (solicitud === this.solicitudMovimientosActual) {
          this.cargandoMovimientos = false;
        }

      }

    },
    onPageMovimientos(event) {

      if (
        this.cargandoMovimientos ||
        !this.consultaMovimientosRealizada
      ) {
        return;
      }

      const page = Math.floor(event.first / event.rows) + 1;

      this.listarMovimientos(page, true);

    },
    limpiarDetalleMovimientos() {

      this.arrayMovimientosHistorial = [];

      this.tipoDetalleHistorial = null;
      this.mensajeDetalleHistorial = "";

      this.consultaMovimientosRealizada = false;
      this.errorMovimientos = "";

      this.filtrosMovimientosConsultados = null;

      this.resumenHistorial = {
        stock_inicial: null,
        total_entradas: null,
        total_salidas: null,
        stock_final: null
      };

      this.paginationMovimientos = {
        total: 0,
        current_page: 1,
        per_page: 15,
        last_page: 1,
        from: 0,
        to: 0
      };

    },

    cerrarDetalleMovimientos() {

      // Invalidar solicitudes pendientes
      this.solicitudMovimientosActual++;

      this.dialogMovimientos = false;
      this.cargandoMovimientos = false;

      this.productoHistorial = null;

      this.almacenHistorial = null;
      this.almacenesHistorial = [];

      this.limpiarDetalleMovimientos();

    },
    formatearCantidadHistorial(valor) {

      if (
        valor === null ||
        valor === undefined ||
        valor === "" ||
        !Number.isFinite(Number(valor))
      ) {
        return "Sin datos";
      }

      return Number(valor).toLocaleString("es-BO", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 4
      });

    },

    formatearFechaMovimiento(fecha) {

      if (!fecha) {
        return "-";
      }

      const partes = String(fecha)
        .replace("T", " ")
        .split(" ");

      const fechaPartes = partes[0].split("-");

      if (fechaPartes.length !== 3) {
        return fecha;
      }

      const fechaFormateada = fechaPartes
        .reverse()
        .join("/");

      return fechaFormateada +
        (partes[1] ? " " + partes[1].substring(0, 8) : "");

    },
    esEntradaHistorial(movimiento) {

      return movimiento.tipo_movimiento !== "STOCK_INICIAL" &&
        Number(movimiento.cantidad) > 0;

    },

    esSalidaHistorial(movimiento) {

      return movimiento.tipo_movimiento !== "STOCK_INICIAL" &&
        Number(movimiento.cantidad) < 0;

    },
    nombreMovimientoHistorial(tipo) {

      const nombres = {

        STOCK_INICIAL: "Stock inicial",

        COMPRA: "Compra",

        ANULACION_COMPRA: "Anulación de compra",

        ACTUALIZACION_COMPRA_ENTRADA:
          "Actualización compra - Entrada",

        ACTUALIZACION_COMPRA_SALIDA:
          "Actualización compra - Salida",

        VENTA: "Venta",

        ANULACION_VENTA: "Anulación de venta",

        AJUSTE_ENTRADA: "Ajuste de entrada",

        AJUSTE_SALIDA: "Ajuste de salida",

        TRASPASO_ENTRADA: "Traspaso de entrada",

        TRASPASO_SALIDA: "Traspaso de salida",

        ANULACION_TRASPASO_ENTRADA:
          "Anulación traspaso de entrada",

        ANULACION_TRASPASO_SALIDA:
          "Anulación traspaso de salida"

      };

      return nombres[tipo] || tipo || "-";

    },
    claseMovimientoHistorial(movimiento) {

      const tipo = movimiento.tipo_movimiento || "";

      if (tipo === "STOCK_INICIAL") {
        return "tag-inicial";
      }

      if (tipo.indexOf("ANULACION_") === 0) {
        return "tag-anulacion";
      }

      if (tipo.indexOf("ACTUALIZACION_") === 0) {
        return "tag-actualizacion";
      }

      if (tipo.indexOf("AJUSTE_") === 0) {
        return "tag-ajuste";
      }

      if (tipo.indexOf("TRASPASO_") === 0) {
        return "tag-traspaso";
      }

      if (Number(movimiento.cantidad) > 0) {
        return "tag-entrada";
      }

      if (Number(movimiento.cantidad) < 0) {
        return "tag-salida";
      }

      return "tag-inicial";

    },
  },
  async mounted() { this.handleResize(); window.addEventListener("resize", this.handleResize); this.inicializarFechaConsulta(); this.obtenerIdrol(); await this.selectAlmacen(); },
  beforeDestroy() { window.removeEventListener("resize", this.handleResize); this.cancelarBusquedaPendiente(); this.solicitudActual++; }
};
</script>
<style scoped>
/* 🔹 Estilo más pequeño para todos los Toasts */
.p-toast {
  width: 300px !important;
  /* más angosto */
  font-size: 0.75rem !important;
  /* texto más pequeño */
}

.p-toast-message {
  padding: 0.6rem 0.8rem !important;
  /* menos espacio interno */
  border-radius: 6px !important;
}

.p-toast-message-content {
  gap: 0.4rem !important;
  /* reduce separación entre ícono y texto */
}

.p-toast-message-text {
  line-height: 1.2;
}

.p-toast-summary {
  font-weight: 600;
  font-size: 0.85rem !important;
}

.p-toast-detail {
  font-size: 0.8rem !important;
  opacity: 0.9;
}

/* 🔹 Ícono más pequeño */
.p-toast-icon {
  font-size: 1rem !important;
}

/* 🔹 Márgenes y posición */
.p-toast-top-right {
  top: 1rem !important;
  right: 1rem !important;
}

.info-tip {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  padding: 8px 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 12px;
  color: #475569;
}

.info-tip i {
  color: #3b82f6;
  font-size: 14px;
  flex-shrink: 0;
}

/* Estilo de tabla con scroll horizontal */
.tabla-pro {
  width: 100%;
  white-space: nowrap;
  /* evita salto de columnas */
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

/* =========================================
   DROPDOWNS
========================================= */

.dropdown-full {
  width: 100% !important;
  max-width: 100%;
  min-width: 0 !important;
  box-sizing: border-box;
}

/* Input dentro del dropdown */
.dropdown-full>>>.p-dropdown-label {
  padding: 6px 8px !important;
  font-size: 0.8rem;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Flecha del dropdown */
.dropdown-full>>>.p-dropdown-trigger {
  width: 2rem !important;
  flex-shrink: 0;
}

/* Borde del dropdown */
.dropdown-full {
  border: 1px solid #ccc;
  transition: border 0.2s;
}

/* Borde al focus */
.dropdown-full.p-focus {
  border-color: #0ea5e9;
  box-shadow: 0 0 0 0.15rem rgba(14, 165, 233, 0.25);
}

/* Opciones del panel */
.dropdown-full>>>.p-dropdown-panel .p-dropdown-item {
  font-size: 0.8rem !important;
  padding: 6px 10px !important;
  min-height: auto !important;
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

/* 🔹 Botones pequeños */



/* 🔹 Botones pequeños inputs */
.btn-sm-input {
  font-size: 0.8rem;
  padding: 0.5rem 0.9rem;
  border-radius: 6px;
  line-height: 1.1;
}

.btn-sm-input .pi {
  font-size: 0.65rem;
  margin-right: 4px;
}

.swal2-confirm-excel {
  background-color: #22c55e !important;
  border-color: #22c55e !important;
  color: #fff !important;
  font-weight: bold;
}

.swal2-deny-pdf {
  background-color: #dc2626 !important;
  border-color: #dc2626 !important;
  color: #fff !important;
  font-weight: bold;
}

.swal2-cancel-cancelar {
  background-color: #6b7280 !important;
  border-color: #6b7280 !important;
  color: #fff !important;
  font-weight: bold;
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

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.panel-title-section {
  display: flex;
  align-items: center;
}

.panel-title {
  margin: 0;
  padding-left: 5px;
}

.panel-actions {
  display: flex;
  align-items: center;
}

/* =========================================
   CONTENEDOR DE FILTROS
========================================= */

.filters-container {
  margin-bottom: 1rem;
  padding: 1rem;
  background-color: #f8f9fa;
  border-radius: 0.375rem;
  border: 1px solid #dee2e6;
  width: 100%;
  max-width: 100%;
  min-width: 0;
  box-sizing: border-box;
}

.filter-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr)) auto;
  align-items: end;
  gap: 1rem;
  width: 100%;
  min-width: 0;
}

.filter-group-almacen {
  width: 100%;
  min-width: 0;
}

.filter-group-actions {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.panel-actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.filter-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 600;
  margin-bottom: 0.4rem;
  color: #6b7280;
  text-transform: uppercase;
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


/* =========================================
   TABLETS
========================================= */

@media (max-width: 1024px) {

  >>>.p-datatable {
    font-size: 0.85rem;
  }

  .filter-row {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
  }

  .filter-group-almacen {
    width: 100%;
    min-width: 0;
  }

  .filter-group-actions {
    grid-column: 1 / -1;
    width: 100%;
  }

  .panel-actions {
    justify-content: flex-end;
  }
}

/* =========================================
   CELULARES
========================================= */

@media (max-width: 768px) {

  .toolbar .p-button .p-button-label {
    display: none;
  }

  .toolbar-container {
    gap: 0.5rem;
  }

  .filters-container {
    padding: 0.75rem;
  }

  /* Distribución vertical de los filtros */
  .filter-row {
    grid-template-columns: minmax(0, 1fr);
    gap: 0.75rem;
    align-items: stretch;
  }

  .filter-group-almacen,
  .filter-group-actions {
    width: 100%;
    min-width: 0;
  }

  .filter-group-actions {
    grid-column: auto;
  }

  .panel-actions {
    width: 100%;
    justify-content: stretch;
  }

  /* Botón de exportación al ancho completo */
  .panel-actions>>>.p-button {
    flex: 1;
    width: 100%;
    justify-content: center;
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

  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  .search-bar .p-inputtext-sm {
    padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
    font-size: 0.85rem !important;
  }

  >>>.p-dropdown {
    font-size: 0.9rem;
  }
}

/* =========================================
   CELULARES PEQUEÑOS (HASTA 480px)
========================================= */

@media (max-width: 480px) {

  .toolbar .p-button .p-button-label {
    display: none;
  }

  .toolbar-container {
    gap: 0.4rem;
    flex-wrap: nowrap;
  }

  .toolbar {
    flex-shrink: 0;
    min-width: 0;
  }

  .search-bar {
    flex: 1;
    min-width: 0;
  }

  /* Contenedor de filtros */
  .filters-container {
    padding: 0.65rem;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
  }

  /* Distribución vertical */
  .filter-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: stretch;
    gap: 0.65rem;
    width: 100%;
    min-width: 0;
  }

  /* Grupos de filtros */
  .filter-group-almacen,
  .filter-group-actions {
    width: 100%;
    min-width: 0;
    margin-left: 0;
  }

  .filter-group-actions {
    grid-column: auto;
  }

  /* Dropdowns */
  .dropdown-full {
    width: 100% !important;
    max-width: 100%;
    min-width: 0 !important;
  }

  .dropdown-full>>>.p-dropdown-label {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.8rem;
  }

  /* Panel de acciones */
  .panel-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    width: 100%;
  }

  /* Botones al ancho completo */
  .panel-actions>>>.p-button {
    flex: 1;
    width: 100% !important;
    justify-content: center;
  }

  /* Etiquetas */
  .filter-label {
    font-size: 0.7rem;
    margin-bottom: 0.25rem;
  }

  /* Radio buttons */



  /* Toolbar */
  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  /* Buscador */
  .search-bar .p-inputtext-sm {
    padding: 0.3rem 0.5rem 0.3rem 2.5rem !important;
    font-size: 0.8rem !important;
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
  }

  /* DataTable */
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

  >>>.p-dropdown {
    font-size: 0.85rem;
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

.info-inventario-historico {
  padding: 12px 15px;
  margin-bottom: 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
}

.info-historico-detalle {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  font-size: 13px;
  color: #334155;
}

.estado-historico {
  padding: 4px 9px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
}

.estado-actual {
  background: #dbeafe;
  color: #1d4ed8;
}

.estado-registrado {
  background: #dcfce7;
  color: #166534;
}

.estado-estimado {
  background: #fef3c7;
  color: #92400e;
}

.mensaje-historico {
  display: block;
  margin-top: 7px;
  color: #64748b;
  font-size: 12px;
}

.barra-paginacion {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
  padding: 5px 0;
}

.contador-registros {
  font-size: 12px;
  color: #64748b;
  font-weight: 500;
}

.barra-paginacion>>>.p-paginator {
  padding: 0;
  border: none;
  background: transparent;
}

.stock-desconocido {
  color: #64748b;
}

@media (max-width: 768px) {
  .barra-paginacion {
    flex-direction: column;
    justify-content: center;
  }
}

/* ==========================================
   BOTÓN HISTORIAL
========================================== */

.btn-historial {
  background: #e8eef5 !important;
  border-color: #d5e0eb !important;
  color: #34495e !important;
}

.btn-historial:hover {
  background: #d5e0eb !important;
  border-color: #c3d2e0 !important;
  color: #243b53 !important;
}

/* ==========================================
   ENCABEZADO DEL DIALOG
========================================== */

.historial-dialog-header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.historial-header-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: #34495e;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.historial-header-icon i {
  font-size: 21px;
}

.historial-header-subtitle {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1px;
  color: #64748b;
  margin-bottom: 4px;
}

.historial-dialog-header h3 {
  font-size: 19px;
  font-weight: 700;
  color: #26384a;
  margin: 0;
}

/* ==========================================
   INFORMACIÓN DEL PRODUCTO
========================================== */

.historial-info-producto {
  display: grid;
  grid-template-columns: 0.7fr 2fr 0.8fr 1.2fr;
  gap: 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 16px;
}

.historial-info-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
  min-width: 0;
}

.historial-info-item span {
  font-size: 11px;
  color: #64748b;
  font-weight: 500;
}

.historial-info-item strong {
  font-size: 13px;
  color: #334155;
  font-weight: 600;
  overflow-wrap: anywhere;
}

/* ==========================================
   FILTROS
========================================== */

.historial-filtros {
  display: grid;
  grid-template-columns: 1.4fr 1fr 1fr 1.5fr auto;
  gap: 12px;
  align-items: end;
  margin-bottom: 16px;
}

.historial-filtro {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}

.historial-filtro label {
  font-size: 12px;
  font-weight: 600;
  color: #475569;
}

.historial-filtro .p-dropdown,
.historial-filtro input {
  width: 100%;
  min-width: 0;
  font-size: 13px;
}

.historial-filtro input {
  height: 38px;
}

.historial-filtro-boton .p-button {
  min-height: 38px;
  background: #34495e;
  border-color: #34495e;
}

/* ==========================================
   INDICADOR DE HISTORIAL
========================================== */

.historial-indicador {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 14px;
  border-radius: 7px;
  margin-bottom: 16px;
  border: 1px solid transparent;
}

.historial-indicador i {
  margin-top: 2px;
  font-size: 17px;
}

.historial-indicador strong {
  font-size: 13px;
  font-weight: 700;
}

.historial-indicador p {
  font-size: 12px;
  margin: 4px 0 0;
  line-height: 1.5;
}

.indicador-registrado {
  background: #ecfdf5;
  border-color: #bbf7d0;
  color: #166534;
}

.indicador-estimado {
  background: #fff7ed;
  border-color: #fed7aa;
  color: #9a3412;
}

.indicador-mixto {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #1e40af;
}

.indicador-neutral {
  background: #f8fafc;
  border-color: #e2e8f0;
  color: #475569;
}

/* ==========================================
   TARJETAS DE RESUMEN
========================================== */

.historial-resumen {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}

.historial-resumen-card {
  padding: 15px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  border-left: 3px solid #64748b;
}

.historial-resumen-label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 12px;
  color: #64748b;
  font-weight: 600;
  gap: 8px;
}

.historial-resumen-label i {
  font-size: 17px;
}

.historial-resumen-valor {
  font-size: 27px;
  font-weight: 700;
  color: #334155;
  margin: 10px 0 6px;
  font-variant-numeric: tabular-nums;
}

.historial-resumen-valor small {
  font-size: 11px;
  font-weight: 600;
}

.historial-resumen-descripcion {
  font-size: 10px;
  color: #94a3b8;
}

.resumen-entrada {
  border-left-color: #16a34a;
}

.resumen-entrada .historial-resumen-valor,
.resumen-entrada .historial-resumen-label i {
  color: #15803d;
}

.resumen-salida {
  border-left-color: #dc2626;
}

.resumen-salida .historial-resumen-valor,
.resumen-salida .historial-resumen-label i {
  color: #dc2626;
}

.resumen-final {
  border-left-color: #2563eb;
  background: #f8fbff;
}

.resumen-final .historial-resumen-valor,
.resumen-final .historial-resumen-label i {
  color: #1d4ed8;
}

/* ==========================================
   TABLA DE MOVIMIENTOS
========================================== */

.historial-tabla-contenedor {
  width: 100%;
  overflow-x: auto;
  margin-bottom: 12px;
}

.historial-tabla-titulo {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 700;
  color: #334155;
  margin-bottom: 12px;
}

.tabla-movimientos {
  min-width: 1100px;
}

.historial-fecha {
  white-space: nowrap;
  font-size: 12px;
}

.historial-referencia {
  font-size: 11px;
  font-weight: 600;
  color: #475569;
}

.historial-cantidad-entrada {
  color: #15803d;
  font-weight: 700;
  white-space: nowrap;
}

.historial-cantidad-salida {
  color: #dc2626;
  font-weight: 700;
  white-space: nowrap;
}

.historial-stock-resultante {
  font-weight: 700;
  color: #334155;
}

.historial-stock-negativo {
  color: #dc2626 !important;
}

.historial-guion {
  color: #cbd5e1;
}

.historial-observacion {
  font-size: 11px;
  color: #64748b;
  min-width: 150px;
  display: inline-block;
  overflow-wrap: anywhere;
}

/* ==========================================
   ETIQUETAS SOFT UI
========================================== */

.historial-tag {
  display: inline-flex;
  align-items: center;
  padding: 4px 8px;
  border-radius: 5px;
  font-size: 10px;
  font-weight: 600;
  white-space: nowrap;
}

.tag-entrada,
.tag-registrado {
  background: #dcfce7;
  color: #166534;
}

.tag-salida {
  background: #fee2e2;
  color: #991b1b;
}

.tag-anulacion,
.tag-estimado {
  background: #fef3c7;
  color: #92400e;
}

.tag-ajuste {
  background: #f3e8ff;
  color: #6b21a8;
}

.tag-traspaso {
  background: #dbeafe;
  color: #1e40af;
}

.tag-actualizacion {
  background: #e0f2fe;
  color: #075985;
}

.tag-inicial {
  background: #e2e8f0;
  color: #475569;
}

/* ==========================================
   ESTADOS DE CARGA Y ERROR
========================================== */

.historial-loading,
.historial-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 40px 15px;
  color: #64748b;
  font-size: 13px;
  text-align: center;
}

.historial-empty i {
  font-size: 30px;
  color: #94a3b8;
}

.historial-error {
  padding: 15px;
  border: 1px solid #fecaca;
  background: #fef2f2;
  color: #991b1b;
  border-radius: 7px;
  font-size: 13px;
}

.historial-error i {
  margin-right: 6px;
}

/* ==========================================
   RESPONSIVE
========================================== */

@media (max-width: 992px) {

  .historial-info-producto {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .historial-filtros {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .historial-resumen {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

}

@media (max-width: 576px) {

  .historial-info-producto,
  .historial-filtros {
    grid-template-columns: 1fr;
  }

  .historial-resumen {
    gap: 8px;
  }

  .historial-resumen-card {
    padding: 10px;
  }

  .historial-resumen-valor {
    font-size: 21px;
  }

  .historial-filtro-boton .p-button {
    width: 100%;
  }

  .historial-dialog-header h3 {
    font-size: 16px;
  }

}
</style>
