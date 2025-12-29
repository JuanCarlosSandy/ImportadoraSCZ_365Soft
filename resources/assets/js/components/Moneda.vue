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
                    <i class="fa fa-align-justify"></i> Moneda
                    <icon-button icon="icon-plus" size="small" color="secondary" @click="abrirModal('moneda', 'registrar')" label="Nuevo"/>
                </div>
                <div class="card-body">
                    <div class="form-group row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <select class="form-control col-md-3" v-model="criterio">
                                    <option value="nombre">Nombre</option>
                                    <option value="pais">País</option>
                                    <option value="simbolo">Símbolo</option>
                                    <option value="tipo_cambio">Tipo cambio</option>
                                </select>
                                <input type="text" v-model="buscar" @keyup.enter="listarMoneda(1, buscar, criterio)"
                                    class="form-control" placeholder="Texto a buscar">
                                <button type="submit" @click="listarMoneda(1, buscar, criterio)" class="btn btn-primary"><i
                                        class="fa fa-search"></i> Buscar</button>
                            </div>
                        </div>
                        <div class="col-md-2 mt-1 mx-auto">
                            <small class="text-muted">Mostrar:&nbsp;</small>

                            <select class="custom-select bg-light" v-model="itemsPerPage" @change="cambiarNumeroElementos">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-5  align-items-center ">

                            <label class="form-control-label" for="text-input"><b>Moneda de Referencia:</b> Dólar
                                Estadounidense (USD)</label>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Acciones</th>
                                    <th>Empresa</th>
                                    <th>Nombre de la moneda</th>
                                    <th>Codigo</th>
                                    <th>País</th>
                                    <th>Tipo cambio</th>
                                    <th>Fecha actualizacion</th>
                                    <th>Estado</th>

                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="moneda in arrayMoneda" :key="moneda.id">
                                    <td>
                              <icon-button icon="icon-pencil" size="small" color="warning" @click="abrirModal('moneda', 'actualizar', moneda)" />
                              <icon-button v-if="moneda.activo" icon="icon-trash" size="small" color="danger" @click="desactivarMoneda(moneda.id)" />
                    <icon-button v-else icon="icon-check" size="small" color="info" @click="activarMoneda(moneda.id)"/>


        
                                    </td>
                                    <td v-text="moneda.nombre_empresa"></td>
                                    <td v-text="moneda.nombre"></td>
                                    <td v-text="moneda.simbolo"></td>
                                    <td>
                                        <img :src="getFlagUrl(moneda.pais)" alt="Bandera" v-if="getFlagUrl(moneda.pais)"
                                            class="flag-img">

                                        {{ moneda.pais }}
                                    </td>



                                    <td v-text="moneda.tipo_cambio"></td>
                                    <td v-text="formatFecha(moneda.updated_at)"></td>

                                    <td>
                                        <div v-if="moneda.activo">
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
                            <li class="page-item" v-if="pagination.current_page > 1">
                                <a class="page-link" href="#"
                                    @click.prevent="cambiarPagina(pagination.current_page - 1, buscar, criterio)">Ant</a>
                            </li>
                            <li class="page-item" v-for="page in pagesNumber" :key="page"
                                :class="[page == isActived ? 'active' : '']">
                                <a class="page-link" href="#" @click.prevent="cambiarPagina(page, buscar, criterio)"
                                    v-text="page"></a>
                            </li>
                            <li class="page-item" v-if="pagination.current_page < pagination.last_page">
                                <a class="page-link" href="#"
                                    @click.prevent="cambiarPagina(pagination.current_page + 1, buscar, criterio)">Sig</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
            <!-- Fin ejemplo de tabla Listado -->
        </div>
        <!--Inicio del modal agregar/actualizar-->
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
                                <div class="col-md-6">
                                    <label for="" class="font-weight-bold">Nombre de la moneda <span
                                            class="text-danger">*</span></label>
                                    <input type="text" v-model="datosFormulario.nombre" class="form-control"
                                        placeholder="Ej. Dolar estadounidense" :class="{ 'is-invalid': errores.nombre }"
                                        @input="validarCampo('nombre')">
                                    <p class="text-danger" v-if="errores.nombre">{{ errores.nombre }}</p>

                                </div>
                                <div class="col-md-6">
                                    <label for="" class="font-weight-bold">Pais <span class="text-danger">*</span></label>

                                    <input class="form-control" type="text" id="country" v-model="selectedCountry"
                                        @input="filterCountries" 
                                        @keydown.enter="selectClosestCountry" placeholder="Buscar país"
                                        :class="{ 'is-invalid': errores.pais }" />

                                    <ul class="ulAutocomplete" v-if="showDropdown">
                                        <li class="liAutocomplete" v-for="(country, code) in filteredCountries" :key="code"
                                            @click="selectCountry(code)">
                                            {{ country }}
                                        </li>
                                    </ul>
                                    <p class="text-danger" v-if="errores.pais">{{ errores.pais }}</p>

                                </div>
                            </div>

                            <div class="form-group row">

                                <div class="col-md-6">
                                    <label for="" class="font-weight-bold">Tipo cambio <span
                                            class="text-danger">*</span></label>

                                    <input type="number" v-model="datosFormulario.tipo_cambio" class="form-control"
                                        step=".01" placeholder="" :class="{ 'is-invalid': errores.tipo_cambio }"
                                        @input="validarCampo('tipo_cambio')">
                                    <p class="text-danger" v-if="errores.tipo_cambio">{{ errores.tipo_cambio }}</p>

                                </div>
                                <div class="col-md-6">
                                    <label for="" class="font-weight-bold">Codigo de la moneda <span
                                            class="text-danger">*</span></label>

                                    <input type="text" v-model="datosFormulario.simbolo" class="form-control"
                                        placeholder="Ej. USD" :class="{ 'is-invalid': errores.simbolo }"
                                        @input="validarCampo('simbolo')">
                                    <p class="text-danger" v-if="errores.simbolo">{{ errores.simbolo }}</p>

                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" @click="cerrarModal()">Cerrar</button>
                            <button type="submit" v-if="tipoAccion == 1" class="btn btn-success">Guardar</button>
                            <button type="submit" v-if="tipoAccion == 2" class="btn btn-success">Actualizar</button>
                        </div>
                    </form>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!--Fin del modal-->
    </main>
