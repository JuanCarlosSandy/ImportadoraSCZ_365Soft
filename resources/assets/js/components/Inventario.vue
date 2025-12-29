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
          <div class="panel-title-section">
            <i class="pi pi-bars"></i>
            <h4 class="panel-title">INVENTARIO</h4>
          </div>
          <div class="panel-actions">
            <Button :label="mostrarLabel ? 'Importar' : ''" icon="pi pi-upload" @click="abrirModalImportar()"
              class="p-button-success p-button-sm" />
            <Button :label="mostrarLabel ? 'Exportar' : ''" icon="pi pi-download" @click="exportarInventario"
              class="p-button-info p-button-sm" style="margin-left: 0.5rem;" />
          </div>
        </div>
      </template>

      <div class="filters-container">
        <div class="filter-row">
          <div class="filter-group-almacen">
            <label class="filter-label">ALMACÉN DE TRABAJO</label>
            <Dropdown v-model="AlmacenSeleccionado" :options="arrayAlmacenes" optionLabel="nombre_almacen"
              optionValue="id" placeholder="Seleccione un almacén" @change="getDatosAlmacen" class="p-dropdown-sm" />
          </div>

          <div class="filter-group-modo">
            <!--<label class="filter-label">MODO VISTA</label>
            <div class="radio-group">
              <div class="p-field-radiobutton">
                <RadioButton id="porItem" v-model="tipoSeleccionado" value="item" @change="cambiarTipo" />
                <label for="porItem">Por Item</label>
              </div>
              <div class="p-field-radiobutton">
                <RadioButton id="porLote" v-model="tipoSeleccionado" value="lote" @change="cambiarTipo" />
                <label for="porLote">Por Lote</label>
              </div>
            </div>-->
          </div>
        </div>
      </div>

      <div class="toolbar-container">
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText v-model="buscar" placeholder="Texto a buscar" class="p-inputtext-sm" @keyup="buscarInventario" />
          </span>
        </div>
        <div class="toolbar">
          <Button :label="mostrarLabel ? 'Reset' : ''" icon="pi pi-refresh" @click="resetBusqueda"
            class="p-button-help p-button-sm" />
        </div>
      </div>
      <!-- DataTable para vista por Item -->
      <DataTable v-if="tipoSeleccionado == 'item'" :value="arrayInventario" class="p-datatable-sm p-datatable-gridlines"
        responsiveLayout="scroll">
                <Column field="codigo" header="CODIGO"></Column>
        <Column field="nombre_producto" header="PRODUCTO"></Column>
        <Column field="nombre_proveedor" header="PROVEEDOR"></Column>
        <Column field="unidad_envase" header="UNID X PAQ."></Column>
        <Column field="stock_formateado" header="STOCK CAJAS">
          <template #body="slotProps">

            <!-- Sin stock -->
            <span v-if="slotProps.data.cajas == 0 && slotProps.data.unidades == 0"
              style="color: #dc2626; font-weight: bold; display: flex; align-items: center;">
              <i class="pi pi-exclamation-triangle" style="margin-right: 6px; font-size: 1.1em;"></i>
              Sin Stock
            </span>

            <!-- Mostrar: "7 cajas y 2 unidades" -->
            <span v-else>
              {{ slotProps.data.stock_formateado }}
            </span>

          </template>
        </Column>

        <Column field="saldo_stock_total" header="STOCK UNIDADES">
          <template #body="slotProps">
            <span v-if="slotProps.data.saldo_stock_total == 0"
              style="color: #dc2626; font-weight: bold; display: flex; align-items: center;">
              <i class="pi pi-exclamation-triangle" style="margin-right: 6px; font-size: 1.1em;"></i>
              Sin Stock
            </span>
            <span v-else>
              {{ slotProps.data.saldo_stock_total }} {{ slotProps.data.descripcion_fabrica }}
            </span>
          </template>
        </Column>
      </DataTable>

      <!-- DataTable para vista por Lote -->
      <DataTable v-if="tipoSeleccionado == 'lote'" :value="arrayInventario" class="p-datatable-sm p-datatable-gridlines"
        responsiveLayout="scroll">
        <Column field="nombre_producto" header="ITEM"></Column>
        <Column field="nombre_proveedor" header="PROVEEDOR"></Column>
        <Column field="unidad_envase" header="UNID X PAQ"></Column>
        <Column field="stock_en_paquetes" header="STOCK CAJAS">
          <template #body="slotProps">
            <span v-if="slotProps.data.stock_en_paquetes == 0"
              style="color: #dc2626; font-weight: bold; display: flex; align-items: center;">
              <i class="pi pi-exclamation-triangle" style="margin-right: 6px; font-size: 1.1em;"></i>
              Sin Stock
            </span>
            <span v-else>
              {{ slotProps.data.stock_en_paquetes }}
            </span>
          </template>
        </Column>
        <Column field="saldo_stock" header="STOCK UNIDADES">
          <template #body="slotProps">
            <span v-if="slotProps.data.saldo_stock == 0"
              style="color: #dc2626; font-weight: bold; display: flex; align-items: center;">
              <i class="pi pi-exclamation-triangle" style="margin-right: 6px; font-size: 1.1em;"></i>
              Sin Stock
            </span>
            <span v-else>
              {{ slotProps.data.saldo_stock }}
            </span>
          </template>
        </Column>
        <Column field="fecha_ingreso" header="FECHA INGRESO"></Column>
      </DataTable>

      <!-- Paginación manual del servidor -->
      <Paginator :rows="pagination.per_page" :totalRecords="pagination.total"
        :first="(pagination.current_page - 1) * pagination.per_page" @page="onPageChange" />
    </Panel>
    <!-- Fin ejemplo de tabla Listado -->

    <!--Inicio del modal agregar/actualizar-->

    <!--Fin del modal-->

    <div v-if="modalImportar">
      <ImportarExcelInventario @cerrar="cerrarModalImportar" />
    </div>
  </main>
