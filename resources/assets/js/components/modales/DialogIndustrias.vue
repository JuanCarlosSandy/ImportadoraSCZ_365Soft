<template>
    <main class="main">
        <Dialog
            header="Industrias"
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
                @click="abrirModal('industria', 'registrar')"
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
                    @keyup="buscarIndustria"
                    
                />
                </span>
            </div>
            </div>
            <DataTable
            class="p-datatable-sm p-datatable-gridlines"
            :value="arrayIndustria"
            :paginator="true"
            responsiveLayout="scroll"
            :rows="5"
            >
            <Column field="opciones" header="Opciones">
                <template #body="slotProps">
                <Button
                    icon="pi pi-check"
                    class="p-button-sm p-button-success custom-icon-size"
                    @click="seleccionarIndustria(slotProps.data)"
                />
                <Button
                    icon="pi pi-pencil"
                    class="p-button-warning p-button-sm"
                    @click="abrirModal('industria', 'actualizar', slotProps.data)"
                />
                </template>
            </Column>
            <Column field="nombre" header="Nombre" />
            <Column field="estado" header="Estado">
                <template #body="slotProps">
                <span :class="['status-badge', slotProps.data.estado === 1 ? 'active' : 'inactive']">
                    {{ slotProps.data.estado === 1 ? 'Activo' : 'Inactivo' }}
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
        <Dialog :visible.sync="modal" modal :header="tituloModal"  @hide="cerrarModal" :containerStyle="{ width: '700px' }" :closable="false" :closeOnEscape="false">
                <template #footer>
                    <Button label="Cancelar" icon="pi pi-times" class="p-button-sm p-button-danger" @click="cerrarModal()" />
                    <Button v-if="tipoAccion === 1" label="Guardar" icon="pi pi-check" class="p-button-sm p-button-success" @click="registrarIndustria()" />
                    <Button v-if="tipoAccion === 2" label="Actualizar" icon="pi pi-check" class="p-button-sm p-button-warning" @click="actualizarIndustria()" />
                </template>
                <div class="p-fluid">
                    <div class="p-field">
                        <div class="p-field input-container">
                            <label for="name">Nombre Industria</label>
                            <InputText id="name" v-model="nombre" required autofocus :class="{'p-invalid': nombreError}" @input="validarNombreEnTiempoReal" />
                            <small class="p-error error-message" v-if="nombreError"> <strong>{{ nombreError }}</strong></small>
                        </div>
                    </div>
                </div>
        </Dialog>
    </main>
</template>

<script>
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
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
        InputText
    },
    data() {
        return {
            industria_id: 0,
            modal1: this.visible,
            modal: false,
            tituloModal: 'Registrar industria',
            tipoAccion: 1,
            arrayIndustria: [],
            criterio: 'nombre',
            buscar: '',
            nombre: '', 
            nombreError: '',
            industriaSeleccionado : null,
        };
    },
    methods: {
        closeDialog() {
            this.$emit('close');
        },
        seleccionarIndustria(data){
            console.log("data ",data.condicion)
            if (data.estado == 0) {
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
                this.industriaSeleccionado = data;
                this.$emit('industria-seleccionado', this.industriaSeleccionado);
                this.closeDialog();
            }
            
        },
        validarNombreEnTiempoReal() {
            if (this.nombre.trim() === '') {
                this.nombreError = "El nombre de Industria no puede estar vacío.";
            } else {
                this.nombreError = '';
            }
        },
        registrarIndustria() {
            if (this.validarIndustria()) {
                return;
            }
            let me = this;

            axios.post('/industria/registrar', {
                'nombre': this.nombre
            }).then(function (response) {
                me.cerrarModal();
                console.log(response)
                me.listarIndustria(1, '', 'nombre');
            }).catch(function (error) {
                console.log(error);
            });
        },
        actualizarIndustria() {
            if (this.validarIndustria()) {
                return;
            }

            let me = this;

            axios.put('/industria/actualizar', {
                'nombre': this.nombre,
                'id': this.industria_id
            }).then(function (response) {
                me.cerrarModal();
                me.listarIndustria(1, '', 'nombre');
            }).catch(function (error) {
                console.log(error);
            });
        },
        validarIndustria() {
            this.nombreError = '';

            if (!this.nombre.trim()) {
                this.nombreError = "El nombre de Industria no puede estar vacío.";
                return true;
            }

            return false;
        },
        buscarIndustria(){
            this.listarIndustria(1,this.buscar,this.criterio)
        },
        listarIndustria(page, buscar, criterio) {
          let url = '/industrianewview?page=' + page + '&buscar=' + buscar + '&criterio=' + criterio;
          axios.get(url)
              .then(response => {
                  this.arrayIndustria = response.data.industrias;
              })
              .catch(error => {
                  console.error(error);
              });
        },
        abrirModal(modelo, accion, data = []) {
        switch (modelo) {
                case "industria":
                    {
                        switch (accion) {
                            case 'registrar':
                                {
                                    this.modal = true;
                                    this.modal1 = false;
                                    this.tituloModal = 'Registrar Industria';
                                    this.nombre = '';
                                    //this.descripcion = '';
                                    this.tipoAccion = 1;
                                    break;
                                }
                            case 'actualizar':
                                {
                                    //console.log(data);
                                    this.modal = true;
                                    this.modal1 = false;
                                    this.tituloModal = 'Actualizar Industria';
                                    this.tipoAccion = 2;
                                    this.industria_id = data['id'];
                                    this.nombre = data['nombre'];
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
            this.nombreError = ''; // Limpiar el error
        },
    }, 
    mounted() {
        this.listarIndustria(1, '', this.criterio);
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
.searchbar {
  display: flex;
  align-items: center;    
  justify-content: flex-start;
  display: inline-block;
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