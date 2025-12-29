<template>
  <Panel>
    <template #header>
        <div class="panel-header">
          <i class="pi pi-bars panel-icon"></i>
          <h4 class="panel-title">PRECIOS ACTUALIZADOS</h4>
        </div>
      </template>
    <DataTable
      v-if="articulosActualizados.length > 0"
      :value="articulosActualizados"
      :paginator="true"
      :rows="pagination.per_page"
      :totalRecords="pagination.total"
      :first="(pagination.current_page - 1) * pagination.per_page"
      :lazy="true"
      @page="onPage"
      :sortField="criterioOrdenacion"
      :sortOrder="ordenAscendente ? 1 : -1"
      dataKey="id"
      responsiveLayout="scroll"
    >
      <Column
        field="nombre_producto"
        header="Item"
      />
      <Column
        field="nombre_proveedor"
        header="Proveedor"
      />
      <Column
        field="precio_venta"
        header="Precio"
        :body="precioBodyTemplate"
      />
      <Column
        field="updated_at"
        header="Fecha de Actualización"
        :body="fechaBodyTemplate"
      />
    </DataTable>
    <div v-else>
      <label>Muy pronto...</label>
    </div>
  </Panel>
</template>

<script>
import Panel from 'primevue/panel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import axios from 'axios';

export default {
  components: {
    Panel,
    DataTable,
    Column,
  },
  props: {
    fechaInicio: { type: String, required: true },
    fechaFin: { type: String, required: true },
    moneda: { type: Array, required: true },
  },
  data() {
    return {
      articulosActualizados: [],
      criterioOrdenacion: 'updated_at',
      ordenAscendente: false,
      pagination: {
        total: 0,
        current_page: 1,
        per_page: 10,
        last_page: 1,
      },
    };
  },
  methods: {
    onPage(event) {
      this.pagination.current_page = event.page + 1;
      this.obtenerArticulosActualizados();
    },
    ordenar(campo) {
      if (this.criterioOrdenacion === campo) {
        this.ordenAscendente = !this.ordenAscendente;
      } else {
        this.criterioOrdenacion = campo;
        this.ordenAscendente = true;
      }
      this.obtenerArticulosActualizados();
    },
    formatoFecha(fecha) {
      const f = new Date(fecha);
      return f.toISOString().split('T')[0];
    },
    formatoFechaHora(fecha) {
      const f = new Date(fecha);
      const pad = (n) => (n < 10 ? '0' + n : n);
      return `${f.getFullYear()}-${pad(f.getMonth() + 1)}-${pad(f.getDate())} ${pad(f.getHours())}:${pad(f.getMinutes())}:${pad(f.getSeconds())}`;
    },
    precioBodyTemplate(rowData) {
      return parseFloat(rowData.precio_venta).toFixed(2);
    },
    fechaBodyTemplate(row) {
      return this.formatoFechaHora(row.updated_at);
    },
    obtenerArticulosActualizados() {
      const params = {
        fecha_inicio: this.formatoFecha(this.fechaInicio),
        fecha_fin: this.formatoFecha(this.fechaFin),
        page: this.pagination.current_page,
      };

      axios
        .get('/nuevoprecioarti', { params })
        .then((res) => {
          this.articulosActualizados = res.data.articulos.data;
          this.pagination = res.data.pagination;
        })
        .catch((err) => {
          console.error('Error al obtener artículos actualizados:', err);
        });
    },
  },
  watch: {
    fechaInicio() {
      this.pagination.current_page = 1;
      this.obtenerArticulosActualizados();
    },
    fechaFin() {
      this.pagination.current_page = 1;
      this.obtenerArticulosActualizados();
    },
  },
  mounted() {
    this.obtenerArticulosActualizados();
  },
};
</script>
<style scoped>
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

/* Mobile Styles */
@media (max-width: 768px) {
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
}
/* Extra Small Mobile */
@media (max-width: 480px) {
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
>>> .p-dialog .p-dialog-content {
  padding: 0 1.5rem 1.5rem 1.5rem;
}
</style>