<template>
  <main class="main">
    <Toast :breakpoints="{ '920px': { width: '100%', right: '0', left: '0' } }" style="padding-top: 10px;"
      appendTo="body" :baseZIndex="99999"></Toast>
    <Dialog
      header="Almacenes"
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
            @click="abrirModal('almacenes', 'registrar')"
            class="p-button-secondary p-button-sm btn-sm"
          />
        </div>
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText
              type="text"
              v-model="buscar"
              placeholder="Texto a buscar"
              class="p-inputtext-sm"
              @keyup="buscarAlmacenes"
            />
          </span>
        </div>
      </div>
      <DataTable
        class="p-datatable-sm p-datatable-gridlines tabla-pro"
        :value="arrayAlmacen"
        :paginator="true"
        responsiveLayout="scroll"
        :rows="7"
      >
        <Column field="opciones" header="Opciones">
          <template #body="slotProps">
            <Button
              icon="pi pi-check"
              class="p-button-success custom-icon-size btn-mini"
              @click="seleccionarAlmacen(slotProps.data)"
            />
            <Button
              icon="pi pi-pencil"
              class="p-button-warning btn-mini"
              @click="abrirModal('almacenes', 'actualizar', slotProps.data)"
            />
          </template>
        </Column>
        <Column field="nombre_almacen" header="Nombre Almacen" />
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
          <div class="p-field p-col-12">
            <label for="nombreAlmacen" class="required-field">
              <span class="required-icon">*</span>
              Nombre del almacén
            </label>
            <InputText
              id="nombreAlmacen"
              class="input-full" 
              placeholder="Ej. Almacén Principal"
              v-model="datosFormulario.nombre_almacen"
              :class="{ 'input-error': errores.nombre_almacen }"
              @input="validarCampo('nombre_almacen')"
              required
            />
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="sucursal" class="required-field">
              <span class="required-icon">*</span>
              Sucursal
            </label>
            <AutoComplete
              class="p-inputtext-sm autocomplete-full"
              v-model="sucursalSeleccionado"
              :suggestions="arraySucursal"
              @complete="selectSucursal($event)"
              @item-select="getDatosSucursales"
              field="nombre"
              forceSelection
              :class="{ 'input-error': errores.sucursal }"
              placeholder="Buscar Sucursales..."
              required
            />
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="ubicacion" class="optional-field">
              <i class="pi pi-info-circle optional-icon"></i>
              Ubicación
              <span class="p-tag p-tag-secondary tag-opcional">Opcional</span>
            </label>
            <InputText
              id="ubicacion"
              class="input-full" 
              placeholder="Ej. Calle 123, Ciudad"
              v-model="datosFormulario.ubicacion"
            />
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="encargados" class="required-field">
              <span class="required-icon">*</span>
              Encargados
            </label>
            <AutoComplete
              v-model="usuariosSeleccionados"
              class="autocomplete-full"
              :suggestions="arrayUsuario"
              @complete="selectUsuario($event)"
              @item-select="actualizarEncargados"
              field="nombre"
              :class="{ 'input-error': errores.encargado }"
              placeholder="Buscar Usuarios..."
              required
            />
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="telefono" class="optional-field">
              <i class="pi pi-info-circle optional-icon"></i>
              Teléfono de Contacto
              <span class="p-tag p-tag-secondary tag-opcional">Opcional</span>
            </label>
            <InputText
              id="telefono"
              class="input-full" 
              placeholder="Ej. 123456789"
              v-model="datosFormulario.telefono"
            />
          </div>
          
          <div class="p-field p-col-12">
            <label for="observaciones" class="optional-field">
              <i class="pi pi-info-circle optional-icon"></i>
              Observaciones
              <span class="p-tag p-tag-secondary tag-opcional">Opcional</span>
            </label>
            <Textarea
              id="observaciones"
              class="textarea-full"
              placeholder="Ej. Horario de funcionamiento, Capacidad de almacenamiento, etc."
              rows="2"
              v-model="datosFormulario.observaciones"
            />
          </div>
        </div>
      </form>
      <template #footer>
        <Button
          label="Cerrar"
          icon="pi pi-times"
          class="p-button-sm p-button-danger btn-sm"
          @click="cerrarModal"
        />
        <Button
          v-if="tipoAccion == 1"
          label="Guardar"
          icon="pi pi-check"
          class="p-button-sm p-button-success btn-sm"
          @click="enviarFormulario()"
        />
        <Button
          v-if="tipoAccion == 2"
          label="Actualizar"
          icon="pi pi-check"
          class="p-button-sm p-button-warning btn-sm"
          @click="enviarFormulario()"
        />
      </template>
    </Dialog>
  </main>
