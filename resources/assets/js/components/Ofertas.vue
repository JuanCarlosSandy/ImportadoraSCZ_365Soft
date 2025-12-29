<template>
  <main class="main">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a class="text-decoration-none" href="/">Escritorio</a></li>
    </ol>
    <div class="container-fluid">
      <!-- Ejemplo de tabla Listado -->
      <div class="card">
        <div class="card-header">
          <i class="fa fa-align-justify"></i> Ofertas
          <icon-button icon="icon-plus" size="small" color="secondary" @click="abrirModal('oferta', 'registrar')"
            label="Nuevo" />
        </div>
        <div class="card-body">
          <div class="form-group row">
            <div class="col-md-6">
              <div class="input-group">
                <select class="form-control col-md-3" v-model="criterio">
                  <option value="nombre">Nombres</option>
                </select>
                <input type="text" v-model="buscar" @keyup.enter="listarOfertas(1, buscar, criterio)" class="form-control"
                  placeholder="Texto a buscar">
                <button type="submit" @click="listarOfertas(1, buscar, criterio)" class="btn btn-primary"><i
                    class="fa fa-search"></i> Buscar</button>
              </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
              <thead>
                <tr>
                  <th>Acciones</th>
                  <th>Nombre de la Oferta</th> 
                  <th>Descuento (%)</th> 

                  <th>Fecha de Inicio</th>
                  <th>Fecha de Finalización</th>
                  <th>Artículos Incluidos</th>

                  <th>Estado</th> 
                </tr>
              </thead>

              <tbody>
                <tr v-for="oferta in arrayOfertas" :key="oferta.id">
                  <td>
                    <icon-button icon="icon-pencil" size="small" color="warning"
                      @click="abrirModal('oferta', 'actualizar', oferta)" />
                    <icon-button icon="icon-eye" size="small" color="success" @click="abrirModalDetalles(oferta)" />
                    <template v-if="new Date(oferta.fecha_final) > new Date()">
                      <icon-button v-if="oferta.estado == 'Activo'" icon="icon-trash" size="small" color="danger"
                        @click="desactivarOferta(oferta.id)" />
                      <icon-button v-else icon="icon-check" size="small" color="info" @click="activarOferta(oferta.id)" />
                    </template>

                  </td>
                  <td v-text="oferta.nombre"></td>
                  <td>
                    {{ oferta.porcentaje }} %
                  </td>
                  <td>
                    {{ new Date(oferta.updated_at).toLocaleDateString('es-ES') }}
                  </td>
                  <td>
                    {{ new Date(oferta.fecha_final).toLocaleDateString('es-ES') }}
                  </td>
                  <td>{{ oferta.cantidad_articulos }} articulos</td>
                  <td>

                    <i class="fa fa-circle"
                      :style="{ color: getColorForEstado(oferta.estado, oferta.fecha_final) }"></i>&nbsp;
                    {{ new Date(oferta.fecha_final) < new Date() ? 'Vencido' : oferta.estado }} </td>
                </tr>
              </tbody>
            </table>
          </div>
          <nav>
            <ul class="pagination mt-2">
              <li class="page-item" v-if="pagination.current_page > 1">
                <a class="page-link" href="#"
                  @click.prevent="cambiarPagina(pagination.current_page - 1, buscar, criterio)">Anterior</a>
              </li>
              <li class="page-item" v-for="page in pagesNumber" :key="page" :class="[page == isActived ? 'active' : '']">
                <a class="page-link" href="#" @click.prevent="cambiarPagina(page, buscar, criterio)" v-text="page"></a>
              </li>
              <li class="page-item" v-if="pagination.current_page < pagination.last_page">
                <a class="page-link" href="#"
                  @click.prevent="cambiarPagina(pagination.current_page + 1, buscar, criterio)">Siguiente</a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
      <!-- Fin ejemplo de tabla Listado -->
    </div>
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
          <form @submit.prevent="enviarFormulario">
            <div class="modal-body">
              <div class="form-group row">
                <div class="col-md-8">
                  <label for="" class="font-weight-bold">Nombre de la oferta <span class="text-danger">*</span></label>
                  <input type="text" v-model="datosFormulario.nombre" class="form-control"
                    placeholder="Ej. Descuento Navideño" :class="{ 'is-invalid': errores.nombre }"
                    @input="validarCampo('nombre')" />
                  <p class="text-danger" v-if="errores.nombre">{{ errores.nombre }}</p>
                </div>
              </div>

              <div class="form-group row">
                <div class="col-md-6">
                  <label for="" class="font-weight-bold">Porcentaje de descuento <span
                      class="text-danger">*</span></label>
                  <input type="number" v-model="datosFormulario.porcentaje" class="form-control"
                    placeholder="Ej. 10 (sin símbolo de %)" :class="{ 'is-invalid': errores.porcentaje }"
                    @input="validarCampo('porcentaje')" />
                  <p class="text-danger" v-if="errores.porcentaje">{{ errores.porcentaje }}</p>
                </div>

                <div class="col-md-6">
                  <label for="" class="font-weight-bold">Fecha final <span class="text-danger">*</span></label>
                  <input type="date" v-model="datosFormulario.fecha_final" class="form-control"
                    :class="{ 'is-invalid': errores.fecha_final }" @input="validarCampo('fecha_final')" :min="hoy" />
                  <p class="text-danger" v-if="errores.fecha_final">{{ errores.fecha_final }}</p>
                </div>

              </div>
              <button class="btn btn-secondary btn-sm" type="button" @click="abrirModalArticulos">Agregar productos a la
                oferta</button>
              <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                  <thead>
                    <tr>
                      <th>Acciones</th>
                      <th>Código</th>
                      <th>Nombre comercial</th>
                      <th>Costo unit</th>
                      <th>Costo paquete</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="articulo in arrayArticulosSeleccionados" :key="articulo.id">
                      <td>
                        <button type="button" @click="quitarArticulo(articulo)" class="btn btn-danger btn-sm">
                          Quitar
                        </button>
                      </td>
                      <td v-text="articulo.codigo"></td>
                      <td v-text="articulo.nombre"></td>
                      <td>

                        <p style="text-decoration-line: line-through;color:red">
                          {{ (articulo.precio_costo_unid * parseFloat(monedaPrincipal[0])).toFixed(2) }} {{
                            monedaPrincipal[1] }}
                        </p>
                        <p>{{ ((articulo.precio_costo_unid * parseFloat(monedaPrincipal[0])) * (1 -
                          datosFormulario.porcentaje / 100)).toFixed(2) }} {{ monedaPrincipal[1] }} </p>

                      </td>
                      <td>
                        <p style="text-decoration-line: line-through;color:red">
                          {{ ((articulo.precio_costo_paq) * parseFloat(monedaPrincipal[0])).toFixed(2) }} {{
                            monedaPrincipal[1] }}

                        </p>
                        <p>{{ (((articulo.precio_costo_paq) * parseFloat(monedaPrincipal[0])) * (1 -
                          datosFormulario.porcentaje / 100)).toFixed(2) }} {{ monedaPrincipal[1] }} </p>


                      </td>

                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-danger" @click="cerrarModal()">Cerrar</button>
              <button type="submit" v-if="tipoAccion == 1" class="btn btn-success">Guardar</button>
              <button type="submit" v-if="tipoAccion == 2" class="btn btn-success">Actualizar</button>
            </div>
          </form>

        </div>
      </div>
    </div>
    <!-- Fin del modal -->

    <div class="modal " tabindex="-1" :class="{ 'mostrar': modalProductos }" role="dialog"
      aria-labelledby="myModalLabel" style="display: none;" aria-hidden="true">
      <div class="modal-dialog modal-primary modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h6>Seleccione articulos</h6>
          </div>
          <div class="modal-body">
            <div class="form-group row">
              <div class="col-md-6">
                <div class="input-group">
                  <select class="form-control col-md-3" v-model="criterioA">
                    <option value="nombre">Nombre</option>
                    <option value="descripcion">Descripción</option>
                    <option value="codigo">Código</option>
                  </select>
                  <input type="text" v-model="buscarA" @keyup="listarArticulo(1, buscarA, criterioA)" class="form-control"
                    placeholder="Texto a buscar">
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-striped table-sm">
                <thead>
                  <tr>
                    <th>Opciones</th>
                    <th>Código</th>
                    <th>Nombre comercial</th>
                    <th>Costo unit</th>
                    <th>Costo paquete</th>
                    <th>Proveedor habitual</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="articulo in arrayArticulo" :key="articulo.id">
                    <td>
                      <button type="button" @click="agregarDetalleModal(articulo)" class="btn btn-success btn-sm">
                        <i class="icon-check"></i>
                      </button>
                    </td>
                    <td v-text="articulo.codigo"></td>
                    <td v-text="articulo.nombre"></td>
                    <td>
                      {{ (articulo.precio_costo_unid * parseFloat(monedaPrincipal[0])).toFixed(2) }} {{ monedaPrincipal[1]
                      }}

                    </td>
                    <td>
                      {{ ((articulo.precio_costo_unid * articulo.unidad_envase) *
                        parseFloat(monedaPrincipal[0])).toFixed(2) }}
                      {{ monedaPrincipal[1] }}

                    </td>
                    <td v-text="articulo.nombre_proveedor"></td>
                    <td>
                      <div v-if="articulo.condicion">
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
          </div>
          <div class="modal-footer">

            <button type="button" class="btn btn-danger" @click="() => modalProductos = 0">Cerrar</button>

          </div>
        </div>
      </div>
    </div>

    <div class="modal " tabindex="-1" :class="{ 'mostrar': modalDetalle }" role="dialog"
      aria-labelledby="myModalLabel" style="display: none;" aria-hidden="true">
      <div class="modal-dialog modal-primary modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            Detalle de la oferta
          </div>
          <div class="modal-body">
            <div class="col-md-8">
              <label for="" class="font-weight-bold">Nombre de la oferta: </label>
              {{ datosFormulario.nombre }}
            </div>
            <div class="col-md-5">
              <label for="" class="font-weight-bold">Descuento: </label>
              {{ datosFormulario.porcentaje }} %
            </div>
            <div class="col-md-5">
              <label for="" class="font-weight-bold">Fecha final: </label>
              {{ datosFormulario.fecha_final }}
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-striped table-sm">
                <thead>
                  <tr>
                    <th>Código</th>
                    <th>Nombre comercial</th>
                    <th>Costo unit</th>
                    <th>Costo paquete</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="articulo in arrayArticulosSeleccionados" :key="articulo.id">

                    <td v-text="articulo.codigo"></td>
                    <td v-text="articulo.nombre"></td>
                    <td>

                      <p style="text-decoration-line: line-through;color:red">
                        {{ (articulo.precio_costo_unid * parseFloat(monedaPrincipal[0])).toFixed(2) }} {{
                          monedaPrincipal[1]
                        }}
                      </p>
                      <p>{{ ((articulo.precio_costo_unid * parseFloat(monedaPrincipal[0])) * (1 -
                        datosFormulario.porcentaje / 100)).toFixed(2) }} {{ monedaPrincipal[1] }} </p>

                    </td>
                    <td>
                      <p style="text-decoration-line: line-through;color:red">
                        {{ ((articulo.precio_costo_paq) * parseFloat(monedaPrincipal[0])).toFixed(2) }} {{
                          monedaPrincipal[1]
                        }}

                      </p>
                      <p>{{ (((articulo.precio_costo_paq) * parseFloat(monedaPrincipal[0])) * (1 -
                        datosFormulario.porcentaje / 100)).toFixed(2) }} {{ monedaPrincipal[1] }} </p>


                    </td>

                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" @click="() => modalDetalle = 0">Cerrar</button>

          </div>
        </div>
      </div>
    </div>
  </main>
