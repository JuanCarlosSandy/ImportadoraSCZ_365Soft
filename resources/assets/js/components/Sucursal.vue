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
          <i class="pi pi-bars panel-icon"></i>
          <h4 class="panel-title">SUCURSALES</h4>
        </div>
      </template>

      <div class="toolbar-container">
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText v-model="buscar" placeholder="Buscar sucursal..." style="width: 100%;" @input="onBuscarInput"
              class="p-inputtext-sm" />
          </span>
        </div>
        <div class="toolbar">
          <Button :label="mostrarLabel ? 'Reset' : ''" icon="pi pi-refresh" @click="resetBusqueda"
            class="p-button-help p-button-sm" />
          <!--<Button
            :label="mostrarLabel ? 'Nuevo' : ''"
            icon="pi pi-plus"
            @click="abrirModal('sucursal', 'registrar')"
            class="p-button-secondary p-button-sm"
          />-->
        </div>
      </div>

      <div>
        <DataTable :value="arraySucursal" responsiveLayout="scroll" class="p-datatable-gridlines p-datatable-sm"
          :rows="5" :responsive="true">

          <Column field="nombre" header="Nombre sucursal"></Column>

          <!-- columnas que se ocultan en mobile -->
          <Column v-if="mostrarLabel" field="direccion" header="Dirección"></Column>
          <Column v-if="mostrarLabel" field="correo" header="Correo"></Column>
          <Column v-if="mostrarLabel" field="telefono" header="Teléfono"></Column>

          <Column field="departamento" header="Departamento"></Column>
          <Column header="Estado">
            <template #body="slotProps">
              <Tag :severity="slotProps.data.condicion ? 'success' : 'danger'"
                :value="slotProps.data.condicion ? 'Activo' : 'Desactivado'" class="tag-mini" />
            </template>
          </Column>

          <Column header="Acciones">
            <template #body="slotProps">
              <!-- Botones comunes (aparecen siempre) -->
              <Button icon="pi pi-pencil" class="p-button-warning p-button-sm btn-mini"
                @click="abrirModal('sucursal', 'actualizar', slotProps.data)"  v-tooltip.top="'Editar'"/>
              <Button v-if="slotProps.data.condicion" icon="pi pi-trash" class="p-button-danger p-button-sm btn-mini"
                @click="desactivarSucursal(slotProps.data.id)"  v-tooltip.top="'Desactivar'"/>
              <Button v-else icon="pi pi-check" class="p-button-info p-button-sm btn-mini"
                @click="activarSucursal(slotProps.data.id)"  v-tooltip.top="'Activar'"/>

              <!-- Botón adicional SOLO visible en móvil -->
              <Button v-if="!mostrarLabel" icon="pi pi-info-circle" class="p-button-help p-button-sm btn-mini"
                @click="mostrarDetalleSucursal(slotProps.data)" />
            </template>
          </Column>
        </DataTable>
        <Paginator :rows="10" :totalRecords="pagination.total" @page="onPageChange($event)"></Paginator>
      </div>
    </Panel>

<Dialog
  :visible.sync="modal"
  :modal="true"
  :closable="false"
  :draggable="false"
  :dismissableMask="true"
  :containerStyle="{ width: '95vw', maxWidth: '650px', borderRadius: '14px' }"
