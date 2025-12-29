<template>
  <main class="main">
    <Toast :breakpoints="{ '920px': { width: '100%', right: '0', left: '0' } }" style="padding-top: 10px;"
      appendTo="body" :baseZIndex="99999"></Toast>
    <Dialog
      header="Proveedores"
      :visible.sync="modal1"
      :modal="true"
      :containerStyle="dialogContainerStyle"
      :closable="false"
      :closeOnEscape="false"
      class="responsive-dialog"
    >
      <div class="toolbar-container">
        <div class="toolbar">
          <Button
            label="Nuevo"
            icon="pi pi-plus"
            @click="abrirModal('persona', 'registrar')"
            class="p-button-secondary p-button-sm btn-sm"
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
              @keyup="buscarProveedor"
            />
          </span>
        </div>
      </div>
      <DataTable
        class="p-datatable-sm p-datatable-gridlines tabla-pro"
        :value="arrayProveedor"
        :paginator="true"
        responsiveLayout="scroll"
        :rows="7"
      >
        <Column field="opciones" header="Opciones">
          <template #body="slotProps">
            <Button
              icon="pi pi-check"
              class="p-button-success custom-icon-size btn-mini"
              @click="seleccionarProveedor(slotProps.data)"
            />
            <Button
              icon="pi pi-pencil"
              class="p-button-warning btn-mini"
              @click="abrirModal('persona', 'actualizar', slotProps.data)"
            />
          </template>
        </Column>
        <Column field="nombre" header="Nombre" />
      </DataTable>
      <template #footer>
        <Button
          label="Cerrar"
          icon="pi pi-times"
          class="p-button-danger p-button-sm btn-sm"
          @click="closeDialog"
        />
      </template>
    </Dialog>
    <Dialog
      :header="tituloModal"
      :visible.sync="modal"
      :modal="true"
      :closable="false"
      :containerStyle="formDialogContainerStyle"
      :closeOnEscape="false"
      class="responsive-dialog form-dialog"
    >
      <form @submit.prevent="enviarFormulario">
        <div class="p-fluid p-formgrid p-grid form-compact">
          <div class="p-field p-col-12 p-md-6">
            <label for="name" class="required-field">
              <span class="required-icon">*</span>
              Nombre del proveedor
            </label>
            <InputText
              id="name"
              class="input-full"
              v-model="datosFormulario.nombre"
              @input="validarCampo('nombre')"
              :class="{ 'input-error': errores.nombre }"
              required
            />
          </div>

          <div class="p-field p-col-12 p-md-6">
            <label for="documentType" class="optional-field">
              <i class="pi pi-info-circle optional-icon"></i>
              Tipo de documento
              <span class="p-tag p-tag-secondary tag-opcional">Opcional</span>
            </label>
            <Dropdown
              id="documentType"
              class="dropdown-full"
              v-model="datosFormulario.tipo_documento"
              :options="tiposDocumentos"
              optionLabel="etiqueta"
              optionValue="valor"
            />
          </div>
          
          <div class="p-field p-col-12 p-md-6">
            <label for="documentNumber" class="optional-field">
              <i class="pi pi-info-circle optional-icon"></i>
              Nro de documento
              <span class="p-tag p-tag-secondary tag-opcional">Opcional</span>
            </label>
            <InputText
              id="documentNumber"
              v-model="datosFormulario.num_documento"
              class="input-full"
            />
          </div>

          <div class="p-field p-col-12 p-md-6">
            <label for="phone" class="required-field">
              <span class="required-icon">*</span>
              Teléfono
            </label>
            
            <InputText
              id="phone"
              class="input-full"
              type="number"
              v-model="datosFormulario.telefono"
              @input="validarCampo('telefono')"
              :class="{ 'input-error': errores.telefono }"
              required
            />
          </div>
          
          <div class="p-field p-col-12 p-md-6">
            <label for="contact" class="required-field">
              <span class="required-icon">*</span>
              Contacto
            </label>
            <InputText
              id="contact"
              class="input-full"
              v-model="datosFormulario.contacto"
              @input="validarCampo('contacto')"
              :class="{ 'input-error': errores.contacto }"
              required
            />
          </div>
          
          <div class="p-field p-col-12 p-md-6">
            <label for="contactPhone" class="required-field">
              <span class="required-icon">*</span>
              Teléfono de contacto
            </label>
            
            <InputText
              id="contactPhone"
              class="input-full"
              type="number"
              v-model="datosFormulario.telefono_contacto"
              @input="validarCampo('telefono_contacto')"
              :class="{ 'input-error': errores.telefono_contacto }"
              required
            />
          </div>
        </div>
      </form>
      <template #footer>
        <Button
          label="Cerrar"
          icon="pi pi-times"
          class="p-button-danger p-button-sm btn-sm"
          @click="cerrarModal"
        />
        <Button
          v-if="tipoAccion == 1"
          class="p-button-success p-button-sm btn-sm"
          label="Guardar"
          icon="pi pi-check"
          @click="enviarFormulario"
        />
        <Button
          v-if="tipoAccion == 2"
          class="p-button-warning p-button-sm btn-sm"
          label="Actualizar"
          icon="pi pi-check"
          @click="enviarFormulario"
        />
      </template>
    </Dialog>
  </main>
