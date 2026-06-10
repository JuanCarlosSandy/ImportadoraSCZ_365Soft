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
          <h4 class="panel-title">PROVEEDORES</h4>
        </div>
      </template>
      <div class="info-tip">
        <i class="pi pi-info-circle"></i>
        <span>
          Filtre el nombre, documento o teléfono del proveedor, edite y registre nuevos proveedores.
        </span>
      </div>
      <div class="toolbar-container">
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText class="p-inputtext-sm" v-model="buscar" placeholder="Texto a buscar"
              @input="buscarProveedores" />
          </span>
        </div>
        <div class="toolbar">
          <Button :label="mostrarLabel ? 'Nuevo' : ''" icon="pi pi-plus" @click="abrirModal('persona', 'registrar')"
            class="p-button-secondary p-button-sm" />
          <!--<Button
            :label="mostrarLabel ? 'Importar' : ''"
            icon="pi pi-upload"
            @click="abrirModalImportar()"
            class="p-button-success p-button-sm"
          />-->
        </div>
      </div>

      <DataTable :value="arrayPersona" :paginator="true" :rows="25" :totalRecords="pagination.total" :lazy="true"
        @page="onPageChange" responsiveLayout="scroll" class="p-datatable-gridlines p-datatable-sm">
        <Column header="Acciones">
          <template #body="slotProps">
            <Button icon="pi pi-pencil" class="p-button-warning p-button-sm"
              style="padding: 0.3rem 0.4rem; font-size: 0.75rem; width: auto; min-width: unset;"
              @click="abrirModal('persona', 'actualizar', slotProps.data)" v-tooltip.top="'Editar'" />
            <Button icon="pi pi-trash" class="p-button-danger p-button-sm"
              style="padding: 0.3rem 0.4rem; font-size: 0.75rem; width: auto; min-width: unset; margin-left: 5px;"
              @click="desactivarProveedor(slotProps.data)" v-tooltip.top="'Eliminar'" />
          </template>
        </Column>
        <Column field="nombre" header="Nombre Proveedor"></Column>
        <Column field="documento" header="Documento">
          <template #body="slotProps">
            {{ getDocumento(slotProps.data) }}
          </template>
        </Column>
        <Column field="telefono" header="Teléfono">
          <template #body="slotProps">
            <span v-if="slotProps.data.telefono" class="text-success">
              {{ slotProps.data.telefono }}
            </span>
            <span v-else class="datos-no-registrados">
              <i class="pi pi-exclamation-triangle"></i>
              Datos no registrados
            </span>
          </template>
        </Column>

        <!-- Columnas de Contacto - Ocultas para rol Vendedor
        <Column v-if="!esVendedor" field="contacto" header="Contacto">
          <template #body="slotProps">
            <span v-if="slotProps.data.contacto" class="text-success">
              {{ slotProps.data.contacto }}
            </span>
            <span v-else class="datos-no-registrados">
              <i class="pi pi-exclamation-triangle"></i>
              Datos no registrados
            </span>
          </template>
        </Column>
        
        <Column v-if="!esVendedor" field="telefono_contacto" header="Teléfono de Contacto">
          <template #body="slotProps">
            <span v-if="slotProps.data.telefono_contacto" class="text-success">
              {{ slotProps.data.telefono_contacto }}
            </span>
            <span v-else class="datos-no-registrados">
              <i class="pi pi-exclamation-triangle"></i>
              Datos no registrados
            </span>
          </template>
        </Column>
         -->
      </DataTable>
    </Panel>

    <Dialog :visible.sync="modal" :containerStyle="{ width: '800px' }" :modal="true" :closable="true">
      <template #header>
        <h3>{{ tituloModal }}</h3>
      </template>

      <form @submit.prevent="enviarFormulario">
        <div class="p-fluid p-formgrid p-grid">
          <div class="p-field p-col-12 p-md-6">
            <label for="nombre" class="required-field">
              Nombre del Proveedor
              <span class="required-icon">*</span>
            </label>
            <InputText id="nombre" v-model="datosFormulario.nombre" :class="{ 'p-invalid': errores.nombre }"
              @input="validarCampo('nombre')" />
            <small class="p-error" v-if="errores.nombre">{{
              errores.nombre
            }}</small>
          </div>

          <div class="p-field p-col-12 p-md-6">
            <label for="tipo_documento">
              Tipo de documento
              <span class="p-tag p-tag-secondary">Opcional</span>
            </label>
            <Dropdown id="tipo_documento" v-model="datosFormulario.tipo_documento" :options="tiposDocumentos"
              optionLabel="etiqueta" optionValue="valor" placeholder="Selecciona un tipo de documento"
              :class="{ 'p-invalid': errores.tipo_documento }" @change="validarCampo('tipo_documento')" />
          </div>

          <div class="p-field p-col-12 p-md-6">
            <label for="num_documento">
              Nro de documento
              <span class="p-tag p-tag-secondary">Opcional</span>
            </label>
            <InputText id="num_documento" v-model="datosFormulario.num_documento"
              :class="{ 'p-invalid': errores.num_documento }" @input="validarCampo('num_documento')" />
          </div>

          <div class="p-field p-col-12 p-md-6">
            <label for="telefono">
              Teléfono
              <span class="p-tag p-tag-secondary">Opcional</span>
            </label>
            <InputNumber id="telefono" v-model="datosFormulario.telefono" :useGrouping="false"
              :class="{ 'p-invalid': errores.telefono }" @input="validarCampo('telefono')" />
          </div>

          <!-- CAMPOS AGREGADOS: Contacto y Teléfono de Contacto 
          <div class="p-field p-col-12 p-md-6">
            <label for="contacto">
              Contacto
              <span class="p-tag p-tag-secondary">Opcional</span>
            </label>
            <InputText
              id="contacto"
              v-model="datosFormulario.contacto"
              placeholder="Nombre de la persona de contacto"
            />
          </div>

          <div class="p-field p-col-12 p-md-6">
            <label for="telefono_contacto">
              Teléfono de Contacto
              <span class="p-tag p-tag-secondary">Opcional</span>
            </label>
            <InputNumber
              id="telefono_contacto"
              v-model="datosFormulario.telefono_contacto"
              placeholder="Teléfono del contacto"
              :useGrouping="false"
            />
          </div>
          -->
        </div>
      </form>

      <template #footer>
        <div class="d-flex gap-2 justify-content-end modal-footer-buttons">
          <Button label="Cerrar" icon="pi pi-times" class="p-button-danger p-button-sm" @click="cerrarModal" />
          <Button v-if="tipoAccion === 1" label="Guardar" icon="pi pi-check" class="p-button-success p-button-sm"
            @click="enviarFormulario" />
          <Button v-if="tipoAccion === 2" label="Actualizar" icon="pi pi-check" class="p-button-warning p-button-sm"
            @click="enviarFormulario" />
        </div>
      </template>
    </Dialog>

    <div v-if="modalImportar">
      <ImportarExcelProvedores @cerrar="cerrarModalImportar" />
    </div>
  </div>