>
  <template #header>
    <div class="dialog-header">
      <i class="pi pi-building dialog-icon"></i>
      <h3>{{ tituloModal }}</h3>
    </div>
  </template>

  <form @submit.prevent="enviarFormulario" class="dialog-body">
    <div class="p-fluid p-formgrid p-grid">
      <div class="p-field p-col-12 p-md-8">
        <label for="nombre">Nombre de la sucursal <span class="required">*</span></label>
        <InputText
          id="nombre"
          v-model="datosFormulario.nombre"
          :class="{ 'p-invalid': errores.nombre }"
          @input="validarCampo('nombre')"
        />
        <small v-if="errores.nombre" class="p-error">{{ errores.nombre }}</small>
      </div>

      <div class="p-field p-col-12 p-md-6">
        <label for="correo">Correo electrónico</label>
        <InputText
          id="correo"
          v-model="datosFormulario.correo"
          :class="{ 'p-invalid': errores.correo }"
          @input="validarCampo('correo')"
        />
        <small v-if="errores.correo" class="p-error">{{ errores.correo }}</small>
      </div>

      <div class="p-field p-col-12 p-md-6">
        <label for="telefono">Teléfono</label>
        <InputNumber
          id="telefono"
          v-model="datosFormulario.telefono"
          :class="{ 'p-invalid': errores.telefono }"
          @input="validarCampo('telefono')"
        />
        <small v-if="errores.telefono" class="p-error">{{ errores.telefono }}</small>
      </div>

      <div class="p-field p-col-12 p-md-6">
        <label for="direccion">Dirección</label>
        <InputText
          id="direccion"
          v-model="datosFormulario.direccion"
          :class="{ 'p-invalid': errores.direccion }"
          @input="validarCampo('direccion')"
        />
        <small v-if="errores.direccion" class="p-error">{{ errores.direccion }}</small>
      </div>

      <div class="p-field p-col-12 p-md-6">
        <label for="departamento">Departamento</label>
        <Dropdown
          id="departamento"
          v-model="datosFormulario.departamento"
          :options="arrayDepartamentos"
          placeholder="Seleccione"
          :class="{ 'p-invalid': errores.departamento }"
          @change="validarCampo('departamento')"
        />
        <small v-if="errores.departamento" class="p-error">{{ errores.departamento }}</small>
      </div>
    </div>

    <div class="codigo-sucursal" v-if="tipoAccion === 1 || tipoAccion === 2">
      <i class="pi pi-qrcode"></i>
      <strong>Código de Sucursal:</strong>
      <span>{{ tipoAccion === 1 ? codigoSucursal : datosFormulario.codigoSucursal }}</span>
    </div>
  </form>

  <template #footer>
    <div class="dialog-footer">
      <Button
        label="Cerrar"
        icon="pi pi-times"
        class="p-button-danger p-button-sm"
        @click="cerrarModal"
      />
      <Button
        v-if="tipoAccion === 1"
        label="Guardar"
        icon="pi pi-check"
        class="p-button-success p-button-sm"
        @click="enviarFormulario"
        autofocus
      />
      <Button
        v-if="tipoAccion === 2"
        label="Actualizar"
        icon="pi pi-check"
        class="p-button-warning p-button-sm"
        @click="enviarFormulario"
        autofocus
      />
    </div>
  </template>
</Dialog>


   <Dialog
  :visible.sync="dialogDetalle"
  header="Detalles de la Sucursal"
  :modal="true"
  :closable="false"
  :draggable="false"
  :dismissableMask="true"
  :containerStyle="{ width: '90vw', maxWidth: '420px', borderRadius: '12px' }"
>
  <div class="dialog-detalle">
    <div class="detalle-item">
      <i class="pi pi-map-marker icono"></i>
      <div>
        <label>Dirección</label>
        <p>{{ sucursalSeleccionada.direccion || '—' }}</p>
      </div>
    </div>

    <div class="detalle-item">
      <i class="pi pi-envelope icono"></i>
      <div>
        <label>Correo</label>
        <p>{{ sucursalSeleccionada.correo || '—' }}</p>
      </div>
    </div>

    <div class="detalle-item">
      <i class="pi pi-phone icono"></i>
      <div>
        <label>Teléfono</label>
        <p>{{ sucursalSeleccionada.telefono || '—' }}</p>
      </div>
    </div>
  </div>

  <template #footer>
    <Button
      label="Cerrar"
      icon="pi pi-times"
      class="p-button-danger p-button-sm"
      @click="dialogDetalle = false"
    />
  </template>
</Dialog>
  </main>
</template>

