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
          <i class="pi pi-align-justify"></i>
          <h4 class="panel-title">CATEGORIAS SERVICIOS</h4>
        </div>
      </template>
      <div class="toolbar-container">
        <div class="toolbar">
          <Button
            label="Nuevo"
            icon="pi pi-plus"
            @click="abrirModal('categoria', 'registrar')"
            class="p-button-secondary p-button-sm"
          />
        </div>
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText
              type="text"
              placeholder="Texto a buscar"
              v-model="buscar"
              class="p-inputtext-sm"
              @keyup="buscarlinea"
            />
          </span>
        </div>
      </div>
      <DataTable
        :value="arrayCategoria"
        class="p-datatable-sm p-datatable-gridlines"
        responsiveLayout="scroll"
        paginator
        :rows="9"
      >
        <Column
          header="Opciones"
          style="width: 100px; text-align: left;"
          class="col-opciones"
        >
          <template #body="slotProps">
            <div class="botones-opciones">
              <Button
                icon="pi pi-pencil"
                class="btn-icon p-button-warning"
                @click="abrirModal('categoria', 'actualizar', slotProps.data)"
              />
              <Button
                v-if="slotProps.data.condicion"
                icon="pi pi-ban"
                class="btn-icon p-button-danger"
                @click="desactivarCategoria(slotProps.data.id)"
              />
              <Button
                v-else
                icon="pi pi-check-circle"
                class="btn-icon p-button-success"
                @click="activarCategoria(slotProps.data.id)"
              />
            </div>
          </template>
        </Column>
        <Column field="nombre" header="Nombre"></Column>
        <Column field="estado" header="Estado">
          <template #body="slotProps">
            <span
              :class="[
                'status-badge',
                slotProps.data.condicion === 1 ? 'active' : 'inactive',
              ]"
            >
              {{ slotProps.data.condicion === 1 ? "Activo" : "Inactivo" }}
            </span>
          </template>
        </Column>
      </DataTable>
      <Dialog
        :visible.sync="modal"
        modal
        :header="tituloModal"
        :closable="true"
        @hide="cerrarModal"
      >
        <template #footer>
          <Button
            label="Cancelar"
            icon="pi pi-times"
            class="p-button-text"
            @click="cerrarModal()"
          />
          <Button
            v-if="tipoAccion === 1"
            label="Guardar"
            icon="pi pi-check"
            class="p-button-text"
            @click="registrarCategoria()"
          />
          <Button
            v-if="tipoAccion === 2"
            label="Actualizar"
            icon="pi pi-check"
            class="p-button-text"
            @click="actualizarCategoria()"
          />
        </template>
        <div class="p-fluid ">
          <div class="p-field input-container">
            <label for="nombre">Nombre Línea</label>
            <InputText
              id="nombre"
              v-model="nombre"
              required
              autofocus
              :class="{ 'p-invalid': nombreError }"
              @input="validarNombreEnTiempoReal"
            />
            <small class="p-error error-message" v-if="nombreError"
              ><strong>{{ nombreError }}</strong></small
            >
          </div>
          <div class="p-field input-container">
            <label for="descripcion">Descripción</label>
            <InputText
              id="descripcion"
              v-model="descripcion"
              required
              :class="{ 'p-invalid': descripcionError }"
              @input="validarDescripcionEnTiempoReal"
            />
            <small class="p-error error-message" v-if="descripcionError"
              ><strong>{{ descripcionError }}</strong></small
            >
          </div>
        </div>
      </Dialog>
    </Panel>
  </main>
</template>

