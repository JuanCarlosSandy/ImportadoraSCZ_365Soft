<template>
  <div
    class="modal "
    tabindex="-1"
    :class="{ mostrar: true }"
    role="dialog"
    aria-labelledby="myModalLabel"
    style="display: none;"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-primary modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Agregar productos</h4>
          <button
            type="button"
            class="close"
            @click="cerrarModal()"
            aria-label="Close"
          >
            <span aria-hidden="true">×</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group row">
            <div class="col-md-6">
              <div class="input-group">
                <input
                  type="text"
                  v-model="buscarA"
                  @input="listarArticuloDebounced(buscarA, criterioA)"
                  class="form-control"
                  placeholder="Texto a buscar"
                />
              </div>
            </div>
          </div>
          <DataTable
            :value="arrayArticulo"
            responsiveLayout="scroll"
            stripedRows
            size="small"
            class="p-datatable-sm"
            paginator
            :rows="5"
            :rowsPerPageOptions="[5, 10, 20, 50]"
          >
            <Column header="Opciones">
              <template #body="slotProps">
                <Button
                  icon="pi pi-check"
                  class="p-button-success p-button-sm"
                  @click="agregarDetalleModal(slotProps.data)"
                />
              </template>
            </Column>

            <Column field="codigo" header="Código" />
            <Column field="nombre" header="Nombre comercial" />
            <Column field="nombre_proveedor" header="Proveedor" />

            <Column header="Costo unit">
              <template #body="slotProps">
                {{
                  (
                    slotProps.data.precio_costo_unid *
                    parseFloat(monedaPrincipal[0])
                  ).toFixed(2)
                }}
                {{ monedaPrincipal[1] }}
              </template>
            </Column>

            <Column header="Costo paquete">
              <template #body="slotProps">
                {{
                  (
                    slotProps.data.precio_costo_unid *
                    slotProps.data.unidad_envase *
                    parseFloat(monedaPrincipal[0])
                  ).toFixed(2)
                }}
                {{ monedaPrincipal[1] }}
              </template>
            </Column>
          </DataTable>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            @click="cerrarModal()"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
<script>

import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import debounce from "lodash/debounce";
 
export default {
  props: {
    idproveedor: Number,
    monedaPrincipal: {
      type: Array,
      required: true,
    },
  },
  created() {},
  components: {
    DataTable,
    Column,
    Button,
  },
  data() {
    return {
      criterio: "num_comprobante",
      buscar: "",
      criterioA: "nombre",
      buscarA: "",
      arrayArticulo: [],
      codigo: "",
      articulo: "",
      precio: 0,
    };
  },

  methods: {
    cerrarModal() {
      console.log("Cerrando");
      this.$emit("cerrar");
    },
    listarArticulo(buscar, criterio) {
      let me = this;
      var url =
        "/articulo/listarArticulo?buscar=" + buscar + "&criterio=" + criterio;
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.arrayArticulo = respuesta.articulos;
          console.log(me.arrayArticulo);
        })
        .catch(function(error) {
          console.log(error);
        });
    },
    listarArticuloDebounced: debounce(function(buscar, criterio) {
      this.listarArticulo(buscar, criterio);
    }, 200), // espera 500ms
    agregarDetalleModal(data) {
      console.log("agregando", data);
      this.$emit("agregarArticulo", data);
    },
  },
  mounted() {
    this.listarArticulo("", "");
  },
};
</script>
