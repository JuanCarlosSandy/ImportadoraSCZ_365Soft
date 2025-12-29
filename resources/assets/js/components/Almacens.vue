<template>
  <div class="main">
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

      <DataTable :value="arrayAlmacen" class="p-datatable-sm p-datatable-gridlines" responsiveLayout="scroll"
        :paginator="true" :rows="7">
        <Column header="Acciones">
          <template #body="slotProps">
            <Button icon="pi pi-pencil" class="p-button-warning p-button-sm"
              style="padding: 0.3rem 0.4rem; font-size: 0.75rem; width: auto; min-width: unset;"
              @click="abrirModal('almacenes', 'actualizar', slotProps.data)" v-tooltip.top="'Editar'"/>
          </template>
        </Column>
        <Column field="nombre_almacen" header="Nombre del Almacén"></Column>
        <Column field="ubicacion" header="Dirección (Ubicación)"></Column>
        <Column field="encargados_nombres" header="Encargado"></Column>
        <Column field="telefono" header="Teléfono"></Column>
        <Column field="nombre_sucursal" header="Sucursal"></Column>
        <Column header="Observaciones">
          <template #body="slotProps">
            <Button icon="pi pi-eye" class="p-button-info p-button-sm" style="background-color:#1976d2;border:none;" @click="verObservaciones(slotProps.data)" />
          </template>
        </Column>
      </DataTable>
    </Panel>

    <Dialog :visible.sync="dialogObservaciones" :modal="true" :closable="true" :containerStyle="{ width: '400px', maxWidth: '90vw', borderRadius: '16px', padding: '0' }" class="dialog-observaciones">
      <template #header>
        <div style="display:flex;align-items:center;gap:8px;">
          <i class="pi pi-eye" style="color:#1976d2;font-size:1.3rem;"></i>
          <span style="font-weight:600;font-size:1.1rem;">{{ observacionAlmacen }}</span>
        </div>
      </template>
      <div style="padding:1.5rem 1rem 1rem 1rem;">
        <div style="font-size:0.95rem;color:#555;">Observaciones:</div>
        <div style="margin-top:0.5rem;background:#f4f8fb;border-radius:8px;padding:1rem;min-height:60px;max-height:200px;overflow-y:auto;color:#222;font-size:1.05rem;white-space:pre-line;word-break:break-word;">
          {{ observacionTexto || 'Sin observaciones.' }}
        </div>
      </div>
      <template #footer>
        <Button label="Cerrar" icon="pi pi-times" @click="dialogObservaciones=false" class="p-button-secondary p-button-sm" style="background:#607d8b;border:none;" />
      </template>
    </Dialog>

    <Dialog :visible.sync="modal" :modal="true" :closable="false" :containerStyle="dialogContainerStyle"
      class="responsive-dialog">
      <template #header>
        <h3>{{ tituloModal }}</h3>
      </template>

      <form @submit.prevent="enviarFormulario">
        <div class="p-fluid p-formgrid p-grid form-compact">
          <div class="p-field p-col-12 p-md-6">
            <label for="nombre_almacen" class="required-field">
              <span class="required-icon">*</span>
              Nombre del almacén
            </label>
            <InputText id="nombre_almacen" v-model="datosFormulario.nombre_almacen"
              :class="{ 'p-invalid': errores.nombre_almacen }" @input="validarCampo('nombre_almacen')" />
            <small class="p-error" v-if="errores.nombre_almacen">{{
              errores.nombre_almacen
            }}</small>
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="ubicacion" class="required-field">
              <span class="required-icon">*</span>
              Ubicación
            </label>
            <InputText id="ubicacion" v-model="datosFormulario.ubicacion" :class="{ 'p-invalid': errores.ubicacion }"
              @input="validarCampo('ubicacion')" />
            <small class="p-error" v-if="errores.ubicacion">{{
              errores.ubicacion
            }}</small>
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="encargado" class="required-field">
              <span class="required-icon">*</span>
              Encargado
            </label>
            <Dropdown v-model="usuarioSeleccionado" :options="arrayUsuario" optionLabel="nombre"
              placeholder="Buscar encargado..." :filter="true" :showClear="true" @filter="onFilterUsuarios"
              @change="getDatosUsuario" />
            <small class="p-error" v-if="errores.encargado">{{
              errores.encargado
            }}</small>
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="telefono" class="required-field">
              <span class="required-icon">*</span>
              Teléfono
            </label>
            <InputNumber id="telefono" v-model="datosFormulario.telefono" :class="{ 'p-invalid': errores.telefono }"
              @input="validarCampo('telefono')" />
            <small class="p-error" v-if="errores.telefono">{{
              errores.telefono
            }}</small>
          </div>
          <div class="p-field p-col-12 p-md-6">
            <label for="sucursal" class="required-field">
              <span class="required-icon">*</span>
              Sucursal
            </label>
            <Dropdown v-model="sucursalSeleccionado" :options="arraySucursal" optionLabel="nombre"
              placeholder="Buscar Sucursales..." :class="{ 'p-invalid': errores.sucursal }" @change="getDatosSucursales"
              :filter="true" @filter="onFilterSucursales" />
            <small class="p-error" v-if="errores.sucursal">{{
              errores.sucursal
            }}</small>
          </div>
          <div class="p-field p-col-12">
            <label for="observaciones" class="optional-field">
              <i class="pi pi-info-circle optional-icon"></i>
              Observaciones
              <span class="p-tag p-tag-secondary">Opcional</span>
            </label>
            <Textarea id="observaciones" v-model="datosFormulario.observaciones" rows="3" />
          </div>
        </div>
      </form>

      <template #footer>
        <Button label="Cerrar" icon="pi pi-times" @click="cerrarModal" class="p-button-danger p-button-sm" />
        <Button v-if="tipoAccion == 1" label="Guardar" class="p-button-success p-button-sm" @click="enviarFormulario"
          autofocus />
        <Button v-if="tipoAccion == 2" label="Actualizar" class="p-button-warning p-button-sm" @click="enviarFormulario"
          autofocus />
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
import Tooltip from 'primevue/tooltip';
import debounce from "lodash/debounce";

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
  },directives: {
    'tooltip': Tooltip
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
    verObservaciones(data) {
      this.observacionAlmacen = data.nombre_almacen || 'Almacén';
      this.observacionTexto = data.observacion || data.observaciones || '';
      this.dialogObservaciones = true;
    },
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
    resetBusqueda() {
      this.buscar = "";
      this.listarAlmacenes(1, "", this.criterio);
    },
    onFilterUsuarios(event) {
      this.selectUsuario(event.value);
    },
    async selectUsuario(search) {
      let me = this;
      try {
        this.isLoading = true; // Activar loading
        const url = "/user/selectUser/filter?filtro=" + search + "&idrol=3";
        const response = await axios.get(url);
        me.arrayUsuario = response.data.usuarios;
      } catch (error) {
        console.error("Error al cargar usuarios:", error);
        me.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudieron cargar los usuarios",
          life: 3000,
        });
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },
    getDatosUsuario(val) {
      this.datosFormulario.encargado = val.value && val.value.id ? val.value.id : null;
    },
    onFilterSucursales(event) {
      this.selectSucursal(event.value);
    },
    async selectSucursal(search) {
      let me = this;
      try {
        this.isLoading = true; // Activar loading
        const url = "/sucursal/selectedSucursal/filter?filtro=" + search;
        const response = await axios.get(url);
        me.arraySucursal = response.data.sucursales;
      } catch (error) {
        console.error("Error al cargar sucursales:", error);
        me.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudieron cargar las sucursales",
          life: 3000,
        });
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },
    getDatosSucursales(val) {
      console.log(val.value.id);
      this.datosFormulario.sucursal =
        val.value && val.value.id ? val.value.id : null;
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
      } catch (error) {
        console.error("Error al registrar:", error);
        me.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo registrar el almacén",
          life: 3000,
        });
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
      } catch (error) {
        console.error("Error al actualizar:", error);
        me.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo actualizar el almacén",
          life: 3000,
        });
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
.required-field {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-weight: 600;
  color: #2c3e50;
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
  align-items: center;
  gap: 0.4rem;
  font-weight: 500;
  color: #6c757d;
}

.optional-icon {
  color: #17a2b8;
  font-size: 0.8rem;
}

/* DataTable Responsive */
>>>.p-datatable {
  font-size: 0.9rem;
}

>>>.p-datatable .p-datatable-tbody>tr>td {
  padding: 0.5rem;
  word-break: break-word;
  text-align: left;
}

>>>.p-datatable .p-datatable-thead>tr>th {
  padding: 0.75rem 0.5rem;
  font-size: 0.85rem;
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
  .required-icon {
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

  .required-icon {
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