<script>
import Dialog from "primevue/dialog";
import Panel from "primevue/panel";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import FileUpload from "primevue/fileupload";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import axios from "axios";
export default {
  components: {
    DataTable,
    Column,
    Button,
    FileUpload,
    InputText,
    Panel,
    Dialog,
    InputNumber,
  },
  data() {
    return {
      isLoading: false,
      modal: false,
      tituloModal: "",
      tipoAccion: 1,
      nombre: "",
      descripcion: "",
      codigoProductoSin: "",
      codigoProductoSinError: "",
      nombreError: "",
      descripcionError: "",
      codigoError: "",
      arrayCategoria: [],
      criterio: "nombre",
      buscar: "",
      movil: "9",
      arrayProductoServicio: [],
      arrayActividadEconomica: [],
      codigoActividadEconomica: "",
    };
  },
  computed: {},
  methods: {
    buscarlinea() {
      this.listarCategoria(1, this.buscar);
    },
    validarNombreEnTiempoReal() {
      if (!this.nombre.trim()) {
        this.nombreError = "El nombre de la linea no puede estar vacío.";
      } else {
        this.nombreError = "";
      }
    },
    validarDescripcionEnTiempoReal() {
      if (!this.descripcion.trim()) {
        this.descripcionError =
          "La descripción de la linea no puede estar vacía.";
      } else {
        this.descripcionError = "";
      }
    },
    validarCodigoEnTiempoReal() {
      if (
        this.codigoProductoSin === null ||
        this.codigoProductoSin === undefined ||
        String(this.codigoProductoSin).trim() === ""
      ) {
        this.codigoProductoSinError = "El código no puede estar vacío.";
      } else {
        this.codigoProductoSinError = "";
      }
    },
  registrarCategoria() {
    if (this.validarCategoria()) {
      return;
    }
    let me = this;
    me.isLoading = true;
    
    axios.post("/categoria/servicio/registrar", {
      nombre: this.nombre,
      descripcion: this.descripcion,
      codigoProductoSin: this.codigoProductoSin,
    })
    .then(function(response) {
      me.cerrarModal();
      me.listarCategoria(1, "", "nombre");
    })
    .catch(function(error) {
      console.log(error);
    })
    .finally(() => {
      me.isLoading = false;
    });
  },
    validarCategoria() {
      let hasError = false;
      this.codigoProductoSinError = "";
      this.descripcionError = "";
      this.nombreError = "";
      if (!this.descripcion.trim()) {
        this.descripcionError =
          "La descripción de la linea no puede estar vacía.";
      }
      if (
        this.codigoProductoSin === null ||
        this.codigoProductoSin === undefined ||
        String(this.codigoProductoSin).trim() === ""
      ) {
        this.codigoProductoSinError = "El código no puede estar vacío.";
      }
      if (!this.nombre.trim()) {
        this.nombreError = "El nombre de la linea no puede estar vacío.";
      }
    },
  actualizarCategoria() {
    if (this.validarCategoria()) {
      return;
    }
    let me = this;
    me.isLoading = true;
    
    axios.put("/categoria/actualizar", {
      nombre: this.nombre,
      descripcion: this.descripcion,
      codigoProductoSin: this.codigoProductoSin,
      id: this.categoria_id,
    })
    .then(function(response) {
      me.cerrarModal();
      me.listarCategoria(1, "", "nombre");
    })
    .catch(function(error) {
      console.log(error);
    })
    .finally(() => {
      me.isLoading = false;
    });
  },

  listarCategoria(page, buscar, criterio) {
    let me = this;
    me.isLoading = true;
    
    var url = "/categoria/servicio/lista?page=" + page + "&buscar=" + buscar + "&criterio=" + criterio;
    axios.get(url)
      .then(function(response) {
        var respuesta = response.data;
        me.arrayCategoria = respuesta.categorias;
        me.pagination = respuesta.pagination;
      })
      .catch(function(error) {
        console.log(error);
      })
      .finally(() => {
        me.isLoading = false;
      });
  },
    consultaProductosServicios() {
      let me = this;
      me.isLoading = true;
      
      axios.get("/categoria/consultaProductosServicios")
        .then(function(response) {
          var respuesta = response.data;
          me.arrayProductoServicio = respuesta.RespuestaListaProductos.listaCodigos;
        })
        .catch(function(error) {
          console.log(error);
        })
        .finally(() => {
          me.isLoading = false;
        });
    },

    consultaActividadEconomica() {
      let me = this;
      me.isLoading = true;
      
      axios.get("/categoria/consultaActividadEconomica")
        .then(function(response) {
          var respuesta = response.data;
          me.arrayActividadEconomica = respuesta.RespuestaListaActividades.listaActividades;
        })
        .catch(function(error) {
          console.log(error);
        })
        .finally(() => {
          me.isLoading = false;
        });
    },
  desactivarCategoria(id) {
    let me = this;
    swal({
      title: "Esta seguro de desactivar esta categoría?",
      type: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Aceptar!",
      cancelButtonText: "Cancelar",
      confirmButtonClass: "btn btn-success",
      cancelButtonClass: "btn btn-danger",
      buttonsStyling: false,
      reverseButtons: true,
    }).then((result) => {
      if (result.value) {
        me.isLoading = true;
        
        axios.put("/categoria/desactivar", {
          id: id,
        })
        .then(function(response) {
          me.listarCategoria(1, "", "nombre");
          swal(
            "Desactivado!",
            "El registro ha sido desactivado con éxito.",
            "success"
          );
        })
        .catch(function(error) {
          console.log(error);
        })
        .finally(() => {
          me.isLoading = false;
        });
      }
    });
  },
    activarCategoria(id) {
      let me = this;
      swal({
        title: "Esta seguro de activar esta categoría?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Aceptar!",
        cancelButtonText: "Cancelar",
        confirmButtonClass: "btn btn-success",
        cancelButtonClass: "btn btn-danger",
        buttonsStyling: false,
        reverseButtons: true,
      }).then((result) => {
        if (result.value) {
          me.isLoading = true;
          
          axios.put("/categoria/activar", {
            id: id,
          })
          .then(function(response) {
            me.listarCategoria(1, "", "nombre");
            swal(
              "Activado!",
              "El registro ha sido activado con éxito.",
              "success"
            );
          })
          .catch(function(error) {
            console.log(error);
          })
          .finally(() => {
            me.isLoading = false;
          });
        }
      });
    },
    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "categoria": {
          switch (accion) {
            case "registrar": {
              this.modal = true;
              this.tituloModal = "Registrar Categoría";
              this.nombre = "";
              this.descripcion = "";
              this.codigoProductoSin = 0;
              this.tipoAccion = 1;
              break;
            }
            case "actualizar": {
              //console.log(data);
              this.modal = true;
              this.tituloModal = "Actualizar categoría";
              this.tipoAccion = 2;
              this.categoria_id = data["id"];
              this.nombre = data["nombre"];
              this.descripcion = data["descripcion"];
              this.codigoProductoSin = data["codigoProductoSin"];
              break;
            }
          }
        }
      }
    },
    cerrarModal() {
      this.modal = false;
      this.tituloModal = "";
      this.nombre = "";
      this.descripcion = "";
      this.codigoProductoSin = "";
      this.nombreError = "";
      this.descripcionError = "";
      this.codigoProductoSinError = "";
    },
  },