</template>

<script>
import Swal from "sweetalert2";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Dropdown from "primevue/dropdown";
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import { esquemaProveedor } from "../../constants/validations"; // Asegúrate de importar tu esquema de validación

export default {
  props: {
    visible: {
      type: Boolean,
      required: true,
    },
  },
  components: {
    Button,
    DataTable,
    Column,
    Dialog,
    InputText,
    Dropdown,
    InputNumber,
    ToastService,
    Toast
  },
  data() {
    return {
      datosFormulario: {
        nombre: "",
        tipo_documento: "",
        num_documento: "",
        direccion: "",
        telefono: "",
        email: "",
        contacto: "",
        telefono_contacto: "",
      },
      modal1: this.visible,
      errores: {},
      buscar: "",
      tipoAccion: 0,
      arrayProveedor: [],
      modal: false,
      tituloModal: "",
      documentoSeleccionado: null,
      proveedorSeleccionado: null,
      tiposDocumentos: [
        { valor: "1", etiqueta: "CI - CEDULA DE IDENTIDAD" },
        { valor: "2", etiqueta: "CEX - CEDULA DE IDENTIDAD EXTRANJERO" },
        { valor: "5", etiqueta: "NIT - NÚMERO IDENTIFICACIÓN TRIBUTARIA" },
        { valor: "3", etiqueta: "PAS - PASAPORTE" },
        { valor: "4", etiqueta: "OD - OTRO DOCUMENTO DE IDENTIDAD" },
      ],
    };
  },
  computed: {
    dialogContainerStyle() {
      if (window.innerWidth <= 480) {
        return { width: '95vw', maxWidth: '95vw', margin: '0 auto' };
      } else if (window.innerWidth <= 768) {
        return { width: '90vw', maxWidth: '90vw', margin: '0 auto' };
      } else if (window.innerWidth <= 1024) {
        return { width: '80vw', maxWidth: '800px', margin: '0 auto' };
      } else {
        return { width: '700px', maxWidth: '90vw', margin: '0 auto' };
      }
    },
    formDialogContainerStyle() {
      if (window.innerWidth <= 480) {
        return { width: '95vw', maxWidth: '95vw', margin: '0 auto' };
      } else if (window.innerWidth <= 768) {
        return { width: '90vw', maxWidth: '90vw', margin: '0 auto' };
      } else if (window.innerWidth <= 1024) {
        return { width: '85vw', maxWidth: '900px', margin: '0 auto' };
      } else {
        return { width: '700px', maxWidth: '90vw', margin: '0 auto' };
      }
    }
  },
  methods: {
    toastError(mensaje) {
      this.$toasted.show(
        `
    <div style="height: 60px;font-size:16px;">
        <br>
        ` +
          mensaje +
          `<br>
    </div>`,
        {
          type: "error",
          position: "bottom-right",
          duration: 4000,
        }
      );
    },
    closeDialog() {
      this.$emit("close");
    },
    buscarProveedor() {
      this.listarproveedor(this.buscar);
    },
    seleccionarProveedor(data) {
      this.proveedorSeleccionado = data;
      this.$emit("proveedor-seleccionado", this.proveedorSeleccionado);
      this.$emit("close");
    },
    async validarCampo(campo) {
      try {
        await esquemaProveedor.validateAt(campo, this.datosFormulario);
        this.errores[campo] = null;
      } catch (error) {
        this.errores[campo] = error.message;
      }
    },

    async enviarFormulario() {
      // Reiniciar errores
      this.errores = {
        nombre: "",
        telefono: "",
        contacto: "",
        telefono_contacto: ""
      };

      let hasError = false;

      // VALIDACIÓN DE NOMBRE
      if (!this.datosFormulario.nombre || this.datosFormulario.nombre.trim() === "") {
        this.errores.nombre = "El nombre no puede estar vacío.";
        hasError = true;
      }

      // VALIDACIÓN DE TELÉFONO
      if (!this.datosFormulario.telefono || this.datosFormulario.telefono.trim() === "") {
        this.errores.telefono = "El teléfono no puede estar vacío.";
        hasError = true;
      }

      // VALIDACIÓN DE CONTACTO
      if (!this.datosFormulario.contacto || this.datosFormulario.contacto.trim() === "") {
        this.errores.contacto = "El nombre del contacto no puede estar vacío.";
        hasError = true;
      }

      // VALIDACIÓN DE TELÉFONO DE CONTACTO
      if (!this.datosFormulario.telefono_contacto || this.datosFormulario.telefono_contacto.trim() === "") {
        this.errores.telefono_contacto = "El teléfono de contacto no puede estar vacío.";
        hasError = true;
      }

      // SI HAY ERRORES → MOSTRAR TOAST Y NO ENVIAR
      if (hasError) {
        this.$toast.add({
          severity: "error",
          summary: "Errores en el formulario",
          detail: "Por favor revise los campos marcados en rojo.",
          life: 3500,
        });
        return;
      }

      // SI TODO ESTÁ CORRECTO → GUARDAR / ACTUALIZAR
      if (this.tipoAccion == 2) {
        this.actualizarPersona(this.datosFormulario);
      } else {
        this.registrarPersona(this.datosFormulario);
      }
    },

    listarproveedor(buscar) {
      let me = this;
      var url = "/proveedornewview?buscar=" + buscar;
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.arrayProveedor = respuesta.personas;
          me.pagination = respuesta.pagination;
        })
        .catch(function(error) {
          console.log(error);
        });
    },
    registrarPersona(data) {
      let me = this;
      axios
        .post("/proveedor/registrar", data)
        .then(function(response) {

          // 🔵 ÉXITO
          me.$toast.add({
            severity: "success",
            summary: "Registrado",
            detail: "El proveedor fue registrado correctamente.",
            life: 2500,
          });

          me.cerrarModal();
          me.listarproveedor("");
        })
        .catch(function(error) {

          // 🔴 ERROR
          me.$toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudo registrar el proveedor.",
            life: 3000,
          });

          console.log(error);
        });
    },
    actualizarPersona(data) {
      let me = this;

      axios
        .put("/proveedor/actualizar", data)
        .then(function(response) {

          // 🔵 ÉXITO
          me.$toast.add({
            severity: "success",
            summary: "Actualizado",
            detail: "El proveedor fue actualizado correctamente.",
            life: 2500,
          });

          me.cerrarModal();
          me.listarproveedor("");
        })
        .catch(function(error) {

          // 🔴 ERROR
          me.$toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudo actualizar el proveedor.",
            life: 3000,
          });

          console.log(error);
        });
    },
    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "persona": {
          switch (accion) {
            case "registrar": {
              this.modal1 = false;
              this.modal = true;
              console.log("modal nuevo", this.modal);
              console.log("modal tabla", this.modal1);
              this.tituloModal = "Registrar Proveedor";
              this.tipoAccion = 1;
              this.datosFormulario = {
                nombre: "",
                tipo_documento: "",
                num_documento: "",
                direccion: "",
                telefono: "",
                email: "",
                contacto: "",
                telefono_contacto: "",
              };
              break;
            }
            case "actualizar": {
              this.modal1 = false;
              this.modal = true;
              console.log("modal nuevo", this.modal);
              console.log("modal tabla", this.modal1);
              this.tituloModal = "Actualizar Proveedor";
              this.tipoAccion = 2;
              this.datosFormulario = data;
              this.persona_id = data["id"];
              break;
            }
          }
        }
      }
    },
    cerrarModal() {
      this.modal = false;
      this.modal1 = true;
      this.tituloModal = "";
      this.errores = {};
    },
  },
  mounted() {
    this.listarproveedor("");
  },
};
</script>

