<template>
  <main class="main">
    <Dialog
      header="Medidas"
      :visible.sync="modal1"
      :modal="true"
      :containerStyle="dialogContainerStyle"
      :closable="false"
      :closeOnEscape="false"
      class="responsive-dialog"
    >
      <div class="toolbar-container">
        <div class="toolbar">
          <!--<Button
            label="Nuevo"
            icon="pi pi-plus"
            class="p-button-secondary p-button-sm"
            @click="abrirModal('medida', 'registrar')"
          />-->
        </div>
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText
              id="search"
              type="text"
              placeholder="Texto a buscar"
              class="p-inputtext-sm"
              v-model="buscar"
              @keyup="buscarMedida"
            />
          </span>
        </div>
      </div>
      <DataTable
        class="p-datatable-sm p-datatable-gridlines"
        :value="arrayMedida"
        :paginator="true"
        responsiveLayout="scroll"
        :rows="5"
      >
        <Column field="opciones" header="Opciones">
          <template #body="slotProps">
            <Button
              icon="pi pi-check"
              class="p-button-sm p-button-success"
              style="padding: 0.3rem 0.4rem; font-size: 0.75rem; width: auto; min-width: unset;"
              @click="seleccionarMedida(slotProps.data)"
            />
            <!--<Button
              icon="pi pi-pencil"
              class="p-button-warning p-button-sm"
              style="padding: 0.3rem 0.4rem; font-size: 0.75rem; width: auto; min-width: unset;"
              @click="abrirModal('medida', 'actualizar', slotProps.data)"
            />-->
          </template>
        </Column>
        <Column field="descripcion_medida" header="Descripción" />
        <!--<Column field="codigoClasificador" header="Código Clasificador" />
        <Column field="estado" header="Estado">
          <template #body="slotProps">
            <span :class="['status-badge', slotProps.data.estado === 1 ? 'active' : 'inactive']">
              {{ slotProps.data.estado === 1 ? 'Activo' : 'Inactivo' }}
            </span>
          </template>
        </Column>-->
      </DataTable>
      <template #footer>
        <Button
          label="Cerrar"
          icon="pi pi-times"
          class="p-button-danger p-button-sm"
          @click="closeDialog()"
        />
      </template>
    </Dialog>
    <Dialog 
      :visible.sync="modal" 
      modal 
      :header="tituloModal" 
      :containerStyle="formDialogContainerStyle"  
      :closable="false" 
      :closeOnEscape="false"
      class="responsive-dialog form-dialog"
    >
      <div class="p-fluid p-formgrid p-grid form-compact">
        <div class="p-field p-col-12">
          <label for="descripcionMedida" class="required-field">
            <span class="required-icon">*</span>
            Descripción
          </label>
          <InputText 
            id="descripcionMedida" 
            v-model="descripcionMedida" 
            required 
            autofocus 
            :class="{'p-invalid': descripcionMedidaError}" 
            @input="validarDescripcionEnTiempoReal"
          />
          <small class="p-error error-message" v-if="descripcionMedidaError">
            <strong>{{ descripcionMedidaError }}</strong>
          </small>
        </div>
        
        <div class="p-field p-col-12">
          <label for="codigoClasificador" class="required-field">
            <span class="required-icon">*</span>
            Código Clasificador
          </label>
          <InputText 
            id="codigoClasificador" 
            v-model="codigoClasificador" 
            required 
            :class="{'p-invalid': codigoClasificadorError}" 
            @input="validarCodigoEnTiempoReal"
          />
          <small class="p-error error-message" v-if="codigoClasificadorError">
            <strong>{{ codigoClasificadorError }}</strong>
          </small>
        </div>
      </div>
      
      <template #footer>
        <Button 
          label="Cancelar" 
          icon="pi pi-times" 
          class="p-button-danger p-button-sm" 
          @click="cerrarModal()" 
        />
        <Button 
          v-if="tipoAccion === 1" 
          label="Guardar" 
          icon="pi pi-check" 
          class="p-button-success p-button-sm" 
          @click="registrarMedida()" 
        />
        <Button 
          v-if="tipoAccion === 2" 
          label="Actualizar" 
          icon="pi pi-check" 
          class="p-button-warning p-button-sm" 
          @click="actualizarMedida()" 
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

