<template>
  <Panel>
    <div v-if="isLoading" class="loading-overlay">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">LOADING...</div>
      </div>
    </div>
    <template #header>
      <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <i class="pi pi-bars panel-icon"></i>
          <h4 class="panel-title" style="margin: 0;">PRODUCTOS CON BAJO STOCK</h4>
        </div>
        <div class="botones-export">
          <Button
            icon="pi pi-file-pdf"
            label="PDF"
            class="p-button-danger p-button-sm bt-pdf p-mr-2"
            @click="exportarPDF"
            :disabled="isLoading"
          />
          <Button
            icon="pi pi-file-excel"
            label="Excel"
            class="p-button-success p-button-sm bt-ex"
            @click="exportarExcel"
            :disabled="isLoading"
          />
        </div>
      </div>
    </template>

    <div class="filters-container" style="margin-bottom: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 6px; border: 1px solid #dee2e6;">
      <div class="p-fluid grid formgrid" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
        <div class="field col-12 md:col-3" style="flex: 1; min-width: 200px;">
          <label for="almacen" style="font-weight: bold; font-size: 0.9rem;">Almacén</label>
          <Dropdown
            id="almacen"
            v-model="filtros.almacen_id"
            :options="arrayAlmacenes"
            optionLabel="nombre_almacen"
            optionValue="id"
            placeholder="Todos los almacenes"
            showClear
            @change="listarInventario(1)"
            class="p-inputtext-sm"
          />
        </div>

        <div class="field col-12 md:col-3" style="flex: 1; min-width: 200px;">
          <label for="proveedor" style="font-weight: bold; font-size: 0.9rem;">Proveedor</label>
          <InputText
            id="proveedor"
            v-model="filtros.proveedor"
            placeholder="Escribe para buscar..."
            class="p-inputtext-sm"
            @input="buscarConRetraso"
          />
        </div>

        <div class="field col-12 md:col-3" style="flex: 1; min-width: 200px;">
          <label for="producto" style="font-weight: bold; font-size: 0.9rem;">Producto</label>
          <InputText
            id="producto"
            v-model="filtros.producto"
            placeholder="Escribe para buscar..."
            class="p-inputtext-sm"
            @input="buscarConRetraso"
          />
        </div>

        <div style="display: flex;">
          <Button
            icon="pi pi-filter-slash"
            class="p-button-help p-button-sm p-button-outlined"
            @click="resetFiltros"
            title="Limpiar todos los filtros"
            label="Limpiar"
          />
        </div>
      </div>
    </div>

    <DataTable
      :value="arrayInventario"
      :rowClass="getRowClass"
      responsiveLayout="scroll"
      stripedRows
      rowGroupMode="subheader"
      groupRowsBy="nombre_almacen"
      sortMode="single"
      sortField="nombre_almacen"
      :sortOrder="1"
    >
      <template #groupheader="slotProps">
        <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background-color: #e9ecef;">
          <i class="pi pi-building" style="font-size: 1.2rem; color: #495057;"></i>
          <span style="font-weight: bold; font-size: 1.1rem; color: #495057;">
            {{ slotProps.data.nombre_almacen }}
          </span>
        </div>
      </template>

      <Column field="nombre_proveedor" header="Proveedor">
        <template #body="slotProps">
          <span style="font-weight: 600; color: #007bff;">
            {{ slotProps.data.nombre_proveedor }}
          </span>
        </template>
      </Column>

      <Column field="nombre_producto" header="Producto"></Column>

      <Column field="saldo_stock" header="Stock Actual">
        <template #body="slotProps">
          <span style="font-weight: bold; font-size: 1.1em;">
            {{ slotProps.data.saldo_stock }}
          </span>
        </template>
      </Column>

      <Column header="Estado">
        <template #body="slotProps">
          <Tag
            v-if="Number(slotProps.data.saldo_stock) === 0"
            severity="danger"
            icon="pi pi-times-circle"
            value="Sin Stock"
          />
          <Tag
            v-else
            severity="warning"
            icon="pi pi-exclamation-triangle"
            value="Bajo Stock"
          />
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
import Dropdown from "primevue/dropdown";
import InputText from "primevue/inputtext";
import axios from "axios";