</template>

<script>
import Panel from "primevue/panel";
import Swal from "sweetalert2";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import InputText from "primevue/inputtext";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import RadioButton from "primevue/radiobutton";
import Paginator from "primevue/paginator";

export default {
  components: {
    Panel,
    Button,
    Dropdown,
    InputText,
    DataTable,
    Column,
    RadioButton,
    Paginator,
  },
  data() {
    return {
      mostrarLabel: true,
      isLoading: false,
      arrayInventario: [],
      arrayAlmacenes: [],
      AlmacenSeleccionado: 1,
      idalmacen: 0,
      tipoSeleccionado: "item",
      modalImportar: 0,
      pagination: {
        total: 0,
        current_page: 0,
        per_page: 0,
        last_page: 0,
        from: 0,
        to: 0,
      },
      offset: 3,
      criterio: "nombre",
      buscar: "",
    };
  },
  computed: {
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

      var to = from + this.offset * 2;
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
  },
  methods: {
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
        Swal.fire('Error al generar el reporte', '', 'error');
      }
    },
    async exportarInventario() {
      const result = await Swal.fire({
        title: 'En qué formato desea exportar',
        icon: 'question',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: '<i class="pi pi-file-excel"></i> Excel',
        denyButtonText: '<i class="pi pi-file-pdf"></i> PDF',
        cancelButtonText: 'Cancelar',
        customClass: {
          confirmButton: 'swal2-confirm-excel',
          denyButton: 'swal2-deny-pdf',
          cancelButton: 'swal2-cancel-cancelar',
        },
      });
      const modo = this.tipoSeleccionado;
      const idAlmacen = this.AlmacenSeleccionado;
      if (result.isConfirmed) {
        // Exportar a Excel
        const url = `/inventario/exportar-excel?modo=${modo}&idAlmacen=${idAlmacen}`;
        await this.descargarArchivoReporte(url, 'inventario.xlsx');
      } else if (result.isDenied) {
        // Exportar a PDF
        const url = `/inventario/exportar-pdf?modo=${modo}&idAlmacen=${idAlmacen}`;
        await this.descargarArchivoReporte(url, 'inventario.pdf');
      }
    },
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    async buscarInventario() {
      try {
        if (this.searchTimeout) {
          clearTimeout(this.searchTimeout);
        }

        this.searchTimeout = setTimeout(async () => {
          this.isLoading = true; // Activar loading
          await this.listarInventario(1, this.buscar, this.criterio);
          setTimeout(() => {
            this.isLoading = false; // Desactivar loading
          }, 500);
        }, 300);
      } catch (error) {
        console.error("Error en la búsqueda:", error);
        this.isLoading = false;
      }
    },
    resetBusqueda() {
      this.buscar = ""; // Limpiar input
      this.listarInventario(1, "", ""); // Llamar con valores vacíos
    },
    onPageChange(event) {
      const page = Math.floor(event.first / event.rows) + 1;
      this.cambiarPagina(page, this.buscar, this.criterio);
    },
    abrirModalImportar() {
      this.modalImportar = 1;
    },
    cerrarModalImportar() {
      this.modalImportar = 0;
      this.listarInventario(1, "", "nombre");
    },
    async cambiarPagina(page, buscar, criterio) {
      let me = this;
      try {
        me.pagination.current_page = page;
        await me.listarInventario(page, buscar, criterio);
      } catch (error) {
        console.error("Error al cambiar de página:", error);
        Swal.fire("Error", "No se pudo cambiar de página", "error");
      }
    },

    async listarInventario(page, buscar, criterio) {
      let me = this;
      try {
        let url =
          "/inventarios/itemLote/" +
          me.tipoSeleccionado +
          "?&idAlmacen=" +
          me.almacenSeleccionado +
          "&buscar=" +
          buscar +
          "&criterio=" +
          criterio +
          "&page=" +
          page;

        const response = await axios.get(url);
        var respuesta = response.data;
        me.arrayInventario = respuesta.inventarios.data;
        me.pagination = respuesta.pagination;
      } catch (error) {
        Swal.fire("Error", "No se pudo cargar el inventario", "error");
      }
    },
    async selectAlmacen() {
      let me = this;
      try {
        const url = "/almacen/selectAlmacen";
        const response = await axios.get(url);

        const respuesta = response.data;
        me.arrayAlmacenes = respuesta.almacenes;

        // Selecciona el primer almacén si hay al menos uno
        if (me.arrayAlmacenes.length > 0) {
          me.AlmacenSeleccionado = me.arrayAlmacenes[0].id;
          await me.getDatosAlmacen(); // 👈 Dispara manualmente después de asignar
        }
      } catch (error) {
        console.error("Error al cargar almacenes:", error);
        Swal.fire("Error", "No se pudieron cargar los almacenes", "error");
      }
    },

    async getDatosAlmacen() {
      let me = this;
      try {
        if (me.AlmacenSeleccionado === "") {
          return;
        }

        me.almacenSeleccionado = me.AlmacenSeleccionado;
        me.idalmacen = Number(me.AlmacenSeleccionado);

        await me.listarInventario(1, me.buscar, "");
      } catch (error) {
        console.error("Error al obtener datos del almacén:", error);
        Swal.fire("Error", "No se pudieron cargar los datos del almacén", "error");
      }
    },
    async cambiarTipo() {
      try {
        this.isLoading = true; // Activar loading
        await this.getDatosAlmacen();
      } catch (error) {
        console.error("Error al cambiar tipo de vista:", error);
        Swal.fire("Error", "No se pudo cambiar el tipo de vista", "error");
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 100);
      }
    },
    //--------------------------------------
  },
  async mounted() {
    this.handleResize();
    window.addEventListener("resize", this.handleResize);
    try {
      await Promise.all([this.selectAlmacen()]);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
      Swal.fire("Error", "Error al cargar los datos iniciales", "error");
    }
  },
  beforeUnmount() {
    window.removeEventListener("resize", this.handleResize);
  },
};
</script>
<style scoped>
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

