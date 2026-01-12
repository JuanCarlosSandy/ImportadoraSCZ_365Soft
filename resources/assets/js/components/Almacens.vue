<template>
  <div class="main">
    <div class="loading-overlay" v-if="isLoading">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">LOADING...</div>
      </div>
    </div>
    <Toast :breakpoints="{ '920px': { width: '100%', right: '0', left: '0' } }" style="padding-top: 10px;"
      appendTo="body" :baseZIndex="99999"></Toast>
    <Panel>
      <template #header>
        <div class="panel-header">
          <i class="pi pi-bars panel-icon"></i>
          <h4 class="panel-title">ALMACENES</h4>
        </div>
      </template>
      <div class="toolbar-container">
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText v-model="buscar" placeholder="Texto a buscar" class="p-inputtext-sm" />
          </span>
        </div>
        <div class="toolbar">
          <Button :label="mostrarLabel ? 'Reset' : ''" icon="pi pi-refresh" @click="resetBusqueda"
            class="p-button-help p-button-sm" />
          <Button :label="mostrarLabel ? 'Nuevo' : ''" icon="pi pi-plus" @click="abrirModal('almacenes', 'registrar')"
            class="p-button-secondary p-button-sm" />
        </div>
      </div>

      <DataTable :value="arrayAlmacen" class="p-datatable-sm p-datatable-gridlines tabla-pro" responsiveLayout="scroll"
        :paginator="true" :rows="7">
        <Column header="Acciones">
          <template #body="slotProps">
            <Button icon="pi pi-pencil" class="p-button-warning p-button-sm btn-mini"
              @click="abrirModal('almacenes', 'actualizar', slotProps.data)" />
          </template>
        </Column>
        <Column field="nombre_almacen" header="Nombre del Almacén"></Column>
        <Column field="encargados_nombres" header="Encargado"></Column>
        <Column field="nombre_sucursal" header="Sucursal"></Column>
        <Column field="telefono" header="Teléfono">
          <template #body="slotProps">
            <span v-if="slotProps.data.telefono">
              {{ slotProps.data.telefono }}
            </span>
            <span v-else style="color: #d32f2f; display: flex; align-items: center; gap: 4px;">
              <i class="pi pi-exclamation-triangle" style="font-size: 1em;"></i>
              Teléfono no registrado
            </span>
          </template>
        </Column>
        <Column field="ubicacion" header="Ubicación">
          <template #body="slotProps">
            <span v-if="slotProps.data.ubicacion">
              {{ slotProps.data.ubicacion }}
            </span>
            <span v-else style="color: #d32f2f; display: flex; align-items: center; gap: 4px;">
              <i class="pi pi-exclamation-triangle" style="font-size: 1em;"></i>
              Ubicación no registrada
            </span>
          </template>
        </Column>
        <Column header="Observaciones">
          <template #body="slotProps">
            <Button icon="pi pi-eye" class="p-button-info p-button-sm btn-mini"
              style="background-color:#1976d2;border:none;" @click="verObservaciones(slotProps.data)" />
          </template>
        </Column>
      </DataTable>
    </Panel>

    <Dialog :visible.sync="dialogObservaciones" :modal="true" :closable="true"
      :containerStyle="{ width: '400px', maxWidth: '90vw', borderRadius: '16px', padding: '0' }"
      class="dialog-observaciones">
      <template #header>
        <div style="display:flex;align-items:center;gap:8px;">
          <i class="pi pi-eye" style="color:#1976d2;font-size:1.3rem;"></i>
          <span style="font-weight:600;font-size:1.1rem;">{{ observacionAlmacen }}</span>
        </div>
      </template>
      <div style="padding:1.5rem 1rem 1rem 1rem;">
        <div style="font-size:0.95rem;color:#555;">Observaciones:</div>
        <div
          style="margin-top:0.5rem;background:#f4f8fb;border-radius:8px;padding:1rem;min-height:60px;max-height:200px;overflow-y:auto;color:#222;font-size:1.05rem;white-space:pre-line;word-break:break-word;">
          {{ observacionTexto || 'Sin observaciones.' }}
        </div>
      </div>
      <template #footer>
        <Button label="Cerrar" icon="pi pi-times" @click="dialogObservaciones = false" class="p-button-danger btn-sm"
          style="background:#607d8b;border:none;" />
      </template>
    </Dialog>

    <Dialog :visible.sync="modal" :modal="true" :closable="false" :containerStyle="dialogContainerStyle"
      class="responsive-dialog">
      <template #header>
        <div class="dialog-header">
          <i class="pi pi-box header-icon"></i>
          <span class="header-title">{{ tituloModal }}</span>
        </div>
      </template>

      <form @submit.prevent="enviarFormulario">
        <div class="p-fluid p-formgrid p-grid form-compact">
          <div class="p-field p-col-12">
            <label for="nombreAlmacen" class="label-input">
              <span class="text-required">*</span>
              Nombre del almacén
            </label>
            <InputText id="nombreAlmacen" class="input-full" placeholder="Ej. Almacén Principal"
              v-model="datosFormulario.nombre_almacen" :class="{ 'input-error': errores.nombre_almacen }"
              @input="validarCampo('nombre_almacen')" required />
            <small v-if="errores.nombre_almacen" class="text-error">{{ errores.nombre_almacen }}</small>
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="sucursal" class="label-input">
              <span class="text-required">*</span>
              Sucursal
            </label>
            <AutoComplete class="p-inputtext-sm autocomplete-full" v-model="sucursalSeleccionado"
              :suggestions="arraySucursal" @complete="selectSucursal($event)" @item-select="getDatosSucursales"
              field="nombre" forceSelection :class="{ 'input-error': errores.sucursal }"
              placeholder="Buscar Sucursales..." required />
            <small v-if="errores.sucursal" class="text-error">{{ errores.sucursal }}</small>
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="ubicacion" class="optional-field">
              <i class="pi pi-info-circle optional-icon"></i>
              Ubicación
              <span class="optional-tag">Opcional</span>
            </label>
            <InputText id="ubicacion" class="input-full" placeholder="Ej. Calle 123, Ciudad"
              v-model="datosFormulario.ubicacion" />
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="encargados" class="label-input">
              <span class="text-required">*</span>
              Encargados
            </label>
            <AutoComplete v-model="usuarioSeleccionado" class="autocomplete-full" :suggestions="arrayUsuario"
              @complete="selectUsuario($event)" @item-select="actualizarEncargados" field="nombre"
              :class="{ 'input-error': errores.encargado }" placeholder="Buscar Usuarios..." required />
            <small v-if="errores.encargado" class="text-error">{{ errores.encargado }}</small>
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="telefono" class="optional-field">
              <i class="pi pi-info-circle optional-icon"></i>
              Teléfono de Contacto
              <span class="optional-tag">Opcional</span>
            </label>
            <InputText id="telefono" class="input-full" placeholder="Ej. 123456789"
              v-model="datosFormulario.telefono" />
          </div>

          <div class="p-field p-col-12">
            <label for="observaciones" class="optional-field">
              <i class="pi pi-info-circle optional-icon"></i>
              Observaciones
              <span class="optional-tag">Opcional</span>
            </label>
            <Textarea id="observaciones" class="textarea-full"
              placeholder="Ej. Horario de funcionamiento, Capacidad de almacenamiento, etc." rows="2"
              v-model="datosFormulario.observaciones" />
          </div>
        </div>
      </form>

      <template #footer>
        <Button label="Cerrar" icon="pi pi-times" @click="cerrarModal" class="p-button-danger btn-sm" />
        <Button v-if="tipoAccion == 1" label="Guardar" icon="pi pi-check" class="p-button-success btn-sm"
          @click="enviarFormulario" autofocus />
        <Button v-if="tipoAccion == 2" label="Actualizar" icon="pi pi-check" class="p-button-warning btn-sm"
          @click="enviarFormulario" autofocus />
      </template>
    </Dialog>
  </div>
