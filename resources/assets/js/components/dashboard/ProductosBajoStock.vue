<template>
  <Panel>
    <template #header>
      <div
        style="display: flex; align-items: center; justify-content: space-between; width: 100%;"
      >
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <i class="pi pi-bars panel-icon"></i>
          <h4 class="panel-title" style="margin: 0;">BAJO STOCK (REPONER)</h4>
        </div>
        <Button
          icon="pi pi-download"
          :label="mostrarLabel ? 'Descargar' : ''"
          class="p-button-secondary p-button-sm"
          @click="cargarExcel"
        />
      </div>
    </template>
    <DataTable
      :value="arrayInventario"
      :rowClass="getRowClass"
      responsiveLayout="scroll"
      stripedRows
    >
      <Column field="codigo" header="Código"></Column>
      <Column field="nombre_producto" header="Nombre de artículo"></Column>
      <Column field="nombre_proveedor" header="Proveedor"></Column>
      <Column field="nombre_almacen" header="Almacén"></Column>
      <Column field="saldo_total_cajas" header="En Stock"></Column>
      <Column header="Estado">
        <template #body="slotProps">
          <Tag
            v-if="slotProps.data.saldo_stock === 0"
            severity="danger"
            icon="pi pi-times-circle"
          />
          <Tag v-else severity="warning" icon="pi pi-exclamation-triangle" />
        </template>
      </Column>
    </DataTable>
    <Paginator
      :rows="pagination.per_page"
      :totalRecords="pagination.total"
      :first="(pagination.current_page - 1) * pagination.per_page"
      @page="onPageChange"
    />
  </Panel>
</template>

<script>
import Panel from "primevue/panel";
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import Paginator from "primevue/paginator";

export default {
  components: {
    Panel,
    Button,
    DataTable,
    Column,
    Tag,
    Paginator,
  },
  data() {
    return {
      mostrarLabel: true,
      arrayInventario: [],
      pagination: {
        total: 0,
        current_page: 1,
        per_page: 10,
        last_page: 0,
      },
      buscar: "",
      criterio: "nombre_almacen",
    };
  },
  methods: {
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    listarInventario(page = 1, buscar = "", criterio = "") {
      axios
        .get(
          `/inventarios/productosbajostock?page=${page}&buscar=${buscar}&criterio=${criterio}`
        )
        .then((response) => {
          this.arrayInventario = response.data.inventarios.data;
          this.pagination = response.data.pagination;
        })
        .catch((error) => {
          console.error(error);
        });
    },
    onPageChange(event) {
      const page = event.page + 1;
      this.pagination.current_page = page;
      this.listarInventario(page, this.buscar, this.criterio);
    },
    cargarExcel() {
      window.open("/inventarios/listarReporteBajoStockExcel", "_blank");
    },
    getRowClass(data) {
      return {
        "bg-red-100": data.saldo_stock === 0,
        "bg-yellow-100": data.saldo_stock > 0,
      };
    },
  },
  mounted() {
    this.handleResize();
    window.addEventListener("resize", this.handleResize);
    this.listarInventario(1, this.buscar, this.criterio);
  },
};
</script>

<style scoped>
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

.input-container {
  position: relative;
  padding-bottom: 20px; /* Aumentado de 8px a 12px para dar espacio al error */
  margin-bottom: 8px; /* Agregado margen inferior pequeño */
}

.input-container .p-inputtext {
  width: 100%;
  margin-bottom: 0; /* Eliminar margen inferior si existe */
}

.error-message {
  position: absolute;
  bottom: 2px; /* Ajustado para tener más espacio arriba del input */
  left: 0;
  font-size: 0.75rem; /* Tamaño de fuente más pequeño */
  margin-top: 0; /* Eliminado margen superior */
}

/* Panel Content Spacing */
>>> .p-panel .p-panel-content {
  padding: 1rem;
}
>>> .p-panel .p-panel-header {
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
}
>>> .p-panel .p-panel-header .p-panel-title {
  font-weight: 600;
}

/* Responsive Dialog Styles */
.responsive-dialog >>> .p-dialog {
  margin: 0.75rem;
  max-height: 90vh;
  overflow-y: auto;
}

.responsive-dialog >>> .p-dialog-content {
  overflow-x: auto;
  padding: 0.75rem 1rem; /* Reducido padding vertical */
}

.responsive-dialog >>> .p-dialog-header {
  padding: 0.75rem 1.5rem; /* Reducido padding vertical */
  font-size: 1.1rem;
}