</template>

<script>
import { esquemaProveedor } from "../constants/validations";

import Panel from "primevue/panel";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import Dropdown from "primevue/dropdown";
import InputNumber from "primevue/inputnumber";
import Toast from "primevue/toast";
import ToastService from "primevue/toastservice";
import Tooltip from 'primevue/tooltip';
export default {
  components: {
    Panel,
    Button,
    InputText,
    DataTable,
    Column,
    Dialog,
    Dropdown,
    InputNumber,
    Toast,
    ToastService,
  },
  directives: {
    'tooltip': Tooltip
  },
  data() {
    return {
      mostrarLabel: true,
      isLoading: false,
      searchTimeout: null,
      tiposDocumento: {
        "1": "CI",
        "2": "CEX",
        "3": "PAS",
        "4": "OD",
        "5": "NIT",
      },
      datosFormulario: {
        nombre: "",
        documento: "",
        tipo_documento: "",
        num_documento: "",
        direccion: "",
        telefono: "",
        email: "",
        contacto: "",
        telefono_contacto: "",
      },
      errores: {},
      tiposDocumentos: [
        { valor: "1", etiqueta: "CI - CEDULA DE IDENTIDAD" },
        { valor: "2", etiqueta: "CEX - CEDULA DE IDENTIDAD DE EXTRANJERO" },
        { valor: "5", etiqueta: "NIT - NÚMERO DE IDENTIFICACIÓN TRIBUTARIA" },
        { valor: "3", etiqueta: "PAS - PASAPORTE" },
        { valor: "4", etiqueta: "OD - OTRO DOCUMENTO DE IDENTIDAD" },
      ],
      persona_id: 0,
      arrayPersona: [],
      modal: false,
      tituloModal: "",
      tipoAccion: 0,
      errorPersona: 0,
      errorMostrarMsjPersona: [],
      pagination: {
        total: 0,
        current_page: 1,
        last_page: 0,
      },
      offset: 3,
      criterio: "todos",
      buscar: "",
      modalImportar: false,
      esVendedor: false, // Nueva propiedad para controlar visibilidad
      idrol: 0,
    };
  },
  computed: {
    isActived() {
      return this.pagination.current_page;
    },
    pagesNumber() {
      let from = this.pagination.current_page - 2;
      if (from < 1) {
        from = 1;
      }
      let to = from + 4;
      if (to >= this.pagination.last_page) {
        to = this.pagination.last_page;
      }
      let pagesArray = [];
      for (let page = from; page <= to; page++) {
        pagesArray.push(page);
      }
      return pagesArray;
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
        life: 2000,
      });
    },
    toastWarning(mensaje) {
      this.$toast.add({
        severity: "warn",
        summary: "Advertencia",
        detail: mensaje,
        life: 2000,
      });
    },
    toastInfo(mensaje) {
      this.$toast.add({
        severity: "info",
        summary: "Información",
        detail: mensaje,
        life: 2000,
      });
    },
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768;
    },
    getDocumento(data) {
      const tipo = this.tiposDocumento[data.tipo_documento];
      const numero = data.num_documento;

      if (!tipo || !numero) {
        return "Documento no Registrado";
      }

      return `${tipo} ${numero}`;
    },
    onPageChange(event) {
      let page = event.page + 1;
      this.cambiarPagina(page, this.buscar, this.criterio);
    },
    async cambiarPagina(page, buscar, criterio) {
      try {
        this.isLoading = true;
        this.pagination.current_page = page;
        await this.listarProveedor(page, buscar, criterio);
      } catch (error) {
        console.error("Error al cambiar página:", error);
        swal("Error", "No se pudo cambiar de página", "error");
      } finally {
        setTimeout(() => {
          this.isLoading = false;
        }, 500);
      }
    },
    async validarCampo(campo) {
      try {
        const valor = this.datosFormulario[campo];
        // No validar campos opcionales si están vacíos
        const camposOpcionales = ['tipo_documento', 'num_documento', 'telefono'];
        if (camposOpcionales.includes(campo) && (valor === null || valor === '' || valor === undefined)) {
          this.errores[campo] = null;
          return;
        }
        await esquemaProveedor.validateAt(campo, this.datosFormulario);
        this.errores[campo] = null;
      } catch (error) {
        this.errores[campo] = error.message;
      }
    },
    async enviarFormulario() {
      if (!this.datosFormulario.nombre) {
        swal({
          title: "¡Campo obligatorio!",
          text: "El Nombre del Proveedor es obligatorio.",
          icon: "warning",
        });
        return;
      }

      await esquemaProveedor
        .validate(this.datosFormulario, { abortEarly: false })
        .then(() => {
          if (this.tipoAccion == 2) {
            this.actualizarPersona(this.datosFormulario);
          } else {
            this.registrarPersona(this.datosFormulario);
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
    getTipoDocumentoText(value) {
      const documento = this.tiposDocumentos.find((doc) => doc.valor === value);
      return documento ? documento.etiqueta : "";
    },
    abrirModalImportar() {
      this.modalImportar = true;
    },
    cerrarModalImportar() {
      this.modalImportar = false;
      this.listarProveedor(1, "", "nombre");
    },
    async listarProveedor(page, buscar, criterio) {
      let me = this;
      try {
        this.isLoading = true;
        const url = `/proveedor?page=${page}&buscar=${buscar}&criterio=${criterio}`;
        const response = await axios.get(url);
        const respuesta = response.data;

        // Laravel paginate() devuelve un objeto con la propiedad "data"
        me.arrayPersona = respuesta.personas.data.map((persona) => {
          persona.documento = persona.tipo_documento
            ? `${me.getTipoDocumentoText(
              persona.tipo_documento
            )} ${persona.num_documento || ""}`
            : "Documento no Registrado";
          return persona;
        });

        me.pagination = respuesta.pagination;
      } catch (error) {
        console.error("Error al listar proveedores:", error);
        swal("Error", "No se pudieron cargar los proveedores", "error");
      } finally {
        setTimeout(() => {
          this.isLoading = false;
        }, 500);
      }
    },
    async buscarProveedores() {
      try {
        if (this.searchTimeout) {
          clearTimeout(this.searchTimeout);
        }

        this.searchTimeout = setTimeout(async () => {
          this.isLoading = true;
          await this.listarProveedor(1, this.buscar, this.criterio);
        }, 300);
      } catch (error) {
        console.error("Error en la búsqueda:", error);
        this.isLoading = false;
      }
    },
    async registrarPersona(data) {
      try {
        this.isLoading = true;
        await axios.post("/proveedor/registrar", data);
        this.cerrarModal();
        await this.listarProveedor(this.pagination.current_page, this.buscar, this.criterio);
        swal("Éxito", "Proveedor registrado correctamente", "success");
      } catch (error) {
        console.error("Error al registrar:", error);
        swal("Error", "No se pudo registrar el proveedor", "error");
      } finally {
        this.isLoading = false;
      }
    },
    async actualizarPersona(data) {
      try {
        this.isLoading = true;
        await axios.put("/proveedor/actualizar", data);
        this.cerrarModal();
        await this.listarProveedor(this.pagination.current_page, this.buscar, this.criterio);
        swal("Éxito", "Proveedor actualizado correctamente", "success");
      } catch (error) {
        console.error("Error al actualizar:", error);
        swal("Error", "No se pudo actualizar el proveedor", "error");
      } finally {
        this.isLoading = false;
      }
    },
    desactivarProveedor(data) {
      if (this.idrol == 2) {
        this.toastWarning("Esta acción solo está permitida para Administradores.");
        return;
      }
      this.$swal.fire({
        title: "¿Estás seguro de eliminar permanentemente el proveedor?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "OK",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#dc3545",
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          this.ejecutarDesactivarProveedor(data.id);
        }
      });
    },
    async ejecutarDesactivarProveedor(id) {
      try {
        this.isLoading = true;
        const response = await axios.put("/proveedor/desactivar", { id: id });
        if (response.data.success) {
          this.toastSuccess(response.data.message);
          await this.listarProveedor(this.pagination.current_page, this.buscar, this.criterio);
        } else {
          this.toastError(response.data.message || "Error al eliminar el proveedor");
        }
      } catch (error) {
        console.error("Error al eliminar proveedor:", error);
        this.toastError("No se pudo eliminar el proveedor");
      } finally {
        this.isLoading = false;
      }
    },
    cerrarModal() {
      this.modal = false;
      this.tituloModal = "";
    },
    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "persona":
          switch (accion) {
            case "registrar":
              if (this.idrol == 2) {
                this.toastWarning("Esta acción solo está permitida para Administradores.");
                return;
              }
              this.modal = true;
              this.tituloModal = "REGISTRAR PROVEEDOR";
              this.tipoAccion = 1;
              this.datosFormulario = {
                nombre: "",
                tipo_documento: "",
                num_documento: "",
                telefono: "",
                contacto: "",
                telefono_contacto: "",
              };
              break;
            case "actualizar":
              if (this.idrol == 2) {
                this.toastWarning("Esta acción solo está permitida para Administradores.");
                return;
              }
              this.modal = true;
              this.tituloModal = "ACTUALIZAR PROVEEDOR";
              this.tipoAccion = 2;
              this.datosFormulario = data;
              this.persona_id = data["id"];
              break;
          }
          break;
      }
    },
    verificarRolUsuario() {
      // Verificar el rol del usuario logueado
      if (window.userData && window.userData.rol) {
        this.idrol = window.userData.rol;
        this.esVendedor = window.userData.rol === 2;
      }
    },
  },
  async mounted() {
    this.handleResize();
    window.addEventListener("resize", this.handleResize);
    this.verificarRolUsuario(); // Verificar rol al montar

    try {
      this.isLoading = true;
      await this.listarProveedor(1, this.buscar, this.criterio);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
      swal("Error", "Error al cargar los datos iniciales", "error");
    } finally {
      setTimeout(() => {
        this.isLoading = false;
      }, 500);
    }
  },
};
</script>

<style scoped>
.info-tip {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  padding: 8px 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 12px;
  color: #475569;
}

.info-tip i {
  color: #3b82f6;
  font-size: 14px;
  flex-shrink: 0;
}

/* Estilo para "Datos no registrados" */
.datos-no-registrados {
  color: #dc3545;
  font-size: 0.85rem;
  font-style: italic;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.datos-no-registrados i {
  font-size: 0.9rem;
}

.text-success {
  color: #28a745;
  font-weight: 500;
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
  margin-bottom: 8px;
}

.input-container .p-inputtext {
  width: 100%;
  margin-bottom: 0;
}

.error-message {
  position: absolute;
  bottom: 2px;
  left: 0;
  font-size: 0.75rem;
  margin-top: 0;
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

/* Reducir padding del contenedor del diálogo */
.responsive-dialog>>>.p-dialog-content {
  padding: 0.75rem 1rem !important;
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