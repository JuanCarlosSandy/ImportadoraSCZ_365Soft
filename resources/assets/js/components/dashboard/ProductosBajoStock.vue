<template>
  <main class="main">
    <Panel class="custom-panel">
      <div v-if="isLoading" class="loading-overlay">
        <div class="loading-container">
          <div class="spinner"></div>
          <div class="loading-text">LOADING...</div>
        </div>
      </div>

      <template #header>
        <div class="panel-header-content">
          <div class="header-left">
            <i class="pi pi-bars panel-icon"></i>
            <h4 class="panel-title">REPORTE DE PRODUCTOS CON BAJO STOCK</h4>
          </div>
          <div class="header-right">
            <Button
              icon="pi pi-file-pdf"
              label="PDF"
              class="p-button-danger p-button-sm bt-pdf p-mr-2"
              @click="exportarPdf"
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

      <div class="filters-container">
        <div class="filters-row">
          <div class="filter-item">
            <label for="almacen" class="filter-label">Almacen</label>
            <Dropdown
              id="almacen"
              v-model="filtros.idAlmacen"
              :options="arrayAlmacenes"
              optionLabel="nombre_almacen"
              optionValue="id"
              panelClass="almacen-dropdown-panel"
              emptyMessage="No hay almacenes disponibles"
              placeholder="Todos los almacenes"
              class="p-inputtext-sm"
              showClear
              @change="listarInventario(1)"
            />
            <small v-if="!arrayAlmacenes.length" class="warning-text">
              No se cargaron almacenes para este usuario.
            </small>
          </div>

          <div class="filter-item">
            <label for="proveedor" class="filter-label">Proveedor</label>
            <AutoComplete
              id="proveedor"
              v-model="filtros.proveedor"
              :suggestions="sugerenciasProveedor"
              :dropdown="true"
              placeholder="Escribe para buscar..."
              class="p-inputtext-sm"
              @complete="buscarProveedores"
              @item-select="listarInventario(1)"
              @change="buscarConRetraso"
            />
          </div>

          <div class="filter-item">
            <label for="productos" class="filter-label">Productos</label>
            <AutoComplete
              id="productos"
              v-model="filtros.productos"
              :suggestions="sugerenciasProductos"
              :dropdown="true"
              placeholder="Escribe para buscar..."
              class="p-inputtext-sm"
              @complete="buscarProductos"
              @item-select="listarInventario(1)"
              @change="buscarConRetraso"
            />
          </div>

          <div class="filter-actions">
            <Button
              icon="pi pi-filter-slash"
              class="p-button-help p-button-sm p-button-outlined"
              @click="resetFiltros"
              title="Limpiar todos los filtros"
              label="Limpiar"
            />
          </div>
        </div>
        <div class="filters-row filters-row-secondary">
          <div class="filter-item">
            <label for="categoria" class="filter-label">Categoria</label>
            <Dropdown
              id="categoria"
              v-model="filtros.idCategoria"
              :options="arrayCategorias"
              optionLabel="nombre"
              optionValue="id"
              placeholder="Todas"
              class="p-inputtext-sm"
              showClear
              @change="listarInventario(1)"
            />
          </div>
        </div>
      </div>

      <DataTable
        :value="arrayInventario"
        responsiveLayout="scroll"
        stripedRows
        rowGroupMode="subheader"
        groupRowsBy="nombre_almacen"
        sortMode="single"
        sortField="nombre_almacen"
        :sortOrder="1"
        class="custom-table"
      >
        <template #groupheader="slotProps">
          <div class="group-header">
            <i class="pi pi-building"></i>
            <span>{{ slotProps.data.nombre_almacen }}</span>
          </div>
        </template>

        <Column field="nombre_proveedor" header="Proveedor">
          <template #body="slotProps">
            <span class="proveedor-text">{{ getProveedor(slotProps.data) }}</span>
          </template>
        </Column>

        <Column field="nombre_producto" header="Productos">
          <template #body="slotProps">
            <span>{{ getProducto(slotProps.data) }}</span>
          </template>
        </Column>

        <Column field="nombre_categoria" header="Categoria">
          <template #body="slotProps">
            <span>{{ getCategoria(slotProps.data) }}</span>
          </template>
        </Column>

        <Column field="stock_actual" header="Stock Actual">
          <template #body="slotProps">
            <span class="stock-text">{{ formatNumber(getStockActual(slotProps.data)) }}</span>
          </template>
        </Column>

        <Column field="stock_minimo" header="Stock Minimo">
          <template #body="slotProps">
            <span>{{ formatNumber(getStockMinimo(slotProps.data)) }}</span>
          </template>
        </Column>

        <Column header="Estado">
          <template #body="slotProps">
            <Tag
              v-if="Number(getStockActual(slotProps.data)) <= 0"
              class="tag-sin-stock"
              icon="pi pi-times-circle"
              value="Sin Stock"
            />
            <Tag
              v-else
              class="tag-bajo-stock"
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
        class="paginator-custom"
      />
    </Panel>
  </main>