<script>
import { esquemaSucursal } from "../constants/validations";
import Breadcrumb from "primevue/breadcrumb";
import Panel from "primevue/panel";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Dropdown from "primevue/dropdown";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import Paginator from "primevue/paginator";
import Tag from "primevue/tag";
import InputNumber from "primevue/inputnumber";
import Tooltip from 'primevue/tooltip';
export default {
  components: {
    Breadcrumb,
    Panel,
    Button,
    InputText,
    Dropdown,
    DataTable,
    Column,
    Dialog,
    Paginator,
    Tag,
    InputNumber,
  },
  directives: {
    'tooltip': Tooltip
  },
  data() {
    return {
      dialogDetalle: false,
      sucursalSeleccionada: {},
      mostrarLabel: true,
      isLoading: false,
      // criterioOptions eliminado
      datosFormulario: {
        nombre: "",
        direccion: "",
        correo: "",
        telefono: "",
        departamento: "",
      },
      errores: {},

      codigoSucursal: 1,
      arraySucursal: [],
      arrayDepartamentos: [
        "Beni",
        "Chuquisaca",
        "Cochabamba",
        "La Paz",
        "Oruro",
        "Pando",
        "Potosí",
        "Santa Cruz",
        "Tarija",
      ],
      modal: 0,
      tituloModal: "",
      tipoAccion: 0,
      pagination: {
        total: 0,
        current_page: 0,
        per_page: 0,
        last_page: 0,
        from: 0,
        to: 0,
      },
      offset: 3,
      // criterio eliminado
      searchTimeout: null,
      arrayEmpresa: [],
      buscar: "",
    };
  },
  computed: {
    isActived: function () {
      return this.pagination.current_page;
    },
    pagesNumber: function () {
      if (!this.pagination.to) {
        return [];
      }

      var from = this.pagination.current_page - this.offset;
      if (from < 1) {
        from = 1;
      }

      var to = from + this.offset * 2;
      if (to >= this.pagination.last_page) {
        to = this.pagination.last_page;
      }

      var pagesArray = [];
      while (from <= to) {
        pagesArray.push(from);
        from++;
      }
      return pagesArray;
    },
  },

  methods: {
    mostrarDetalleSucursal(data) {
      this.sucursalSeleccionada = data;
      this.dialogDetalle = true;
    },
    resetBusqueda() {
      this.buscar = "";
      this.listarSucursal(1, this.buscar);
    },
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
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
    async validarCampo(campo) {
      try {
        await esquemaSucursal.validateAt(campo, this.datosFormulario);
        this.errores[campo] = null;
      } catch (error) {
        this.errores[campo] = error.message;
      }
    },
    async enviarFormulario() {
      await esquemaSucursal
        .validate(this.datosFormulario, { abortEarly: false })
        .then(() => {
          if (this.tipoAccion == 2) {
            this.actualizarSucursal(this.datosFormulario);
          } else {
            this.datosFormulario.codigoSucursal = this.codigoSucursal;
            this.registrarSucursal(this.datosFormulario);
          }
        })
        .catch((error) => {
          const erroresValidacion = {};
          error.inner.forEach((e) => {
            erroresValidacion[e.path] = e.message;
          });

          this.errores = erroresValidacion;
        });
    },

    async listarSucursal(page, buscar) {
      let me = this;
      try {
        this.isLoading = true; // Activar loading
        const url = `/sucursal?page=${page}&buscar=${buscar}`;
        const response = await axios.get(url);
        const respuesta = response.data;
        me.arraySucursal = respuesta.sucursales.data;
        me.pagination = respuesta.pagination;
      } catch (error) {
        console.error("Error al listar sucursales:", error);
        swal("Error", "No se pudieron cargar las sucursales", "error");
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },
    onBuscarInput() {
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout);
      }
      this.searchTimeout = setTimeout(() => {
        this.listarSucursal(1, this.buscar);
      }, 300);
    },
    cambiarPagina(page, buscar) {
      let me = this;
      me.pagination.current_page = page;
      me.listarSucursal(page, buscar);
    },

    async obtenerUltimoCodigoSucursal() {
      try {
        this.isLoading = true; // Activar loading
        const response = await axios.get(
          "/ruta-api-para-obtener-ultimo-codigo-sucursal"
        );
        const ultimoCodigo = response.data.ultimoCodigo;
        this.codigoSucursal = ultimoCodigo + 1;
      } catch (error) {
        console.error("Error al obtener el último código de sucursal:", error);
        swal("Error", "No se pudo obtener el código de sucursal", "error");
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },

    async registrarSucursal(datos) {
      try {
        this.isLoading = true; // Activar loading
        await axios.post("/sucursal/registrar", datos);
        this.toastSuccess("Sucursal Registrada");
        this.cerrarModal();
        await this.listarSucursal(1, "", "nombre");
      } catch (error) {
        console.error("Error al registrar:", error);
        swal(
          "ERROR AL REGISTRAR LA NUEVA SUCURSAL",
          "Intente de nuevo",
          "error"
        );
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },

    async actualizarSucursal(datos) {
      try {
        this.isLoading = true; // Activar loading
        await axios.put("/sucursal/actualizar", datos);
        this.toastSuccess("Sucursal actualizada correctamente");

        this.cerrarModal();
        await this.listarSucursal(1, this.buscar);
      } catch (error) {
        console.error("Error al actualizar:", error);
        swal(
          "ERROR AL ACTUALIZAR LA NUEVA SUCURSAL",
          "Intente de nuevo",
          "error"
        );
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    async desactivarSucursal(id) {
      try {
        const result = await swal({
          title: "Esta seguro de desactivar esta sucursal?",
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
        });

        if (result.value) {
          this.isLoading = true; // Activar loading
          await axios.put("/sucursal/desactivar", { id: id });
          await this.listarSucursal(1, this.buscar);;

          this.toastSuccess("Sucursal desactivada correctamente");
        }
      } catch (error) {
        console.error("Error al desactivar:", error);
        swal("Error", "No se pudo desactivar la sucursal", "error");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    async activarSucursal(id) {
      try {
        const result = await swal({
          title: "Esta seguro de activar esta sucursal?",
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
        });

        if (result.value) {
          this.isLoading = true; // Activar loading
          await axios.put("/sucursal/activar", { id: id });
          await this.listarSucursal(1, this.buscar);;

          this.toastSuccess("Activacion exitosa");
        }
      } catch (error) {
        console.error("Error al activar:", error);
        swal("Error", "No se pudo activar la sucursal", "error");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    cerrarModal() {
      this.modal = 0;
      this.tituloModal = "";
      this.errores = {};
    },
    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "sucursal": {
          switch (accion) {
            case "registrar": {
              this.modal = 1;
              this.tituloModal = "REGISTRAR SUCURSAL";
              (this.datosFormulario = {
                nombre: "",
                direccion: "",
                correo: "",
                telefono: "",
                departamento: "",
              }),
                (this.tipoAccion = 1);
              this.obtenerUltimoCodigoSucursal();

              break;
            }
            case "actualizar": {
              this.modal = 1;
              this.tituloModal = "ACTUALIZAR SUCURSAL";
              this.tipoAccion = 2;
              this.datosFormulario = {
                id: data["id"],
                idempresa: data["idempresa"],
                nombre: data["nombre"],
                direccion: data["direccion"],
                correo: data["correo"],
                telefono: data["telefono"],
                departamento: data["departamento"],
                codigoSucursal: data["codigoSucursal"],
              };
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
      this.isLoading = true; // Activar loading
      await this.listarSucursal(1, this.buscar);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
      swal("Error", "Error al cargar los datos iniciales", "error");
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
.dialog-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.dialog-header h3 {
  font-weight: 600;
  color: #1f2937;
  font-size: 1.25rem;
  margin: 0;
}

.dialog-icon {
  font-size: 1.5rem;
  color: #3b82f6;
}

.dialog-body label {
  font-weight: 600;
  color: #374151;
  font-size: 0.85rem;
}

.dialog-body .required {
  color: #ef4444;
  font-weight: bold;
}

.codigo-sucursal {
  margin-top: 1rem;
  background: #f3f4f6;
  padding: 0.75rem;
  border-radius: 8px;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #374151;
}

.codigo-sucursal i {
  color: #3b82f6;
  font-size: 1.1rem;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}
.dialog-detalle {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 0.5rem 0;
}

.detalle-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 0.75rem;
  transition: box-shadow 0.2s ease;
}

.detalle-item:hover {
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.detalle-item label {
  font-weight: 600;
  color: #374151;
  font-size: 0.85rem;
}

.detalle-item p {
  margin: 0.1rem 0 0 0;
  color: #4b5563;
  font-size: 0.9rem;
  word-break: break-word;
}

.icono {
  font-size: 1.4rem;
  color: #3b82f6;
  margin-top: 0.1rem;
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
  font-size: 0.78rem;
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

.modal-footer-buttons {
  padding-top: 1rem;
}
</style>
