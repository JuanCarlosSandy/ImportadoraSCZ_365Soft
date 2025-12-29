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
          <h4 class="panel-title">PROVEEDORES</h4>
        </div>
      </template>
      <div class="toolbar-container">
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText
              class="p-inputtext-sm"
              v-model="buscar"
              placeholder="Texto a buscar"
              @input="buscarProveedores"
            />
          </span>
        </div>
        <div class="toolbar">
          <Button
            :label="mostrarLabel ? 'Nuevo' : ''"
            icon="pi pi-plus"
            @click="abrirModal('persona', 'registrar')"
            class="p-button-secondary p-button-sm"
          />
          <Button
            :label="mostrarLabel ? 'Importar' : ''"
            icon="pi pi-upload"
            @click="abrirModalImportar()"
            class="p-button-success p-button-sm"
          />
        </div>
      </div>

      <DataTable
        :value="arrayPersona"
        :paginator="true"
        :rows="9"
        :totalRecords="pagination.total"
        :lazy="true"
        @page="onPageChange"
        responsiveLayout="scroll"
        class="p-datatable-gridlines p-datatable-sm"
      >
        <Column header="Acciones">
          <template #body="slotProps">
            <Button
              icon="pi pi-pencil"
              class="p-button-warning p-button-sm"
              style="padding: 0.3rem 0.4rem; font-size: 0.75rem; width: auto; min-width: unset;"
              @click="abrirModal('persona', 'actualizar', slotProps.data)" v-tooltip.top="'Editar'"
            />
          </template>
        </Column>
        <Column field="nombre" header="Nombre_proveedor"></Column>
        <template>
          <Column field="documento" header="Documento">
            <template #body="slotProps">
              {{ getDocumento(slotProps.data) }}
            </template>
          </Column>
        </template>
        <Column field="telefono" header="Teléfono"></Column>
        <Column field="contacto" header="Contacto"></Column>
      </DataTable>
    </Panel>

    <Dialog
      :visible.sync="modal"
      :containerStyle="{ width: '800px' }"
      :modal="true"
      :closable="true"
    >
      <template #header>
        <h3>{{ tituloModal }}</h3>
      </template>

      <form @submit.prevent="enviarFormulario">
        <div class="p-fluid p-formgrid p-grid">
          <div class="p-field p-col-12 p-md-6">
            <label for="nombre">Nombre del proveedor</label>
            <InputText
              id="nombre"
              v-model="datosFormulario.nombre"
              :class="{ 'p-invalid': errores.nombre }"
              @input="validarCampo('nombre')"
            />
            <small class="p-error" v-if="errores.nombre">{{
              errores.nombre
            }}</small>
          </div>

          <div class="p-field p-col-12 p-md-6">
            <label for="tipo_documento"
              >Tipo de documento
              <span class="p-tag p-tag-secondary">Opcional</span></label
            >
            <Dropdown
              id="tipo_documento"
              v-model="datosFormulario.tipo_documento"
              :options="tiposDocumentos"
              optionLabel="etiqueta"
              optionValue="valor"
              placeholder="Selecciona un tipo de documento"
              
            />
          </div>

          <div class="p-field p-col-12 p-md-6">
            <label for="num_documento"
              >Nro de documento
              <span class="p-tag p-tag-secondary">Opcional</span></label
            >
            <InputText
              id="num_documento"
              v-model="datosFormulario.num_documento"
              
            />
          </div>

          <div class="p-field p-col-12 p-md-6">
            <label for="telefono">Teléfono</label>
            <InputNumber
              id="telefono"
              v-model="datosFormulario.telefono"
              :class="{ 'p-invalid': errores.telefono }"
              @input="validarCampo('telefono')"
            />
            <small class="p-error" v-if="errores.telefono">{{
              errores.telefono
            }}</small>
          </div>

          <div class="p-field p-col-12 p-md-6">
            <label for="contacto">Contacto</label>
            <InputText
              id="contacto"
              v-model="datosFormulario.contacto"
              :class="{ 'p-invalid': errores.contacto }"
              @input="validarCampo('contacto')"
            />
            <small class="p-error" v-if="errores.contacto">{{
              errores.contacto
            }}</small>
          </div>

          <div class="p-field p-col-12 p-md-6">
            <label for="telefono_contacto">Teléfono de contacto</label>
            <InputNumber
              id="telefono_contacto"
              v-model="datosFormulario.telefono_contacto"
              :class="{ 'p-invalid': errores.telefono_contacto }"
              @input="validarCampo('telefono_contacto')"
            />
            <small class="p-error" v-if="errores.telefono_contacto">{{
              errores.telefono_contacto
            }}</small>
          </div>
        </div>
      </form>

      <template #footer>
        <div class="d-flex gap-2 justify-content-end modal-footer-buttons">
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
          />
          <Button
            v-if="tipoAccion === 2"
            label="Actualizar"
            icon="pi pi-check"
            class="p-button-warning p-button-sm"
            @click="enviarFormulario"
          />
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
  },directives: {
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
        //tipo_documento: '',
        //num_documento: '',
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
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
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
      let page = event.page + 1; // PrimeVue pages are 0-based, while your logic uses 1-based
      this.cambiarPagina(page, this.buscar, this.criterio);
    },
    async cambiarPagina(page, buscar, criterio) {
      try {
        this.isLoading = true; // Activar loading
        this.pagination.current_page = page;
        await this.listarPersona(page, buscar, criterio);
      } catch (error) {
        console.error("Error al cambiar página:", error);
        swal("Error", "No se pudo cambiar de página", "error");
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
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
      if (!this.datosFormulario.nombre) {
        swal({
          title: "¡Campo obligatorio!",
          text: "El Nombre del proveedor es obligatorio.",
          icon: "warning",
        });
        return; // Detiene la ejecución si el nombre está vacío
      }

      if (!this.datosFormulario.telefono) {
        swal({
          title: "¡Campo obligatorio!",
          text: "El Teléfono es obligatorio.",
          icon: "warning",
        });
        return; // Detiene la ejecución si el teléfono está vacío
      }

      if (!this.datosFormulario.contacto) {
        swal({
          title: "¡Campo obligatorio!",
          text: "El Contacto es obligatorio.",
          icon: "warning",
        });
        return; // Detiene la ejecución si el contacto está vacío
      }

      if (!this.datosFormulario.telefono_contacto) {
        swal({
          title: "¡Campo obligatorio!",
          text: "El Teléfono de contacto es obligatorio.",
          icon: "warning",
        });
        return; // Detiene la ejecución si el teléfono de contacto está vacío
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
      this.listarPersona(1, "", "nombre");
    },
    async listarPersona(page, buscar, criterio) {
      let me = this;
      try {
        this.isLoading = true; // Activar loading
        const url = `/proveedor?page=${page}&buscar=${buscar}&criterio=${criterio}`;
        const response = await axios.get(url);
        const respuesta = response.data;

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
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },
    async buscarProveedores() {
      try {
        if (this.searchTimeout) {
          clearTimeout(this.searchTimeout);
        }

        this.searchTimeout = setTimeout(async () => {
          this.isLoading = true; // Activar loading
          await this.listarPersona(1, this.buscar, this.criterio);
        }, 300);
      } catch (error) {
        console.error("Error en la búsqueda:", error);
        this.isLoading = false;
      }
    },
    async registrarPersona(data) {
      try {
        this.isLoading = true; // Activar loading
        await axios.post("/proveedor/registrar", data);
        this.cerrarModal();
        await this.listarPersona(1, "", "nombre");
        swal("Éxito", "Proveedor registrado correctamente", "success");
      } catch (error) {
        console.error("Error al registrar:", error);
        swal("Error", "No se pudo registrar el proveedor", "error");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    async actualizarPersona(data) {
      try {
        this.isLoading = true; // Activar loading
        await axios.put("/proveedor/actualizar", data);
        this.cerrarModal();
        await this.listarPersona(1, "", "nombre");
        swal("Éxito", "Proveedor actualizado correctamente", "success");
      } catch (error) {
        console.error("Error al actualizar:", error);
        swal("Error", "No se pudo actualizar el proveedor", "error");
      } finally {
        this.isLoading = false; // Desactivar loading
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
              this.modal = true;
              this.tituloModal = "REGISTRAR PROVEEDOR";
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
            case "actualizar":
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
  },
  async mounted() {
    this.handleResize();
    window.addEventListener("resize", this.handleResize);
    try {
      this.isLoading = true; // Activar loading
      await this.listarPersona(1, this.buscar, this.criterio);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
      swal("Error", "Error al cargar los datos iniciales", "error");
    } finally {
      setTimeout(() => {
        this.isLoading = false; // Desactivar loading
      }, 500);
    }
  },
};
</script>

<style scoped>
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
  padding-bottom: 20px; /* Aumentado de 8px a 12px para dar espacio al error */
  margin-bottom: 8px; /* Agregado margen inferior pequeño */
}

.input-container .p-inputtext {
  width: 100%;
  margin-bottom: 0; /* Eliminar margen inferior si existe */
}

.error-message {
  position: absolute;
  bottom: 2px; /* Ajustado para tener más espacio arriba del input */
  left: 0;
  font-size: 0.75rem; /* Tamaño de fuente más pequeño */
  margin-top: 0; /* Eliminado margen superior */
}

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

/* Responsive Dialog Styles */
.responsive-dialog >>> .p-dialog {
  margin: 0.75rem;
  max-height: 90vh;
  overflow-y: auto;
}

.responsive-dialog >>> .p-dialog-content {
  overflow-x: auto;
  padding: 0.75rem 1rem; /* Reducido padding vertical */
}

.responsive-dialog >>> .p-dialog-header {
  padding: 0.75rem 1.5rem; /* Reducido padding vertical */
  font-size: 1.1rem;
}

.responsive-dialog >>> .p-dialog-footer {
  padding: 0.5rem 1.5rem; /* Reducido padding vertical */
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
.form-compact >>> .p-field {
  margin-bottom: 0.25rem !important; /* Reducido de 0.5rem a 0.25rem */
}

>>> .p-fluid .p-field {
  margin-bottom: 0.25rem; /* Reducido de 0.5rem a 0.25rem */
}

/* Reducir padding del contenedor del diálogo */
.responsive-dialog >>> .p-dialog-content {
  padding: 0.75rem 1rem !important; /* Reducido padding vertical */
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

/* SweetAlert z-index para que aparezca por encima de los diálogos */
>>> .swal2-container {
  z-index: 99999 !important;
}

>>> .swal2-popup {
  z-index: 99999 !important;
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
}

/* Mobile Styles */
@media (max-width: 768px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog >>> .p-dialog {
    margin: 0.25rem;
    max-height: 98vh;
  }

  .responsive-dialog >>> .p-dialog-content {
    padding: 0.5rem 0.75rem; /* Más compacto en móviles */
  }

  .responsive-dialog >>> .p-dialog-header {
    padding: 0.5rem 1rem; /* Reducido padding vertical */
    font-size: 1rem;
  }

  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.4rem 1rem; /* Reducido padding vertical */
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

  /* Ajustar botones en móviles */
  >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }

  /* Ajustar botón "Nuevo" para que coincida con otros botones */
  .toolbar >>> .p-button-sm {
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

  >>> .p-inputtext,
  >>> .p-dropdown,
  >>> .p-inputnumber-input {
    font-size: 0.9rem;
    padding: 0.5rem;
  }

  /* Reducir espacios entre campos en móviles */
  .input-container {
    padding-bottom: 20px; /* Aumentado para dar espacio al error en móviles */
    margin-bottom: 6px;
  }
}

/* Extra Small Mobile */
@media (max-width: 480px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog >>> .p-dialog {
    margin: 0.1rem;
    max-height: 99vh;
  }

  .responsive-dialog >>> .p-dialog-content {
    padding: 0.4rem 0.5rem; /* Más compacto en móviles extra pequeños */
  }

  .responsive-dialog >>> .p-dialog-header {
    padding: 0.4rem 0.75rem; /* Reducido padding vertical */
    font-size: 0.95rem;
  }

  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.3rem 0.75rem; /* Reducido padding vertical */
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

  /* Ajustar botones para que coincidan */
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

  /* Iconos más pequeños en móviles extra pequeños */
  .required-icon {
    font-size: 0.7rem;
  }

  .optional-icon {
    font-size: 0.55rem;
  }

  >>> .p-inputtext,
  >>> .p-dropdown,
  >>> .p-inputnumber-input {
    font-size: 0.85rem;
    padding: 0.4rem;
  }

  >>> .p-tag {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
  }

  /* Espacios aún más compactos en móviles extra pequeños */
  .input-container {
    padding-bottom: 20px; /* Aumentado para dar espacio al error en móviles pequeños */
    margin-bottom: 4px;
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
