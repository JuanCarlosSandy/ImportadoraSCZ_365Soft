<template>
  <Panel>
     <template #header>
        <div class="panel-header">
          <i class="pi pi-bars panel-icon"></i>
          <h4 class="panel-title">CLIENTES FRECUENTES</h4>
        </div>
      </template>
    <DataTable
      v-if="topClientesOrdenados.length > 0"
      :value="topClientesOrdenados"
      :paginator="true"
      :rows="5"
      responsiveLayout="scroll"
      dataKey="id"
      :sortField="criterioOrdenacion"
      :sortOrder="ordenAscendente ? 1 : -1"
    >
      <Column
        field="nombreCliente"
        header="Nombre cliente"
        sortable
        @click="ordenar('nombreCliente')"
        :headerStyle="{ cursor: 'pointer' }"
        :body="nombreTemplate"
      />
      <Column
        field="cantidadCompras"
        header="Cantidad de compras"
        sortable
        @click="ordenar('cantidadCompras')"
        :headerStyle="{ cursor: 'pointer' }"
      />
      <Column
        field="totalGastado"
        header="Total gastado"
        sortable
        @click="ordenar('totalGastado')"
        :headerStyle="{ cursor: 'pointer' }"
        :body="monedaTemplate"
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
    fechaFin: { type: String, required: true },
    moneda: { type: Array, required: true } // [factor, símbolo]
  },
  data() {
    return {
      topClientes: [],
      criterioOrdenacion: '',
      ordenAscendente: true
    };
  },
  computed: {
    topClientesOrdenados() {
      const copia = [...this.topClientes];
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
    obtenerTopClientes() {
      axios
        .get('/top-clientes', {
          params: {
            fecha_inicio: this.formatoFecha(this.fechaInicio),
            fecha_fin: this.formatoFecha(this.fechaFin)
          }
        })
        .then(res => {
          this.topClientes = res.data.topClientes;
        })
        .catch(err => {
          console.error('Error al obtener el top de clientes:', err);
        });
    },
    nombreTemplate(row) {
      return row.nombreCliente;
    },
    monedaTemplate(row) {
      const total = row.totalGastado * this.moneda[0];
      return `${total.toFixed(2)} ${this.moneda[1]}`;
    }
  },
  watch: {
    fechaInicio() {
      this.obtenerTopClientes();
    },
    fechaFin() {
      this.obtenerTopClientes();
    }
  },
  mounted() {
    this.obtenerTopClientes();
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