</template>

<script>
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Dropdown from "primevue/dropdown";
import AutoComplete from "primevue/autocomplete";
import Textarea from "primevue/textarea";
import { esquemaAlmacen } from "../../constants/validations";
import Swal from "sweetalert2";
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';

// Asegúrate de importar tu esquema de validación

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
    AutoComplete,
    Textarea,
    ToastService,
    Toast
  },
  data() {
    return {
      datosFormulario: {
        nombre_almacen: "",
        ubicacion: "",
        encargado: -1,
        telefono: "",
        sucursal: -1,
        observaciones: "",
      },
      modal1: this.visible,
      errores: {},
      buscar: "",
      tipoAccion: 0,
      arraySucursal: [],
      modal: false,
      tituloModal: "",
      arrayUsuario: [],
      usuariosSeleccionados: null,
      sucursalSeleccionado: null,
      almacenSeleccionado: null,
      arrayAlmacen: [],
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
    closeDialog() {
      this.$emit("close");
    },
    buscarAlmacenes() {
      this.listarAlmacenes(1, this.buscar);
    },
    selectSucursal(event) {
      let me = this;

      if (!event.query.trim().length) {
        var url = "/sucursal/selectedSucursal/filter?filtro=";
        axios
          .get(url)
          .then(function(response) {
            var respuesta = response.data;
            me.arraySucursal = respuesta.sucursales;
            me.loading = false;
          })
          .catch(function(error) {
            console.log(error);
            me.loading = false;
          });
      } else {
        this.loading = true;

        var url =
          "/sucursal/selectedSucursal/filter?filtro=" + me.sucursalSeleccionado;
        axios
          .get(url)
          .then(function(response) {
            var respuesta = response.data;
            me.arraySucursal = respuesta.sucursales;
            me.loading = false;
          })
          .catch(function(error) {
            console.log(error);
            me.loading = false;
          });
      }
    },
    getDatosSucursales(val1) {
      console.log("Ejecucion de sucursales");

      // Guardar solo el ID de la sucursal seleccionada
      this.datosFormulario.sucursal =
        val1 && val1.value && val1.value.id ? val1.value.id : -1;

      // 🔥 Quitar el error visual inmediatamente
      this.errores.sucursal = null;

      // 🔥 Validar nuevamente
      this.validarCampo("sucursal");
    },
    actualizarEncargados(event) {
      var encargado = event.value; // PrimeVue entrega { value: {id, nombre} }

      // Guardar el ID o vacío si no existe
      this.datosFormulario.encargado = encargado && encargado.id ? encargado.id : "";

      // Quitar error visual
      this.errores.encargado = null;

      // Revalidar
      this.validarCampo("encargado");
    },
    getDatosUsuarios(event) {
      const val1 = event.value;
      if (this.tipoAccion === 2) {
        this.datosFormulario.encargado =
          val1 && val1.length > 0
            ? val1.map((v) => v.id).join(",")
            : this.datosFormulario.encargado2;
        delete this.datosFormulario["encargado2"];
      } else {
        this.datosFormulario.encargado =
          val1 && val1.length > 0 ? val1.map((v) => v.id).join(",") : "";
      }
      console.log("val 1", val1);
      console.log("datos formulario", this.datosFormulario);
    },
    selectUsuario(event) {
      let me = this;

      if (!event.query.trim().length) {
        var url = "/user/selectUser/filter?idrol=3";
        axios
          .get(url)
          .then(function(response) {
            var respuesta = response.data;
            me.arrayUsuario = respuesta.usuarios;
            me.loading = false;
          })
          .catch(function(error) {
            console.log(error);
            me.loading = false;
          });
      } else {
        this.loading = true;

        var url =
          "/user/selectUser/filter?filtro=" +
          event.query.toLowerCase() +
          "&idrol=3";
        axios
          .get(url)
          .then(function(response) {
            var respuesta = response.data;
            me.arrayUsuario = respuesta.usuarios;
            me.loading = false;
          })
          .catch(function(error) {
            console.log(error);
            me.loading = false;
          });
      }
    },
    seleccionarAlmacen(data) {
      this.almacenSeleccionado = data;
      this.$emit("almacen-seleccionado", this.almacenSeleccionado);
      this.$emit("close");
    },
    async validarCampo(campo) {
      try {
        console.log("formulario", this.datosFormulario);
        await esquemaAlmacen.validateAt(campo, this.datosFormulario);
        this.errores[campo] = null;
      } catch (error) {
        this.errores[campo] = error.message;
      }
    },
    async enviarFormulario() {
      await esquemaAlmacen
        .validate(this.datosFormulario, { abortEarly: false })
        .then(() => {
          // Acción según modo
          if (this.tipoAccion == 2) {
            this.actualizarAlmacen(this.datosFormulario);
          } else {
            this.registrarAlmacen(this.datosFormulario);
          }
        })
        .catch((error) => {
          console.log(error);

          const erroresValidacion = {};
          error.inner.forEach((e) => {
            erroresValidacion[e.path] = e.message;
          });

          this.errores = erroresValidacion;

          // 🔴 Toast global de errores al validar formulario
          this.$toast.add({
            severity: "error",
            summary: "Formulario incompleto",
            detail: "Por favor, complete los campos obligatorios.",
            life: 3000,
          });
        });
    },

    listarAlmacenes(page, buscar, criterio) {
      let me = this;
      var url =
        "/almacennewview?page=" +
        page +
        "&buscar=" +
        buscar +
        "&criterio=" +
        criterio;
      axios
        .get(url)
        .then(function(response) {
          let respuesta = response.data;
          me.arrayAlmacen = respuesta.almacenes;
          me.pagination = respuesta.pagination;
          console.log("Array de almacenes:", me.arrayAlmacen); // Verifica los datos en arrayAlmacen
        })
        .catch(function(error) {
          console.log(error);
        });
    },

    registrarAlmacen(data) {
      let me = this;
      axios
        .post("/almacen/registrar", data)
        .then(function(response) {

          // 🔵 ÉXITO
          me.$toast.add({
            severity: "success",
            summary: "Registrado",
            detail: "El almacén fue registrado correctamente.",
            life: 2500,
          });

          me.cerrarModal();
          me.listarAlmacenes(1, "", "nombre_almacen");

          // limpiar selects / arrays si aplica
          me.usuariosSeleccionados = null; 
          me.arrayUsuario = [];
        })
        .catch(function(error) {

          // 🔴 ERROR
          me.$toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudo registrar el almacén.",
            life: 3000,
          });

          console.log(error);
        });
    },
    actualizarAlmacen(data) {
      let me = this;
      axios
        .put("/almacen/editar", data)
        .then(function(response) {

          // 🔵 ÉXITO
          me.$toast.add({
            severity: "success",
            summary: "Actualizado",
            detail: "El almacén fue actualizado correctamente.",
            life: 2500,
          });

          me.cerrarModal();
          me.listarAlmacenes(1, "", "nombre_almacen");
        })
        .catch(function(error) {

          // 🔴 ERROR
          me.$toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudo actualizar el almacén.",
            life: 3000,
          });

          console.log(error);
        });
    },

    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "almacenes": {
          switch (accion) {
            case "registrar": {
              this.modal = true;
              this.modal1 = false;
              this.tituloModal = "Registrar Almacen";
              this.tipoAccion = 1;
              this.datosFormulario = {
                nombre_almacen: "",
                ubicacion: "",
                encargado: "",
                telefono: "",
                sucursal: "",
                observaciones: "",
              };
              this.errores = {};
              break;
            }
            case "actualizar": {
              console.log("Datos almacen:", data);
              this.modal = true;
              this.modal1 = false;
              this.tituloModal = "Actualizar Almacen";
              this.tipoAccion = 2;
              this.datosFormulario = {
                id: data["id"],
                nombre_almacen: data["nombre_almacen"],
                ubicacion: data["ubicacion"],
                encargado: data["encargado"],
                telefono: data["telefono"],
                sucursal: data["sucursal"],
                sucursal2: data["sucursal"],
                encargado2: data["encargado"],
                observaciones:
                  data["observacion"] == null ? "" : data["observacion"],
              };
              this.sucursalSeleccionado = data["nombre_sucursal"];
              this.usuariosSeleccionados = data["encargados_nombres"];

              this.errores = {};

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
      this.sucursalSeleccionado = "";
      this.usuarioSeleccionado = "";
      this.usuariosSeleccionados = "";
    },
  },
  mounted() {
    this.listarAlmacenes(1, "");
  },
};
</script>