</template>
    
<script>
import { esquemaOfertas } from '../constants/validations';
export default {
  data() {
    return {
      hoy: this.obtenerFechaActual(),
      monedaPrincipal: [],
      datosFormulario: {
        codigo: 'NA',
        nombre: '',
        porcentaje: 0,
        fecha_final: '',
        estado: 'Activo',
        tipo_promocion: 1,
        precio: 0
      },
      errores: {},


      arrayOfertas: [],
      arrayArticulosSeleccionados: [],
      arrayArticulo: [],

      criterio: 'num_comprobante',
      buscar: '',
      criterioA: 'nombre',
      buscarA: '',
      modal: 0,
      modalProductos: 0,
      modalDetalle: 0,
      tituloModal: '',
      tipoAccion: 0,
      pagination: {
        'total': 0,
        'current_page': 0,
        'per_page': 0,
        'last_page': 0,
        'from': 0,
        'to': 0,
      },
      offset: 3,
      criterio: 'nombre',
      arrayEmpresa: [],
      buscar: ''
    }
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

      var to = from + (this.offset * 2);
      if (to >= this.pagination.last_page) {
        to = this.pagination.last_page;
      }

      var pagesArray = [];
      while (from <= to) {
        pagesArray.push(from);
        from++;
      }
      return pagesArray;

    }
  },

  methods: {
    obtenerFechaActual() {
      const hoy = new Date();
      const mes = hoy.getMonth() + 1;
      const formatoMes = mes < 10 ? `0${mes}` : mes;
      return `${hoy.getFullYear()}-${formatoMes}-${hoy.getDate()}`;
    },
    getColorForEstado(estado, fecha_final) {
      const fechaFinal = new Date(fecha_final) < new Date();

      if (fechaFinal) {
        return '#ff0000';
      }
      switch (estado) {
        case 'Activo':
          return '#5ebf5f';
        case 'Inactivo':
          return '#d76868';
        case 'Vencido':
          return '#ce4444';
        default:
          return '#f9dda6';
      }
    },

    obtenerDatosPromocion(idPromocion) {
      axios.get('/ofertas/id', {
        params: {
          idPromocion: idPromocion
        }
      })
        .then((response) => {
          const datos = response.data.articulosPorPromocion
          this.arrayArticulosSeleccionados = datos.map(item => item.articulo);
        })
        .catch((error) => {
          console.error(error);
        });
    },

    quitarArticulo(idAEliminar) {
      let indexAEliminar = this.arrayArticulosSeleccionados.findIndex(objeto => objeto.id === idAEliminar);
      this.arrayArticulosSeleccionados.splice(indexAEliminar, 1);
    },
    agregarDetalleModal(dato) {
      const objetoExistente = this.arrayArticulosSeleccionados.find(item => {
        return item.id === dato.id;
      });

      if (!objetoExistente) {
        this.arrayArticulosSeleccionados.push(dato);
        this.modalProductos = 0;

      } else {
        this.toastError("Este articulo ya agrego")

      }

    },

    datosConfiguracion() {
      let me = this;
      var url = '/configuracion';

      axios.get(url).then(function (response) {
        var respuesta = response.data;

        me.monedaPrincipal = [respuesta.configuracionTrabajo.valor_moneda_principal, respuesta.configuracionTrabajo.simbolo_moneda_principal]

      })
        .catch(function (error) {
          console.log(error);
        });
    },
    listarArticulo(page, buscar, criterio) {
      let me = this;
      var url = '/articulo?page=' + page + '&buscar=' + buscar + '&criterio=' + criterio;
      axios.get(url).then(function (response) {
        var respuesta = response.data;
        me.arrayArticulo = respuesta.articulos.data;
        me.pagination = respuesta.pagination;
      })
        .catch(function (error) {
          console.log(error);
        });
    },
    async validarCampo(campo) {
      try {
        await esquemaOfertas.validateAt(campo, this.datosFormulario);
        this.errores[campo] = null;
      } catch (error) {
        this.errores[campo] = error.message;
      }
    },

    async enviarFormulario() {
      await esquemaOfertas.validate(this.datosFormulario, { abortEarly: false })
        .then(() => {
          if (this.arrayArticulosSeleccionados.length > 0) {
            if (this.tipoAccion == 2) {
              this.actualizarOFerta(this.datosFormulario);
            } else {

              this.registrarOferta(this.datosFormulario);
            }
          } else {
            this.toastError("Seleccione al menos un articulo a la oferta")
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
    listarOfertas(page, buscar, criterio) {
      let me = this;
      let url = '/ofertas';

      axios.get(url, {
        params: {
          page: page,
          buscar: buscar,
          criterio: criterio
        }
      }).then(function (response) {
        let respuesta = response.data;
        me.arrayOfertas = response.data.ofertas.data;
        me.pagination = respuesta.pagination;
      }).catch(function (error) {
        console.log(error);
      });
    },

    cambiarPagina(page, buscar, criterio) {
      let me = this;
      me.pagination.current_page = page;
      me.listarOfertas(page, buscar, criterio);
    },


    registrarOferta(datos) {
      datos['articulos'] = this.arrayArticulosSeleccionados;

      axios.post('/ofertas/registrar', datos)
        .then((response) => {
          swal(
            'OFERTA REGISTRADO',
            'Correctamente',
            'success'
          );
          this.cerrarModal();
          this.listarOfertas(1, '', 'nombre');
        })
        .catch((error) => {
          if (error.response && error.response.status === 422 && error.response.data.errors) {
            const errores = error.response.data.errors;
            Object.values(errores).forEach(errorMsg => {
              swal('Error de Validación', errorMsg[0], 'error');
            });
          } else {
            swal('Error', 'Hubo un problema al procesar la solicitud.', 'error');
          }
        });
    },



    actualizarOFerta(datos) {
      datos['articulos'] = this.arrayArticulosSeleccionados;
      axios.put('/ofertas/actualizar', datos)
        .then((response) => {
          swal(
            'OFERTA ACTUALIZADO',
            'Correctamente',
            'success'
          );
          this.cerrarModal();
          this.listarOfertas(1, '', 'nombre');
        })
        .catch((error) => {
          console.log(error.response.data);
          swal(
            'ERROR AL ACTUALIZAR EL OFERTA',
            'Intente de nuevo',
            'error'
          );
        });

    },
    abrirModalDetalles(data) {
      this.arrayArticulosSeleccionados = [];

      this.modalDetalle = 1;
      this.datosFormulario = {
        id: data['id'],
        nombre: data['nombre'],
        porcentaje: data['porcentaje'],
        codigo: data['codigo'],

        fecha_final: (new Date(data['fecha_final'])).toISOString().split('T')[0],
        tipo_promocion: data['tipo_promocion'],
        estado: data['estado'],
        precio: data['precio'],

      };
      this.obtenerDatosPromocion(data['id'])

    },
    abrirModalArticulos() {
      this.modalProductos = 1;

      this.listarArticulo(1, '', 'nombre')
    },
    desactivarOferta(id) {
      swal({
        title: 'Esta seguro de desactivar este oferta?',
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

          axios.put('ofertas/estado', {
            'id': id,
            'estado': 'Inactivo'
          }).then(function (response) {
            me.listarOfertas(1, '', 'nombre');
            swal(
              'Desactivado!',
              'El registro ha sido desactivado con éxito.',
              'success'
            )
          }).catch(function (error) {
            console.log(error);
          });


        } else if (
          result.dismiss === swal.DismissReason.cancel
        ) {

        }
      })
    },
    activarOferta(id) {
      swal({
        title: 'Esta seguro de activar este oferta?',
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

          axios.put('/ofertas/estado', {
            'id': id,
            'estado': 'Activo'
          }).then(function (response) {
            me.listarOfertas(1, '', 'nombre');
            swal(
              'Activado!',
              'El registro ha sido activado con éxito.',
              'success'
            )
          }).catch(function (error) {
            console.log(error);
          });


        } else if (
          result.dismiss === swal.DismissReason.cancel
        ) {

        }
      })
    },
    cerrarModal() {
      this.modal = 0;
      this.tituloModal = '';
      this.errores = {};
    },
    toastSuccess(mensaje) {
      this.$toasted.show(`
    <div style="height: 60px;font-size:16px;">
        <br>
        `+ mensaje + `.<br>
    </div>`, {
        type: "success",
        position: "bottom-right",
        duration: 4000
      });
    },
    toastError(mensaje) {
      this.$toasted.show(`
    <div style="height: 60px;font-size:16px;">
        <br>
        `+ mensaje + `<br>
    </div>`, {
        type: "error",
        position: "bottom-right",
        duration: 4000
      });
    },
    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "oferta":
          {
            switch (accion) {
              case 'registrar':
                {
                  this.arrayArticulosSeleccionados = [];

                  this.modal = 1;
                  this.tituloModal = 'Registrar nuevo oferta';
                  this.datosFormulario = {
                    codigo: 'NA',
                    nombre: '',
                    porcentaje: 0,
                    fecha_final: '',
                    estado: 'Activo',
                    tipo_promocion: 1,
                    precio: 0
                  },
                    this.tipoAccion = 1;

                  break;
                }
              case 'actualizar':
                {
                  this.arrayArticulosSeleccionados = [];

                  this.modal = 1;
                  this.tituloModal = 'Actualizar oferta';
                  this.tipoAccion = 2;
                  this.datosFormulario = {
                    id: data['id'],
                    nombre: data['nombre'],
                    porcentaje: data['porcentaje'],
                    codigo: data['codigo'],

                    fecha_final: (new Date(data['fecha_final'])).toISOString().split('T')[0],
                    tipo_promocion: data['tipo_promocion'],
                    estado: data['estado'],
                    precio: data['precio'],

                  };
                  this.obtenerDatosPromocion(data['id'])
                  break;
                }
            }
          }
      }
    }
  },
  mounted() {
    this.listarOfertas(1, this.buscar, this.criterio);
    this.datosConfiguracion()
  }
}
</script>
  
  