</template>

<script>
import Panel from "primevue/panel";
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import Paginator from "primevue/paginator";
import Dropdown from "primevue/dropdown";
import AutoComplete from "primevue/autocomplete";
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
    AutoComplete,
  },
  data() {
    return {
      isLoading: false,
      arrayInventario: [],
      arrayAlmacenes: [],
      arrayCategorias: [],
      timerBusqueda: null,
      sugerenciasProveedor: [],
      sugerenciasProductos: [],
      filtros: {
        idAlmacen: null,
        proveedor: "",
        productos: "",
        idCategoria: null,
      },
      pagination: {
        total: 0,
        current_page: 1,
        per_page: 10,
        last_page: 0,
      },
    };
  },
  methods: {
    formatNumber(value) {
      var number = parseFloat(value || 0);
      return Number.isNaN(number) ? "0" : String(Math.round(number));
    },
    getProveedor(row) {
      if (!row) return "SIN PROVEEDOR";
      return row.nombre_proveedor || row.proveedor || "SIN PROVEEDOR";
    },
    getProducto(row) {
      if (!row) return "";
      return row.nombre_producto || row.producto || "";
    },
    getCategoria(row) {
      if (!row) return "SIN CATEGORIA";
      return row.nombre_categoria || row.categoria || "SIN CATEGORIA";
    },
    getStockActual(row) {
      if (!row) return 0;
      if (row.stock_actual !== undefined && row.stock_actual !== null) return row.stock_actual;
      if (row.saldo_stock !== undefined && row.saldo_stock !== null) return row.saldo_stock;
      return 0;
    },
    getStockMinimo(row) {
      if (!row) return 0;
      return row.stock_minimo !== undefined && row.stock_minimo !== null ? row.stock_minimo : 0;
    },
    getParams(page) {
      return {
        page: page || 1,
        idAlmacen: this.filtros.idAlmacen || "",
        proveedor: this.filtros.proveedor || "",
        productos: this.filtros.productos || "",
        idCategoria: this.filtros.idCategoria || "",
      };
    },
    normalizeAlmacenes(response) {
      var list = [];
      if (!response || !response.data) return list;
      if (Array.isArray(response.data.almacenes)) list = response.data.almacenes;
      else if (response.data.almacenes && Array.isArray(response.data.almacenes.data)) list = response.data.almacenes.data;

      return list.map(function (item) {
        var nombre = item && (item.nombre_almacen || item.nombre || item.almacen || "");
        return Object.assign({}, item, {
          nombre_almacen: nombre || ("Almacen " + (item && item.id ? item.id : "")),
        });
      });
    },
    async getDatosAlmacen() {
      try {
        var response = await axios.get("/almacen/selectAlmacen");
        var almacenes = this.normalizeAlmacenes(response);
        if (!almacenes.length) {
          response = await axios.get("/almacenes/lista");
          almacenes = this.normalizeAlmacenes(response);
        }
        if (!almacenes.length) {
          response = await axios.get("/almacen/selectAlmacenDest");
          almacenes = this.normalizeAlmacenes(response);
        }
        this.arrayAlmacenes = almacenes;
      } catch (error) {
        console.error("Error cargando almacenes:", error);
        this.arrayAlmacenes = [];
      }
    },
    async buscarProveedores(event) {
      var query = event && event.query ? event.query : "";
      try {
        var response = await axios.get("/proveedor/selectNombreProveedor", {
          params: { filtro: query },
        });
        var list = response.data && response.data.proveedores ? response.data.proveedores : [];
        this.sugerenciasProveedor = list.map(function (item) {
          return item.nombre;
        });
      } catch (error) {
        console.error("Error cargando sugerencias de proveedor:", error);
        this.sugerenciasProveedor = [];
      }
    },
    async buscarProductos(event) {
      var query = event && event.query ? event.query : "";
      try {
        var response = await axios.get("/articulo", {
          params: {
            page: 1,
            buscar: query,
            criterio: "todos",
          },
        });
        var data = response.data && response.data.articulos ? response.data.articulos.data : [];
        var unicos = {};
        var sugerencias = [];
        data.forEach(function (item) {
          var nombre = item && item.nombre ? item.nombre : "";
          if (nombre && !unicos[nombre]) {
            unicos[nombre] = true;
            sugerencias.push(nombre);
          }
        });
        this.sugerenciasProductos = sugerencias;
      } catch (error) {
        console.error("Error cargando sugerencias de productos:", error);
        this.sugerenciasProductos = [];
      }
    },
    async getCategorias() {
      try {
        var response = await axios.get("/categorianewview");
        var categorias = [];
        if (response && response.data) {
          if (Array.isArray(response.data.categorias)) categorias = response.data.categorias;
          else if (response.data.categorias && Array.isArray(response.data.categorias.data)) categorias = response.data.categorias.data;
        }
        this.arrayCategorias = categorias;
      } catch (error) {
        console.error("Error cargando categorias:", error);
        this.arrayCategorias = [];
      }
    },
    buscarConRetraso() {
      var me = this;
      if (this.timerBusqueda) clearTimeout(this.timerBusqueda);
      this.timerBusqueda = setTimeout(function () {
        me.listarInventario(1);
      }, 400);
    },
    async listarInventario(page) {
      this.isLoading = true;
      try {
        var response = await axios.get("/inventarios/productosbajostock", {
          params: this.getParams(page || 1),
        });
        this.arrayInventario = response.data && response.data.inventarios ? response.data.inventarios.data : [];
        this.pagination = response.data && response.data.pagination ? response.data.pagination : this.pagination;
      } catch (error) {
        console.error("Error al listar productos bajo stock:", error);
      } finally {
        this.isLoading = false;
      }
    },
    onPageChange(event) {
      this.listarInventario(event.page + 1);
    },
    resetFiltros() {
      this.filtros = {
        idAlmacen: null,
        proveedor: "",
        productos: "",
        idCategoria: null,
      };
      this.listarInventario(1);
    },
    async exportarExcel() {
      try {
        var response = await axios.get("/inventarios/listarReporteBajoStockExcel", {
          params: this.getParams(1),
          responseType: "blob",
          timeout: 600000,
        });
        var url = window.URL.createObjectURL(new Blob([response.data]));
        var link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", "ReporteProductosBajoStock.xlsx");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error("Error al exportar Excel:", error);
      }
    },
    async exportarPdf() {
      try {
        var response = await axios.get("/inventarios/listarReporteBajoStockPdf", {
          params: this.getParams(1),
          responseType: "blob",
          timeout: 600000,
        });
        var url = window.URL.createObjectURL(new Blob([response.data]));
        var link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", "ReporteProductosBajoStock.pdf");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error("Error al exportar PDF:", error);
      }
    },
  },
  mounted() {
    this.getDatosAlmacen();
    this.getCategorias();
    this.listarInventario(1);
  },
};
</script>

