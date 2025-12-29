<template>
  <Panel>
    <template #header>
        <div class="panel-header">
          <i class="pi pi-bars panel-icon"></i>
          <h4 class="panel-title">ARTÍCULOS MÁS VENDIDO</h4>
        </div>
      </template>
    <DataTable
      v-if="topArticulosOrdenados.length > 0"
      :value="topArticulosOrdenados"
      :paginator="true"
      :rows="5"
      dataKey="id"
      responsiveLayout="scroll"
      :sortField="criterioOrdenacion"
      :sortOrder="ordenAscendente ? 1 : -1"
    >
      <Column
        field="nombreArticulo"
        header="Nombre del artículo"
        sortable
        @click="ordenar('nombreArticulo')"
        :headerStyle="{ cursor: 'pointer' }"
        :body="nombreArticuloTemplate"
      />
      <Column
        field="cantidadTotal"
        header="Cantidad vendida"
        sortable
        @click="ordenar('cantidadTotal')"
        :headerStyle="{ cursor: 'pointer' }"
      />
      <Column
        field="vecesVendido"
        header="Veces vendidas"
        sortable
        @click="ordenar('vecesVendido')"
        :headerStyle="{ cursor: 'pointer' }"
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
    Column
  },
  props: {
    fechaInicio: { type: String, required: true },
    fechaFin: { type: String, required: true }
  },
  data() {
    return {
      topArticulos: [],
      criterioOrdenacion: '',
      ordenAscendente: true
    };
  },
  computed: {
    topArticulosOrdenados() {
      const copia = [...this.topArticulos];
      if (this.criterioOrdenacion) {
        copia.sort((a, b) => {
          if (a[this.criterioOrdenacion] < b[this.criterioOrdenacion]) {
            return this.ordenAscendente ? -1 : 1;
          }
          if (a[this.criterioOrdenacion] > b[this.criterioOrdenacion]) {
            return this.ordenAscendente ? 1 : -1;
          }
          return 0;
        });
      }
      return copia;
    }
  },
  methods: {
    ordenar(campo) {
      if (this.criterioOrdenacion === campo) {
        this.ordenAscendente = !this.ordenAscendente;
      } else {
        this.criterioOrdenacion = campo;
        this.ordenAscendente = true;
      }
    },
    formatoFecha(fecha) {
      const f = new Date(fecha);
      const pad = n => (n < 10 ? '0' + n : n);
      return `${f.getFullYear()}-${pad(f.getMonth() + 1)}-${pad(f.getDate())}`;
    },
    obtenerTopArticulos() {
      axios
        .get('/top-articulos', {
          params: {
            fecha_inicio: this.formatoFecha(this.fechaInicio),
            fecha_fin: this.formatoFecha(this.fechaFin)
          }
        })
        .then(res => {
          this.topArticulos = res.data.topProductos;
        })
        .catch(err => {
          console.error('Error al obtener el top de artículos:', err);
        });
    },
    nombreArticuloTemplate(row) {
      return row.nombreArticulo;
    }
  },
  watch: {
    fechaInicio() {
      this.obtenerTopArticulos();
    },
    fechaFin() {
      this.obtenerTopArticulos();
    }
  },
  mounted() {
    this.obtenerTopArticulos();
  }
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
