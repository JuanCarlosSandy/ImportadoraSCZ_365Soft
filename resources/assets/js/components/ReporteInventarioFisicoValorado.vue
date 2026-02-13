<template>
    <main class="main">
        <div class="loading-overlay" v-if="isLoading">
            <div class="loading-container">
                <div class="spinner"></div>
                <div class="loading-text">LOADING...</div>
            </div>
        </div>
        <Panel>
            <template #header>
                <div class="panel-header">
                    <div class="panel-title-section">
                        <i class="pi pi-bars"></i>
                        <h4 class="panel-title">INFORME INVENTARIO FISICO VALORADO</h4>
                    </div>
                    <div class="panel-actions">
                        <Button 
                        :label="mostrarLabel ? 'Pdf' : ''" 
                        icon="pi pi-download"
                        @click="exportarInventarioPdf()" 
                        class="p-button-danger p-button-sm"
                        style="margin-right: 0.5rem;" 
                        :disabled="isLoading"
                        />
                        <Button 
                        :label="mostrarLabel ? 'Excel' : ''" 
                        icon="pi pi-download"
                        @click="exportarInventarioExcel()" 
                        class="p-button-success p-button-sm"
                        style="margin-right: 0.5rem;" 
                        :disabled="isLoading"
                        />
                    </div>
                </div>
            </template>

            <!-- FILTROS -->
            <div class="filters-container">
                <div class="filter-row">
                    <!-- SELECTOR DE ALMACÉN -->
                    <div class="filter-group-almacen">
                        <label class="filter-label">ALMACÉN DE TRABAJO</label>
                        <Dropdown v-model="AlmacenSeleccionado" :options="arrayAlmacenes" optionLabel="nombre_almacen"
                            optionValue="id" placeholder="Seleccione un almacén" @change="getDatosAlmacen"
                            class="p-dropdown-sm" />
                    </div>

                    <!-- BUSCADOR DE LABORATORIO -->
                    <div class="filter-group-laboratorio" style="margin-left: 0rem; position: relative;">
                        <label class="filter-label">PROVEEDOR</label>
                        <input type="text" v-model="buscarLaboratorioTexto" @input="buscarLaboratorios"
                            @focus="mostrarLista = true" @blur="ocultarListaConRetraso"
                            placeholder="Escriba para buscar..." class="form-control p-inputtext-sm"
                            style="width: 220px;" />

                        <!-- Lista de resultados -->
                        <ul v-if="mostrarLista && arrayLaboratorios.length > 0" class="list-group" style="
                                position: absolute;
                                top: 60px;
                                left: 0;
                                width: 220px;
                                max-height: 200px;
                                overflow-y: auto;
                                background: white;
                                border: 1px solid #ccc;
                                border-radius: 5px;
                                z-index: 1000;
                                padding: 0;
                                margin: 0;
                                list-style: none;
                            ">
                            <li v-for="lab in arrayLaboratorios" :key="lab.id" @mousedown="seleccionarLaboratorio(lab)"
                                style="
                                    padding: 6px 10px;
                                    cursor: pointer;
                                " @mouseover="hovered = lab.id" :style="{
                                    backgroundColor: hovered === lab.id ? '#e3f2fd' : 'white',
                                }">
                                {{ lab.nombre }}
                            </li>
                        </ul>
                    </div>

                    <!-- BUSCADOR DE PRESENTACIÓN -->
                    <div class="filter-group-presentacion" style="margin-left: 0rem; position: relative;">
                        <label class="filter-label">CATEGORÍA</label>
                        <input type="text" v-model="buscarPresentacionTexto" @input="filtrarPresentaciones"
                            @focus="mostrarListaPresentacion = true" @blur="ocultarListaPresentacionConRetraso"
                            placeholder="Escriba para buscar..." class="form-control p-inputtext-sm"
                            style="width: 220px;" />

                        <!-- Lista de resultados -->
                        <ul v-if="
                            mostrarListaPresentacion &&
                            arrayPresentacionesFiltradas.length > 0
                        " class="list-group" style="
                                position: absolute;
                                top: 60px;
                                left: 0;
                                width: 220px;
                                max-height: 200px;
                                overflow-y: auto;
                                background: white;
                                border: 1px solid #ccc;
                                border-radius: 5px;
                                z-index: 1000;
                                padding: 0;
                                margin: 0;
                                list-style: none;
                            ">
                            <li v-for="pres in arrayPresentacionesFiltradas" :key="pres.id"
                                @mousedown="seleccionarPresentacion(pres)" style="padding: 6px 10px; cursor: pointer;"
                                @mouseover="hoveredPresentacion = pres.id" :style="{
                                    backgroundColor:
                                        hoveredPresentacion === pres.id ? '#e3f2fd' : 'white',
                                }">
                                {{ pres.nombre }}
                            </li>
                        </ul>
                    </div>

                    <div style="margin-top: 1.6rem;">
                        <Button label="Reset Filtros" icon="pi pi-refresh" class="p-button-help p-button-sm"
                            @click="resetLaboratorioPresentacion" />
                    </div>
                </div>
            </div>

            <!-- BUSCADOR Y TABLA -->
            <div class="toolbar-container">
                <div class="search-bar">
                    <span class="p-input-icon-left">
                        <i class="pi pi-search" />
                        <InputText v-model="buscar" placeholder="Texto a buscar" class="p-inputtext-sm"
                            @keyup="buscarInventario" />
                    </span>
                </div>
                <div class="toolbar">
                    <Button :label="mostrarLabel ? 'Reset' : ''" icon="pi pi-refresh" @click="resetBusqueda"
                        class="p-button-help p-button-sm" />
                </div>
            </div>

            <!-- TABLA -->
            <DataTable v-if="tipoSeleccionado == 'item'" :value="arrayInventario"
                class="p-datatable-sm p-datatable-gridlines" responsiveLayout="scroll">
                <Column field="nombre_producto" header="ITEM"></Column>
                <Column field="nombre_proveedor" header="PROVEEDORES"></Column>
                <Column field="nombre_categoria" header="CATEGORÍA" class="d-none d-md-table-cell">
                    <template #body="slotProps">
                        <span v-if="slotProps.data.nombre_categoria">
                            {{ slotProps.data.nombre_categoria }}
                        </span>
                        <span v-else style="color: #999; font-style: italic;">Sin presentación</span>
                    </template>
                </Column>
                <Column field="saldo_stock_total" header="STOCK UNIDADES">
                    <template #body="slotProps">
                        <span v-if="parseFloat(slotProps.data.saldo_stock_total) !== 0">
                            {{ slotProps.data.saldo_stock_total }}
                        </span>
                        <span v-else style="color: #e57373; font-weight: 500;">
                            <i class="pi pi-exclamation-triangle mr-1"></i> Sin Stock
                        </span>
                    </template>
                </Column>
                <Column field="precio_venta" header="PRECIO VENTA"></Column>
                <Column field="precio_costo_unid" header="COSTO UNID.">
                    <template #body="slotProps">
                        Bs. {{ parseFloat(slotProps.data.precio_costo_unid).toFixed(2) }}
                    </template>
                </Column>
                <Column field="valor_total" header="VALOR TOTAL">
                    <template #body="slotProps">
                        Bs. {{ parseFloat(slotProps.data.valor_total).toFixed(2) }}
                    </template>
                </Column>
            </DataTable>

            <!-- PAGINACIÓN -->
            <Paginator :rows="pagination.per_page" :totalRecords="pagination.total"
                :first="(pagination.current_page - 1) * pagination.per_page" @page="onPageChange" />
        </Panel>
    </main>