/* Filters Container */
.filters-container {
  margin-bottom: 1rem;
  padding: 1rem;
  background-color: #f8f9fa;
  border-radius: 0.375rem;
  border: 1px solid #dee2e6;
}

.filter-row {
  display: flex;
  gap: 2rem;
  align-items: flex-end;
}

.filter-group-almacen {
  flex: 2;
  min-width: 200px;
}

.filter-group-modo {
  flex: 1;
  min-width: 150px;
}

.filter-label {
  display: block;
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.filter-group-almacen .filter-label,
.filter-group-modo .filter-label {
  margin-bottom: 0.4rem;
}

.radio-group {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.p-field-radiobutton {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.p-field-radiobutton label {
  font-size: 0.875rem;
  color: #495057;
  cursor: pointer;
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

/* Tablet Styles */
@media (max-width: 1024px) {
  >>>.p-datatable {
    font-size: 0.85rem;
  }

  .filter-row {
    flex-direction: row;
    gap: 1rem;
    align-items: flex-end;
  }

  .filter-group-almacen,
  .filter-group-modo {
    min-width: auto;
    flex: 1;
  }
}

/* Mobile Styles */
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

  .filter-row {
    flex-direction: row;
    gap: 1rem;
    align-items: flex-start;
  }

  .filter-group-almacen {
    flex: 1;
    min-width: 0;
  }

  .filter-group-modo {
    flex: 1;
    min-width: 0;
  }

  .radio-group {
    flex-direction: row;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
  }

  .p-field-radiobutton {
    margin: 0;
  }

  .p-field-radiobutton label {
    font-size: 0.8rem;
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

/* Extra Small Mobile */
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
    min-width: auto;
  }

  .search-bar {
    flex: 1;
    min-width: 0;
  }

  .filters-container {
    padding: 0.5rem;
  }

  .filter-row {
    flex-direction: column;
    gap: 0.75rem;
    align-items: stretch;
  }

  .filter-group-almacen {
    flex: none;
    min-width: auto;
  }

  .filter-group-modo {
    flex: none;
    min-width: auto;
  }

  .filter-label {
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
  }

  .radio-group {
    flex-direction: row;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
  }

  .p-field-radiobutton label {
    font-size: 0.75rem;
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

  >>>.p-dropdown {
    font-size: 0.85rem;
  }

  .p-field-radiobutton label {
    font-size: 0.8rem;
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
</style>
