<template>
    <main class="main">
      <Dialog
        header="Marcas"
        :visible.sync="modal1"
        :modal="true"
        :containerStyle="{ width: '700px' }"
        :closable="false" 
        :closeOnEscape="false"
      >
        <div class="toolbar-container">
          
          <div class="toolbar">
            <Button
              label="Nuevo"
              icon="pi pi-plus"
              class="p-button-secondary p-button-sm"
              @click="abrirModal('categoria', 'registrar')"
            />
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
                @keyup="buscarMarca"
              />
            </span>
          </div>
        </div>
        <DataTable
          class="p-datatable-sm p-datatable-gridlines"
          :value="arrayCategoria"
          :paginator="true"
          responsiveLayout="scroll"
          :rows="5"
        >
          <Column field="opciones" header="Opciones">
            <template #body="slotProps">
              <Button
                icon="pi pi-check"
                class="p-button-sm p-button-success custom-icon-size"
                @click="seleccionarMarca(slotProps.data)"
              />
              <Button
                icon="pi pi-pencil"
                class="p-button-warning p-button-sm"
                @click="abrirModal('categoria', 'actualizar', slotProps.data)"
              />
            </template>
          </Column>
          <Column field="id" header="Id" />
          <Column field="nombre" header="Nombre" />
          <Column field="estado" header="Estado">
            <template #body="slotProps">
              <span :class="['status-badge', slotProps.data.condicion === 1 ? 'active' : 'inactive']">
                {{ slotProps.data.condicion === 1 ? 'Activo' : 'Inactivo' }}
              </span>
            </template>
          </Column>
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
      <Dialog :visible.sync="modal" :header="tituloModal" modal @hide="cerrarModal" :containerStyle="{ width: '700px' }" :closable="false" :closeOnEscape="false">
                <div class="p-fluid ">
                    <div class="p-field input-container">
                        <label for="nombre">Nombre marca</label>
                        <InputText id="nombre" v-model="nombre" required autofocus :class="{'p-invalid': nombreError}" @input="validarNombreEnTiempoReal" />
                        <small class="p-error error-message" v-if="nombreError"><strong>{{ nombreError }}</strong></small>
                    </div>
                </div>
                <template #footer>
                    <Button label="Cancelar" icon="pi pi-times" class="p-button-sm p-button-danger" @click="cerrarModal()" />
                    <Button v-if="tipoAccion === 1" label="Guardar" icon="pi pi-check" class="p-button-sm p-button-success" @click="registrarCategoria()" />
                    <Button v-if="tipoAccion === 2" label="Actualizar" icon="pi pi-check" class="p-button-sm p-button-warning" @click="actualizarCategoria()" />
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
            nombre:'',
            nombreError: '',
            tituloModal: '',
            tipoAccion: 0,
            errorCategoria: 0,
            arrayCategoria : [],
            criterio: 'nombre',
            buscar: '',
            marcaSeleccionado: null,
            marcas: [
                { nombre: 'Marca A', codigo: '001' },
                { nombre: 'Marca B', codigo: '002' },
            ],
        };
    },
    methods: {
        closeDialog() {
            this.$emit('close');
        },
        seleccionarMarca(data){
            console.log("data ",data.condicion)
            if (data.condicion == 0) {
                swal({
                    title: 'Marca Inactiva',
                    text: 'No puede seleccionar esta opción porque está inactiva.',
                    type: 'warning',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Aceptar',
                    confirmButtonClass: 'btn btn-success',
                    buttonsStyling: false,
                })
            } else {
                this.marcaSeleccionado = data;
                this.$emit('marca-seleccionado', this.marcaSeleccionado);
                this.closeDialog();
            }
            
        },
        buscarMarca() {
            this.listarCategoria(1, this.buscar,this.criterio);
        },
        registrarCategoria() {
            if (this.validarCategoria()) {
                return;
            }

            let me = this;

            axios.post('/marca/registrar', {
                'nombre': this.nombre,

            }).then(function (response) {
                console.log("Registrado");
                me.cerrarModal();
                me.listarCategoria(1, '', 'nombre');
            }).catch(function (error) {
                console.log(error);
            });
        },
        actualizarCategoria() {
            if (this.validarCategoria()) {
                return;
            }

            let me = this;

            axios.put('/marca/actualizar', {
                'nombre': this.nombre,
                'id': this.categoria_id
            }).then(function (response) {
                me.cerrarModal();
                me.listarCategoria(1, '', 'nombre');
            }).catch(function (error) {
                console.log(error);
            });
        },
        listarCategoria(page, buscar, criterio) {
            let me = this;
            console.log("Listano");
            var url = '/marcanewview?page=' + page + '&buscar=' + buscar + '&criterio=' + criterio;

            axios.get(url).then(function (response) {

                var respuesta = response.data;
                console.log(respuesta);

                me.arrayCategoria = respuesta.marcas;
                me.pagination = respuesta.pagination;
                console.log("Listad0");

            })
                .catch(function (error) {
                    console.log(error);
                });
        },
        validarCategoria() {
            let hasError = false;
            this.nombreError = '';
            if (!this.nombre.trim()) {
                this.nombreError = "El nombre de la marca no puede estar vacío.";
            }
        },
        validarNombreEnTiempoReal() {
            if (!this.nombre.trim()) {
                this.nombreError = "El nombre de la marca no puede estar vacío.";
            } else {
                this.nombreError = '';
            }
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
                                    this.tituloModal = 'Registrar marca';
                                    this.nombre = '';
                                    this.tipoAccion = 1;
                                    break;
                                }
                            case 'actualizar':
                                {
                                    //console.log(data);
                                    this.modal = true;
                                    this.modal1 = false;
                                    this.tituloModal = 'Actualizar marca';
                                    this.tipoAccion = 2;
                                    this.categoria_id = data['id'];
                                    this.nombre = data['nombre'];
                                    break;
                                }
                        }
                    }
            }
        },
        cerrarModal() {
            this.modal = false;
            this.modal1 =true;
            this.tituloModal = '';
            this.nombre = '';
            this.nombreError= '';
        },
    },
    mounted(){
        this.listarCategoria(1, this.buscar, this.criterio);
    }
};
</script>
<style scoped>
.toolbar-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}
.toolbar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}
.search-bar {
  display: flex;
  align-items: center;
  gap: 10px;
}
.search-bar .p-input-icon-left {
  width: 100%;
}
.search-bar .p-inputtext-sm {
  width: 100%;
}
.bold-input {
  font-weight: bold;
}
.p-fluid .p-field {
  margin-bottom: 1rem;
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
</style>