<style scoped>
.textarea-full {
    width: 100% !important;
    font-size: 0.8rem !important;
    box-sizing: border-box;
}

/* Estilo base del Textarea de PrimeVue */
.textarea-full>>>.p-inputtextarea {
    width: 100% !important;
    font-size: 0.8rem !important;
    padding: 6px 8px !important;
    border: 1px solid #ccc !important;
    border-radius: 6px !important;
    min-height: 42px;                   /* misma altura mínima que Inputs */
    transition: border 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
    resize: vertical;                   /* permite redimensionar verticalmente */
}

/* 🔹 Focus igual que los otros campos */
.textarea-full>>>.p-inputtextarea:focus {
    border-color: #0ea5e9 !important;
    box-shadow: 0 0 0 0.15rem rgba(14, 165, 233, 0.25);
    outline: none !important;
}

/* 🔹 Hover opcional (igual que dropdown/inputtext) */
.textarea-full>>>.p-inputtextarea:hover {
    border-color: #a8a8a8;
}
/* Contenedor del AutoComplete */
.autocomplete-full {
    width: 100% !important;
    font-size: 0.8rem;
    border-radius: 6px;
    box-sizing: border-box;
}

/* Input interno */
.autocomplete-full>>>.p-inputtext {
    width: 100% !important;
    font-size: 0.8rem !important;
    padding: 6px 8px !important;
    border-radius: 6px;
    box-sizing: border-box;
}