export default {
  components: {
    Panel,
    Button,
    DataTable,
    Column,
    Tag,
    Paginator,
    Dropdown,
    InputText,
  },
  data() {
    return {
      isLoading: false,
      arrayInventario: [],
      pagination: { total: 0, current_page: 1, per_page: 10, last_page: 0 },
      filtros: {
        almacen_id: null,
        proveedor: "",
        producto: "",
      },
      arrayAlmacenes: [],
      timerBusqueda: null,
    };
  },
  methods: {
    async getDatosAlmacen() {
      try {
        const response = await axios.get(`/almacenes/lista/`);
        this.arrayAlmacenes = response.data.almacenes;
      } catch (error) {
        console.error(error);
      }
    },

    buscarConRetraso() {
      if (this.timerBusqueda) {
        clearTimeout(this.timerBusqueda);
      }
      this.timerBusqueda = setTimeout(() => {
        this.listarInventario(1);
      }, 500);
    },

    listarInventario(page = 1) {
      this.isLoading = true;
      const params = {
        page: page,
        almacen_id: this.filtros.almacen_id,
        proveedor: this.filtros.proveedor,
        producto: this.filtros.producto,
      };
      axios
        .get("/inventarios/productosbajostock", { params })
        .then((response) => {
          this.arrayInventario = response.data.inventarios.data;
          this.pagination = response.data.pagination;
        })
        .catch((error) => console.error(error))
        .finally(() => (this.isLoading = false));
    },

    resetFiltros() {
      this.filtros = { almacen_id: null, proveedor: "", producto: "" };
      this.listarInventario(1);
    },

    onPageChange(event) {
      this.listarInventario(event.page + 1);
    },

    async exportarPDF() {
      this.isLoading = true;
      try {
        const params = {
          almacen_id: this.filtros.almacen_id,
          proveedor: this.filtros.proveedor,
          producto: this.filtros.producto,
        };
        const response = await axios.get('/inventarios/exportproductosbajostock', {
          params: params,
          responseType: 'blob'
        });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;

        const fecha = new Date();
        const anio = fecha.getFullYear();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const dia = String(fecha.getDate()).padStart(2, '0');
        const nombreArchivo = `Productos_bajo_stock_${anio}-${mes}-${dia}.pdf`;

        link.setAttribute('download', nombreArchivo);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error("Error al exportar PDF:", error);
        this.$toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo exportar el PDF', life: 3000 });
      } finally {
        this.isLoading = false;
      }
    },

    async exportarExcel() {
      this.isLoading = true;
      try {
        const params = {
          almacen_id: this.filtros.almacen_id || '',
          proveedor: this.filtros.proveedor || '',
          producto: this.filtros.producto || '',
        };
        const response = await axios.get('/inventarios/listarReporteBajoStockExcel', {
          params: params,
          responseType: 'blob'
        });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;

        const fecha = new Date();
        const anio = fecha.getFullYear();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const dia = String(fecha.getDate()).padStart(2, '0');
        const nombreArchivo = `Productos_bajo_stock_${anio}-${mes}-${dia}.xlsx`;

        link.setAttribute('download', nombreArchivo);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error("Error al exportar Excel:", error);
        this.$toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo exportar el Excel', life: 3000 });
      } finally {
        this.isLoading = false;
      }
    },

    getRowClass(data) {
      return {
        "bg-red-100": Number(data.saldo_stock) === 0,
        "bg-yellow-100": Number(data.saldo_stock) > 0,
      };
    },
  },
  mounted() {
    this.getDatosAlmacen();
    this.listarInventario(1);
  },
};
</script>

<style scoped>
/* Estilos similares a otros componentes */
.botones-export {
  display: flex;
  gap: 10px;
}
.filters-container {
  margin-bottom: 1rem;
  padding: 1rem;
  background-color: #f8f9fa;
  border-radius: 0.375rem;
  border: 1px solid #dee2e6;
}
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
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>