</template>

<script>
import { esquemaAlmacen } from "../constants/validations";

import Panel from "primevue/panel";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import InputText from "primevue/inputtext";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import MultiSelect from "primevue/multiselect";
import InputNumber from "primevue/inputnumber";
import Textarea from "primevue/textarea";
import debounce from "lodash/debounce";
import AutoComplete from "primevue/autocomplete";
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
export default {
  components: {
    Panel,
    Button,
    Dropdown,
    InputText,
    DataTable,
    Column,
    Dialog,
    MultiSelect,
    InputNumber,
    Textarea,
    AutoComplete,
    ToastService,
    Toast,
  },
  data() {
    return {
      mostrarLabel: true,
      buscarDebounced: null,
      isLoading: false,
      criterioOptions: [
        { label: "Nombre Almacen", value: "nombre_almacen" },
        { label: "Nombre Encargado", value: "nombre_encargado" },
        { label: "Nombre Sucursal", value: "nombre_sucursal" },
      ],
      arraySucursal: [],
      sucursalSeleccionado: null,
      arrayUsuario: [],
      usuarioSeleccionado: null,
      datosFormulario: {
        nombre_almacen: "",
        ubicacion: "",
        encargado: "",
        telefono: null,
        sucursal: null,
        observaciones: "",
      },
      errores: {},
      arrayAlmacen: [],
      modal: false,
      tituloModal: "",
      tipoAccion: 0,
      criterio: "nombre_almacen",
      buscar: "",
      dialogObservaciones: false,
      observacionAlmacen: '',
      observacionTexto: '',
    };
  },
  computed: {
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
  created() {
    this.buscarDebounced = debounce(() => {
      this.listarAlmacenes(1, this.buscar, this.criterio);
    }, 200); // 500 ms de espera
  },
  watch: {
    buscar() {
      this.buscarDebounced();
    },
  },
  methods: {
    toastSuccess(mensaje) {
      this.$toast.add({
        severity: "success",
        summary: "Éxito",
        detail: mensaje,
        life: 2000,
      });
    },
    toastError(mensaje) {
      this.$toast.add({
        severity: "error",
        summary: "Error",
        detail: mensaje,
        life: 3500,
      });
    },
    verObservaciones(data) {
      this.observacionAlmacen = data.nombre_almacen || 'Almacén';
      this.observacionTexto = data.observacion || data.observaciones || '';
      this.dialogObservaciones = true;
    },
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    resetBusqueda() {
      this.buscar = "";
      this.listarAlmacenes(1, "", this.criterio);
    },
    selectUsuario(event) {
      let me = this;

      if (!event.query.trim().length) {
        var url = "/user/selectUser/filter?idrol=3";
        axios
          .get(url)
          .then(function (response) {
            var respuesta = response.data;
            me.arrayUsuario = respuesta.usuarios;
            me.loading = false;
          })
          .catch(function (error) {
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
          .then(function (response) {
            var respuesta = response.data;
            me.arrayUsuario = respuesta.usuarios;
            me.loading = false;
          })
          .catch(function (error) {
            console.log(error);
            me.loading = false;
          });
      }
    },
    getDatosUsuario(val) {
      this.datosFormulario.encargado = val.value && val.value.id ? val.value.id : null;
    },

    selectSucursal(event) {
      let me = this;

      if (!event.query.trim().length) {
        var url = "/sucursal/selectedSucursal/filter?filtro=";
        axios
          .get(url)
          .then(function (response) {
            var respuesta = response.data;
            me.arraySucursal = respuesta.sucursales;
            me.loading = false;
          })
          .catch(function (error) {
            console.log(error);
            me.loading = false;
          });
      } else {
        this.loading = true;

        var url =
          "/sucursal/selectedSucursal/filter?filtro=" + me.sucursalSeleccionado;
        axios
          .get(url)
          .then(function (response) {
            var respuesta = response.data;
            me.arraySucursal = respuesta.sucursales;
            me.loading = false;
          })
          .catch(function (error) {
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
    async validarCampo(campo) {
      try {
        await esquemaAlmacen.validateAt(campo, this.datosFormulario);
        this.errores[campo] = null;
      } catch (error) {
        this.errores[campo] = error.message;
      }
    },
    async enviarFormulario() {
      console.log(this.datosFormulario);
      try {
        await esquemaAlmacen.validate(this.datosFormulario, {
          abortEarly: false,
        });
        if (this.tipoAccion == 2) {
          this.actualizarAlmacen(this.datosFormulario);
        } else {
          this.registrarAlmacen(this.datosFormulario);
        }
      } catch (error) {
        const erroresValidacion = {};
        error.inner.forEach((e) => {
          erroresValidacion[e.path] = e.message;
        });
        this.errores = erroresValidacion;
        console.log("Errores de validación:", this.errores);
      }
    },
    async listarAlmacenes(page, buscar, criterio) {
      let me = this;
      try {
        var url =
          "/almacen?page=" +
          page +
          "&buscar=" +
          buscar +
          "&criterio=" +
          criterio;
        const response = await axios.get(url);
        me.arrayAlmacen = response.data.almacenes.data;
      } catch (error) {
        console.error("Error al listar almacenes:", error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudieron cargar los almacenes",
          life: 3000,
        });
      }
    },
    async registrarAlmacen(data) {
      let me = this;
      try {
        this.isLoading = true; // Activar loading
        await axios.post("/almacen/registrar", data);
        me.cerrarModal();
        await me.listarAlmacenes(1, "", "nombre_almacen");
        me.toastSuccess("Almacén registrado");
        // limpiar selects / arrays si aplica
        me.usuarioSeleccionado = null;
        me.arrayUsuario = [];
      } catch (error) {
        console.error("Error al registrar:", error);
        this.toastError("No se pudo registrar el almacén.");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    async actualizarAlmacen(data) {
      let me = this;
      try {
        this.isLoading = true; // Activar loading
        await axios.put("/almacen/editar", data);
        me.cerrarModal();
        await me.listarAlmacenes(1, "", "nombre_almacen");
        me.toastSuccess("Almacén actualizado");
        // limpiar selects / arrays si aplica
        me.usuarioSeleccionado = null;
        me.arrayUsuario = [];
      } catch (error) {
        console.error("Error al actualizar:", error);
        this.toastError("No se pudo actualizar el almacén.");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    cerrarModal() {
      this.modal = false;
      this.tituloModal = "";
      this.sucursalSeleccionado = null;
      this.usuarioSeleccionado = null;
      this.datosFormulario = {
        nombre_almacen: "",
        ubicacion: "",
        encargado: "",
        telefono: null,
        sucursal: null,
        observaciones: "",
      };
      this.errores = {};
    },
    async abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "almacenes": {
          switch (accion) {
            case "registrar": {
              this.modal = true;
              this.tituloModal = "REGISTRAR ALMACEN";
              this.tipoAccion = 1;
              break;
            }
            case "actualizar": {
              this.modal = true;
              this.tituloModal = "ACTUALIZAR ALMACEN";
              this.tipoAccion = 2;
              this.datosFormulario = { ...data };
              if (data.observacion !== undefined) {
                this.datosFormulario.observaciones = data.observacion;
              }

              // Sucursal
              const sucursalObj = { id: data.sucursal, nombre: data.nombre_sucursal };
              if (!this.arraySucursal.some(s => s.id === sucursalObj.id)) {
                this.arraySucursal = [...this.arraySucursal, sucursalObj];
              }
              this.sucursalSeleccionado = sucursalObj;

              // Encargado
              let encargadoObj = null;
              if (data.encargados && data.encargados.length > 0) {
                encargadoObj = { id: data.encargados[0].id, nombre: data.encargados[0].nombre };
              } else if (data.encargado && data.encargados_nombres) {
                encargadoObj = { id: data.encargado, nombre: data.encargados_nombres };
              }
              if (encargadoObj && !this.arrayUsuario.some(u => u.id === encargadoObj.id)) {
                this.arrayUsuario = [...this.arrayUsuario, encargadoObj];
              }
              this.usuarioSeleccionado = encargadoObj;
              break;
            }
          }
        }
      }
    },
  },
  async mounted() {
    this.handleResize();
    window.addEventListener("resize", this.handleResize);
    try {
      await this.listarAlmacenes(1, this.buscar, this.criterio);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
      this.$toast.add({
        severity: "error",
        summary: "Error",
        detail: "Error al cargar los datos iniciales",
        life: 3000,
      });
    }
  },
  beforeUnmount() {
    window.removeEventListener("resize", this.handleResize);
  },
};
</script>

<style scoped>
  
/* 🔹 Error */
.div-error {
  margin-top: 0.5rem;
}

.text-error {
  color: #dc2626;
  font-size: 0.8rem;
  background-color: #fee2e2;
  border: 1px solid #fecaca;
  border-radius: 6px;
  padding: 6px 10px;
  line-height: 1.2;
}

.text-error div+div {
  margin-top: 2px;
}

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
  min-height: 42px;
  /* misma altura mínima que Inputs */
  transition: border 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
  resize: vertical;
  /* permite redimensionar verticalmente */
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

.panel-header {
  display: flex;
  align-items: center;
}

.panel-title {
  margin: 0;
  padding-left: 5px;
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
}

.responsive-dialog>>>.p-dialog-header {
  padding: 0.75rem 1.5rem;
  font-size: 1.1rem;
}

.responsive-dialog>>>.p-dialog-footer {
  padding: 0.5rem 1.5rem;
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
}

>>>.p-fluid .p-field {
  margin-bottom: 0.25rem;
}

/* Estilos para campos obligatorios */
.label-input {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 4px;
}

.text-required {
  color: #e74c3c;
  font-size: 1rem;
  font-weight: bold;
  margin-right: 0.2rem;
}

/* 🔹 Label obligatorio */
.label-input {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 4px;
}

.text-required {
  color: #dc2626;
  /* rojo */
  font-weight: 700;
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

.optional-tag {
  background-color: #eff6ff;
  color: #2563eb;
  font-size: 0.7rem;
  border-radius: 4px;
  padding: 0.1rem 0.3rem;
  margin-left: 4px;
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
>>>.p-formgrid.p-grid {
  margin: 0;
}

>>>.p-formgrid .p-field {
  padding: 0.5rem;
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

  >>>.p-formgrid .p-field.p-col-12.p-md-6 {
    width: 100% !important;
    flex: 0 0 100% !important;
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
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.5rem 1rem;
    font-size: 1rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.4rem 1rem;
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

  >>>.p-formgrid .p-field {
    padding: 0.25rem;
    margin-bottom: 0.4rem !important;
  }

  >>>.p-formgrid .p-field label {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
  }

  /* Ajustar iconos en móviles */
  .text-required {
    font-size: 0.8rem;
  }

  .optional-icon {
    font-size: 0.6rem;
  }

  >>>.p-inputtext,
  >>>.p-dropdown,
  >>>.p-inputnumber-input,
  >>>.p-multiselect,
  >>>.p-inputtextarea {
    font-size: 0.9rem;
    padding: 0.5rem;
  }

  >>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }

  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  .search-bar .p-inputtext-sm {
    padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
    font-size: 0.85rem !important;
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
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.4rem 0.75rem;
    font-size: 0.95rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.3rem 0.75rem;
    justify-content: flex-end;
  }

  .responsive-dialog>>>.p-dialog-footer .p-button {
    width: auto;
    margin-bottom: 0.25rem;
  }

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

  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

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

  >>>.p-formgrid .p-field {
    padding: 0.2rem;
    margin-bottom: 0.3rem !important;
  }

  >>>.p-formgrid .p-field label {
    font-size: 0.85rem;
  }

  .text-required {
    font-size: 0.7rem;
  }

  .optional-icon {
    font-size: 0.55rem;
  }

  >>>.p-inputtext,
  >>>.p-dropdown,
  >>>.p-inputnumber-input,
  >>>.p-multiselect,
  >>>.p-inputtextarea {
    font-size: 0.85rem;
    padding: 0.4rem;
  }

  >>>.p-tag {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
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

.p-dialog-mask {
  z-index: 9990 !important;
}

.p-dialog {
  z-index: 9990 !important;
}
</style>