<style scoped>
.custom-panel >>> .p-panel-header {
  background: #fff;
  border-bottom: 1px solid #dee2e6;
  padding: 1rem 1.25rem;
}

.panel-header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.panel-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.header-right {
  display: flex;
  gap: 0.5rem;
}

.filters-container {
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 6px;
  border: 1px solid #dee2e6;
}

.filters-row {
  display: flex;
  gap: 1rem;
  align-items: flex-end;
  flex-wrap: wrap;
}

.filter-item {
  flex: 1;
  min-width: 200px;
}

.filters-row-secondary {
  margin-top: 0.5rem;
}

.filter-label {
  display: block;
  font-weight: 700;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}

.warning-text {
  display: block;
  margin-top: 0.35rem;
  color: #b45309;
  font-size: 0.75rem;
}

.filter-actions {
  display: flex;
  align-items: flex-end;
}

.custom-table >>> .p-datatable-wrapper {
  overflow-x: auto;
}

.group-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  background-color: #e9ecef;
  font-weight: bold;
}

.proveedor-text {
  font-weight: 600;
  color: #007bff;
}

.stock-text {
  font-weight: bold;
  font-size: 1.05em;
}

.tag-bajo-stock {
  background-color: #ff9800 !important;
  color: #fff !important;
}

.tag-sin-stock {
  background-color: #d32f2f !important;
  color: #fff !important;
}

.paginator-custom {
  margin-top: 0.75rem;
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

@media (max-width: 768px) {
  .panel-header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }
  .header-right {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>

<style>
.almacen-dropdown-panel .p-dropdown-items .p-dropdown-item {
  color: #2d3748 !important;
  background: #ffffff !important;
}

.almacen-dropdown-panel .p-dropdown-items .p-dropdown-item.p-highlight,
.almacen-dropdown-panel .p-dropdown-items .p-dropdown-item:hover {
  color: #1a202c !important;
  background: #edf2f7 !important;
}
</style>