/* Botón del dropdown (flecha) */
.autocomplete-full>>>.p-autocomplete-dropdown {
    width: 2rem !important;
    border-radius: 0 6px 6px 0;
}

/* Contenedor general del input + botón */
.autocomplete-full>>>.p-autocomplete {
    width: 100% !important;
    border: 1px solid #ccc !important;
    border-radius: 6px;
    transition: border 0.2s;
    display: flex;
    align-items: center;
}

/* Focus del input */
.autocomplete-full>>>.p-inputtext:focus,
.autocomplete-full>>>.p-autocomplete.p-focus {
    border-color: #0ea5e9 !important;
    box-shadow: 0 0 0 0.15rem rgba(14, 165, 233, 0.25);
}

/* Panel de sugerencias */
.autocomplete-full>>>.p-autocomplete-panel {
    font-size: 0.8rem !important;
}

/* Sugerencia individual */
.autocomplete-full>>>.p-autocomplete-items .p-autocomplete-item {
    padding: 6px 10px !important;
    font-size: 0.8rem !important;
    min-height: auto !important;
    cursor: pointer;
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
  padding: 0.5rem;
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
  
  >>> .p-inputtext, >>> .p-dropdown, >>> .p-inputnumber-input, >>> .p-autocomplete-input {
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
  
  >>> .p-inputtext, >>> .p-dropdown, >>> .p-inputnumber-input, >>> .p-autocomplete-input {
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
