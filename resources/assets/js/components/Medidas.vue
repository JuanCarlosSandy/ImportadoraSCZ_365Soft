<template>
  <main class="main">


    <!-- Ejemplo de tabla Listado -->
    <div class="card">
      <div class="card-header">
        <i class="fa fa-align-justify"></i> Medida
        <button type="button" @click="abrirModal('medida', 'registrar')" class="btn btn-secondary">
          <i class="icon-plus"></i>&nbsp;Nuevo
        </button>
        <button type="button" @click="cargarExcel()" class="btn btn-info">
          <i class="icon-doc"></i>&nbsp;Exportar
        </button>
        <button type="button" @click="abrirModalImportMedida()" class="btn btn-success">
          <i class="icon-plus"></i>&nbsp;Importar
        </button>
      </div>
      <div class="card-body">
        <div class="form-group row">
          <div class="col-md-6">
            <div class="input-group">
              <select class="form-control col-md-3" v-model="criterio">
                <option value="descripcion_medida">Descripción</option>
                <option value="codigoClasificador">Código Clasificador</option>
              </select>
              <input type="text" v-model="buscar" @keyup.enter="listarMedidas(1, buscar, criterio)" class="form-control"
                placeholder="Texto a buscar">
              <button type="submit" @click="listarMedidas(1, buscar, criterio)" class="btn btn-primary"><i
                  class="fa fa-search"></i> Buscar</button>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-bordered table-striped table-sm">
            <thead>
              <tr>
                <th>Opciones</th>
                <th>Descripción</th>
                <th>Código Clasificador</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="medida in arrayMedida" :key="medida.id">
                <td class="sticky-column">
                  <button type="button" @click="abrirModal('medida', 'actualizar', medida)"
                    class="btn btn-warning btn-sm">
                    <i class="icon-pencil"></i>
                  </button> &nbsp;
                  <template v-if="medida.estado">
                    <button type="button" class="btn btn-danger btn-sm" @click="desactivarMedida(medida.id)">
                      <i class="icon-trash"></i>
                    </button>
                  </template>
                  <template v-else>
                    <button type="button" class="btn btn-info btn-sm" @click="activarMedida(medida.id)">
                      <i class="icon-check"></i>
                    </button>
                  </template>
                </td>
                <td v-text="medida.descripcion_medida"></td>
                <td v-text="medida.codigoClasificador"></td>
                <td>
                  <div v-if="medida.estado">
                    <span class="badge badge-success">Activo</span>
                  </div>
                  <div v-else>
                    <span class="badge badge-danger">Desactivado</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <nav>
          <ul class="pagination">
            <li class="page-item" v-if="paginationMedida.current_page > 1">
              <a class="page-link" href="#"
                @click.prevent="cambiarPagina(paginationMedida.current_page - 1, buscar, criterio)">Ant</a>
            </li>
            <li class="page-item" v-for="page in pagesNumber" :key="page" :class="[page == isActived ? 'active' : '']">
              <a class="page-link" href="#" @click.prevent="cambiarPagina(page, buscar, criterio)" v-text="page"></a>
            </li>
            <li class="page-item" v-if="paginationMedida.current_page < paginationMedida.last_page">
              <a class="page-link" href="#"
                @click.prevent="cambiarPagina(paginationMedida.current_page + 1, buscar, criterio)">Sig</a>
            </li>
          </ul>
        </nav>
      </div>
    </div>
    <!-- Fin ejemplo de tabla Listado -->

    <!-- Inicio del modal agregar/actualizar -->
    <div class="modal " tabindex="-1" :class="{ 'mostrar': modal }" role="dialog" aria-labelledby="myModalLabel"
      style="display: none;" aria-hidden="true">
      <div class="modal-dialog modal-primary modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title" v-text="tituloModal"></h4>
            <button type="button" class="close" @click="cerrarModal()" aria-label="Close">
              <span aria-hidden="true">×</span>
            </button>
          </div>
          <div class="modal-body">
            <form action="" method="post" enctype="multipart/form-data" class="form-horizontal">
              <div class="form-group row">
                <label class="col-md-3 form-control-label" for="text-input">Descripción</label>
                <div class="col-md-9">
                  <input type="text" v-model="descripcionMedida" class="form-control"
                    placeholder="Descripción de la medida">
                </div>
              </div>
              <div class="form-group row">
                <label class="col-md-3 form-control-label" for="text-input">Código Clasificador</label>
                <div class="col-md-9">
                  <input type="text" v-model="descripcionCorta" class="form-control"
                    placeholder="Descripción corta de la medida">
                </div>
              </div>
              <div v-show="errorMedida" class="form-group row div-error">
                <div class="text-center text-error">
                  <div v-for="error in errorMostrarMsjMedida" :key="error" v-text="error"></div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="cerrarModal()">Cerrar</button>
            <button type="button" v-if="tipoAccion === 1" class="btn btn-primary"
              @click="registrarMedida()">Guardar</button>
            <button type="button" v-if="tipoAccion === 2" class="btn btn-primary"
              @click="actualizarMedida()">Actualizar</button>
          </div>
        </div>
      </div>
    </div>
    <!-- Fin del modal -->
    <div class="modal " tabindex="-1" :class="{ 'mostrar': modalImportar }" role="dialog" aria-labelledby="myModalLabel"
      style="display: none;" aria-hidden="true">
      <div class="modal-dialog modal-primary modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Importar Medidas</h4>
            <button type="button" class="close" @click="cerrarModalImportar()" aria-label="Close">
              <span aria-hidden="true">×</span>
            </button>
          </div>
          <div class="modal-body">
            <form id="mainFormUsers">
              <div class="form-group">
                <table class="table">
                  <tr>
                    <label class="btn btn-primary" style="margin: 5px;" v-if="showUpload">Cargar_Archivo
                      <input type="submit" style="display: none;" name="upload" @click.prevent="saveExecelUser">
                    </label>
                    <div class="border border-dashed border-3 p-3 text-center" style="cursor: pointer">
                      <label class="custom-file">
                        <i class="fa fa-cloud-upload fa-2x" aria-hidden="true"></i>
                        <p class="custom-file-label">Seleccionar archivo CSV</p>
                        <input type="file" class="custom-file-input" @change="showUploadButton"
                          name="select_users_file">
                      </label>
                    </div>
                  </tr>
                </table>
              </div>
            </form>

          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="cerrarModalImportar()">Cerrar</button>
          </div>
        </div>
      </div>

    </div>
  </main>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      medida_id: 0,
      descripcionMedida: '',
      codigoClasificador: '',
      arrayMedida: [],
      modal: 0,
      tituloModal: '',
      tipoAccion: 0,
      errorMedida: 0,
      errorMostrarMsjMedida: [],
      paginationMedida: {
        'total': 0,
        'current_page': 0,
        'per_page': 0,
        'last_page': 0,
        'from': 0,
        'to': 0,
      },
      offset: 3,
      criterio: 'descripcion_medida',
      buscar: '',
      modalImportar: 0,
      showUpload: false,
    };
  },
  computed: {
    isActived: function () {
      return this.paginationMedida.current_page;
    },
    pagesNumber: function () {
      if (!this.paginationMedida.to) {
        return [];
      }

      var from = this.paginationMedida.current_page - this.offset;
      if (from < 1) {
        from = 1;
      }

      var to = from + this.offset * 2;
      if (to >= this.paginationMedida.last_page) {
        to = this.paginationMedida.last_page;
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
    //---------------- Exportar de excel----
    cargarExcel() {
      window.open('/medida/exportexcel', '_blank');
    },
    abrirModalImportMedida() {
      this.modalImportar = 1;
    },
    cerrarModalImportar() {
      this.modalImportar = 0;
      this.showUpload = false;
    },
    showUploadButton(event) {
      // Verifica si se ha seleccionado un archivo
      this.showUpload = event.target.files.length > 0;
    },
    //----------importar-------
    saveExecelUser() {
      var $mainFormUsers = $('#mainFormUsers')
      var data = new FormData(mainFormUsers)
      axios.post('/medida/import_excel', data)
        .then((res) => {
          console.log("Importado");
          swal(
            'IMPORTADO!',
            'Lista de Medidas.',
            'success'
          );
          this.cerrarModalImportar();
          this.listarMedidas(1, '', 'descripcion_medida');
        }).catch(function (error) {
          console.log(error);
        });
    },
    listarMedidas(page, buscar, criterio) {
      let me = this;
      var url = '/medida?page=' + page + '&buscar=' + buscar + '&criterio=' + criterio;
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayMedida = respuesta.medidas.data;
          me.paginationMedida = respuesta.paginationMedida;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    cambiarPagina(page, buscar, criterio) {
      let me = this;
      me.paginationMedida.current_page = page;
      me.listarMedidas(page, buscar, criterio);
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
    desactivarMedida(id) {
      swal({
        title: 'Esta seguro de desactivar esta Medida?',
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Aceptar!',
        cancelButtonText: 'Cancelar',
        confirmButtonClass: 'btn btn-success',
        cancelButtonClass: 'btn btn-danger',
        buttonsStyling: false,
        reverseButtons: true
      }).then((result) => {
        if (result.value) {
          let me = this;

          axios.put('/medida/desactivar', {
            'id': id
          }).then(function (response) {
            me.listarMedidas(1, '', 'nombre');
            swal(
              'Desactivado!',
              'El registro ha sido desactivado con éxito.',
              'success'
            )
          }).catch(function (error) {
            console.log(error);
          });


        } else if (
          // Read more about handling dismissals
          result.dismiss === swal.DismissReason.cancel
        ) {

        }
      })
    },
    activarMedida(id) {
      swal({
        title: 'Esta seguro de activar esta Medida?',
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Aceptar!',
        cancelButtonText: 'Cancelar',
        confirmButtonClass: 'btn btn-success',
        cancelButtonClass: 'btn btn-danger',
        buttonsStyling: false,
        reverseButtons: true
      }).then((result) => {
        if (result.value) {
          let me = this;

          axios.put('/medida/activar', {
            'id': id
          }).then(function (response) {
            me.listarMedidas(1, '', 'nombre');
            swal(
              'Activado!',
              'El registro ha sido activado con éxito.',
              'success'
            )
          }).catch(function (error) {
            console.log(error);
          });


        } else if (
          // Read more about handling dismissals
          result.dismiss === swal.DismissReason.cancel
        ) {

        }
      })
    },
    // ... Métodos para desactivar y activar medidas ...
    validarMedida() {
      this.errorMedida = 0;
      this.errorMostrarMsjMedida = [];

      if (!this.descripcionMedida) this.errorMostrarMsjMedida.push('La descripción de la medida no puede estar vacía.');
      if (!this.codigoClasificador) this.errorMostrarMsjMedida.push('El código clasificador no puede estar vacío.');

      if (this.errorMostrarMsjMedida.length) this.errorMedida = 1;

      return this.errorMedida;
    },
    cerrarModal() {
      this.modal = 0;
      this.tituloModal = '';
      this.descripcionMedida = '';
      this.codigoClasificador = '';
    },
    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case 'medida': {
          switch (accion) {
            case 'registrar': {
              this.modal = 1;
              this.tituloModal = 'Registrar Medida';
              this.descripcionMedida = '';
              this.codigoClasificador = '';
              this.tipoAccion = 1;
              break;
            }
            case 'actualizar': {
              this.modal = 1;
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
  },
  mounted() {
    this.listarMedidas(1, this.buscar, this.criterio);
  },
};
</script>

<style>
/* ... Estilos ... */
</style>
