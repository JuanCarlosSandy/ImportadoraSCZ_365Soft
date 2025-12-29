<template>
    <main class="main">
        <Dialog :visible.sync="modal1" header="Grupo/Familia" modal :containerStyle="{ width: '700px' }" :closable="false" :closeOnEscape="false">
            <div class="toolbar-container">
              <div class="toolbar">
                    <Button label="Nuevo" icon="pi pi-plus" @click="abrirModal('grupos','registrar')" class="p-button-secondary p-button-sm" />   
                </div>
                <div class="search-bar">
                    <span class="p-input-icon-left">
                        <i class="pi pi-search" />
                        <InputText type="text" v-model="buscar" placeholder="Buscar por nombre" class="p-inputtext-sm" @keyup="buscarGrupo" />
                    </span>
                </div>
                
            </div>
            <DataTable :value="arrayGrupo" class="p-datatable-gridlines p-datatable-sm"   responsiveLayout="scroll" paginator :rows="5">
                    <Column header="Opciones">
                        <template #body="slotProps">
                            <Button icon="pi pi-check" class="p-button-sm p-button-success" @click="seleccionarGrupo(slotProps.data)"/>
                            <Button icon="pi pi-pencil" class="p-button-sm p-button-warning" @click="abrirModal('grupos','actualizar',slotProps.data)" />
                        </template>
                    </Column>
                    <Column field="nombre_grupo" header="Nombre"></Column>
                </DataTable>
            <div class="d-flex justify-content-end mt-2">
                <Button label="Cerrar" icon="pi pi-times" class="p-button-danger" @click="closeDialog" />
            </div>
        </Dialog>
        <Dialog :visible.sync="modal" modal :header="tituloModal" @hide="cerrarModal" :containerStyle="{ width: '700px' }" :closable="false" :closeOnEscape="false">
            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" class="p-button-sm p-button-danger" @click="cerrarModal()" />
                <Button v-if="tipoAccion === 1" label="Guardar" icon="pi pi-check" class="p-button-sm p-button-success" @click="registrarGrupo()" />
                <Button v-if="tipoAccion === 2" label="Actualizar" icon="pi pi-check" class="p-button-sm p-button-warning" @click="actualizarGrupo()" />
            </template>
            <div class="p-fluid">
                <div class="p-field">
                    <div class="p-field input-container">
                        <label for="name">Nombre Grupo</label>
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
            arrayGrupo: [],
            buscar: '',
            nombre: '', 
            nombreError: '',
            tituloModal: 'Registrar Grupo',
            modal: false,
            modal1: this.visible,
            grupoSeleccionado : null,
        };
    },
    methods: {
        closeDialog() {
            this.$emit('close');
        },
        seleccionarGrupo(data){
            this.grupoSeleccionado = data;
            this.$emit('grupo-seleccionado', this.grupoSeleccionado);
            this.closeDialog();
        },
        buscarGrupo(){
            this.listarGrupo(1,this.buscar,'nombre_grupo')
        },
        listarGrupo (page,buscar,criterio){
            let me=this;
            //var url= '/industria?page=' + page + '&buscar='+ buscar + '&criterio='+ criterio;
            var url= '/gruposnewview?page=' + page + '&buscar='+ buscar + '&criterio='+ criterio;
            axios.get(url).then(function (response) {
                var respuesta= response.data;
                me.arrayGrupo = respuesta.grupos;
            })
            .catch(function (error) {
                console.log(error);
            });
        },
        registrarGrupo(){
            if (this.validarGrupo()){
                return;
            }
            let me = this;

            axios.post('/grupos/registrar',{
                'nombre_grupo': this.nombre           
            }).then(function (response) {
                me.cerrarModal();
                console.log(response)
                me.listarGrupo(1,'','nombre_grupo');
            }).catch(function (error) {
                console.log(error);
            });
        },
        actualizarGrupo(){
        if (this.validarGrupo()){
                return;
            }
            
            let me = this;

            axios.put('/grupos/actualizar',{
                'id' : this.grupo_id,
                'nombre_grupo': this.nombre
            }).then(function (response) {
                me.cerrarModal();
                me.listarGrupo(1,'','nombre_grupo');
            }).catch(function (error) {
                console.log(error);
            }); 
        },
        validarGrupo() {
            this.nombreError = '';
            if (!this.nombre.trim()) {
                this.nombreError = "El nombre del grupo no puede estar vacío.";
                return true;
            }
  
              return false;
        },
        validarNombreEnTiempoReal() {
            if (this.nombre.trim() === '') {
                this.nombreError = "El nombre del grupo no puede estar vacío.";
            } else {
                this.nombreError = '';
            }
        },
        abrirModal(modelo, accion, data = []){
            switch(modelo){
                case "grupos":
                {
                    switch(accion){
                        case 'registrar':
                        {
                            this.modal = true;
                            this.modal1 = false;
                            this.tituloModal = 'Registrar Grupo';
                            this.tipoAccion = 1;
                            this.nombre = '';
                            break;
                        }
                        case 'actualizar':
                        {
                            //console.log(data);
                            this.modal=true;
                            this.modal1 = false;
                            this.tituloModal='Actualizar Grupo';
                            this.tipoAccion=2;
                            this.grupo_id=data['id'];
                            this.nombre = data['nombre_grupo'];
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
        this.listarGrupo(1, '', this.criterio);
    }
};
</script>
<style scoped>
.p-dialog-mask {
  z-index: 9999 !important;
}
.p-dialog {
  z-index: 10000 !important;
}
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
</style>