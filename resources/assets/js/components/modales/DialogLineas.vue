<template>
  <main class="main">
    <Toast :breakpoints="{ '920px': { width: '100%', right: '0', left: '0' } }" style="padding-top: 10px;"
      appendTo="body" :baseZIndex="99999"></Toast>
    <Dialog
      header="Categoria"
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
            class="p-button-secondary p-button-sm btn-sm"
            @click="abrirModal('categoria', 'registrar')"
          />
        </div>
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText
              type="text"
              placeholder="Texto a buscar"
              class="p-inputtext-sm"
              v-model="buscar"
              @keyup="buscarLinea"
            />
          </span>
        </div>
      </div>
      <DataTable
        class="p-datatable-sm p-datatable-gridlines tabla-pro"
        :value="arrayCategoria"
        :paginator="true"
        responsiveLayout="scroll"
        :rows="6"
      >
        <Column field="opciones" header="Opciones">
          <template #body="slotProps">
            <Button
              icon="pi pi-check"
              class="p-button-success custom-icon-size btn-mini"
              @click="seleccionarLinea(slotProps.data)"
            />
            <Button
              icon="pi pi-pencil"
              class="p-button-warning btn-mini"
              @click="abrirModal('categoria', 'actualizar', slotProps.data)"
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
      :visible.sync="modal" 
      modal 
      :header="tituloModal" 
      @hide="cerrarModal"  
      :containerStyle="formDialogContainerStyle" 
      :closable="false" 
      :closeOnEscape="false"
      class="responsive-dialog form-dialog"
    >
      <div class="p-fluid p-formgrid p-grid form-compact">
        <div class="p-field p-col-12">
          <label for="nombre" class="required-field">
            <span class="required-icon">*</span>
            Nombre de Categoría
          </label>
          <InputText 
            id="nombre"
            class="input-full" 
            v-model="nombre" 
            required 
            autofocus 
            :class="{'input-error': nombreError}" 
            @input="validarNombreEnTiempoReal" 
          />
          <small class="p-error error-message" v-if="nombreError">
            <strong>{{ nombreError }}</strong>
          </small>
        </div>
        
        <div class="p-field p-col-12">
           <label for="documentType" class="optional-field">
              <i class="pi pi-info-circle optional-icon"></i>
              Descripcion
              <span class="p-tag p-tag-secondary tag-opcional">Opcional</span>
            </label>
          <InputText 
            id="descripcion" 
            class="input-full"
            v-model="descripcion" 
          />
        </div>
      </div>
      
      <template #footer>
        <Button 
          label="Cancelar" 
          icon="pi pi-times" 
          class="p-button-sm p-button-danger btn-sm" 
          @click="cerrarModal()" 
        />
        <Button 
          v-if="tipoAccion === 1" 
          label="Guardar" 
          icon="pi pi-check" 
          class="p-button-sm p-button-success btn-sm" 
          @click="registrarCategoria()" 
        />
        <Button 
          v-if="tipoAccion === 2" 
          label="Actualizar" 
          icon="pi pi-check" 
          class="p-button-sm p-button-warning btn-sm" 
          @click="actualizarCategoria()" 
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
import InputNumber from 'primevue/inputnumber';
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

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
    InputNumber,
    ToastService,
    Toast
  },
  data() {
    return {
      modal1: this.visible,
      modal: false,
      buscar: '',
      arrayCategoria:[],
      nombre: '',
      descripcion: '',
      codigoProductoSin: 0,
      codigoProductoSinError: '',
      nombreError: '',
      descripcionError: '',
      tituloModal: '',
      tipoAccion: '',
      lineaSeleccionado: null,
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
        return { width: '600px', maxWidth: '90vw', margin: '0 auto' };
      }
    }
  },
  methods: {
    closeDialog() {
      this.$emit('close');
    },
    buscarLinea() {
      this.listarCategoria(1, this.buscar, this.criterio);
    },
    seleccionarLinea(data){
      console.log("data ", data.condicion)
      if (data.condicion == 0) {
        swal({
          title: 'Linea Inactiva',
          text: 'No puede seleccionar esta opción porque está inactiva.',
          type: 'warning',
          confirmButtonColor: '#3085d6',
          confirmButtonText: 'Aceptar',
          confirmButtonClass: 'btn btn-success',
          buttonsStyling: false,
        })
      } else {
        this.lineaSeleccionado = data;
        this.$emit('linea-seleccionado', this.lineaSeleccionado);
        this.closeDialog();
      }
    },
    validarNombreEnTiempoReal() {
      if (!this.nombre.trim()) {
        this.nombreError = "El nombre de la linea no puede estar vacío.";
      } else {
        this.nombreError = '';
      }
    },
    validarCodigoEnTiempoReal() {
      if (this.codigoProductoSin === null || this.codigoProductoSin === undefined || String(this.codigoProductoSin).trim() === '') {
        this.codigoProductoSinError = 'El código no puede estar vacío.';
      } else {
        this.codigoProductoSinError = '';
      }
    },
    registrarCategoria() {
      if (this.validarCategoria()) {
        // Si hay errores de validación, mostrar toast
        this.$toast.add({
          severity: "error",
          summary: "Validación",
          detail: "Complete los campos obligatorios.",
          life: 3000,
        });
        return;
      }

      let me = this;
      axios.post('/categoria/registrar', {
        nombre: this.nombre,
        descripcion: this.descripcion,
        codigoProductoSin: this.codigoProductoSin
      })
      .then(function (response) {
        me.$toast.add({
          severity: "success",
          summary: "Categoría registrada",
          detail: "La categoría fue registrada correctamente",
          life: 2500,
        });

        me.cerrarModal();
        me.listarCategoria(1, '', 'nombre');
      })
      .catch(function (error) {
        me.$toast.add({
          severity: "error",
          summary: "Error al registrar",
          detail: "No se pudo registrar la categoría",
          life: 3000,
        });
        console.log(error);
      });
    },
    actualizarCategoria() {
      if (this.validarCategoria()) {
        this.$toast.add({
          severity: "error",
          summary: "Validación",
          detail: "Complete los campos obligatorios.",
          life: 3000,
        });
        return;
      }

      let me = this;
      axios.put('/categoria/actualizar', {
        nombre: this.nombre,
        descripcion: this.descripcion,
        codigoProductoSin: this.codigoProductoSin,
        id: this.categoria_id
      })
      .then(function (response) {

        me.$toast.add({
          severity: "success",
          summary: "Categoría actualizada",
          detail: "La categoría fue actualizada correctamente",
          life: 2500,
        });

        me.cerrarModal();
        me.listarCategoria(1, '', 'nombre');
      })
      .catch(function (error) {
        me.$toast.add({
          severity: "error",
          summary: "Error al actualizar",
          detail: "No se pudo actualizar la categoría",
          life: 3000,
        });
        console.log(error);
      });
    },

    validarCategoria() {
      let hasError = false;
      this.codigoProductoSinError = '';
      this.descripcionError ='';
      this.nombreError = '';
      if (this.codigoProductoSin === null || this.codigoProductoSin === undefined || String(this.codigoProductoSin).trim() === '') {
        this.codigoProductoSinError = 'El código no puede estar vacío.';
        hasError = true;
      }
      if (!this.nombre.trim()) {
        this.nombreError = "El nombre de la linea no puede estar vacío.";
        hasError = true;
      }
      return hasError;
    },
    listarCategoria(page, buscar, criterio) {
      let me = this;
      var url = '/categorianewview?page=' + page + '&buscar=' + buscar + '&criterio=' + criterio;
      axios.get(url).then(function (response) {
        var respuesta = response.data;
        me.arrayCategoria = respuesta.categorias;
        me.pagination = respuesta.pagination;
      })
      .catch(function (error) {
        console.log(error);
      });
    },
    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "categoria":
        {
          switch (accion) {
            case 'registrar':
            {
              this.modal = true;
              this.modal1 = false;
              this.tituloModal = 'REGISTRAR CATEGORÍA';
              this.nombre = '';
              this.descripcion = '';
              this.codigoProductoSin = 0;
              this.tipoAccion = 1;
              break;
            }
            case 'actualizar':
            {
              this.modal = true;
              this.modal1 = false;
              this.tituloModal = 'ACTUALIZAR CATEGORÍA';
              this.tipoAccion = 2;
              this.categoria_id = data['id'];
              this.nombre = data['nombre'];
              this.descripcion = data['descripcion'];
              this.codigoProductoSin = data['codigoProductoSin'];
              break;
            }
          }
        }
      }
    },
    cerrarModal() {
      this.modal = false;
      this.modal1 = true;
      this.tituloModal = '';
      this.nombre = '';
      this.descripcion = '';
      this.codigoProductoSin = '';
      this.nombreError= '';
      this.descripcionError= '';
      this.codigoProductoSinError = '';
    },
  },
  mounted(){
    this.listarCategoria(1,'','nombre');
  }
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

/* Status badges */
.status-badge {
  padding: 0.25em 0.5em;
  border-radius: 4px;
  color: white;
  font-size: 0.8rem;
  font-weight: 500;
}

.status-badge.active {
  background-color: #28a745;
}

.status-badge.inactive {
  background-color: #dc3545;
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
  
  >>> .p-formgrid .p-field.p-col-12 {
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
  
  >>> .p-inputtext, >>> .p-dropdown, >>> .p-inputnumber-input {
    font-size: 0.85rem;
    padding: 0.4rem;
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