<style scoped>
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
/* Estilo uniforme para Dropdown (igual que InputText) */
.dropdown-full {
    width: 100% !important;
    font-size: 0.8rem;
    border-radius: 6px;
    box-sizing: border-box;
}

/* Input dentro del dropdown */
.dropdown-full>>>.p-dropdown-label {
    padding: 6px 8px !important;
    font-size: 0.8rem;
}

/* Flecha del dropdown */
.dropdown-full>>>.p-dropdown-trigger {
    width: 2rem !important;
}

/* Borde al focus */
.dropdown-full>>>.p-dropdown {
    border: 1px solid #ccc;
    transition: border 0.2s;
}

.dropdown-full>>>.p-dropdown.p-focus {
    border-color: #0ea5e9;
    box-shadow: 0 0 0 0.15rem rgba(14, 165, 233, 0.25);
}

/* 🔹 Opciones del panel (lista desplegable) */
.dropdown-full>>>.p-dropdown-panel .p-dropdown-item {
    font-size: 0.8rem !important;
    padding: 6px 10px !important;
    min-height: auto !important;
    /* evita que queden muy grandes */
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

/* 🔹 Estilo especial para InputNumber */
.input-number-full {
  width: 100%;
}

.input-number-full>>>.p-inputtext {
  width: 100% !important;
  font-size: 0.8rem;
  padding: 6px 8px;
  box-sizing: border-box;
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

.form-group {
  margin-bottom: 15px;
}

/* Responsive Dialog Styles */
.responsive-dialog >>> .p-dialog {
  margin: 0.75rem;
  max-height: 90vh;
  overflow-y: auto;
}

.responsive-dialog >>> .p-dialog-content {
  overflow-x: auto;
  padding: 1rem;
}

.responsive-dialog >>> .p-dialog-header {
  padding: 1rem 1.5rem;
  font-size: 1.1rem;
}

.responsive-dialog >>> .p-dialog-footer {
  padding: 0.75rem 1.5rem;
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
}

.bold-input {
  font-weight: bold;
}

/* Formulario compacto - Reducir espacios entre campos */
.form-compact >>> .p-field {
  margin-bottom: 0.5rem !important;
}

>>> .p-fluid .p-field {
  margin-bottom: 0.5rem;
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

/* Form Grid Responsive */
>>> .p-formgrid.p-grid {
  margin: 0;
}

>>> .p-formgrid .p-field {
  padding: 0.2rem;
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
  
  >>> .p-formgrid .p-field.p-col-12.p-md-6 {
    width: 100% !important;
    flex: 0 0 100% !important;
  }
}

/* Mobile Styles */
@media (max-width: 768px) {
  .responsive-dialog >>> .p-dialog {
    margin: 0.25rem;
    max-height: 98vh;
  }
  
  .responsive-dialog >>> .p-dialog-content {
    padding: 0.75rem;
  }
  
  .responsive-dialog >>> .p-dialog-header {
    padding: 0.75rem 1rem;
    font-size: 1rem;
  }
  
  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.5rem 1rem;
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
  
  >>> .p-formgrid .p-field {
    padding: 0.25rem;
    margin-bottom: 0.4rem !important;
  }
  
  >>> .p-formgrid .p-field label {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
  }
  
  /* Ajustar iconos en móviles */
  .required-icon {
    font-size: 0.8rem;
  }
  
  .optional-icon {
    font-size: 0.6rem;
  }
  
  >>> .p-inputtext, >>> .p-dropdown, >>> .p-inputnumber-input {
    font-size: 0.9rem;
    padding: 0.5rem;
  }
  
  >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }
  
  /* Ajustar botón "Nuevo" para que coincida con botón "Cerrar" */
  .toolbar >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }
  
  /* Reducir altura del input buscador */
  .search-bar .p-inputtext-sm {
    padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
    font-size: 0.85rem !important;
  }
}

/* Extra Small Mobile */
@media (max-width: 480px) {
  .responsive-dialog >>> .p-dialog {
    margin: 0.1rem;
    max-height: 99vh;
  }
  
  .responsive-dialog >>> .p-dialog-content {
    padding: 0.5rem;
  }
  
  .responsive-dialog >>> .p-dialog-header {
    padding: 0.5rem 0.75rem;
    font-size: 0.95rem;
  }
  
  /* Footer mantiene botones alineados a la derecha, no ocupan todo el ancho */
  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.5rem 0.75rem;
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
  
  /* Ajustar botón "Nuevo" para que coincida con botón "Cerrar" */
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
  
  >>> .p-formgrid .p-field {
    padding: 0.2rem;
    margin-bottom: 0.3rem !important;
  }
  
  >>> .p-formgrid .p-field label {
    font-size: 0.85rem;
  }
  
  /* Iconos más pequeños en móviles extra pequeños */
  .required-icon {
    font-size: 0.7rem;
  }
  
  .optional-icon {
    font-size: 0.55rem;
  }
  
  >>> .p-inputtext, >>> .p-dropdown, >>> .p-inputnumber-input {
    font-size: 0.85rem;
    padding: 0.4rem;
  }
  
  >>> .p-tag {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
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

/* Error Messages Responsive */
>>> .p-error {
  font-size: 0.8rem;
}

@media (max-width: 480px) {
  >>> .p-error {
    font-size: 0.75rem;
  }
}
</style>

<!-- Estilos globales necesarios para z-index -->
<style>
.p-dialog-mask {
  z-index: 9990 !important;
}
.p-dialog {
  z-index: 9990 !important;
}
.swal-zindex {
  z-index: 99999 !important;
}
</style>