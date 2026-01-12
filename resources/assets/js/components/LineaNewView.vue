<template>
  <main class="main">
    <Toast :breakpoints="{ '920px': { width: '100%', right: '0', left: '0' } }" style="padding-top: 10px;"
      appendTo="body" :baseZIndex="99999"></Toast>
    <div class="loading-overlay" v-if="isLoading">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">LOADING...</div>
      </div>
    </div>
    <Panel>
      <template #header>
        <div class="panel-header">
          <i class="pi pi-bars panel-icon"></i>
          <h4 class="panel-title">CATEGORIAS</h4>
        </div>
      </template>
      <div class="toolbar-container">
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText type="text" placeholder="Texto a buscar" v-model="buscar" class="p-inputtext-sm"
              @keyup="buscarlinea" />
          </span>
        </div>
        <div class="toolbar">
          <Button :label="mostrarLabel ? 'Nuevo' : ''" icon="pi pi-plus" @click="abrirModal('categoria', 'registrar')"
            class="p-button-secondary p-button-sm" />
          <Button :label="mostrarLabel ? 'Exportar' : ''" icon="pi pi-cloud-download" @click="cargarExcel"
            class="p-button-success p-button-sm" />
        </div>
      </div>
      <DataTable :value="arrayCategoria" class="p-datatable-sm p-datatable-gridlines tabla-pro"
        responsiveLayout="scroll" :paginator="true" :rows="10">
        <Column header="Opciones">
          <template #body="slotProps">
            <div class="d-flex align-items-center gap-1">
              <Button icon="pi pi-pencil" class="p-button-sm p-button-warning btn-mini"
                @click="abrirModal('categoria', 'actualizar', slotProps.data)" v-tooltip.top="'Editar'"/>
            </div>
          </template>
        </Column>
        <Column field="nombre" header="Nombre"></Column>
        <Column header="Descripción">
          <template #body="slotProps">
            <div v-if="slotProps.data.descripcion === null" class="dato-no-registrado">
              <i class="pi pi-exclamation-triangle"></i>
              <span style="margin-left: 4px;">No registrado</span>
            </div>
            <span v-else>
              {{ slotProps.data.descripcion }}
            </span>
          </template>
        </Column>
      </DataTable>
      <Dialog :visible.sync="modal" modal :header="tituloModal" :closable="true" @hide="cerrarModal"
        :containerStyle="dialogContainerStyle" class="responsive-dialog">
        <template #footer>
          <Button label="Cancelar" icon="pi pi-times" class="p-button-danger p-button-sm btn-sm"
            @click="cerrarModal()" />
          <Button v-if="tipoAccion === 1" label="Guardar" icon="pi pi-check" class="p-button-success p-button-sm btn-sm"
            @click="registrarCategoria()" />
          <Button v-if="tipoAccion === 2" label="Actualizar" icon="pi pi-check"
            class="p-button-warning p-button-sm btn-sm" @click="actualizarCategoria()" />
        </template>
        <div class="p-fluid form-compact">
          <div class="p-field input-container">
            <label for="nombre" class="required-field">
              <span class="required-icon">*</span>
              Nombre de Categoría
            </label>
            <InputText id="nombre" v-model="nombre" required autofocus :class="{ 'input-error': nombreError }"
              @input="validarNombreEnTiempoReal" class="input-full" />
            <small class="p-error error-message" v-if="nombreError"><strong>{{ nombreError }}</strong></small>
          </div>
          <div class="p-field input-container">
            <label for="descripcion" class="optional-field">
              <i class="pi pi-info-circle optional-icon"></i>
              Descripción
              <span class="p-tag p-tag-secondary tag-opcional">Opcional</span>
            </label>
            <InputText id="descripcion" v-model="descripcion" class="input-full" />
          </div>
        </div>
      </Dialog>

      <Dialog :visible.sync="importar" modal :header="'Importar Industrias'" :closable="true">
        <FileUpload @select="onFileSelect" :showUploadButton="false" chooseLabel="Seleccionar" cancelLabel="Cancelar"
          :customUpload="true" :multiple="false" accept=".xls,.xlsx,.csv" :maxFileSize="1000000">
          <template #empty>
            <p>Arrastra y suelta archivos aquí o haz clic para seleccionar.</p>
          </template>
        </FileUpload>
        <template #footer>
          <Button label="Subir archivo" icon="pi pi-upload" class="p-button-help btn-sm" @click="uploadFile"
            style="margin-top: 1.5rem;" />
        </template>
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
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import axios from "axios";
import Swal from "sweetalert2";
import Tooltip from 'primevue/tooltip';
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
    ToastService,
    Toast
  },directives: {
    'tooltip': Tooltip
  },
  data() {
    return {
      mostrarLabel: true,
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
      modalImportar: 0,
      showUpload: false,
      importar: false,
      archivo: null,
      movil: "9",
      arrayProductoServicio: [],
      arrayActividadEconomica: [],
      codigoActividadEconomica: "",
    };
  },
  computed: {
    isMobile() {
      return window.innerWidth <= 768;
    },
    dialogContainerStyle() {
      if (window.innerWidth <= 480) {
        return { width: "95vw", maxWidth: "95vw", margin: "0 auto" };
      } else if (window.innerWidth <= 768) {
        return { width: "90vw", maxWidth: "90vw", margin: "0 auto" };
      } else if (window.innerWidth <= 1024) {
        return { width: "85vw", maxWidth: "900px", margin: "0 auto" };
      } else {
        return { width: "800px", maxWidth: "90vw", margin: "0 auto" };
      }
    },
  },
  methods: {
    toastSuccess(mensaje) {
      this.$toasted.show(
        `
    <div style="height: 50px;font-size:16px;">
        <br>
        ` +
        mensaje +
        `.<br>
    </div>`,
        {
          type: "success",
          position: "bottom-right",
          duration: 2000,
        }
      );
    },
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    vistamovil() {
      if (this.isMobile) {
        this.movil = "7";
      }
    },
    async buscarlinea() {
      try {
        if (this.searchTimeout) {
          clearTimeout(this.searchTimeout);
        }

        this.searchTimeout = setTimeout(async () => {
          this.isLoading = true; // Activar loading
          await this.listarCategoria(1, this.buscar);
          setTimeout(() => {
            this.isLoading = false; // Desactivar loading
          }, 500);
        }, 300);
      } catch (error) {
        console.error("Error en la búsqueda:", error);
        this.isLoading = false;
      }
    },
    onFileSelect(event) {
      this.archivo = event.files[0];
    },
    async uploadFile() {
      if (!this.archivo) {
        console.error("No file selected");
        return;
      }
      try {
        this.isLoading = true; // Activar loading

        const tempForm = document.createElement("form");
        tempForm.id = "mainFormUsers";
        const fileInput = document.createElement("input");
        fileInput.type = "file";
        fileInput.name = "select_users_file";
        tempForm.appendChild(fileInput);

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(this.archivo);
        fileInput.files = dataTransfer.files;
        const formData = new FormData(tempForm);

        await axios.post("/linea/import_excel", formData);
        this.toastSuccess("Lista de Linea importada correctamente.");
        this.cerrarModalImportar();
        await this.listarCategoria(1, "", "nombre");

      } catch (error) {
        console.error("Error al importar:", error);
        Swal.fire("Error", "No se pudo importar el archivo", "error");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    showUploadDialog() {
      this.importar = true;
    },
    cargarExcel() {
      window.open("/linea/exportexcel", "_blank");
    },
    validarNombreEnTiempoReal() {
      if (!this.nombre.trim()) {
        this.nombreError = "El nombre de la linea no puede estar vacío.";
      } else {
        this.nombreError = "";
      }
    },
    validarDescripcionEnTiempoReal() {
      // La descripción es opcional, solo validar si hay contenido
      if (this.descripcion && this.descripcion.length > 255) {
        this.descripcionError = "La descripción no puede exceder 255 caracteres.";
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
    async registrarCategoria() {
      if (this.validarCategoria()) {
        return;
      }

      try {
        this.isLoading = true;
        let me = this;

        await axios.post("/categoria/registrar", {
          nombre: this.nombre,
          descripcion: this.descripcion,
          codigoProductoSin: this.codigoProductoSin,
                          tipo_categoria: "M"
        });

        me.cerrarModal();
        await me.listarCategoria(1, "", "nombre");

        // 🟢 TOAST DE ÉXITO
        this.$toast.add({
          severity: "success",
          summary: "Registro exitoso",
          detail: "La categoría fue registrada correctamente.",
          life: 2500,
        });

      } catch (error) {
        console.error(error);

        // 🔴 TOAST DE ERROR
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo registrar la categoría.",
          life: 3500,
        });

      } finally {
        this.isLoading = false;
      }
    },

    validarCategoria() {
      let hasError = false;
      this.codigoProductoSinError = "";
      this.descripcionError = "";
      this.nombreError = "";

      // Validar descripción (opcional)
      if (this.descripcion && this.descripcion.length > 255) {
        this.descripcionError = "La descripción no puede exceder 255 caracteres.";
        hasError = true;
      }

      // Validar código SIN
      if (
        this.codigoProductoSin === null ||
        this.codigoProductoSin === undefined ||
        String(this.codigoProductoSin).trim() === ""
      ) {
        this.codigoProductoSinError = "El código no puede estar vacío.";
        hasError = true;
      }

      // Validar nombre
      if (!this.nombre || !this.nombre.trim()) {
        this.nombreError = "El nombre de la línea no puede estar vacío.";
        hasError = true;
      }

      // 🔥 Si hay cualquier error → mostrar toast GENERAL una sola vez
      if (hasError) {
        this.$toast.add({
          severity: "warn",
          summary: "Formulario incompleto",
          detail: "Por favor verifique los campos marcados en rojo.",
          life: 3000,
        });
      }

      return hasError;
    },

    async actualizarCategoria() {
      if (this.validarCategoria()) {
        return;
      }

      try {
        this.isLoading = true;
        let me = this;

        await axios.put("/categoria/actualizar", {
          nombre: this.nombre,
          descripcion: this.descripcion,
          codigoProductoSin: this.codigoProductoSin,
          id: this.categoria_id,
        });

        me.cerrarModal();
        await me.listarCategoria(1, "", "nombre");

        // 🟢 TOAST DE ÉXITO
        this.$toast.add({
          severity: "success",
          summary: "Actualización exitosa",
          detail: "La categoría fue actualizada correctamente.",
          life: 2500,
        });

      } catch (error) {
        console.error(error);

        // 🔴 TOAST DE ERROR
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo actualizar la categoría.",
          life: 3500,
        });

      } finally {
        this.isLoading = false;
      }
    },

    listarCategoria(page, buscar, criterio) {
      let me = this;
      var url =
        "/categorianewview?page=" +
        page +
        "&buscar=" +
        buscar +
        "&criterio=" +
        criterio;
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          //consol.log('Linea',respuesta);
          me.arrayCategoria = respuesta.categorias;
          me.pagination = respuesta.pagination;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    consultaProductosServicios() {
      let me = this;
      var url = "/categoria/consultaProductosServicios";
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayProductoServicio =
            respuesta.RespuestaListaProductos.listaCodigos;
          console.log(respuesta.RespuestaListaProductos.listaCodigos);
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    consultaActividadEconomica() {
      let me = this;
      var url = "/categoria/consultaActividadEconomica";
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayActividadEconomica =
            respuesta.RespuestaListaActividades.listaActividades;
          console.log(respuesta.RespuestaListaActividades.listaActividades);
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    async desactivarCategoria(id) {
      try {
        const result = await Swal.fire({
          title: "¿Está seguro de desactivar esta categoría?",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#22c55e",
          cancelButtonColor: "#ef4444",
          confirmButtonText: "Aceptar!",
          cancelButtonText: "Cancelar",
          reverseButtons: true,
          customClass: {
            confirmButton: 'swal2-confirm-lineanew',
            cancelButton: 'swal2-cancel-lineanew'
          }
        });

        if (result.value) {
          this.isLoading = true; // Activar loading
          let me = this;
          await axios.put("/categoria/desactivar", { id: id });
          await me.listarCategoria(1, me.buscar);
          this.toastSuccess("El registro ha sido desactivado con éxito.");
        }
      } catch (error) {
        console.error(error);
        Swal.fire("ERROR AL DESACTIVAR LA CATEGORIA", "", "error");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    async activarCategoria(id) {
      try {
        const result = await Swal.fire({
          title: "¿Está seguro de activar esta categoría?",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#22c55e",
          cancelButtonColor: "#ef4444",
          confirmButtonText: "Aceptar!",
          cancelButtonText: "Cancelar",
          reverseButtons: true,
          customClass: {
            confirmButton: 'swal2-confirm-lineanew',
            cancelButton: 'swal2-cancel-lineanew'
          }
        });

        if (result.value) {
          this.isLoading = true; // Activar loading
          let me = this;
          await axios.put("/categoria/activar", { id: id });
          await me.listarCategoria(1, me.buscar);
          this.toastSuccess("El registro ha sido activado con éxito.");
        }
      } catch (error) {
        console.error(error);
        Swal.fire("ERROR AL ACTIVAR LA CATEGORIA", "", "error");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "categoria": {
          switch (accion) {
            case "registrar": {
              this.modal = true;
              this.tituloModal = "REGISTRAR CATEGORÍA";
              this.nombre = "";
              this.descripcion = "";
              this.codigoProductoSin = 0;
              this.tipoAccion = 1;
              break;
            }
            case "actualizar": {
              //console.log(data);
              this.modal = true;
              this.tituloModal = "ACTUALIZAR CATEGORÍA";
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
    cerrarModalImportar() {
      this.importar = false;
    },
  },
  async mounted() {
    this.handleResize();
    window.addEventListener("resize", this.handleResize);
    try {
      this.isLoading = true; // Activar loading
      await Promise.all([
        this.listarCategoria(1, this.buscar, this.criterio),
        this.consultaProductosServicios(),
        this.consultaActividadEconomica()
      ]);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
      Swal.fire("ERROR AL CARGAR LOS DATOS", "", "error");
    } finally {
      setTimeout(() => {
        this.isLoading = false; // Desactivar loading
      }, 500);
    }
  },
  beforeUnmount() {
    window.removeEventListener("resize", this.handleResize);
  },
};
</script>

<style scoped>
.dato-no-registrado {
  color: #b38a00; /* amarillo oscuro */
  font-weight: 600;
  display: flex;
  align-items: center;
}

.dato-no-registrado i {
  font-size: 1rem;
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
  padding-bottom: 20px;
  /* Aumentado de 8px a 12px para dar espacio al error */
  margin-bottom: 8px;
  /* Agregado margen inferior pequeño */
}

.input-container .p-inputtext {
  width: 100%;
  margin-bottom: 0;
  /* Eliminar margen inferior si existe */
}

.error-message {
  position: absolute;
  bottom: 2px;
  /* Ajustado para tener más espacio arriba del input */
  left: 0;
  font-size: 0.75rem;
  /* Tamaño de fuente más pequeño */
  margin-top: 0;
  /* Eliminado margen superior */
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


/* Responsive Dialog Styles */
.responsive-dialog>>>.p-dialog {
  margin: 0.75rem;
  max-height: 90vh;
  overflow-y: auto;
}

.responsive-dialog>>>.p-dialog-content {
  overflow-x: auto;
  padding: 0.75rem 1rem;
  /* Reducido padding vertical */
}

.responsive-dialog>>>.p-dialog-header {
  padding: 0.75rem 1.5rem;
  /* Reducido padding vertical */
  font-size: 1.1rem;
}

.responsive-dialog>>>.p-dialog-footer {
  padding: 0.5rem 1.5rem;
  /* Reducido padding vertical */
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
.form-compact>>>.p-field {
  margin-bottom: 0.25rem !important;
  /* Reducido de 0.5rem a 0.25rem */
}

>>>.p-fluid .p-field {
  margin-bottom: 0.25rem;
  /* Reducido de 0.5rem a 0.25rem */
}

/* Reducir padding del contenedor del diálogo */
.responsive-dialog>>>.p-dialog-content {
  padding: 0.75rem 1rem !important;
  /* Reducido padding vertical */
}

/* Estilos para campos obligatorios */
.required-field {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 4px;
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
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 4px;
  align-items: center;
  gap: 0.4rem;
  font-weight: 500;
  color: #6c757d;
}

.optional-icon {
  color: #17a2b8;
  font-size: 0.5rem;
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


.p-dialog-mask {
  z-index: 9990 !important;
}

.p-dialog {
  z-index: 9990 !important;
}

/* SweetAlert z-index para que aparezca por encima de los diálogos */
>>>.swal2-container {
  z-index: 99999 !important;
}

>>>.swal2-popup {
  z-index: 99999 !important;
}

/* Tablet Styles */
@media (max-width: 1024px) {
  .responsive-dialog>>>.p-dialog {
    margin: 0.5rem;
    max-height: 95vh;
  }

  >>>.p-datatable {
    font-size: 0.85rem;
  }
}

/* Mobile Styles */
@media (max-width: 768px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog>>>.p-dialog {
    margin: 0.25rem;
    max-height: 98vh;
  }

  .responsive-dialog>>>.p-dialog-content {
    padding: 0.5rem 0.75rem;
    /* Más compacto en móviles */
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.5rem 1rem;
    /* Reducido padding vertical */
    font-size: 1rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.4rem 1rem;
    /* Reducido padding vertical */
    justify-content: flex-end;
  }

  .toolbar-container {
    gap: 0.5rem;
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

  /* Ajustar botones en móviles */
  >>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }

  /* Ajustar botón "Nuevo" para que coincida con otros botones */
  .toolbar>>>.p-button-sm {
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

  >>>.p-inputtext,
  >>>.p-dropdown,
  >>>.p-inputnumber-input {
    font-size: 0.9rem;
    padding: 0.5rem;
  }

  /* Reducir espacios entre campos en móviles */
  .input-container {
    padding-bottom: 20px;
    /* Aumentado para dar espacio al error en móviles */
    margin-bottom: 6px;
  }
}

/* Extra Small Mobile */
@media (max-width: 480px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog>>>.p-dialog {
    margin: 0.1rem;
    max-height: 99vh;
  }

  .responsive-dialog>>>.p-dialog-content {
    padding: 0.4rem 0.5rem;
    /* Más compacto en móviles extra pequeños */
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.4rem 0.75rem;
    /* Reducido padding vertical */
    font-size: 0.95rem;
  }

  /* Footer mantiene botones alineados a la derecha, no ocupan todo el ancho */
  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.3rem 0.75rem;
    /* Reducido padding vertical */
    justify-content: flex-end;
  }

  .responsive-dialog>>>.p-dialog-footer .p-button {
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
  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  /* Reducir más la altura del input buscador en móviles pequeños */
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

  /* Iconos más pequeños en móviles extra pequeños */
  .required-icon {
    font-size: 0.7rem;
  }

  .optional-icon {
    font-size: 0.55rem;
  }

  >>>.p-inputtext,
  >>>.p-dropdown,
  >>>.p-inputnumber-input {
    font-size: 0.85rem;
    padding: 0.4rem;
  }

  >>>.p-tag {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
  }

  /* Espacios aún más compactos en móviles extra pequeños */
  .input-container {
    padding-bottom: 20px;
    /* Aumentado para dar espacio al error en móviles pequeños */
    margin-bottom: 4px;
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

/* Action Buttons in DataTable */
>>>.p-datatable .p-button {
  margin-right: 0.25rem;
}

@media (max-width: 768px) {
  >>>.p-datatable .p-button {
    margin-right: 0.15rem;
    margin-bottom: 0.15rem;
  }
}

>>>.p-fileupload .p-button.p-fileupload-choose {
  background-color: #22c55e !important;
  border-color: #22c55e !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

/* Efecto hover */
>>>.p-fileupload .p-button.p-fileupload-choose:enabled:hover {
  background-color: #16a34a !important;
  border-color: #16a34a !important;
}

/* Efecto focus */
>>>.p-fileupload .p-button.p-fileupload-choose:focus {
  box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.5) !important;
}

/* Efecto active (cuando se hace clic) */
>>>.p-fileupload .p-button.p-fileupload-choose:enabled:active {
  background-color: #15803d !important;
  border-color: #15803d !important;
}

/* Estilo cuando está deshabilitado */
>>>.p-fileupload .p-button.p-fileupload-choose:disabled {
  background-color: #22c55e !important;
  border-color: #22c55e !important;
  opacity: 0.6;
}

>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose) {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

/* Efecto hover */
>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):enabled:hover {
  background: #dc2626 !important;
  border-color: #dc2626 !important;
}

/* Efecto focus */
>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):focus {
  box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.5) !important;
}

/* Efecto active (cuando se hace clic) */
>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):enabled:active {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
}

/* Estilo cuando está deshabilitado */
>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):disabled {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  opacity: 0.6;
}

>>>.p-fileupload .p-fileupload-files .p-button {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

/* Efecto hover */
>>>.p-fileupload .p-fileupload-files .p-button:enabled:hover {
  background: #dc2626 !important;
  border-color: #dc2626 !important;
}

/* Efecto focus */
>>>.p-fileupload .p-fileupload-files .p-button:focus {
  box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.5) !important;
}

/* Efecto active (cuando se hace clic) */
>>>.p-fileupload .p-fileupload-files .p-button:enabled:active {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
}

/* Estilo cuando está deshabilitado */
>>>.p-fileupload .p-fileupload-files .p-button:disabled {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  opacity: 0.6;
}

/* Asegurar que el icono dentro del botón también sea blanco */
>>>.p-fileupload .p-fileupload-files .p-button .p-button-icon {
  color: #ffffff !important;
}

>>>.p-fileupload-row>div:first-child {
  display: none !important;
}

>>>.p-dialog .p-dialog-content {
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
</style>