</template>

<script>
import Panel from "primevue/panel";
import Swal from "sweetalert2";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import InputText from "primevue/inputtext";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import RadioButton from "primevue/radiobutton";
import Paginator from "primevue/paginator";
import Dialog from "primevue/dialog";

export default {
    components: {
        Panel,
        Button,
        Dropdown,
        InputText,
        DataTable,
        Column,
        RadioButton,
        Paginator,
        Dialog,
    },
    data() {
        return {
            buscarPresentacionTexto: "",
            arrayPresentaciones: [],
            arrayPresentacionesFiltradas: [],
            mostrarListaPresentacion: false,
            hoveredPresentacion: null,
            PresentacionSeleccionada: "",
            // Nuevos para laboratorio
            buscarLaboratorioTexto: "",
            arrayLaboratorios: [],
            mostrarLista: false,
            hovered: null,
            LaboratorioSeleccionado: "",
            mostrarLabel: true,
            isLoading: false,
            arrayInventario: [],
            arrayAlmacenes: [],
            AlmacenSeleccionado: 1,
            idalmacen: 0,
            tipoSeleccionado: "item",
            pagination: {
                total: 0,
                current_page: 0,
                per_page: 0,
                last_page: 0,
                from: 0,
                to: 0,
            },
            offset: 3,
            criterio: "",
            buscar: "",
        };
    },
    computed: {
        isActived: function () {
            return this.pagination.current_page;
        },
        //Calcula los elementos de la paginación
        pagesNumber: function () {
            if (!this.pagination.to) {
                return [];
            }

            var from = this.pagination.current_page - this.offset;
            if (from < 1) {
                from = 1;
            }

            var to = from + this.offset * 2;
            if (to >= this.pagination.last_page) {
                to = this.pagination.last_page;
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
        async listarLaboratorios() {
            try {
                const response = await axios.get("/proveedornewview", {
                    params: { buscar: '' }, 
                });
                this.arrayLaboratorios = response.data.personas;
            } catch (error) {
                console.error("Error al listar:", error);
            }
        },
        handleResize() {
            this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
        },
        async resetLaboratorioPresentacion() {
            this.buscarLaboratorioTexto = "";
            this.LaboratorioSeleccionado = "";
            this.buscarPresentacionTexto = "";
            this.PresentacionSeleccionada = "";

            // Llamar a la función de listado sin laboratorio ni presentación
            await this.actualizarInventario();
        },
        async cambiarLaboratorio() {
            await this.actualizarInventario();
        },

        async cambiarPresentacion() {
            await this.actualizarInventario();
        },
        async obtenerPresentaciones() {
            try {
                const response = await axios.get("/categorianewview");
                this.arrayPresentaciones = response.data.categorias;
                this.arrayPresentacionesFiltradas = this.arrayPresentaciones;
            } catch (error) {
                console.error("Error al cargar presentaciones:", error);
            }
        },

        // 🔹 Filtrar localmente mientras escribe
        filtrarPresentaciones() {
            const texto = this.buscarPresentacionTexto.trim().toLowerCase();
            if (!texto) {
                this.arrayPresentacionesFiltradas = this.arrayPresentaciones;
                return;
            }

            this.arrayPresentacionesFiltradas = this.arrayPresentaciones.filter((p) =>
                p.nombre.toLowerCase().includes(texto)
            );
            this.mostrarListaPresentacion = true;
        },

        // 🔹 Seleccionar una presentación
        seleccionarPresentacion(pres) {
            this.buscarPresentacionTexto = pres.nombre;
            this.PresentacionSeleccionada = pres.id;
            this.mostrarListaPresentacion = false;

            // actualizar inventario
            this.cambiarPresentacion();
        },

        // 🔹 Cerrar lista al salir del input
        ocultarListaPresentacionConRetraso() {
            setTimeout(() => {
                this.mostrarListaPresentacion = false;
            }, 150);
        },
        async buscarLaboratorios() {
            try {
                const texto = this.buscarLaboratorioTexto.trim();
                if (texto.length < 2) {
                    this.arrayLaboratorios = [];
                    return;
                }

                const response = await axios.get("/proveedornewview", {
                    params: { buscar: texto },
                });

                this.arrayLaboratorios = response.data.personas;
                this.mostrarLista = true;
            } catch (error) {
                console.error("Error al buscar laboratorios:", error);
            }
        },
        seleccionarLaboratorio(lab) {
            this.buscarLaboratorioTexto = lab.nombre;
            this.LaboratorioSeleccionado = lab.id;
            this.mostrarLista = false;

            // actualizar inventario
            this.cambiarLaboratorio();
        },
        ocultarListaConRetraso() {
            setTimeout(() => {
                this.mostrarLista = false;
            }, 150);
        },
async exportarInventarioExcel() {
  if (!this.AlmacenSeleccionado) {
    Swal.fire("Error", "Por favor seleccione un almacén", "error");
    return;
  }

  const almacenSeleccionado = this.arrayAlmacenes.find(
    (almacen) => almacen.id == this.AlmacenSeleccionado
  );
  const nombreAlmacen = almacenSeleccionado
    ? almacenSeleccionado.nombre_almacen
    : "almacen";

  this.isLoading = true;

  try {
    const params = {
      idAlmacen: this.AlmacenSeleccionado,
      nombreAlmacen: nombreAlmacen.replace(/\s+/g, "_"),
      idLaboratorio: this.LaboratorioSeleccionado || "",
      idPresentacion: this.PresentacionSeleccionada || "",
      buscar: this.buscar || "",
      criterio: this.criterio || "",
      page: this.pagination.current_page || 1,
      tipoSeleccionado: this.tipoSeleccionado || "",
    };

    const response = await axios.post("/reporte/inventarioValoradoExcel", params, {
      responseType: 'blob',
      timeout: 600000 // 10 minutos
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;

    let filename = `ReporteInventarioValorado_${nombreAlmacen.replace(/\s+/g, "_")}.xlsx`;
    const disposition = response.headers['content-disposition'];
    if (disposition && disposition.indexOf('attachment') !== -1) {
      const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
      const matches = filenameRegex.exec(disposition);
      if (matches != null && matches[1]) {
        filename = matches[1].replace(/['"]/g, '');
      }
    }

    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);

    Swal.fire("Éxito", "El archivo Excel se ha descargado correctamente", "success");
  } catch (error) {
    console.error("Error al exportar Excel:", error);
    let mensaje = "No se pudo exportar el inventario a Excel";
    if (error.code === 'ECONNABORTED') {
      mensaje = "El reporte es demasiado grande y ha excedido el tiempo de espera.";
    } else if (error.response && error.response.data instanceof Blob) {
      const reader = new FileReader();
      reader.onload = () => {
        try {
          const json = JSON.parse(reader.result);
          if (json.error) Swal.fire("Error", json.error, "error");
        } catch (e) {
          Swal.fire("Error", mensaje, "error");
        }
      };
      reader.readAsText(error.response.data);
      return;
    }
    Swal.fire("Error", mensaje, "error");
  } finally {
    this.isLoading = false;
  }
},
        async exportarInventarioPdf() {

             let me = this;
             try {
                 // Validar que haya un almacén seleccionado
                 if (!me.AlmacenSeleccionado) {
                     Swal.fire("Error", "Por favor seleccione un almacén", "error");
                     return;
                 }
 
                 // Obtener el nombre del almacén seleccionado
                 const almacenSeleccionado = me.arrayAlmacenes.find(
                     (almacen) => almacen.id == me.AlmacenSeleccionado
                 );
                 const nombreAlmacen = almacenSeleccionado
                     ? almacenSeleccionado.nombre_almacen
                     : "almacen";
 
                 // Mostrar loading
                 me.isLoading = true;
 
                 // Preparar datos para enviar
                 const params = {
                     idAlmacen: me.AlmacenSeleccionado,
                     nombreAlmacen: nombreAlmacen.replace(/\s+/g, "_"),
                     idLaboratorio: me.LaboratorioSeleccionado || "",
                     idPresentacion: me.PresentacionSeleccionada || "",
                     buscar: me.buscar || "",
                     criterio: me.criterio || "",
                     page: (me.pagination && me.pagination.current_page) ? me.pagination.current_page : 1,
                     tipoSeleccionado: me.tipoSeleccionado || "",
                 };
 
                 // Realizar petición con axios
                 const response = await axios.post("/reporte/inventarioValoradoPdf", params, {
                     responseType: 'blob',
                     timeout: 600000 // 10 minutos de espera máximo
                 });
 
                 // Crear URL del blob
                 const url = window.URL.createObjectURL(new Blob([response.data]));
                 const link = document.createElement('a');
                 link.href = url;
                 
                 // Intentar obtener nombre del archivo del header
                 let filename = `ReporteInventarioValorado_${nombreAlmacen.replace(/\s+/g, "_")}.pdf`;
                 const disposition = response.headers['content-disposition'];
                 if (disposition && disposition.indexOf('attachment') !== -1) {
                     const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                     const matches = filenameRegex.exec(disposition);
                     if (matches != null && matches[1]) { 
                         filename = matches[1].replace(/['"]/g, '');
                     }
                 }
 
                 link.setAttribute('download', filename);
                 document.body.appendChild(link);
                 link.click();
                 document.body.removeChild(link);
                 window.URL.revokeObjectURL(url);
 
                 // Mostrar mensaje de éxito
                 Swal.fire("Éxito", "El reporte PDF se ha descargado correctamente", "success");
 
             } catch (error) {
                 console.error("Error al exportar PDF:", error);
                 
                 let mensaje = "No se pudo exportar el inventario a PDF";
                 
                 if (error.code === 'ECONNABORTED') {
                     mensaje = "El reporte es demasiado grande y ha excedido el tiempo de espera.";
                 } else if (error.response && error.response.data instanceof Blob) {
                     // Intentar leer el error del Blob
                     const reader = new FileReader();
                     reader.onload = () => {
                         try { const json = JSON.parse(reader.result); if(json.error) Swal.fire("Error", json.error, "error"); } catch(e) { Swal.fire("Error", mensaje, "error"); }
                     };
                     reader.readAsText(error.response.data);
                     return; // Salir para que el reader maneje la alerta
                 }
                 
                 Swal.fire("Error", mensaje, "error");
             } finally {
                 me.isLoading = false;
             }
        },

        async buscarInventario() {
            try {
                if (this.searchTimeout) {
                    clearTimeout(this.searchTimeout);
                }

                this.searchTimeout = setTimeout(async () => {
                    this.isLoading = true; // Activar loading
                    await this.listarInventario(1, this.buscar, this.criterio, this.AlmacenSeleccionado, this.LaboratorioSeleccionado, this.PresentacionSeleccionada);
                    setTimeout(() => {
                        this.isLoading = false; // Desactivar loading
                    }, 500);
                }, 300);
            } catch (error) {
                console.error("Error en la búsqueda:", error);
                this.isLoading = false;
            }
        },
        resetBusqueda() {
            this.buscar = ""; // Limpiar input
            this.listarInventario(1, "", "", this.AlmacenSeleccionado, this.LaboratorioSeleccionado, this.PresentacionSeleccionada); // Llamar con valores vacíos
        },
        onPageChange(event) {
            const page = Math.floor(event.first / event.rows) + 1;
            this.cambiarPagina(page, this.buscar, this.criterio, this.AlmacenSeleccionado, this.LaboratorioSeleccionado, this.PresentacionSeleccionada);
        },
        async cambiarPagina(page, buscar, criterio, alma, lab, presen) {
            let me = this;
            try {
                me.pagination.current_page = page;
                await me.listarInventario(page, buscar, criterio, alma, lab, presen);
            } catch (error) {
                console.error("Error al cambiar de página:", error);
                Swal.fire("Error", "No se pudo cambiar de página", "error");
            }
        },

        async listarInventario(page, buscar, criterio, idAlmacen, idLaboratorio, idPresentacion) {
            try {
                let url = `/reporte/inventariofisicovalorado/${this.tipoSeleccionado}?idAlmacen=${idAlmacen}&idLaboratorio=${idLaboratorio}&idPresentacion=${idPresentacion}&buscar=${buscar}&criterio=${criterio}&page=${page}`;

                const response = await axios.get(url);
                let respuesta = response.data;
                this.arrayInventario = respuesta.inventarios.data;
                this.pagination = respuesta.pagination;
            } catch (error) {
                Swal.fire("Error", "No se pudo cargar el inventario", "error");
            }
        },
        async selectAlmacen() {
            let me = this;
            try {
                const url = "/almacen/selectAlmacen";
                const response = await axios.get(url);

                const respuesta = response.data;
                me.arrayAlmacenes = respuesta.almacenes;

                // Selecciona el primer almacén si hay al menos uno
                if (me.arrayAlmacenes.length > 0) {
                    me.AlmacenSeleccionado = me.arrayAlmacenes[0].id;
                    await me.getDatosAlmacen(); // 👈 Dispara manualmente después de asignar
                }
            } catch (error) {
                console.error("Error al cargar almacenes:", error);
                Swal.fire("Error", "No se pudieron cargar los almacenes", "error");
            }
        },

        async getDatosAlmacen() {
            await this.actualizarInventario();
        },
        async actualizarInventario() {
            try {
                if (!this.AlmacenSeleccionado) return;

                let idAlmacen = Number(this.AlmacenSeleccionado);
                let idLaboratorio = this.LaboratorioSeleccionado || "";
                let idPresentacion = this.PresentacionSeleccionada || "";

                await this.listarInventario(
                    1,
                    this.buscar,
                    "",
                    idAlmacen,
                    idLaboratorio,
                    idPresentacion
                );
            } catch (error) {
                console.error("Error al actualizar inventario:", error);
                Swal.fire("Error", "No se pudo cargar el inventario", "error");
            }
        },
        async cambiarTipo() {
            try {
                this.isLoading = true; // Activar loading
                await this.getDatosAlmacen();
            } catch (error) {
                console.error("Error al cambiar tipo de vista:", error);
                Swal.fire("Error", "No se pudo cambiar el tipo de vista", "error");
            } finally {
                setTimeout(() => {
                    this.isLoading = false; // Desactivar loading
                }, 100);
            }
        },
        //--------------------------------------
    },
    async mounted() {
        this.handleResize();
        window.addEventListener("resize", this.handleResize);
        try {
            await Promise.all([this.obtenerPresentaciones(), this.selectAlmacen(), this.listarLaboratorios()]);
        } catch (error) {
            console.error("Error en la carga inicial:", error);
            Swal.fire("Error", "Error al cargar los datos iniciales", "error");
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

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.panel-title-section {
    display: flex;
    align-items: center;
}

.panel-title {
    margin: 0;
    padding-left: 5px;
}

.panel-actions {
    display: flex;
    align-items: center;
}

/* Filters Container */
.filters-container {
    margin-bottom: 1rem;
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    border: 1px solid #dee2e6;
}

.filter-row {
    display: flex;
    gap: 2rem;
    align-items: flex-end;
}

.filter-group-almacen {
    flex: 2;
    min-width: 200px;
}

.filter-group-laboratorio {
    flex: 2;
    min-width: 200px;
}

.filter-group-presentacion {
    flex: 2;
    min-width: 200px;
}

.filter-group-modo {
    flex: 1;
    min-width: 150px;
}

.filter-label {
    display: block;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-group-almacen .filter-label,
.filter-group-laboratorio .filter-label,
.filter-group-presentacion .filter-label,
.filter-group-modo .filter-label {
    margin-bottom: 0.4rem;
}

.radio-group {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.p-field-radiobutton {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.p-field-radiobutton label {
    font-size: 0.875rem;
    color: #495057;
    cursor: pointer;
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

/* Tablet Styles */
@media (max-width: 1024px) {
    >>>.p-datatable {
        font-size: 0.85rem;
    }

    .filter-row {
        flex-direction: row;
        gap: 1rem;
        align-items: flex-end;
    }

    .filter-group-almacen,
    .filter-group-laboratorio,
    .filter-group-presentacion,
    .filter-group-modo {
        min-width: auto;
        flex: 1;
    }
}

/* Mobile Styles */
@media (max-width: 768px) {
    .toolbar .p-button .p-button-label {
        display: none;
    }

    .toolbar-container {
        gap: 0.5rem;
    }

    .filters-container {
        padding: 0.75rem;
    }

    .filter-row {
        flex-direction: row;
        gap: 1rem;
        align-items: flex-start;
    }

    .filter-group-almacen {
        flex: 1;
        min-width: 0;
    }

    .filter-group-laboratorio {
        flex: 1;
        min-width: 0;
    }

    .filter-group-presentacion {
        flex: 1;
        min-width: 0;
    }

    .filter-group-modo {
        flex: 1;
        min-width: 0;
    }

    .radio-group {
        flex-direction: row;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .p-field-radiobutton {
        margin: 0;
    }

    .p-field-radiobutton label {
        font-size: 0.8rem;
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

    >>>.p-button-sm {
        font-size: 0.75rem !important;
        padding: 0.375rem 0.5rem !important;
        min-width: auto !important;
    }

    .toolbar>>>.p-button-sm {
        font-size: 0.75rem !important;
        padding: 0.375rem 0.5rem !important;
    }

    .search-bar .p-inputtext-sm {
        padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
        font-size: 0.85rem !important;
    }

    >>>.p-dropdown {
        font-size: 0.9rem;
    }
}

/* Extra Small Mobile */
@media (max-width: 480px) {
    .toolbar .p-button .p-button-label {
        display: none;
    }

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

    .filters-container {
        padding: 0.5rem;
    }

    .filter-row {
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
    }

    .filter-group-almacen {
        flex: none;
        min-width: auto;
    }

    .filter-group-laboratorio {
        flex: none;
        min-width: auto;
    }

    .filter-group-presentacion {
        flex: none;
        min-width: auto;
    }

    .filter-group-modo {
        flex: none;
        min-width: auto;
    }

    .filter-label {
        font-size: 0.75rem;
        margin-bottom: 0.25rem;
    }

    .radio-group {
        flex-direction: row;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .p-field-radiobutton label {
        font-size: 0.75rem;
    }

    .toolbar>>>.p-button-sm {
        font-size: 0.75rem !important;
        padding: 0.375rem 0.5rem !important;
    }

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

    >>>.p-dropdown {
        font-size: 0.85rem;
    }

    .p-field-radiobutton label {
        font-size: 0.8rem;
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
</style>