.responsive-dialog >>> .p-dialog-footer {
  padding: 0.5rem 1.5rem; /* Reducido padding vertical */
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
.form-compact >>> .p-field {
  margin-bottom: 0.25rem !important; /* Reducido de 0.5rem a 0.25rem */
}

>>> .p-fluid .p-field {
  margin-bottom: 0.25rem; /* Reducido de 0.5rem a 0.25rem */
}

/* Reducir padding del contenedor del diálogo */
.responsive-dialog >>> .p-dialog-content {
  padding: 0.75rem 1rem !important; /* Reducido padding vertical */
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

.activo {
  color: green;
  font-weight: bold;
}
.status-badge {
  padding: 0.25em 0.5em;
  border-radius: 4px;
  color: white;
}
.status-badge.active {
  background-color: rgb(0, 225, 0);
}
.status-badge.inactive {
  background-color: red;
}

/* DataTable Responsive */
>>> .p-datatable {
  font-size: 0.9rem;
}

>>> .p-datatable .p-datatable-tbody > tr > td {
  padding: 0.5rem;
  word-break: break-word;
  text-align: left;
}

>>> .p-datatable .p-datatable-thead > tr > th {
  padding: 0.75rem 0.5rem;
  font-size: 0.85rem;
}

.p-dialog-mask {
  z-index: 9990 !important;
}
.p-dialog {
  z-index: 9990 !important;
}

/* SweetAlert z-index para que aparezca por encima de los diálogos */
>>> .swal2-container {
  z-index: 99999 !important;
}

>>> .swal2-popup {
  z-index: 99999 !important;
}

/* Tablet Styles */
@media (max-width: 1024px) {
  .responsive-dialog >>> .p-dialog {
    margin: 0.5rem;
    max-height: 95vh;
  }

  >>> .p-datatable {
    font-size: 0.85rem;
  }
}

/* Mobile Styles */
@media (max-width: 768px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog >>> .p-dialog {
    margin: 0.25rem;
    max-height: 98vh;
  }

  .responsive-dialog >>> .p-dialog-content {
    padding: 0.5rem 0.75rem; /* Más compacto en móviles */
  }

  .responsive-dialog >>> .p-dialog-header {
    padding: 0.5rem 1rem; /* Reducido padding vertical */
    font-size: 1rem;
  }

  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.4rem 1rem; /* Reducido padding vertical */
    justify-content: flex-end;
  }

  .toolbar-container {
    gap: 0.5rem;
  }

  >>> .p-datatable {
    font-size: 0.8rem;
  }

  >>> .p-datatable .p-datatable-tbody > tr > td {
    padding: 0.4rem 0.3rem;
  }

  >>> .p-datatable .p-datatable-thead > tr > th {
    padding: 0.5rem 0.3rem;
    font-size: 0.75rem;
  }

  /* Ajustar botones en móviles */
  >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }

  /* Ajustar botón "Nuevo" para que coincida con otros botones */
  .toolbar >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  /* Reducir altura del input buscador */
  .search-bar .p-inputtext-sm {
    padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
    font-size: 0.85rem !important;
  }

  /* Ajustar iconos en móviles */
  .required-icon {
    font-size: 0.8rem;
  }

  .optional-icon {
    font-size: 0.6rem;
  }

  >>> .p-inputtext,
  >>> .p-dropdown,
  >>> .p-inputnumber-input {
    font-size: 0.9rem;
    padding: 0.5rem;
  }

  /* Reducir espacios entre campos en móviles */
  .input-container {
    padding-bottom: 20px; /* Aumentado para dar espacio al error en móviles */
    margin-bottom: 6px;
  }
}

/* Extra Small Mobile */
@media (max-width: 480px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog >>> .p-dialog {
    margin: 0.1rem;
    max-height: 99vh;
  }

  .responsive-dialog >>> .p-dialog-content {
    padding: 0.4rem 0.5rem; /* Más compacto en móviles extra pequeños */
  }

  .responsive-dialog >>> .p-dialog-header {
    padding: 0.4rem 0.75rem; /* Reducido padding vertical */
    font-size: 0.95rem;
  }

  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.3rem 0.75rem; /* Reducido padding vertical */
    justify-content: flex-end;
  }

  .responsive-dialog >>> .p-dialog-footer .p-button {
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
  .toolbar >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  /* Reducir más la altura del input buscador en móviles pequeños */
  .search-bar .p-inputtext-sm {
    padding: 0.3rem 0.5rem 0.3rem 2.5rem !important;
    font-size: 0.8rem !important;
  }

  >>> .p-datatable {
    font-size: 0.75rem;
  }

  >>> .p-datatable .p-datatable-tbody > tr > td {
    padding: 0.3rem 0.2rem;
  }

  >>> .p-datatable .p-datatable-thead > tr > th {
    padding: 0.4rem 0.2rem;
    font-size: 0.7rem;
  }

  /* Iconos más pequeños en móviles extra pequeños */
  .required-icon {
    font-size: 0.7rem;
  }

  .optional-icon {
    font-size: 0.55rem;
  }

  >>> .p-inputtext,
  >>> .p-dropdown,
  >>> .p-inputnumber-input {
    font-size: 0.85rem;
    padding: 0.4rem;
  }

  >>> .p-tag {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
  }

  /* Espacios aún más compactos en móviles extra pequeños */
  .input-container {
    padding-bottom: 20px; /* Aumentado para dar espacio al error en móviles pequeños */
    margin-bottom: 4px;
  }
}