export default {
  props: {
    visible: {
      type: Boolean,
      required: true,
    },
  },
  components:{
    Button,
    DataTable,  
    Column,
    Dialog,
    InputText,
  },
  data() {
    return {
      modal1: this.visible,
      modal: false,
      arrayCategoria: [],
      medida_id: 0,
      descripcionMedida: '',
      codigoClasificador: '',
      tipoAccion: 0,
      arrayMedida: [],
      criterio: 'descripcion_medida',
      buscar: '',
      tituloModal:'',
      descripcionMedidaError: '',
      codigoClasificadorError: '',
      medidaSeleccionado: null
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
    validarDescripcionEnTiempoReal() {
      if (!this.descripcionMedida.trim()) {
        this.descripcionMedidaError = "La descripción de la medida no puede estar vacía.";
      } else {
        this.descripcionMedidaError = '';
      }
    },
    validarCodigoEnTiempoReal() {
      if (!this.codigoClasificador.trim()) {
        this.codigoClasificadorError = "El código clasificador no puede estar vacío.";
      } else {
        this.codigoClasificadorError = '';
      }
    },
    listarMedidas(page, buscar, criterio) {
      let me = this;
      var url = '/medida2?page=' + page + '&buscar=' + buscar + '&criterio=' + criterio;
      axios
      .get(url)
      .then(function (response) {
        var respuesta = response.data;
        me.arrayMedida = respuesta.medidas;
        me.paginationMedida = respuesta.paginationMedida;
      })
      .catch(function (error) {
        console.log(error);
      });
    },
    registrarMedida() {
      if (this.validarMedida()) {
        return;
      }
      let me = this;
      axios
      .post('/medida/registrar', {
        descripcion_medida: this.descripcionMedida,
        codigoClasificador: this.codigoClasificador,
      })
      .then(function (response) {
        me.cerrarModal();
        me.listarMedidas(1, '', 'descripcion_medida');
      })
      .catch(function (error) {
        console.log(error);
      });
    },
    actualizarMedida() {
      if (this.validarMedida()) {
        return;
      }

      let me = this;
      axios
      .put('/medida/actualizar', {
        descripcion_medida: this.descripcionMedida,
        codigoClasificador: this.codigoClasificador,
        id: this.medida_id,
      })
      .then(function (response) {
        me.cerrarModal();
        me.listarMedidas(1, '', 'descripcion_medida');
      })
      .catch(function (error) {
        console.log(error);
      });
    },
    validarMedida() {
      this.descripcionMedidaError = '';
      this.codigoClasificadorError = '';
      let hasError = false;

      if (!this.descripcionMedida.trim()) {
        this.descripcionMedidaError = "La descripción de la medida no puede estar vacía.";
        hasError = true;
      }

      if (!this.codigoClasificador.trim()) {
        this.codigoClasificadorError = "El código clasificador no puede estar vacío.";
        hasError = true;
      }

      return hasError;
    },
    seleccionarMedida(data){
      if (data.estado == 0) {
        swal({
          title: 'Medida Inactiva',
          text: 'No puede seleccionar esta opción porque está inactiva.',
          type: 'warning',
          confirmButtonColor: '#3085d6',
          confirmButtonText: 'Aceptar',
          confirmButtonClass: 'btn btn-success',
          buttonsStyling: false,
        })
      } else {
        this.medidaSeleccionado = data;
        this.$emit('medida-seleccionado', this.medidaSeleccionado);
        this.closeDialog();
      }
    },
    buscarMedida() {
      this.listarMedidas(1, this.buscar, this.criterio);
    },
    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case 'medida': {
          switch (accion) {
            case 'registrar': {
              this.modal = true;
              this.modal1 = false;
              this.tituloModal = 'Registrar Medida';
              this.descripcionMedida = '';
              this.codigoClasificador = '';
              this.tipoAccion = 1;
              break;
            }
            case 'actualizar': {
              this.modal = true;
              this.modal1 = false;
              this.tituloModal = 'Actualizar medida';
              this.tipoAccion = 2;
              this.medida_id = data['id'];
              this.descripcionMedida = data['descripcion_medida'];
              this.codigoClasificador = data['codigoClasificador'];
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
      this.descripcionMedida = '';
      this.codigoClasificador = '';
      this.descripcionMedidaError = '';
      this.codigoClasificadorError = '';
    },
  },
  mounted(){
    this.listarMedidas(1, this.buscar, this.criterio);
  }
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
>>> .p-datatable {
  font-size: 0.9rem;
}

>>> .p-datatable .p-datatable-tbody > tr > td {
  padding: 0.5rem;
  word-break: break-word;
}

>>> .p-datatable .p-datatable-thead > tr > th {
  padding: 0.75rem 0.5rem;
  font-size: 0.85rem;
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