</template>

<script>
import VueBarcode from 'vue-barcode';

import { countries } from './../constants/countries.js';
import { esquemaMoneda } from '../constants/validations';
export default {
    data() {
        return {
            datosFormulario: {
                nombre: '',
                pais: '',
                simbolo: '',
                tipo_cambio: 0,
            },
            errores: {},

            itemsPerPage: 5,

            countries: countries,
            selectedCountry: "",
            filteredCountries: {},
            showDropdown: false,
            arrayMoneda: [],
            modal: 0,
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

            let from = this.pagination.current_page - this.offset;
            if (from < 1) {
                from = 1;
            }

            let to = from + (this.offset * 2);
            if (to >= this.pagination.last_page) {
                to = this.pagination.last_page;
            }

            let pagesArray = [];
            while (from <= to) {
                pagesArray.push(from);
                from++;
            }
            return pagesArray;

        }
    },
    methods: {
        async validarCampo(campo) {
            try {
                await esquemaMoneda.validateAt(campo, this.datosFormulario);
                this.errores[campo] = null;
            } catch (error) {
                this.errores[campo] = error.message;
            }
        },
        async enviarFormulario() {

            await esquemaMoneda.validate(this.datosFormulario, { abortEarly: false })
                .then(() => {
                    if (this.tipoAccion == 2) {
                        this.actualizarMoneda(this.datosFormulario);
                    } else {
                        this.registrarMoneda(this.datosFormulario);
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
        cambiarNumeroElementos() {
            this.listarMoneda(1, this.buscar, this.criterio);

        },
        formatFecha(fechaOriginal) {
            const fecha = new Date(fechaOriginal);
            const dia = fecha.getDate();
            const mes = fecha.getMonth() + 1;
            const anio = fecha.getFullYear();

            const diaFormateado = dia < 10 ? `0${dia}` : dia;
            const mesFormateado = mes < 10 ? `0${mes}` : mes;

            return `${diaFormateado}-${mesFormateado}-${anio}`;
        },
        getFlagUrl(countryName) {
            const code = this.getCountryCodeByName(countryName);
            return code ? `https://flagcdn.com/${code.toLowerCase()}.svg` : null;
        },
        getCountryCodeByName(countryName) {
            const lowerCaseName = countryName.toLowerCase();
            for (const [code, name] of Object.entries(this.countries)) {
                if (name.toLowerCase() === lowerCaseName) {
                    return code;
                }
            }
            return null;
        },
        filterCountries() {

            const query = this.selectedCountry.toLowerCase();
            const filteredEntries = Object.entries(this.countries).filter(([code, country]) =>
                country.toLowerCase().includes(query)
            );
            this.filteredCountries = Object.fromEntries(filteredEntries.slice(0, 5));
            this.showDropdown = filteredEntries.length > 0;

        },
        selectCountry(code) {
            this.selectedCountry = this.countries[code];
            this.showDropdown = false;
            this.pais = this.selectedCountry;
            this.datosFormulario.pais=this.pais;  
            this.validarCampo('pais');


        },
        selectClosestCountry() {
            const firstCountryCode = Object.keys(this.filteredCountries)[0];
            if (firstCountryCode) {
                this.selectCountry(firstCountryCode);
            }
        },
        listarMoneda(page, buscar, criterio) {
            let me = this;
            let url = '/moneda?page=' + page + '&buscar=' + buscar + '&criterio=' + criterio + '&per_page=' + this.itemsPerPage;
            axios.get(url).then(function (response) {
                let respuesta = response.data;
                me.arrayMoneda = respuesta.monedas.data;
                me.pagination = respuesta.pagination;
            })
                .catch(function (error) {
                    console.log(error);
                });
        },
        cambiarPagina(page, buscar, criterio) {
            let me = this;
            me.pagination.current_page = page;
            me.listarMoneda(page, buscar, criterio);
        },
        registrarMoneda(datos) {
    axios.post('/moneda/registrar', datos)
        .then((response) => {
            this.cerrarModal();
            this.listarMoneda(1, '', 'nombre');
            this.toastSuccess("Moneda registrada correctamente");
        })
        .catch((error) => {
            if (error.response) {
                this.toastError(`${error.response.data.error}`);
                console.error("Error al registrar la moneda: ", error.response.data.error);
            } else if (error.request) {
                this.toastError("No se recibió respuesta del servidor.");
                console.error("No se recibió respuesta del servidor.");
            } else {
                this.toastError("Error al realizar la solicitud.");
                console.error("Error al realizar la solicitud: ", error.message);
            }
        });
},

        actualizarMoneda(datos) {

            axios.put('/moneda/actualizar', datos).then((response) => {
                this.cerrarModal();
                this.toastSuccess("Moneda actualizada correctamente")

                this.listarMoneda(1, '', 'nombre');
            }).catch((error) => {
                console.error("Error al actualizar la moneda: ", error);
                this.toastError("Hubo un error al actualizar la moneda")

            });
        },
        desactivarMoneda(id) {
            swal({
                title: 'Esta seguro de desactivar esta moneda?',
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

                    axios.put('/moneda/desactivar', {
                        'id': id
                    }).then(function (response) {
                        me.listarMoneda(1, '', 'nombre');
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
        activarMoneda(id) {
            swal({
                title: 'Esta seguro de activar esta moneda?',
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

                    axios.put('/moneda/activar', {
                        'id': id
                    }).then(function (response) {
                        me.listarMoneda(1, '', 'nombre');
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
            this.selectedCountry = '';
            this.showDropdown = false;
            this.modal = 0;
            this.tituloModal = '';
        },
        abrirModal(modelo, accion, data = []) {
            switch (modelo) {
                case "moneda":
                    {
                        switch (accion) {
                            case 'registrar':
                                {
                                    this.modal = 1;
                                    this.tituloModal = 'Registrar Moneda';
                                    this.tipoAccion = 1;
                                    this.datosFormulario = {
                                        nombre: '',
                                        pais: '',
                                        simbolo: '',
                                        tipo_cambio: 0,
                                    };
                                    this.errores = {};
                                    break;
                                }
                            case 'actualizar':
                                {
                                    this.modal = 1;
                                    this.tituloModal = 'Actualizar Moneda';
                                    this.tipoAccion = 2;

                                    this.datosFormulario = {
                                        id: data['id'],
                                        idempresa: data['idempresa'],
                                        nombre: data['nombre'],
                                        pais: data['pais'],
                                        simbolo: data['simbolo'],
                                        tipo_cambio: data['tipo_cambio'],
                                        activo: data['activo'],

                                    };
                                    this.errores = {};
                                    this.selectedCountry = data['pais'];

                                    break;
                                }
                        }
                    }
            }
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
        }
    },
    mounted() {
        this.listarMoneda(1, this.buscar, this.criterio);
    }
}
</script>
<style>   .flag-img {
       width: 30px;
       height: auto;
   }

   .modal-content {
       width: 100% !important;
       position: absolute !important;
   }

   .mostrar {
       display: list-item !important;
       opacity: 1 !important;
       position: absolute !important;
       background-color: #3c29297a !important;
   }

   .div-error {
       display: flex;
       justify-content: center;
   }

   .text-error {
       color: red !important;
       font-weight: bold;
   }

   .ulAutocomplete {
       list-style-type: none;
       padding: 0;
       margin: 0;
       border: 1px solid #ccc;
   }

   .liAutocomplete {
       padding: 10px;
       border-bottom: 1px solid #eee;
       cursor: pointer;
   }</style>