/* Paginator Responsive */
@media (max-width: 768px) {
  >>> .p-paginator {
    flex-wrap: wrap !important;
    justify-content: center;
    font-size: 0.85rem;
    padding: 0.5rem;
  }

  >>> .p-paginator .p-paginator-page,
  >>> .p-paginator .p-paginator-next,
  >>> .p-paginator .p-paginator-prev,
  >>> .p-paginator .p-paginator-first,
  >>> .p-paginator .p-paginator-last {
    min-width: 32px !important;
    height: 32px !important;
    font-size: 0.85rem !important;
    padding: 0 6px !important;
    margin: 2px !important;
  }
}

@media (max-width: 480px) {
  >>> .p-paginator {
    font-size: 0.8rem;
    padding: 0.4rem;
  }

  >>> .p-paginator .p-paginator-page,
  >>> .p-paginator .p-paginator-next,
  >>> .p-paginator .p-paginator-prev,
  >>> .p-paginator .p-paginator-first,
  >>> .p-paginator .p-paginator-last {
    min-width: 28px !important;
    height: 28px !important;
    font-size: 0.8rem !important;
    padding: 0 4px !important;
    margin: 1px !important;
  }
}

/* Action Buttons in DataTable */
>>> .p-datatable .p-button {
  margin-right: 0.25rem;
}

@media (max-width: 768px) {
  >>> .p-datatable .p-button {
    margin-right: 0.15rem;
    margin-bottom: 0.15rem;
  }
}
>>> .p-fileupload .p-button.p-fileupload-choose {
  background-color: #22c55e !important;
  border-color: #22c55e !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

/* Efecto hover */
>>> .p-fileupload .p-button.p-fileupload-choose:enabled:hover {
  background-color: #16a34a !important;
  border-color: #16a34a !important;
}

/* Efecto focus */
>>> .p-fileupload .p-button.p-fileupload-choose:focus {
  box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.5) !important;
}

/* Efecto active (cuando se hace clic) */
>>> .p-fileupload .p-button.p-fileupload-choose:enabled:active {
  background-color: #15803d !important;
  border-color: #15803d !important;
}

/* Estilo cuando está deshabilitado */
>>> .p-fileupload .p-button.p-fileupload-choose:disabled {
  background-color: #22c55e !important;
  border-color: #22c55e !important;
  opacity: 0.6;
}
>>> .p-fileupload
  .p-fileupload-buttonbar
  .p-button.p-component:not(.p-fileupload-choose) {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

/* Efecto hover */
>>> .p-fileupload
  .p-fileupload-buttonbar
  .p-button.p-component:not(.p-fileupload-choose):enabled:hover {
  background: #dc2626 !important;
  border-color: #dc2626 !important;
}

/* Efecto focus */
>>> .p-fileupload
  .p-fileupload-buttonbar
  .p-button.p-component:not(.p-fileupload-choose):focus {
  box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.5) !important;
}

/* Efecto active (cuando se hace clic) */
>>> .p-fileupload
  .p-fileupload-buttonbar
  .p-button.p-component:not(.p-fileupload-choose):enabled:active {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
}

/* Estilo cuando está deshabilitado */
>>> .p-fileupload
  .p-fileupload-buttonbar
  .p-button.p-component:not(.p-fileupload-choose):disabled {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  opacity: 0.6;
}
>>> .p-fileupload .p-fileupload-files .p-button {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

/* Efecto hover */
>>> .p-fileupload .p-fileupload-files .p-button:enabled:hover {
  background: #dc2626 !important;
  border-color: #dc2626 !important;
}

/* Efecto focus */
>>> .p-fileupload .p-fileupload-files .p-button:focus {
  box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.5) !important;
}

/* Efecto active (cuando se hace clic) */
>>> .p-fileupload .p-fileupload-files .p-button:enabled:active {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
}

/* Estilo cuando está deshabilitado */
>>> .p-fileupload .p-fileupload-files .p-button:disabled {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  opacity: 0.6;
}

/* Asegurar que el icono dentro del botón también sea blanco */
>>> .p-fileupload .p-fileupload-files .p-button .p-button-icon {
  color: #ffffff !important;
}
>>> .p-fileupload-row > div:first-child {
  display: none !important;
}
>>> .p-dialog .p-dialog-content {
  padding: 0 1.5rem 1.5rem 1.5rem;
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
.modal-footer-buttons {
  padding-top: 1rem;
}
</style>