mounted() {
  this.isLoading = true;
  this.listarCategoria(1, this.buscar, this.criterio);
  //this.consultaProductosServicios();
  //this.consultaActividadEconomica();
},
};
</script>

<style scoped>
.botones-opciones {
  display: flex;
  justify-content: flex-start;
  gap: 4px;
}

.btn-icon {
  width: 28px;
  height: 28px;
  padding: 0 !important;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  border-radius: 6px !important;
}
.input-container {
  position: relative;
  padding-bottom: 20px; /* Espacio para el mensaje de error */
}

.input-container .p-inputtext {
  width: 100%;
  margin-bottom: 0; /* Eliminar margen inferior si existe */
}

.error-message {
  position: absolute;
  bottom: 0;
  left: 0;
  font-size: 0.75rem; /* Tamaño de fuente más pequeño */
  margin-top: 2px; /* Pequeño espacio entre el input y el mensaje */
}
>>> .p-paginator {
  padding: 0px;
}
>>> .p-panel .p-panel-header {
  padding-top: 10px;
  padding-bottom: 10px;
}
.panel-header {
  display: flex;
  align-items: center;
}
.panel-title {
  margin: 0;
  padding-left: 5px;
}
.toolbar-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}
.search-bar {
  flex-grow: 0.5;
  display: flex;
  align-items: center;
  justify-content: flex-start;
}
.search-bar .p-input-icon-left {
  width: 100%;
}
.search-bar .p-inputtext-sm {
  width: 100%;
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
@media (max-width: 768px) {
  .toolbar-container {
    flex-direction: column;
    align-items: flex-start;
  }
  .toolbar {
    margin-bottom: 10px;
    justify-content: space-between;
  }
  .searchbar {
    margin-bottom: 10px;
    order: 1; /* Esto asegura que la barra de búsqueda esté abajo en vista móvil */
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
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
