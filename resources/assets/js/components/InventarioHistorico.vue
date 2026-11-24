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
          Seleccione una fecha y un almacén para consultar las existencias. Puede exportar los resultados en Excel o PDF.
        </span>
      </div>

      <div class="filters-container">
  <div class="filter-row">

    <!-- Fecha de consulta -->
    <div class="filter-group-almacen">
      <label for="fechaConsulta" class="filter-label">
        FECHA DE CONSULTA
      </label>

      <input
        id="fechaConsulta"
        type="date"
        v-model="fechaConsulta"
        :max="fechaActual"
        @change="cambiarFechaConsulta"
        class="p-inputtext p-component p-inputtext-sm input-date-full"
      />
    </div>

    <!-- Almacén -->
    <div class="filter-group-almacen">
      <label class="filter-label">
        ALMACÉN DE TRABAJO
      </label>

      <Dropdown
        v-model="AlmacenSeleccionado"
        :options="arrayAlmacenes"
        optionLabel="nombre_almacen"
        optionValue="id"
        placeholder="Seleccione un almacén"
        @change="getDatosAlmacen"
        class="dropdown-full"
      />
    </div>

    <!-- Ordenar inventario -->
    <div class="filter-group-almacen">
      <label class="filter-label">
        ORDENAR PRODUCTOS
      </label>

      <Dropdown
        v-model="ordenInventario"
        :options="opcionesOrden"
        optionLabel="label"
        optionValue="value"
        placeholder="Ordenar por"
        @change="cambiarOrdenInventario"
        class="dropdown-full"
      />
    </div>

    <!-- Acciones -->
    <div class="filter-group-actions">
      <label class="filter-label">ACCIONES</label>

      <div class="panel-actions">
        <Button
          label="Exportar"
          icon="pi pi-download"
          @click="exportarInventario" :disabled="isLoading || !consultaRealizada || Number(idrol) === 2"
          class="p-button-info p-button-sm btn-sm-input"
        />
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

      <div
  v-if="consultaRealizada && tipoConsultaHistorica"
  class="info-inventario-historico"
>
  <div class="info-historico-detalle">

    <i class="pi pi-calendar"></i>

    <strong>
      Inventario al {{ fechaConsultaFormateada }}
    </strong>

    <span
      v-if="tipoConsultaHistorica === 'ACTUAL'"
      class="estado-historico estado-actual"
    >
      Stock actual
    </span>

    <span
      v-else-if="tipoConsultaHistorica === 'HISTORICO_REGISTRADO'"
      class="estado-historico estado-registrado"
    >
      Stock histórico registrado
    </span>

    <span
      v-else-if="tipoConsultaHistorica === 'HISTORICO_ESTIMADO'"
      class="estado-historico estado-estimado"
    >
      Stock histórico estimado
    </span>

  </div>

  <small v-if="mensajeHistorial" class="mensaje-historico">
    {{ mensajeHistorial }}
  </small>
  <small v-if="fechaCorteHistorial" class="mensaje-historico">Inicio del historial registrado: {{ fechaCorteHistorial }}</small>
</div>

      <!-- Inventario histórico por producto -->
<DataTable v-if="consultaRealizada"
  :value="arrayInventario"
  class="p-datatable-sm p-datatable-gridlines tabla-pro"
  responsiveLayout="scroll"
>

  <!-- Código -->
  <Column field="codigo" header="Código"></Column>

  <!-- Producto -->
  <Column field="nombre_producto" header="Producto"></Column>

  <!-- Stock del almacén seleccionado -->
  <Column
    field="saldo_stock_total"
    :header="tituloColumnaStock"
  >
    <template #body="slotProps">

      <span
        v-if="stockDesconocido(slotProps.data.saldo_stock_total)" class="stock-desconocido">Sin datos</span>
      <span v-else-if="Number(slotProps.data.saldo_stock_total) === 0"
        style="color: #dc2626; font-weight: bold;"
      >
        <i
          class="pi pi-exclamation-triangle"
          style="margin-right: 5px;"
        ></i>
        Sin Stock
      </span>

      <span v-else>
        {{ slotProps.data.saldo_stock_total }}
        {{ slotProps.data.descripcion_fabrica }}
      </span>

    </template>
  </Column>

  <!-- Stock del depósito -->
  <Column
    v-if="Number(AlmacenSeleccionado) !== 2"
    field="stock_deposito"
    header="Stock Depósito"
  >
    <template #body="slotProps">

      <span
        v-if="stockDesconocido(slotProps.data.stock_deposito)" class="stock-desconocido">Sin datos</span>
      <span v-else-if="Number(slotProps.data.stock_deposito) === 0"
        style="color: #dc2626; font-weight: 600;"
      >
        Sin Stock
      </span>

      <span v-else>
        {{ slotProps.data.stock_deposito }}
        {{ slotProps.data.descripcion_fabrica }}
      </span>

    </template>
  </Column>

  <!-- Columnas dinámicas de otros almacenes -->
  <Column
    v-for="almacen in arrayAlmacenesColumnas"
    :key="almacen.id"
    :field="'stock_almacen_' + almacen.id"
    :header="almacen.nombre_almacen"
  >
    <template #body="slotProps">

      <span
        v-if="stockDesconocido(slotProps.data['stock_almacen_' + almacen.id])" class="stock-desconocido">Sin datos</span>
      <span v-else-if="Number(slotProps.data['stock_almacen_' + almacen.id]) === 0"
        style="color: #dc2626; font-weight: 600;"
      >
        Sin Stock
      </span>

      <span v-else>
        {{ slotProps.data['stock_almacen_' + almacen.id] }}
        {{ slotProps.data.descripcion_fabrica }}
      </span>

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
  <span class="contador-registros">Mostrando {{ pagination.from }} - {{ pagination.to }} de {{ pagination.total }} productos</span>
<Paginator
  :rows="pagination.per_page"
  :totalRecords="pagination.total"
  :first="(pagination.current_page - 1) * pagination.per_page"
  @page="onPageChange"
/>
</div>
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

const paginacionInicial = () => ({ total: 0, current_page: 1, per_page: 15, last_page: 1, from: 0, to: 0 });

export default {
  components: { Toast, Panel, Button, Dropdown, InputText, DataTable, Column, Paginator },
  data() {
    return {
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
    tituloColumnaStock() { return this.tipoConsultaHistorica === "ACTUAL" ? "Stock Actual" : "Stock Histórico"; }
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
    }
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

.barra-paginacion { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-top: 10px; padding: 5px 0; }
.contador-registros { font-size: 12px; color: #64748b; font-weight: 500; }
.barra-paginacion >>> .p-paginator { padding: 0; border: none; background: transparent; }
.stock-desconocido { color: #64748b; }
@media (max-width: 768px) { .barra-paginacion { flex-direction: column; justify-content: center; } }
</style>
