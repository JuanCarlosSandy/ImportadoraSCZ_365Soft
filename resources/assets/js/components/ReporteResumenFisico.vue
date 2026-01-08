<template>
  <main class="main">
    <Panel>
      <template #header>
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="pi pi-bars panel-icon"></i>
                <h4 class="panel-title" style="margin: 0;">KARDEX FISICO DE INVENTARIO</h4>
            </div>
            
            <div style="display: flex; gap: 0.5rem;">
                <Button
                    icon="pi pi-file-pdf"
                    label="PDF General"
                    class="p-button-danger p-button-sm"
                    @click="descargarPdfGeneral()"
                />

                <Button
                    icon="pi pi-filter"
                    :label="mostrarLabel ? 'Filtros' : ''"
                    class="p-button-secondary p-button-sm"
                    @click="abrirModal('articulo', 'registrar'); listarPrecio();"
                />
            </div>
        </div>
      </template>
      <template>
        <div>
          <span class="p-input-icon-left mb-2">
            <InputText
              v-model="globalFilter"
              placeholder="Buscar en la tabla..."
            />
          </span>
          <div v-if="isLoading" class="custom-loading-overlay">
            <div class="custom-loading-content">
              <span
                class="spinner-border spinner-border-lg"
                role="status"
                aria-hidden="true"
              ></span>
              <div style="margin-top: 1em; font-size: 1.2em; color: #fff;">
                Cargando reporte...
              </div>
            </div>
          </div>
          <DataTable
            :value="sortedResultados"
            scrollable
            stripedRows
            responsiveLayout="scroll"
            size="small"
            paginator
            :rows="5"
            :rowsPerPageOptions="[5, 10, 20, 50]"
            :filters="filters"
            :globalFilterFields="[
              'codigo',
              'nombre_producto',
              'categoria',
              'sucursal',
              'almacen',
            ]"
          >
            <Column 
                header="ACCIONES" 
                headerStyle="width: 12rem; min-width: 12rem; text-align: center" 
                bodyStyle="text-align: center; overflow: visible"
            >
                <template #body="slotProps">
                    <div style="display: flex; justify-content: center; gap: 0.5rem; white-space: nowrap;">
                        <Button
                            type="button"
                            icon="pi pi-eye"
                            class="p-button-rounded p-button-info p-button-text"
                            title="Ver Detalle en Pantalla"
                            @click="verDetalleMovimiento(slotProps.data)"
                        />

                        <Button
                            type="button"
                            icon="pi pi-file-pdf"
                            class="p-button-rounded p-button-danger p-button-text"
                            title="Descargar PDF Detallado"
                            @click="descargarPdfDetallado(slotProps.data)"
                        />
                    </div>
                </template>
            </Column>
            <Column
              field="codigo"
              header="CODIGO ITEM"
              filter
              filterPlaceholder="Buscar código"
            />
            <Column
              field="sucursal"
              header="SUCURSAL"
              filter
              filterPlaceholder="Buscar sucursal"
            />
            <Column
              field="almacen"
              header="ALMACÉN"
              filter
              filterPlaceholder="Buscar almacén"
            />
            <Column
              field="nombre_producto"
              header="ITEM"
              filter
              filterPlaceholder="Buscar item"
            />
            <Column
              field="categoria"
              header="CATEGORÍA"
              filter
              filterPlaceholder="Buscar categoría"
            />
            <Column field="total_ventas_texto" header="TOTAL VENTAS" />
            <Column field="total_ingresos_texto" header="TOTAL COMPRAS" />
            <Column
              field="total_traspasos_entrada"
              header="TOTAL TRASPASO ENTRADA"
            />
            <Column
              field="total_traspasos_salida"
              header="TOTAL TRASPASO SALIDA"
            />
            <Column field="total_ajuste_texto" header="TOTAL AJUSTE" />
            <Column field="saldo_stock_actual_texto" header="STOCK ACTUAL" />
          </DataTable>
        </div>
      </template>
    </Panel>

    <div
      class="modal"
      tabindex="-1"
      :class="{ mostrar: modal }"
      role="dialog"
      aria-labelledby="myModalLabel"
      style="display: none;"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-primary modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">FILTROS DE REPORTES</h4>
            <button
              type="button"
              class="close"
              @click="cerrarModal()"
              aria-label="Close"
            >
              <span aria-hidden="true">×</span>
            </button>
          </div>
          <form @submit.prevent="enviarFormulario">
            <div class="modal-body">
              <!-- Primera fila: Sucursal, Artículo, Categoría -->
              <div class="form-group row">
                <div class="col-12 col-md-4 mb-3">
                  <label class="font-weight-bold"
                    >Sucursal
                    <span class="text-danger">OBLIGATORIO</span></label
                  >
                  <div class="input-group">
                    <input
                      class="form-control"
                      type="text"
                      placeholder="Seleccione una sucursal"
                      disabled
                      v-model="sucursalseleccionada.nombre"
                    />
                    <div class="input-group-append">
                      <button
                        class="btn btn-primary"
                        type="button"
                        @click="abrirModal2('Sucursal')"
                      >
                        ...
                      </button>
                    </div>
                  </div>
                </div>

                <div class="col-12 col-md-4 mb-3">
                  <label class="font-weight-bold">Artículo</label>
                  <div class="input-group">
                    <input
                      class="form-control"
                      type="text"
                      placeholder="Seleccione un artículo"
                      disabled
                      v-model="articuloseleccionada.nombre"
                    />
                    <div class="input-group-append">
                      <button
                        class="btn btn-primary"
                        type="button"
                        @click="abrirModal2('Articulo')"
                      >
                        ...
                      </button>
                    </div>
                  </div>
                </div>

                <div class="col-12 col-md-4 mb-3">
                  <label class="font-weight-bold">Categoría</label>
                  <div class="input-group">
                    <input
                      class="form-control"
                      type="text"
                      placeholder="Seleccione una categoría"
                      disabled
                      v-model="lineaseleccionada.nombre"
                    />
                    <div class="input-group-append">
                      <button
                        class="btn btn-primary"
                        type="button"
                        @click="abrirModal2('Lineas')"
                      >
                        ...
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Segunda fila: Fechas -->
              <div class="form-group row">
                <div class="col-12 col-md-6 mb-3">
                  <label class="font-weight-bold"
                    >Fecha Inicio
                    <span class="text-danger">OBLIGATORIO</span></label
                  >
                  <input
                    class="form-control"
                    type="date"
                    v-model="fechaInicio"
                  />
                </div>

                <div class="col-12 col-md-6 mb-3">
                  <label class="font-weight-bold"
                    >Fecha Fin
                    <span class="text-danger">OBLIGATORIO</span></label
                  >
                  <input class="form-control" type="date" v-model="fechaFin" />
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-danger"
                @click="cerrarModal()"
              >
                Cerrar
              </button>
              <button
                type="submit"
                @click="
                  listaReporte();
                  cerrarModal();
                "
                class="btn btn-primary"
              >
                Visualizar Reporte
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- MODAL PARA LA LISTA DE MEDIDA -->
    <div
      class="modal "
      tabindex="-1"
      :class="{ mostrar: modal6 }"
      role="dialog"
      aria-labelledby="myModalLabel"
      style="display: none;"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-primary modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title" v-text="tituloModal6"></h4>
            <button
              type="button"
              class="close"
              @click="modal6 = false"
              aria-label="Close"
            >
              <span aria-hidden="true">×</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group row">
              <div class="col-md-6">
                <div class="input-group">
                  <button
                    v-show="tituloModal6 == 'Medidas'"
                    type="button"
                    @click="abrirModal7('medida', 'registrarMed')"
                    class="btn btn-secondary"
                  >
                    <i class="icon-plus"></i>&nbsp;Nuevo
                  </button>
                  <!--button type="submit" @click="listarArticulo(buscarA, criterioA)"
                                class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button-->
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-striped table-sm">
                <thead>
                  <tr>
                    <th style="display: none;">ID</th>
                    <th>Opciones</th>
                    <th>Medida</th>
                    <th>Descripción Corta</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="arrayelemento in arrayBuscador"
                    :key="arrayelemento.id"
                  >
                    <td>
                      <button
                        type="button"
                        @click="seleccionar2(arrayelemento)"
                        class="btn btn-success btn-sm"
                      >
                        <i class="icon-check"></i>
                      </button>
                    </td>
                    <td v-if="mostrarElemento" v-text="arrayelemento.id"></td>
                    <td v-text="arrayelemento.descripcion_medida"></td>
                    <td v-text="arrayelemento.descripcion_corta"></td>
                    <td v-if="tituloModal6 == 'Medidas'">
                      <div v-if="arrayelemento.estado">
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
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="
                      cambiarPaginaMedida(
                        paginationMedida.current_page - 2,
                        buscar,
                        criterio
                      )
                    "
                    >Ant</a
                  >
                </li>
                <li
                  class="page-item"
                  v-for="page in pagesNumberMedida"
                  :key="page"
                  :class="[page == isActivedM ? 'active' : '']"
                >
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="cambiarPaginaMedida(page, buscar, criterio)"
                    v-text="page"
                  ></a>
                </li>
                <li
                  class="page-item"
                  v-if="
                    paginationMedida.current_page < paginationMedida.last_page
                  "
                >
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="
                      cambiarPaginaMedida(
                        paginationMedida.current_page + 1,
                        buscar,
                        criterio
                      )
                    "
                    >Sig</a
                  >
                </li>
              </ul>
            </nav>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              @click="modal6 = false"
            >
              Cerrar
            </button>
          </div>
        </div>
        <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
    </div>
    <!-- HASTA AQUI EL MODAL DE LISTA MEDIDA -->

    <div
      class="modal "
      tabindex="-1"
      :class="{ mostrar: modal2 }"
      role="dialog"
      aria-labelledby="myModalLabel"
      style="display: none;"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-primary modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title" v-text="tituloModal2"></h4>
            <button
              type="button"
              class="close"
              @click="modal2 = false"
              aria-label="Close"
            >
              <span aria-hidden="true">×</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group row">
              <div class="col-md-6">
                <div class="input-group">
                  <select class="form-control col-md-3" v-model="criterioA">
                    <option v-if="tituloModal2 !== 'Grupos'" value="nombre"
                      >Nombre</option
                    >
                    <option
                      v-if="tituloModal2 == 'Articulo'"
                      value="descripcion"
                      >Descripcion</option
                    >
                    <option
                      v-else-if="tituloModal2 == 'Grupos'"
                      value="nombre_grupo"
                      >Grupo</option
                    >
                    <!-- <option v-if="tituloModal2=='Grupos'" value="nombre_grupo">Nombre_grupo</option> -->
                  </select>
                  <input
                    v-if="tituloModal2 == 'Marcas'"
                    type="text"
                    v-model="buscarA"
                    @keyup="listarMarca(1, buscarA, criterioA)"
                    class="form-control"
                    placeholder="Texto a buscar"
                  />
                  <input
                    v-if="tituloModal2 == 'Industrias'"
                    type="text"
                    v-model="buscarA"
                    @keyup="listarIndustria(1, buscarA, criterioA)"
                    class="form-control"
                    placeholder="Texto a buscar"
                  />
                  <input
                    v-if="tituloModal2 == 'Lineas'"
                    type="text"
                    v-model="buscarA"
                    @keyup="listarLinea(1, buscarA, criterioA)"
                    class="form-control"
                    placeholder="Texto a buscar"
                  />
                  <input
                    v-if="tituloModal2 == 'Proveedors'"
                    type="text"
                    v-model="buscarA"
                    @keyup="listarproveedor(1, buscarA, criterioA)"
                    class="form-control"
                    placeholder="Texto a buscar"
                  />
                  <input
                    v-if="tituloModal2 == 'Grupos'"
                    type="text"
                    v-model="buscarA"
                    @keyup="listargrupo(1, buscarA, criterioA)"
                    class="form-control"
                    placeholder="Texto a buscar"
                  />
                  <input
                    v-if="tituloModal2 == 'Sucursal'"
                    type="text"
                    v-model="buscarA"
                    @keyup="listarSucursal(1, buscarA, criterioA)"
                    class="form-control"
                    placeholder="Texto a buscar"
                  />
                  <input
                    v-if="tituloModal2 == 'Articulo'"
                    type="text"
                    v-model="buscarA"
                    @keyup="listarArticulo(1, buscarA, criterioA)"
                    class="form-control"
                    placeholder="Texto a buscar"
                  />
                  <input
                    v-if="tituloModal2 == 'Cliente'"
                    type="text"
                    v-model="buscarA"
                    @keyup="listarPersona(1, buscarA, criterioA)"
                    class="form-control"
                    placeholder="Texto a buscar"
                  />
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <table
                class="table table-bordered table-striped table-sm"
                v-if="tituloModal2 !== 'Grupos'"
              >
                <thead>
                  <tr>
                    <th>Opciones</th>
                    <th>Id</th>

                    <th>Nombre</th>
                    <!-- <th>Estado</th> -->
                    <th>
                      <div v-if="tituloModal2 == 'Proveedors'">
                        Nit
                      </div>
                      <div v-else>
                        Estado
                      </div>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="arrayelemento in arrayBuscador"
                    :key="arrayelemento.id"
                  >
                    <td>
                      <button
                        type="button"
                        @click="seleccionar(arrayelemento)"
                        class="btn btn-success btn-sm"
                      >
                        <i class="icon-check"></i>
                      </button>
                    </td>
                    <td v-text="arrayelemento.id"></td>
                    <!-- <div v-if="tituloModal2=='Grupos'">
                                            <td  v-text="arrayelemento.nombre_grupo"></td>
                                        </div> -->
                    <td v-text="arrayelemento.nombre"></td>
                    <td v-if="tituloModal2 == 'Industrias'">
                      <div v-if="arrayelemento.estado">
                        <span class="badge badge-success">Activo</span>
                      </div>
                      <div v-else>
                        <span class="badge badge-danger">Desactivado</span>
                      </div>
                    </td>
                    <td
                      v-if="
                        tituloModal2 == 'Marcas' ||
                          tituloModal2 == 'Lineas' ||
                          tituloModal2 == 'Sucursal' ||
                          tituloModal2 == 'Articulo'
                      "
                    >
                      <div v-if="arrayelemento.condicion">
                        <span class="badge badge-success">Activo</span>
                      </div>
                      <div v-else>
                        <span class="badge badge-danger">Desactivado</span>
                      </div>
                    </td>

                    <div v-if="tituloModal2 == 'Proveedors'">
                      <td v-text="arrayelemento.num_documento"></td>
                    </div>
                  </tr>
                </tbody>
              </table>
              <!--###########################################################-->
              <table
                class="table table-bordered table-striped table-sm"
                v-else-if="tituloModal2 == 'Grupos'"
              >
                <thead>
                  <tr>
                    <th>Opciones</th>
                    <th>Id</th>
                    <th>Nombre</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="arrayelemento in arrayBuscador"
                    :key="arrayelemento.id"
                  >
                    <td>
                      <button
                        type="button"
                        @click="seleccionar(arrayelemento)"
                        class="btn btn-success btn-sm"
                      >
                        <i class="icon-check"></i>
                      </button>
                    </td>
                    <td v-text="arrayelemento.id"></td>
                    <td v-text="arrayelemento.nombre_grupo"></td>
                  </tr>
                </tbody>
              </table>
              <!--##################################################################3-->
            </div>
            <nav v-if="tituloModal2 == 'Marcas'">
              <ul class="pagination">
                <li class="page-item" v-if="pagination.current_page > 1">
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="
                      cambiarPaginaMarca(
                        pagination.current_page - 1,
                        buscar,
                        criterio
                      )
                    "
                    >Ant</a
                  >
                </li>
                <li
                  class="page-item"
                  v-for="page in pagesNumber"
                  :key="page"
                  :class="[page == isActived ? 'active' : '']"
                >
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="cambiarPaginaMarca(page, buscar, criterio)"
                    v-text="page"
                  ></a>
                </li>
                <li
                  class="page-item"
                  v-if="pagination.current_page < pagination.last_page"
                >
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="
                      cambiarPaginaMarca(
                        pagination.current_page + 1,
                        buscar,
                        criterio
                      )
                    "
                    >Sig</a
                  >
                </li>
              </ul>
            </nav>
            <nav v-else-if="tituloModal2 == 'Lineas'">
              <ul class="pagination">
                <li class="page-item" v-if="pagination.current_page > 1">
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="
                      cambiarPaginaLinea(
                        pagination.current_page - 1,
                        buscarA,
                        criterioA
                      )
                    "
                    >Ant</a
                  >
                </li>
                <li
                  class="page-item"
                  v-for="page in pagesNumber"
                  :key="page"
                  :class="[page == isActived ? 'active' : '']"
                >
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="
                      cambiarPaginaLinea(page, buscarA, criterioA)
                    "
                    v-text="page"
                  ></a>
                </li>
                <li
                  class="page-item"
                  v-if="pagination.current_page < pagination.last_page"
                >
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="
                      cambiarPaginaLinea(
                        pagination.current_page + 1,
                        buscarA,
                        criterioA
                      )
                    "
                    >Sig</a
                  >
                </li>
              </ul>
            </nav>
            <nav v-else-if="tituloModal2 == 'Industrias'">
              <ul class="pagination">
                <li class="page-item" v-if="pagination.current_page > 1">
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="
                      cambiarPaginaIndustria(
                        pagination.current_page - 1,
                        buscar,
                        criterio
                      )
                    "
                    >Ant</a
                  >
                </li>
                <li
                  class="page-item"
                  v-for="page in pagesNumber"
                  :key="page"
                  :class="[page == isActivedMar ? 'active' : '']"
                >
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="
                      cambiarPaginaIndustria(page, buscar, criterio)
                    "
                    v-text="page"
                  ></a>
                </li>
                <li
                  class="page-item"
                  v-if="pagination.current_page < pagination.last_page"
                >
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="
                      cambiarPaginaIndustria(
                        pagination.current_page + 1,
                        buscar,
                        criterio
                      )
                    "
                    >Sig</a
                  >
                </li>
              </ul>
            </nav>
            <nav v-else-if="tituloModal2 == 'Proveedors'">
              <ul class="pagination">
                <li class="page-item" v-if="pagination.current_page > 1">
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="
                      cambiarPaginaProveedor(
                        pagination.current_page - 1,
                        buscar,
                        criterio
                      )
                    "
                    >Ant</a
                  >
                </li>
                <li
                  class="page-item"
                  v-for="page in pagesNumber"
                  :key="page"
                  :class="[page == isActivedMar ? 'active' : '']"
                >
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="
                      cambiarPaginaProveedor(page, buscar, criterio)
                    "
                    v-text="page"
                  ></a>
                </li>
                <li
                  class="page-item"
                  v-if="pagination.current_page < pagination.last_page"
                >
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="
                      cambiarPaginaProveedor(
                        pagination.current_page + 1,
                        buscar,
                        criterio
                      )
                    "
                    >Sig</a
                  >
                </li>
              </ul>
            </nav>
            <nav v-else-if="tituloModal2 == 'Grupos'">
              <ul class="pagination">
                <li class="page-item" v-if="pagination.current_page > 1">
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="
                      cambiarPaginaGrupo(
                        pagination.current_page - 1,
                        buscar,
                        criterio
                      )
                    "
                    >Ant</a
                  >
                </li>
                <li
                  class="page-item"
                  v-for="page in pagesNumber"
                  :key="page"
                  :class="[page == isActivedMar ? 'active' : '']"
                >
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="cambiarPaginaGrupo(page, buscar, criterio)"
                    v-text="page"
                  ></a>
                </li>
                <li
                  class="page-item"
                  v-if="pagination.current_page < pagination.last_page"
                >
                  <a
                    class="page-link"
                    href="#"
                    @click.prevent="
                      cambiarPaginaGrupo(
                        pagination.current_page + 1,
                        buscar,
                        criterio
                      )
                    "
                    >Sig</a
                  >
                </li>
              </ul>
            </nav>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              @click="modal2 = false"
            >
              Cerrar
            </button>
          </div>
        </div>
        <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
    </div>
    <!--Fin del modal-->
    <div 
      class="modal fade" 
      tabindex="-1" 
      :class="{ 'mostrar': modalDetalleMovimientos }" 
      role="dialog" 
      style="display: none;" 
      :style="modalDetalleMovimientos ? 'display: block; overflow-y: auto;' : ''"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-dialog-centered modal-primary modal-lg" role="document">            
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    Detalle: {{ productoSeleccionado.nombre_producto }}
                </h4>
                <button type="button" class="close" @click="cerrarModalDetalle()" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link" :class="{ active: tabActual === 'ventas' }" href="#" @click.prevent="tabActual = 'ventas'">
                            <i class="icon-basket"></i> Ventas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ active: tabActual === 'ingresos' }" href="#" @click.prevent="tabActual = 'ingresos'">
                            <i class="icon-bag"></i> Compras
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ active: tabActual === 'ajustes' }" href="#" @click.prevent="tabActual = 'ajustes'">
                            <i class="icon-settings"></i> Ajustes
                        </a>
                    </li>
                </ul>

                <div v-if="tabActual === 'ventas'">
                    <DataTable 
                        key="dt-ventas" 
                        :value="detalleMovimientos.ventas" 
                        :paginator="true" 
                        :rows="5" 
                        size="small" 
                        stripedRows 
                        responsiveLayout="scroll"
                    >
                        <Column field="fecha_hora" header="Fecha"></Column>
                        <Column field="num_comprobante" header="N° Comprobante"></Column>
                        <Column field="nombre_cliente" header="Cliente"></Column>
                        <Column field="modo_venta" header="Modo"></Column>
                        <Column field="cantidad" header="Cantidad"></Column>
                    </DataTable>
                    <div v-if="detalleMovimientos.ventas.length === 0" class="text-center p-3">No hay ventas en este periodo.</div>
                </div>

                <div v-if="tabActual === 'ingresos'">
                    <DataTable 
                        key="dt-ingresos"
                        :value="detalleMovimientos.ingresos" 
                        :paginator="true" 
                        :rows="5" 
                        size="small" 
                        stripedRows 
                        responsiveLayout="scroll"
                    >
                        <Column field="fecha_hora" header="Fecha"></Column>
                        <Column field="num_comprobante" header="N° Comprobante"></Column>
                        <Column field="responsable_compra" header="Registrado Por"></Column>
                        <Column field="cantidad" header="Cantidad"></Column>
                    </DataTable>
                    <div v-if="detalleMovimientos.ingresos.length === 0" class="text-center p-3">No hay compras en este periodo.</div>
                </div>

                <div v-if="tabActual === 'ajustes'">
                  <DataTable 
                      key="dt-ajustes"
                      :value="detalleMovimientos.ajustes" 
                      :paginator="true" 
                      :rows="5" 
                      size="small" 
                      stripedRows 
                      responsiveLayout="scroll"
                  >
                      <Column field="fecha_hora" header="Fecha"></Column>
                      <Column field="motivo" header="Motivo"></Column>
                      <Column field="responsable" header="Registrado Por"></Column>
                      <Column field="tipo_ajuste" header="Tipo"></Column>
                      <Column field="cantidad" header="Cantidad"></Column>                          
                  </DataTable>
                  <div v-if="detalleMovimientos.ajustes.length === 0" class="text-center p-3">No hay ajustes en este periodo.</div>
                </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="cerrarModalDetalle()">Cerrar</button>
            </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script>
import Panel from "primevue/panel";
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import { esquemaArticulos } from "../constants/validations";
import "jspdf-autotable";
import VueBarcode from "vue-barcode";
import * as XLSX from "xlsx-js-style";
import { FilterMatchMode } from "primevue/api";
import InputText from "primevue/inputtext";

export default {
  data() {
    return {
      mostrarLabel: true,
      datosFormulario: {
        nombre: "",
        descripcion: "",
        nombre_generico: "",
        unidad_envase: 0,
        precio_costo_unid: 0,
        precio_costo_paq: 0,
        precio_venta: 0,
        precio_uno: 0,
        precio_dos: 0,
        precio_tres: 0,
        precio_cuatro: 0,
        stock: 0,
        costo_compra: 0,
        codigo: "",
        codigo_alfanumerico: "",
        descripcion_fabrica: "",
        idcategoria: null,
        idmarca: null,
        idindustria: null,
        idgrupo: null,
        idproveedor: null,
        idmedida: null,
      },
      errores: {},

      monedaPrincipal: [],

      successImport: false,
      registrosSuccess: [],
      errorsImport: [],
      erroresNoExiste: [],

      parsedPreviewCsv: [],
      selectedDelimiter: ";",
      includeHeader: true,

      pageImportar: 0,
      modalImportar: 0,
      selectedFile: null,
      csvHeaders: null,
      selectedHeadersFromFile: [],
      selectedHeadersToAssign: [],
      headersAssigned: false,
      previewCsv: "",
      headersArray: [
        "Linea",
        "Grupo",
        "Proveedor",
        "Medida",
        "Codigo",
        "Nombre",
        "Nombre generico",
        "Unidad envase",
        "Precio List unidad",
        "Precio costo unidad",
        "Precio costo paquete",
        "Precio venta",
        "Precio uno",
        "Precio dos",
        "Precio tres",
        "Precio cuatro",
        "Stock minimo",
        "Descripciòn",
        "Estado",
        "Costo compra",
        "Marca",
        "Industria",
      ],

      criterioA: "nombre",
      buscarA: "",
      tituloModal2: "",
      clienteseleccionada: [],
      marcaseleccionada: [],
      industriaseleccionada: [],
      lineaseleccionada: [],
      proveedorseleccionada: [],
      gruposeleccionada: [],
      nombre_grupo: "",

      modal2: false,
      modal6: false,
      articulo_id: 0,
      idcategoria: 0,
      idmarca: 0,
      idindustria: 0,
      idproveedor: 0,
      idgrupo: 0,
      codigoProductoSin: 0,
      idmedida: 0,
      nombreLinea: "",
      nombre_categoria: "",
      nombre_proveedor: "",
      //id:'',//aumente 7 julio
      codigo: "",
      nombre: "",
      nombre_producto: "",
      nombre_generico: "",
      //validaion para inputs
      nombreProductoVacio: false,
      codigoVacio: false,
      unidad_envaseVacio: false,
      nombre_genericoVacio: false,
      precio_costo_unidVacio: false,
      precio_costo_paqVacio: false,
      precio_ventaVacio: false,
      costo_compraVacio: false,
      stockVacio: false,
      descripcionVacio: false,
      fotografiaVacio: false,
      lineaseleccionadaVacio: false,
      marcaseleccionadaVacio: false,
      industriaseleccionadaVacio: false,
      proveedorseleccionadaVacio: false,
      gruposeleccionadaVacio: false,
      medidaseleccionadaVacio: false,
      sucursalseleccionadaVacio: false,
      articuloseleccionadaVacio: false,
      clienteseleccionadaVacio: false,

      //aumente esto
      unidad_envase: 0,
      precio_costo_unid: 0,
      precio_costo_paq: 0,
      //hasta aqui
      precios: [],
      //precioss: [],
      precio: "", //aumente 5julio
      //mostrarPrecios: false,
      //precioCount: 0,
      //aumento 13_junio
      precio_uno: 0,
      precio_dos: 0,
      precio_tres: 0,
      precio_cuatro: 0,
      //hasta aqui
      //--------proveedor para almacer el registrado y editar------
      proveedor_id: 0,
      tipo_documento: "DNI",
      num_documento: "",
      direccion: "",
      telefono: "",
      email: "",
      contacto: "",
      telefono_contacto: "",
      //--------hasta aqui-----------------
      //--grupo--
      nombre_grupo: "",
      grupo_id: 0,
      //---hasta aqui
      precio_venta: 0,
      costo_compra: 0,

      stock: 5,
      nombre_persona: "",
      descripcion: "",
      fotografia: "",
      fotoMuestra: null,
      arrayArticulo: [],
      arrayBuscador: [],
      modal: 0,

      tituloModal: "",
      tipoAccion: 0,
      tipoAccion2: 0,
      tipoAccion6: 0,
      errorArticulo: 0,
      errorMostrarMsjArticulo: [],
      //------registro industia, marcas--
      modal3: 0,
      tituloModal3: "",
      industria_id: 0,
      marca_id: 0,
      linea_id: 0,
      estado: 1,
      condicion: 1,
      nombre_industria: "",
      mostrarElemento: "",
      errorIndustria: 0,
      errorMostrarMsjIndustria: [],
      //--------hasta aqui---
      pagination: this.createPaginationObject(),
      paginationMedida: this.createPaginationObject(),
      offset: {
        pagination: 3,
        paginationMedida: 3,
      },
      criterio: "nombre",
      buscar: "",
      arrayCategoria: [],

      //CONFIGURACIONES
      mostrarSaldosStock: "",
      mostrarProveedores: "",
      mostrarCostos: "",
      rolUsuario: "",

      //medida
      arrayMedida: [],
      errorMedida: 0,
      errorMostrarMsjMedida: [],
      modal7: 0,
      tituloModal6: "",
      tituloModal7: "",
      medida_id: 0,
      descripcion_medida: "",
      descripcion_corta: "",
      medidaseleccionada: [],

      //Sucursal
      arraySucursal: [],
      sucursalseleccionada: [],

      //articulo
      articuloseleccionada: [],
      arrayReporte: [],
      total_saldofisico: 0,

      //fechas
      fechaInicio: "",
      fechaFin: "",
      globalFilter: null,
      isLoading: false, // <-- loading
      filters: {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        codigo: { value: null, matchMode: FilterMatchMode.CONTAINS },
        nombre_producto: { value: null, matchMode: FilterMatchMode.CONTAINS },
        categoria: { value: null, matchMode: FilterMatchMode.CONTAINS },
        sucursal: { value: null, matchMode: FilterMatchMode.CONTAINS },
        almacen: { value: null, matchMode: FilterMatchMode.CONTAINS },
      },

      tabActual: 'ventas',          
      modalDetalleMovimientos: 0,   
      productoSeleccionado: {},     
      detalleMovimientos: {         
          ventas: [],
          ingresos: [],
          ajustes: []
      },
    };
  },
  components: {
    barcode: VueBarcode,
    DataTable,
    Column,
    InputText,
    Panel,
    Button,
  },
  computed: {
    sortedResultados: function() {
      return Object.values(this.arrayReporte).sort((a, b) => {
        return new Date(a.fecha_hora) - new Date(b.fecha_hora);
      });
    },
    isActived: function() {
      return this.pagination.current_page;
    },
    isActivedM: function() {
      return this.pagination.current_page;
    },
    isActivedMar: function() {
      return this.pagination.current_page;
    },

    pagesNumber: function() {
      return this.calculatePages(this.pagination, this.offset.pagination);
    },
    pagesNumberMedida: function() {
      return this.calculatePages(
        this.paginationMedida,
        this.offset.paginationMedida
      );
    },
    imagen() {
      console.log(this.fotoMuestra);
      return this.fotoMuestra;
    },
  },
  watch: {
    selectedDelimiter: "updateData",
    previewCsv: "parseCsv", // Llama a parseCsv cuando previewCsv cambie
    globalFilter(newVal) {
      this.filters.global.value = newVal;
    },
  },

  methods: {
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    toastSuccess(mensaje) {
      this.$toasted.show(
        `
    <div style="height: 60px;font-size:16px;">
        <br>
        ` +
          mensaje +
          `.<br>
    </div>`,
        {
          type: "success",
          position: "bottom-right",
          duration: 4000,
        }
      );
    },
    toastError(mensaje) {
      this.$toasted.show(
        `
    <div style="height: 60px;font-size:16px;">
        <br>
        ` +
          mensaje +
          `<br>
    </div>`,
        {
          type: "error",
          position: "bottom-right",
          duration: 4000,
        }
      );
    },
    asignarCampos() {
      this.datosFormulario.idcategoria = this.lineaseleccionada.id;
      this.datosFormulario.idmarca = this.marcaseleccionada.id;
      this.datosFormulario.idproveedor = this.proveedorseleccionada.id;
      this.datosFormulario.idindustria = this.industriaseleccionada.id;
      this.datosFormulario.idmedida = this.medidaseleccionada.id;
      this.datosFormulario.idgrupo = this.gruposeleccionada.id;
      this.datosFormulario.idSucursal = this.sucursalseleccionada.id;
      this.datosFormulario.idArticulo = this.articuloseleccionada.id;
      this.datosFormulario.idCliente = this.clienteseleccionada.id;

      this.datosFormulario.precio_costo_unid = this.convertDolar(
        this.datosFormulario.precio_costo_unid
      );
      this.datosFormulario.precio_costo_paq = this.convertDolar(
        this.datosFormulario.precio_costo_paq
      );
      this.datosFormulario.precio_venta = this.convertDolar(
        this.datosFormulario.precio_venta
      );

      this.datosFormulario.precio_uno = this.convertDolar(this.precio_uno);
      this.datosFormulario.precio_dos = this.convertDolar(this.precio_dos);
      this.datosFormulario.precio_tres = this.convertDolar(this.precio_tres);
      this.datosFormulario.precio_cuatro = this.convertDolar(
        this.precio_cuatro
      );
      this.datosFormulario.costo_compra = this.convertDolar(
        this.datosFormulario.costo_compra
      );
    },
    async validarCampo(campo) {
      this.asignarCampos();
      try {
        await esquemaArticulos.validateAt(campo, this.datosFormulario);
        this.errores[campo] = null;
      } catch (error) {
        this.errores[campo] = error.message;
      }
    },
    async enviarFormulario() {
      this.asignarCampos();

      await esquemaArticulos
        .validate(this.datosFormulario, { abortEarly: false })
        .then(() => {
          this.datosFormulario.fotografia = this.fotografia;

          console.log(this.datosFormulario);
          if (this.tipoAccion == 2) {
            this.actualizarArticulo(this.datosFormulario);
          } else {
            this.registrarArticulo(this.datosFormulario);
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
    obtenerConfiguracionTrabajo() {
      // Utiliza Axios para realizar la solicitud al backend
      axios
        .get("/configuracion")
        .then((response) => {
          console.log(
            "Esta es la configuracion: ",
            response.data.configuracionTrabajo
          );
        })
        .catch((error) => {
          console.error("Error al obtener configuración de trabajo:", error);
        });
    },

    confirmarRegistro() {
      this.erroresNoExiste.forEach((elemento) => {
        const palabras = elemento.split(" ");
        const primeraPalabra = palabras.shift();
        const restoDelString = palabras.join(" ");
        console.log(palabras);
        if ("Linea" === primeraPalabra) {
          console.log("Se encontro " + restoDelString);
          this.agregarLinea(restoDelString);
        } else if ("Marca" === primeraPalabra) {
          this.agregarMarca(restoDelString);
          console.log("Se encontro " + restoDelString);
        } else if ("Grupo" === primeraPalabra) {
          this.agregarGrupo(restoDelString);
          console.log("Se encontro " + restoDelString);
        } else if ("Industria" === primeraPalabra) {
          this.agregarIndustria(restoDelString);
          console.log("Se encontro " + restoDelString);
        }
      });

      this.submitForm();

      // Puedes realizar alguna acción adicional aquí
    },
    cancelarRegistro() {
      alert("Registro cancelado");
      // Puedes realizar alguna acción adicional aquí
    },
    updateData() {
      this.extractCSVHeaders(this.selectedFile)
        .then((headers) => {
          this.csvHeaders = headers;
        })
        .catch((error) => {
          console.error("Error al extraer encabezados:", error);
        });
    },
    parseCsv() {
      // Divide la cadena de vista previa en filas y luego cada fila en columnas
      this.parsedPreviewCsv = this.previewCsv
        .split("\n")
        .map((row) => row.split(this.selectedDelimiter));
    },

    selectAllHeaders() {
      // Seleccionar todos los encabezados automáticamente
      this.selectedHeadersFromFile = [...this.csvHeaders];
    },

    handleFileChange(event) {
      this.selectedFile = event.target.files[0];
      this.extractCSVHeaders(this.selectedFile)
        .then((headers) => {
          this.csvHeaders = headers;
        })
        .catch((error) => {
          console.error("Error al extraer encabezados:", error);
        });
      this.selectedHeadersFromFile = [];
      this.selectedHeadersToAssign = [];
      this.headersAssigned = false;
    },
    removeFile() {
      this.selectedFile = null;
      this.csvHeaders = null;
      this.selectedHeadersFromFile = [];
      this.selectedHeadersToAssign = [];
      this.headersAssigned = false;
      this.$refs.fileInput.value = "";
      this.previewCsv = "";
    },
    confirmCsv() {
      this.pageImportar += 1;

      if (!this.includeHeader) {
        const mapping = {};
        this.csvHeaders.forEach((item, index) => {
          mapping[item] = (index + 1).toString();
        });
        const newArray = this.csvHeaders.map((item) => mapping[item]);
        this.csvHeaders = newArray;
        console.log(this.csvHeaders);
      }
      console.log(this.selectedDelimiter);
    },
    assignHeaders() {
      if (!this.selectedFile) {
        console.error("No se ha seleccionado un archivo.");
        return;
      }
      this.pageImportar += 1;

      const reader = new FileReader();

      reader.onload = (event) => {
        let csvContent = event.target.result;
        if (!this.includeHeader) {
          const primeraFila = this.csvHeaders.join(",");
          csvContent = `${primeraFila}\n${csvContent}`;
        }

        const newCsvContent = this.getCsvSubset(
          csvContent,
          this.selectedHeadersFromFile
        );

        this.previewCsv = newCsvContent;
      };

      reader.readAsText(this.selectedFile);
    },

    getCsvSubset(csvContent, selectedHeaders) {
      const rows = csvContent.split("\n");
      const headerIndices = selectedHeaders.map((header) =>
        rows[0].split(this.selectedDelimiter).indexOf(header)
      );

      let newRows;

      newRows = rows.slice(1).map((row) => {
        const columns = row.split(this.selectedDelimiter);
        return headerIndices.map((index) => columns[index]).join(",");
      });

      const newCsvContent = newRows.join("\n");
      console.log(newCsvContent);
      console.log(this.includeHeader);
      return newCsvContent;
    },

    downloadCsv() {
      const blob = new Blob([this.previewCsv], { type: "text/csv" });
      const link = document.createElement("a");
      link.href = URL.createObjectURL(blob);
      link.download = "nuevo_csv.csv";
      link.click();
    },
    createNewCsvData() {
      const selectedHeadersFromFile = this.selectedHeadersFromFile;
      const selectedHeadersToAssign = this.selectedHeadersToAssign;
      console.log("xd");

      const newData = this.csvData.map((row) => {
        const newRow = {};

        selectedHeadersFromFile.forEach((header) => {
          newRow[header] = row[header];
        });

        selectedHeadersToAssign.forEach((header) => {
          newRow[header] = "";
        });

        return newRow;
      });

      const newCsvContent = [
        selectedHeadersToAssign.concat(selectedHeadersFromFile).join(","),
      ];
      newData.forEach((row) => {
        const values = selectedHeadersToAssign
          .concat(selectedHeadersFromFile)
          .map((header) => row[header]);
        newCsvContent.push(values.join(","));
      });

      return newCsvContent.join("\n");
    },
    extractCSVHeaders(file) {
      console.log("XDDD esta por acaaaa");
      return new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onload = (e) => {
          const content = e.target.result;
          const rows = content.split("\n");
          const headers = rows[0].split(this.selectedDelimiter);
          resolve(headers);
        };

        reader.onerror = (error) => {
          reject(error);
        };

        reader.readAsText(file);
      });
    },
    submitForm() {
      if (!this.previewCsv) {
        alert("No hay un nuevo CSV para importar.");
        return;
      }
      this.pageImportar = 3;

      const blob = new Blob([this.previewCsv], { type: "text/csv" });
      const newCsvFile = new File([blob], "nuevo_csv.csv", {
        type: "text/csv",
      });
      const formData = new FormData();
      formData.append("archivo", newCsvFile);
      axios
        .post("/articulos/importar", formData)
        .then((response) => {
          console.log(response);
          this.erroresNoExiste = [];
          this.errorsImport = [];
          this.successImport = true;
        })
        .catch((error) => {
          if (error.response && error.response.status === 422) {
            const datos = error.response.data.errors;
            console.log(datos);
            this.erroresNoExiste = datos.flatMap((item) => {
              const match = item.match(/No existe '([^']+)'/);
              return match ? [match[1]] : [];
            });
            console.log("Estos no existen: ", this.erroresNoExiste);
            this.errorsImport = datos.filter(
              (item) => !item.includes("No existe")
            );
            this.erroresNoExiste = this.erroresNoExiste.filter(
              (valor, indice, array) => array.indexOf(valor) === indice
            );
            // Mostrar el nuevo array con los elementos filtrados
            console.log(this.erroresNoExiste);
            console.log(this.errorsImport);
          } else {
            console.error(error);
          }
        });
    },
    calculatePages: function(paginationObject, offset) {
      if (!paginationObject.to) {
        return [];
      }

      var from = paginationObject.current_page - offset;
      if (from < 1) {
        from = 1;
      }

      var to = from + offset * 2;
      if (to >= paginationObject.last_page) {
        to = paginationObject.last_page;
      }

      var pagesArray = [];
      while (from <= to) {
        pagesArray.push(from);
        from++;
      }
      return pagesArray;
    },
    createPaginationObject() {
      return {
        total: 0,
        current_page: 0,
        per_page: 0,
        last_page: 0,
        from: 0,
        to: 0,
      };
    },
    calcularPrecioCostoUnid() {
      if (
        this.datosFormulario.unidad_envase &&
        this.datosFormulario.precio_costo_paq
      ) {
        this.datosFormulario.precio_costo_unid =
          this.datosFormulario.precio_costo_paq /
          this.datosFormulario.unidad_envase;
        this.datosFormulario.precio_costo_unidVacio = false;
      }
    },
    calcularPrecioCostoPaq() {
      if (
        this.datosFormulario.unidad_envase &&
        this.datosFormulario.precio_costo_unid
      ) {
        this.datosFormulario.precio_costo_paq =
          this.datosFormulario.precio_costo_unid *
          this.datosFormulario.unidad_envase;
        this.datosFormulario.precio_costo_paqVacio = false;
      }
    },
    calcularPrecioP(precio_costo_unid, porcentage) {
      const margenG = precio_costo_unid * (porcentage / 100);
      const precioP = parseFloat(precio_costo_unid) + parseFloat(margenG);
      return precioP.toFixed(2);
    },
    //-------------hasta qui calcular -----------
    seleccionar(selected) {
      if (this.tituloModal2 == "Marcas") {
        this.marcaseleccionadaVacio = false;
        if (selected.condicion == 1) {
          this.marcaseleccionada = selected;
          this.validarCampo("idmarca");
        } else if (selected.condicion == 0) {
          this.advertenciaInactiva("Marcas");
        }
      } else if (this.tituloModal2 == "Industrias") {
        this.industriaseleccionadaVacio = false;
        if (selected.estado == 1) {
          this.industriaseleccionada = selected;
          this.validarCampo("idindustria");
        } else if (selected.estado == 0) {
          this.advertenciaInactiva("Industrias");
        }
      } else if (this.tituloModal2 == "Lineas") {
        if (selected.condicion == 1) {
          this.lineaseleccionada = selected;
          this.validarCampo("idcategoria");
        } else if (selected.condicion == 0) {
          this.advertenciaInactiva("Lineas");
        }
      } else if (this.tituloModal2 == "Proveedors") {
        // this.proveedorseleccionada.id = selected.id;
        // this.proveedorseleccionada.nombre = selected.nombre;
        this.proveedorseleccionada = selected;
        this.validarCampo("idproveedor");
      } else if (this.tituloModal2 == "Grupos") {
        this.gruposeleccionada = selected;
        this.validarCampo("idgrupo");
      } else if (this.tituloModal2 == "Sucursal") {
        if (selected.condicion == 1) {
          this.sucursalseleccionada = selected;
          this.validarCampo("idSucursal");
        } else if (selected.condicion == 0) {
          this.advertenciaInactiva("Sucursal");
        }
      } else if (this.tituloModal2 == "Articulo") {
        if (selected.condicion == 1) {
          this.articuloseleccionada = selected;
          this.validarCampo("idArticulo");
        } else if (selected.condicion == 0) {
          this.advertenciaInactiva("Articulo");
        }
      } else if (this.tituloModal2 == "Cliente") {
        this.clienteseleccionadaVacio = false;
        this.clienteseleccionada = selected;
      }

      // if (this.marcaseleccionada.condicion == 1 ){
      //     console.log("selcciona", selected);
      // }else if (this.marcaseleccionada.condicion == 0 ){
      //     console.log("Estado invalido", this.marcaseleccionada.condicion);
      // }
      console.log("Seleccionado", selected);
      this.arrayBuscador = [];
      this.modal2 = false;
    },

    seleccionar2(selected) {
      if (this.tituloModal6 == "Medidas") {
        this.medidaseleccionadaVacio = false;
        if (selected.estado == 1) {
          this.medidaseleccionada = selected;
          this.validarCampo("idmedida");
        } else if (selected.estado == 0) {
          this.advertenciaInactiva("Medidas");
        }
      }
      this.arrayBuscador = [];
      this.modal6 = false;
    },

    listarIndustria(page, buscar, criterio) {
      let me = this;
      var url =
        "/industria?page=" +
        page +
        "&buscar=" +
        buscar +
        "&criterio=" +
        criterio;
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.arrayBuscador = respuesta.industrias.data;
          me.pagination = respuesta.pagination;
        })
        .catch(function(error) {
          console.log(error);
        });
    },
    listarLinea(page, buscar, criterio) {
      let me = this;
      var url =
        "/categoria?page=" +
        page +
        "&buscar=" +
        buscar +
        "&criterio=" +
        criterio;
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.arrayBuscador = respuesta.categorias.data;
          me.pagination = respuesta.pagination;
        })
        .catch(function(error) {
          console.log(error);
        });
    },

    listarMedida(page, buscar, criterio) {
      let me = this;
      var url =
        "/medida?page=" + page + "&buscar=" + buscar + "&criterio=" + criterio;
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.arrayBuscador = respuesta.medidas.data;
          me.paginationMedida = respuesta.paginationMedida;
        })
        .catch(function(error) {
          console.log(error);
        });
    },

    abrirModal2(titulo) {
      if (titulo == "Marcas") {
        this.listarMarca(1, "", "nombre");

        this.modal2 = true;

        this.tituloModal2 = titulo;
        this.marcaseleccionadaVacio = false;
      } else if (titulo == "Industrias") {
        this.listarIndustria(1, "", "nombre");
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.industriaseleccionadaVacio = false;
      } else if (titulo == "Lineas") {
        this.listarLinea(1, "", "nombreLinea");
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.lineaseleccionadaVacio = false;
      } else if (titulo == "Proveedors") {
        this.listarproveedor(1, "", "nombre");
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.proveedorseleccionadaVacio = false;
      } else if (titulo == "Grupos") {
        this.listargrupo(1, "", "nombre_grupo");
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.gruposeleccionadaVacio = false;
      } else if (titulo == "Sucursal") {
        this.listarSucursal(1, "", "nombre");
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.gruposeleccionadaVacio = false;
      } else if (titulo == "Articulo") {
        this.listarArticulo(1, "", "");
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.articuloseleccionadaVacio = false;
      } else if (titulo == "Cliente") {
        this.listarPersona(1, "", "nombre");
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.clienteseleccionada = false;
      }
    },

    abrirModal6(titulo) {
      if (titulo == "Medidas") {
        this.listarMedida(1, "", "descripcion_medida");
        this.modal6 = true;
        this.tituloModal6 = titulo;
        this.medidaseleccionadaVacio = false;
      }
    },

    listarSucursal(page, buscar, criterio) {
      let me = this;
      var url =
        "/sucursal?page=" +
        page +
        "&buscar=" +
        buscar +
        "&criterio=" +
        criterio;
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.arrayBuscador = respuesta.sucursales.data;
          me.pagination = respuesta.pagination;
        })
        .catch(function(error) {
          console.log(error);
        });
    },

    listarPersona(page, buscar, criterio) {
      let me = this;
      var url =
        "/cliente?page=" + page + "&buscar=" + buscar + "&criterio=" + criterio;
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.arrayBuscador = respuesta.usuarios.data;
          me.pagination = respuesta.pagination;
          console.log("hola", me.arrayBuscador);
        })
        .catch(function(error) {
          console.log(error);
        });
    },

    listarArticulo(page, buscar, criterio) {
      let me = this;
      var url =
        "/articulo?page=" +
        page +
        "&buscar=" +
        buscar +
        "&criterio=" +
        criterio;
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.arrayBuscador = respuesta.articulos.data;
          me.pagination = respuesta.pagination;
        })
        .catch(function(error) {
          console.log(error);
        });
    },

    listarMarca(page, buscar, criterio) {
      let me = this;
      console.log("Listano");
      var url =
        "/marca?page=" + page + "&buscar=" + buscar + "&criterio=" + criterio;

      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          console.log(respuesta);

          me.arrayBuscador = respuesta.marcas.data;
          me.pagination = respuesta.pagination;
          console.log("Listad0");
        })
        .catch(function(error) {
          console.log(error);
        });
    },
    //------listado proveedor, Registro Proveedor y editar-----------
    listarproveedor(page, buscar, criterio) {
      let me = this;
      console.log("ListanoProveedor");
      var url =
        "/proveedor?page=" +
        page +
        "&buscar=" +
        buscar +
        "&criterio=" +
        criterio;
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          console.log(respuesta);
          me.arrayBuscador = respuesta.personas.data;
          me.pagination = respuesta.pagination;
          console.log("Listad0");
        })
        .catch(function(error) {
          console.log(error);
        });
    },

    //--grupo listado ,registro y actualizar
    listargrupo(page, buscar, criterio) {
      let me = this;
      console.log("ListanoGrupos");
      var url =
        "/grupos?page=" + page + "&buscar=" + buscar + "&criterio=" + criterio;
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          console.log(respuesta);
          me.arrayBuscador = respuesta.grupos.data;
          me.pagination = respuesta.pagination;
          console.log("Listad::");
        })
        .catch(function(error) {
          console.log("ERRORES", error);
        });
    },

    listaReporte() {
      let me = this;
      me.isLoading = true;

      if (!me.sucursalseleccionada || !me.sucursalseleccionada.id) {
        swal({
          title: "Sucursal no seleccionada",
          text: "Debe seleccionar una sucursal para generar el reporte.",
          type: "warning",
          confirmButtonColor: "#3085d6",
          confirmButtonText: "Aceptar",
          confirmButtonClass: "btn btn-danger",
          buttonsStyling: false,
        });
        me.isLoading = false;
        return; // No continúa si no hay sucursal
      }

      if (!me.fechaInicio || !me.fechaFin) {
        swal({
          title: "Fechas no seleccionadas",
          text:
            "Debe seleccionar una fecha de inicio y una fecha de fin para generar el reporte.",
          type: "warning",
          confirmButtonColor: "#3085d6",
          confirmButtonText: "Aceptar",
          confirmButtonClass: "btn btn-danger",
          buttonsStyling: false,
        });
        me.isLoading = false;
        return; // No continúa si no hay fechas
      }
      const params = new URLSearchParams();
      params.append("sucursal", me.sucursalseleccionada.id);
      params.append("articulo", me.articuloseleccionada.id);
      params.append("marca", me.marcaseleccionada.id);
      params.append("linea", me.lineaseleccionada.id);
      params.append("fechaInicio", me.fechaInicio);
      params.append("fechaFin", me.fechaFin);

      axios
        .get("/reporte-resumen-fisico-movimientos?" + params.toString())
        .then(function(response) {
          const respuesta = response.data;
          me.total_saldofisico = respuesta.total_saldo;
          me.arrayReporte = respuesta.resultados;
          me.isLoading = false;
          console.log("array reporte", me.arrayReporte);
        })
        .catch(function(error) {
          me.isLoading = false;
          console.log("ERRORES", error);
        });
    },

    verDetalleMovimiento(data) {
        let me = this;

        me.modal2 = 0; 

        me.modalDetalleMovimientos = 1;

        me.productoSeleccionado = data;
        me.tabActual = 'ventas'; 
        me.detalleMovimientos = { ventas: [], ingresos: [], ajustes: [] };      

        if (!data.id_articulo || !data.id_almacen) {
            console.error("Faltan IDs en la fila seleccionada. Revisa resumenFisicoMovimientos en Laravel.");
            return;
        }

        const params = new URLSearchParams();
        params.append("idArticulo", data.id_articulo);
        params.append("idAlmacen", data.id_almacen);
        params.append("fechaInicio", me.fechaInicio);
        params.append("fechaFin", me.fechaFin);

        axios
            .get("/reporte-detalle-movimientos?" + params.toString())
            .then(function(response) {
                me.detalleMovimientos = response.data;
            })
            .catch(function(error) {
                console.error("Error cargando detalles", error);
            });
    },

    cerrarModalDetalle() {
        this.modalDetalleMovimientos = 0;
        this.productoSeleccionado = {};
    },

    // Descargar el PDF de la tabla grande (Todos los productos)
    descargarPdfGeneral() {
        let me = this;
        if (!me.sucursalseleccionada || !me.fechaInicio || !me.fechaFin) {
            swal("Atención", "Debe seleccionar Sucursal y Fechas primero", "warning");
            return;
        }

        // Construir la URL con todos los filtros actuales
        let params = new URLSearchParams();
        params.append("sucursal", me.sucursalseleccionada.id);
        params.append("fechaInicio", me.fechaInicio);
        params.append("fechaFin", me.fechaFin);
        
        // Agregar filtros opcionales solo si están seleccionados
        if(me.articuloseleccionada && me.articuloseleccionada.id) params.append("articulo", me.articuloseleccionada.id);
        if(me.marcaseleccionada && me.marcaseleccionada.id) params.append("marca", me.marcaseleccionada.id);
        if(me.lineaseleccionada && me.lineaseleccionada.id) params.append("linea", me.lineaseleccionada.id);

        let url = '/reporte-resumen-general-pdf?' + params.toString();
        window.open(url, '_blank');
    },

    // Descargar el PDF de un solo producto (Ventas, Compras, Ajustes)
    descargarPdfDetallado(data) {
        let me = this;
        if (!me.fechaInicio || !me.fechaFin) {
            swal("Atención", "Debe seleccionar un rango de fechas", "warning");
            return;
        }

        let params = new URLSearchParams();
        params.append("idArticulo", data.id_articulo);
        params.append("idAlmacen", data.id_almacen);
        params.append("fechaInicio", me.fechaInicio);
        params.append("fechaFin", me.fechaFin);

        let url = '/reporte-resumen-detallado-pdf?' + params.toString();
        window.open(url, '_blank');
    },

    exportarExcel() {
      const workbook = XLSX.utils.book_new();
      const worksheet = XLSX.utils.aoa_to_sheet([]);
      const startRow = 5;

      // Merge de celdas para el título
      worksheet["!merges"] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 6 } }];
      // Título del reporte
      worksheet["A1"] = {
        t: "s",
        v: "RESUMEN FISICO DE MOVIMIENTOS",
        s: {
          font: { sz: 16, bold: true, color: { rgb: "FFFFFF" } },
          alignment: { horizontal: "center", vertical: "center" },
          fill: { fgColor: { rgb: "3669a8" } },
        },
      };

      // Estilo para la fecha
      const fechaStyle = { font: { bold: true, color: { rgb: "000000" } } };
      // Fechas de inicio y fin
      worksheet["A2"] = {
        t: "s",
        v: `Fecha inicio: ${this.fechaInicio}`,
        s: fechaStyle,
      };
      worksheet["C2"] = {
        t: "s",
        v: `Fecha fin: ${this.fechaFin}`,
        s: fechaStyle,
      };
      worksheet["F2"] = {
        t: "s",
        v: `Sucursal: ${this.sucursalseleccionada.nombre}`,
        s: fechaStyle,
      };
      worksheet["A3"] = {
        t: "s",
        v: `Articulo: ${this.articuloseleccionada.nombre}`,
        s: fechaStyle,
      };
      worksheet["C3"] = {
        t: "s",
        v: `Linea: ${this.lineaseleccionada.nombre}`,
        s: fechaStyle,
      };

      // Estilo para los encabezados
      const headerStyle = {
        font: { bold: true, color: { rgb: "FFFFFF" } },
        fill: { fgColor: { rgb: "3669a8" } },
      };
      // Cabeceras de las columnas
      const headers = [
        "Codigo Item",
        "Producto",
        "Categoria",
        "Saldo Anterior",
        "Entrada",
        "Salida",
        "Saldo Actual",
      ];

      // Añadir las cabeceras a la hoja de cálculo
      headers.forEach((header, index) => {
        worksheet[XLSX.utils.encode_cell({ r: 3, c: index })] = {
          t: "s",
          v: header,
          s: headerStyle,
        };
      });

      // Añadir los datos al kardex
      Object.values(this.sortedResultados).forEach((item, rowIndex) => {
        const rowData = [
          item.codigo,
          item.nombre_producto,
          item.nombre_categoria,
          item.saldo_anterior,
          item.ingresos,
          item.ventas,
          item.saldo_actual,
        ];

        // Añadir la fila al kardex
        XLSX.utils.sheet_add_aoa(worksheet, [rowData], {
          origin: `A${startRow + rowIndex}`,
        });
      });

      // Añadir el total ganado al final del reporte

      const totalRow = [`Total Ganado: Bs. ${this.total_saldo}`];
      worksheet["!merges"].push({
        s: { r: startRow + Object.values(this.sortedResultados).length, c: 0 },
        e: { r: startRow + Object.values(this.sortedResultados).length, c: 3 },
      });

      // Establecer el ancho de las columnas
      const columnWidths = [
        { wch: 37.22 },
        { wch: 36.67 },
        { wch: 28.33 },
        { wch: 8.56 },
        { wch: 23.89 },
        { wch: 13.68 },
        { wch: 9.11 },
        { wch: 9.0 },
        { wch: 10.78 },
      ];
      worksheet["!cols"] = columnWidths;

      // Añadir la hoja de cálculo al libro
      XLSX.utils.book_append_sheet(
        workbook,
        worksheet,
        "Resumen Movimientos Fisicos"
      );

      // Descargar el archivo
      XLSX.writeFile(workbook, "resumen_movimientos_fisicos.xlsx");
    },

    formateaKardex() {
      let saldo = 0;
      let me = this;
      me.arrayReporte = this.arrayReporte.map((item) => {
        if (item.tipo === "Ingreso") {
          saldo += item.cantidad;
          return {
            fecha_hora: item.fecha_hora,
            entrada: item.cantidad,
            salida: 0,
            saldo,
          };
        } else if (item.tipo === "Salida") {
          saldo -= item.cantidad;
          return {
            fecha_hora: item.fecha_hora,
            entrada: 0,
            salida: item.cantidad,
            saldo,
          };
        }
        // Si hay más tipos, puedes agregar condiciones aquí
      });

      console.log("KARDEX", me.arrayReporte);
    },

    //----listar precio 4_julio-------
    listarPrecio() {
      let me = this;
      var url = "/precios";
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.precios = respuesta.precio.data;
          console.log("PRECIOS", me.precios);
          //me.precioCount = me.arrayBuscador.length;
        })
        .catch(function(error) {
          console.log(error);
        });
    },
    //---------hasta aqui----------------

    selectMedida() {
      let me = this;
      var url = "/medida/selectMedida";
      axios
        .get(url)
        .then(function(response) {
          //console.log(response);
          var respuesta = response.data;
          me.arrayMedida = respuesta.medidas;
        })
        .catch(function(error) {
          console.log(error);
        });
    },

    cambiarPagina(page, buscar, criterio) {
      let me = this;
      //Actualiza la página actual
      me.pagination.current_page = page;
      //Envia la petición para visualizar la data de esa página
      me.listarArticulo(page, buscar, criterio);
    },
    cambiarPaginaMedida(page, buscar, criterio) {
      let me = this;
      //Actualiza la página actual
      me.paginationMedida.current_page = page;
      me.listarMedida(page, buscar, criterio);
      //Envia la petición para visualizar la data de esa página
    },
    cambiarPaginaMarca(page, buscar, criterio) {
      let me = this;
      //Actualiza la página actual
      me.pagination.current_page = page;
      me.listarMarca(page, buscar, criterio);
      //Envia la petición para visualizar la data de esa página
    },
    cambiarPaginaLinea(page, buscar, criterio) {
      let me = this;
      //Actualiza la página actual
      me.pagination.current_page = page;
      me.listarLinea(page, buscar, criterio);
      //Envia la petición para visualizar la data de esa página
    },
    cambiarPaginaIndustria(page, buscar, criterio) {
      let me = this;
      //Actualiza la página actual
      me.pagination.current_page = page;
      me.listarIndustria(page, buscar, criterio);
      //Envia la petición para visualizar la data de esa página
    },
    cambiarPaginaProveedor(page, buscar, criterio) {
      let me = this;
      //Actualiza la página actual
      me.pagination.current_page = page;
      me.listarproveedor(page, buscar, criterio);
      //Envia la petición para visualizar la data de esa página
    },
    cambiarPaginaGrupo(page, buscar, criterio) {
      let me = this;
      //Actualiza la página actual
      me.pagination.current_page = page;
      me.listargrupo(page, buscar, criterio);
      //Envia la petición para visualizar la data de esa página
    },
    //mostrar foto de articulo
    obtenerFotografia(event) {
      let file = event.target.files[0];

      let fileType = file.type;
      // Validar si el archivo es una imagen en formato PNG o JPG
      if (fileType !== "image/png" && fileType !== "image/jpeg") {
        alert("Por favor, seleccione una imagen en formato PNG o JPG.");
        return;
      }

      this.fotografia = file;
      this.mostrarFoto(file);
    },
    mostrarFoto(file) {
      let reader = new FileReader();

      reader.onload = (file) => {
        this.fotoMuestra = file.target.result;
      };
      reader.readAsDataURL(file);
    },
    calcularPrecioValorMoneda(precio) {
      console.log(precio);
      return (precio * parseFloat(this.monedaPrincipal)).toFixed(2);
    },
    convertDolar(precio) {
      return precio / parseFloat(this.monedaPrincipal);
    },

    advertenciaInactiva(nombre) {
      swal({
        title: "Opción Inactiva",
        text: "No puede seleccionar esta opción porque está inactiva.",
        type: "warning",
        confirmButtonColor: "#3085d6",
        confirmButtonText: "Aceptar",
        confirmButtonClass: "btn btn-success",
        buttonsStyling: false,
      }).then(() => {
        if (nombre == "Medidas") {
          this.abrirModal6(nombre);
        } else {
          this.abrirModal2(nombre);
        }
      });
    },

    //#################hasta aqui####################
    validarArticulo() {
      this.errorArticulo = 0;
      this.errorMostrarMsjArticulo = [];

      // if (this.lineaseleccionada.length == 0) this.errorMostrarMsjArticulo.push("");
      // if (this.industriaseleccionada.length == 0) this.errorMostrarMsjArticulo.push("");
      // if (this.marcaseleccionada.length == 0) this.errorMostrarMsjArticulo.push("");
      // if (this.proveedorseleccionada.length == 0) this.errorMostrarMsjArticulo.push("");
      // if (this.medidaseleccionada.length == 0) this.errorMostrarMsjArticulo.push("");
      // if (this.gruposeleccionada.length == 0) this.errorMostrarMsjArticulo.push("");

      if (!this.unidad_envase) this.errorMostrarMsjArticulo.push("");
      if (!this.codigo) this.errorMostrarMsjArticulo.push("");
      if (!this.nombre_producto) this.errorMostrarMsjArticulo.push("");
      if (!this.nombre_generico) this.errorMostrarMsjArticulo.push("");
      if (!this.precio_costo_unid) this.errorMostrarMsjArticulo.push("");
      if (!this.precio_costo_paq) this.errorMostrarMsjArticulo.push("");
      if (!this.descripcion) this.errorMostrarMsjArticulo.push("");
      if (!this.stock) this.errorMostrarMsjArticulo.push("");
      if (!this.precio_venta) this.errorMostrarMsjArticulo.push("");
      if (!this.costo_compra) this.errorMostrarMsjArticulo.push("");
      if (!this.fotografia) this.errorMostrarMsjArticulo.push("");

      if (this.errorMostrarMsjArticulo.length) this.errorArticulo = 1;

      return this.errorArticulo;
    },
    cerrarModal() {
      this.modal = 0;
      this.tituloModal = "";
      //this.idcategoria = 0;
      //this.nombre_categoria = '';
      //validacion para quitar borde rojo en los inputs
      this.codigoVacio = false;
      this.nombreProductoVacio = false;
      this.unidad_envaseVacio = false;
      this.nombre_genericoVacio = false;
      this.precio_costo_unidVacio = false;
      this.precio_costo_paqVacio = false;
      this.precio_ventaVacio = false;
      this.costo_compraVacio = false;
      this.stockVacio = false;
      this.descripcionVacio = false;
      this.fotografiaVacio = false;
      this.lineaseleccionadaVacio = false;
      this.marcaseleccionadaVacio = false;
      this.industriaseleccionadaVacio = false;
      this.proveedorseleccionadaVacio = false;
      this.gruposeleccionadaVacio = false;
      this.medidaseleccionadaVacio = false;
      this.sucursalseleccionadaVacio = false;
      this.articuloseleccionadaVacio = false;

      //
      this.codigo = "";
      this.nombre_producto = "";
      this.nombre_generico = "";
      this.precio_venta = 0;
      this.precio_costo_unid = 0;
      this.precio_costo_paq = 0;
      this.stock = 5;
      this.descripcion = "";
      this.fotografia = ""; //Pasando el valor limpio de la referencia
      this.fotoMuestra = null;
      //this.lineaseleccionada.nombre = '';
      //this.marcaseleccionada.nombre = '';
      this.industriaseleccionada.nombre = "";
      this.proveedorseleccionada.nombre = "";
      this.gruposeleccionada.nombre_grupo = "";
      this.medidaseleccionada.descripcion_medida = "";
      //this.lineaseleccionada.nombre = '';
      //this.articuloseleccionada.nombre = '';
      //this.sucursalseleccionada.nombre = '';
      this.errorArticulo = 0;

      this.idmedida = 0;
      this.costo_compra = 0;

      this.precio_uno = 0;
      this.precio_dos = 0;
      this.precio_tres = 0;
      this.precio_cuatro = 0;

      // unidad_envaseVacio: false,
      // nombre_genericoVacio: false,
      // precio_costo_unidVacio: false,
      // precio_costo_paqVacio: false,
      // precio_ventaVacio: false,
      // costo_compraVacio: false,
      // stockVacio: false,
      // descripcionVacio: false,
      // fecha_vencimientoVacio: false,
      // fotografiaVacio: false,
      // lineaseleccionadaVacio: false,
      // marcaseleccionadaVacio: false,
      // industriaseleccionadaVacio: false,
      // proveedorseleccionadaVacio: false,
      // gruposeleccionadaVacio: false,
      // medidaseleccionadaVacio: false,
    },
    abrirModal(modelo, accion, data = []) {
      //this.sucursalseleccionada = false;
      //this.articuloseleccionada = false;
      //this.lineaseleccionada = false;
      ///this.marcaseleccionada = false;
      //this.fechaInicio = "";
      //this.fechaFin = "";
      switch (modelo) {
        case "articulo": {
          switch (accion) {
            case "registrar": {
              this.modal = 1;
              this.tituloModal = "Registrar Artículo";

              this.tipoAccion = 1;
              this.fotografia = "";

              this.datosFormulario = {
                nombre: "",
                descripcion: "",
                nombre_generico: "",
                unidad_envase: 0,
                precio_costo_unid: 0,
                precio_costo_paq: 0,
                precio_venta: 0,
                precio_uno: 0,
                precio_dos: 0,
                precio_tres: 0,
                precio_cuatro: 0,
                stock: 0,
                costo_compra: 0,
                codigo: "",
                codigo_alfanumerico: "",
                descripcion_fabrica: "",
                idcategoria: null,
                idmarca: null,
                idindustria: null,
                idgrupo: null,
                idproveedor: null,
                idmedida: null,
              };
              this.errores = {};
              break;
            }
            case "actualizar": {
              console.log("datass", data);
              this.modal = 1;
              this.tituloModal = "Actualizar Artículo";
              this.tipoAccion = 2;
              this.datosFormulario = {
                nombre: data["nombre"],
                descripcion: data["descripcion"],
                nombre_generico: data["nombre_generico"],
                unidad_envase: data["unidad_envase"],
                precio_costo_unid: this.calcularPrecioValorMoneda(
                  data["precio_costo_unid"]
                ),
                precio_costo_paq: this.calcularPrecioValorMoneda(
                  data["precio_costo_paq"]
                ),
                precio_venta: this.calcularPrecioValorMoneda(
                  data["precio_venta"]
                ),
                precio_uno: 0,
                precio_dos: 0,
                precio_tres: 0,
                precio_cuatro: 0,
                stock: data["stock"],
                costo_compra: this.calcularPrecioValorMoneda(
                  data["costo_compra"]
                ),
                codigo: data["codigo"],
                codigo_alfanumerico: data["codigo_alfanumerico"],
                descripcion_fabrica: data["descripcion_fabrica"],
                idcategoria: null,
                idmarca: null,
                idindustria: null,
                idgrupo: null,
                idproveedor: null,
                idmedida: data["idmedida"],
                id: data["id"],
              };
              this.errores = {};
              this.idmedida = data["idmedida"];

              this.fotografia = data["fotografia"];
              this.fotoMuestra = data["fotografia"]
                ? "img/articulo/" + data["fotografia"]
                : null;
              //this.industriaseleccionada = { nombre: data['industriaseleccionada.nombre'] };

              //this.industriaseleccionada = {nombre: data['nombre_industria']};
              this.industriaseleccionada = {
                nombre: data["nombre_industria"],
                id: data["idindustria"],
              };
              //this.lineaseleccionada = {nombre: data['nombre_categoria']};
              this.lineaseleccionada = {
                nombre: data["nombre_categoria"],
                id: data["idcategoria"],
              };
              //this.marcaseleccionada = {nombre: data['nombre_marca']};
              this.marcaseleccionada = {
                nombre: data["nombre_marca"],
                id: data["idmarca"],
              };
              this.proveedorseleccionada = {
                nombre: data["nombre_proveedor"],
                id: data["idproveedor"],
              };
              //this.gruposeleccionada = {nombre_grupo: data['nombre_grupo']};
              this.gruposeleccionada = {
                nombre_grupo: data["nombre_grupo"],
                id: data["idgrupo"],
              };
              this.medidaseleccionada = {
                descripcion_medida: data["descripcion_medida"],
                id: data["idmedida"],
              };

              this.precio_uno = this.calcularPrecioValorMoneda(
                data["precio_uno"]
              );
              this.precio_dos = this.calcularPrecioValorMoneda(
                data["precio_dos"]
              );
              this.precio_tres = this.calcularPrecioValorMoneda(
                data["precio_tres"]
              );
              this.precio_cuatro = this.calcularPrecioValorMoneda(
                data["precio_cuatro"]
              );
              // this.precios.forEach((precio) => {
              //     this.calcularPrecio(precio);
              // });
              break;
            }
            case "registrarInd": {
              this.modal = 1;
              this.tituloModal = "Registrar Industria";
              this.nombre = "";
              //this.descripcion = '';
              this.tipoAccion = 3;
              break;
            }
          }
        }
      }
    },

    calcularPrecio(precio, index) {
      if (
        isNaN(this.datosFormulario.precio_costo_unid) ||
        isNaN(parseFloat(precio.porcentage))
      ) {
        console.log("El valor de precio_costo_unid o porcentaje no es válido");
        return;
      }
      const margen_ganancia =
        parseFloat(this.datosFormulario.precio_costo_unid) *
        (parseFloat(precio.porcentage) / 100);
      const precio_publico =
        parseFloat(this.datosFormulario.precio_costo_unid) + margen_ganancia;

      if (index === 0) {
        this.precio_uno = precio_publico.toFixed(2);
      } else if (index === 1) {
        this.precio_dos = precio_publico.toFixed(2);
      } else if (index === 2) {
        this.precio_tres = precio_publico.toFixed(2);
      } else if (index === 3) {
        this.precio_cuatro = precio_publico.toFixed(2);
      }
      console.log("Precio público:", precio_publico);
      console.log("precio_dos:", this.precio_dos);
    },

    cerrarModal3() {
      this.modal3 = 0;
      this.tituloModal3 = "";
      this.nombre = "";
      this.limpiarErrores();
    },
    cerrarModal6() {
      this.modal6 = 0;
      this.tituloModal6 = "";
      this.descripcion_medida = "";
      this.descripcion_corta = "";
    },
    cerrarModal7() {
      this.modal7 = 0;
      this.tituloModal7 = "";
      this.descripcion_medida = "";
      this.descripcion_corta = "";
      this.limpiarErrores2();
    },
    limpiarErrores() {
      if (this.tituloModal2 === "Industrias") {
        this.errorMostrarMsjIndustria = [];
        this.errorIndustria = 0;
      } else if (this.tituloModal2 === "Marcas") {
        this.errorMostrarMsjIndustria = [];
        this.errorIndustria = 0;
      } else {
        this.errorMostrarMsjIndustria = [];
        this.errorIndustria = 0;
      }
    },
    limpiarErrores2() {
      this.tituloModal6 === "Medidas";
      this.errorMostrarMsjMedida = [];
      this.errorMedida = 0;
    },
    //################Validar registros de industrial########
    validarIndustria() {
      this.errorIndustria = 0;
      this.errorMostrarMsjIndustria = [];

      if (this.tituloModal2 === "Industrias") {
        if (!this.nombre)
          this.errorMostrarMsjIndustria.push(
            "El nombre de Industria no puede estar vacío."
          );
      } else if (this.tituloModal2 === "Marcas") {
        if (!this.nombre)
          this.errorMostrarMsjIndustria.push(
            "El nombre de Marca no puede estar vacío."
          );
      } else if (this.tituloModal2 === "Lineas") {
        if (!this.nombreLinea)
          this.errorMostrarMsjIndustria.push(
            "El nombre de Linea no puede estar vacío."
          );
        if (!this.descripcion)
          this.errorMostrarMsjIndustria.push(
            "La descripcion de Linea no puede estar vacío."
          );
        if (!this.codigoProductoSin)
          this.errorMostrarMsjIndustria.push(
            "El codigo de Linea no puede estar vacío."
          );
      }

      //if (!this.nombre) this.errorMostrarMsjIndustria.push("El nombre de Industria no puede estar vacío.");
      if (this.errorMostrarMsjIndustria.length) this.errorIndustria = 1;

      return this.errorIndustria;
    },
    //################Validar registros de medida########
    validarMedida() {
      this.errorMedida = 0;
      this.errorMostrarMsjMedida = [];

      if (!this.descripcion_medida)
        this.errorMostrarMsjMedida.push(
          "El nombre de la Medida no puede estar vacío."
        );
      if (this.errorMostrarMsjMedida.length) this.errorMedida = 1;

      return this.errorMedida;
    },

    //################placeholder mensaje si es indus►ria, marca o linea########
    placeholderInput(inputType) {
      if (this.tituloModal2 === "Marcas") {
        return "Nombre de Marca";
      } else if (this.tituloModal2 === "Industrias") {
        return "Nombre de Industria";
        // } else if (this.tituloModal2 === 'Proveedor') {
        //     return 'Nombre de Proveedor'
      } else if (this.tituloModal2 === "Grupos") {
        return "Nombre de Grupo";
      } else if (this.tituloModal2 === "Lineas") {
        if (inputType === "nombre") {
          return "Nombre de Linea";
        } else if (inputType === "descripcion") {
          return "Descripcion de Linea";
        } else if (inputType === "codigoProductoSin") {
          return "Codigo de Linea";
        }
      }
    },
    //############hasta aqui-#########
    //################-Abrl moda de industrial,marca,Linea########
    abrirModal3(modelo3, accion3, data = []) {
      switch (modelo3) {
        case "industria": {
          switch (accion3) {
            case "registrarInd": {
              this.modal3 = 1;
              this.tituloModal3 = "Registrar Industria";
              this.nombre = "";
              this.estado = "";
              this.tipoAccion2 = 3;
              //this.modal3=true;
              break;
            }
            case "actualizarInd": {
              this.modal3 = 1;
              this.tituloModal3 = "Actualizar Industria";
              this.tipoAccion2 = 4;
              this.industria_id = data["id"];
              this.nombre = data["nombre"];
              this.estado = data["estado"];
              break;
            }
          }
        }
        case "Marca": {
          switch (accion3) {
            case "registrarMar": {
              this.modal3 = 1;
              this.tituloModal3 = "Registrar Marca";
              this.nombre = "";
              this.condicion = "";
              this.tipoAccion2 = 5;
              break;
            }
            case "actualizar": {
              this.modal3 = 1;
              this.tituloModal3 = "Actualizar marca";
              this.tipoAccion2 = 6;
              this.marca_id = data["id"];
              this.nombre = data["nombre"];
              this.condicion = data["condicion"];
              break;
            }
          }
        }
        case "Linea": {
          switch (accion3) {
            case "registrarLin": {
              this.modal3 = 1;
              this.tituloModal3 = "Registrar Linea";
              this.nombreLinea = "";
              this.descripcion = "";
              this.codigoProductoSin = 0;
              this.condicion = "";
              this.tipoAccion2 = 7;
              break;
            }
            case "actualizarLin": {
              this.modal3 = 1;
              this.tituloModal3 = "Actualizar Linea";
              this.tipoAccion2 = 8;
              this.linea_id = data["id"];
              this.nombreLinea = data["nombre"];
              this.descripcion = data["descripcion"];
              this.codigoProductoSin = data["codigoProductoSin"];
              this.condicion = data["condicion"];
              break;
            }
          }
        }
        case "Grupo": {
          switch (accion3) {
            case "registrarGrupo": {
              this.modal3 = 1;
              this.tituloModal3 = "Registrar Grupo";
              this.tipoAccion2 = 11;
              this.nombre_grupo = "";

              break;
            }
            case "actualizarGrupo": {
              this.modal3 = 1;
              this.tituloModal3 = "Actualizar Grupo";
              this.tipoAccion2 = 12;
              this.grupo_id = data["id"];
              this.nombre_grupo = data["nombre_grupo"];
              break;
            }
          }
        }
        case "Proveedor": {
          switch (accion3) {
            case "registrarProv": {
              this.modal3 = 1;
              this.tituloModal3 = "Registrar Provedor";
              this.nombre = "";
              this.tipo_documento = "RUC";
              this.num_documento = "";
              this.direccion = "";
              this.telefono = "";
              this.email = "";
              this.contacto = "";
              this.telefono_contacto = "";
              this.tipoAccion2 = 9;
              break;
            }
            case "actualizarProv": {
              //console.log('Proveedor',data)
              this.modal3 = 1;
              this.tituloModal3 = "Actualizar Provedor";
              this.tipoAccion2 = 10;
              this.proveedor_id = data["id"];
              this.nombre = data["nombre"];
              this.tipo_documento = data["tipo_documento"];
              this.num_documento = data["num_documento"];
              this.direccion = data["direccion"];
              this.telefono = data["telefono"];
              this.email = data["email"];
              this.contacto = data["contacto"];
              this.telefono_contacto = data["telefono_contacto"];
              break;
            }
          }
        }
        case "Sucursal": {
          switch (accion3) {
            case "registrarLin": {
              this.modal3 = 1;
              this.tituloModal3 = "Registrar Linea";
              this.nombreLinea = "";
              this.descripcion = "";
              this.codigoProductoSin = 0;
              this.condicion = "";
              this.tipoAccion2 = 7;
              break;
            }
            case "actualizarLin": {
              this.modal3 = 1;
              this.tituloModal3 = "Actualizar Linea";
              this.tipoAccion2 = 8;
              this.linea_id = data["id"];
              this.nombreLinea = data["nombre"];
              this.descripcion = data["descripcion"];
              this.codigoProductoSin = data["codigoProductoSin"];
              this.condicion = data["condicion"];
              break;
            }
          }
        }
      }
    },
    //############3hasta aqui######################

    //############3hasta aqui######################
    abrirModal7(modelo6, accion6, data = []) {
      switch (modelo6) {
        case "medida": {
          switch (accion6) {
            case "registrarMed": {
              this.modal7 = 1;
              this.tituloModal7 = "Registrar Medida";
              this.descripcion_medida = "";
              this.descripcion_corta = "";
              this.estado = "1";
              this.tipoAccion6 = 6;
              break;
            }
            case "actualizarMed": {
              this.modal7 = 1;
              this.tituloModal7 = "Actualizar Medida";
              this.tipoAccion6 = 7;
              this.medida_id = data["id"];
              this.descripcion_medida = data["descripcion_medida"];
              this.descripcion_corta = data["descripcion_corta"];
              this.estado = data["estado"];
              break;
            }
          }
        }
      }
    },

    datosConfiguracion() {
      let me = this;
      var url = "/configuracion";

      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.mostrarSaldosStock =
            respuesta.configuracionTrabajo.mostrarSaldosStock;
          me.mostrarCostos = respuesta.configuracionTrabajo.mostrarCostos;
          me.mostrarProveedores =
            respuesta.configuracionTrabajo.mostrarProveedores;

          me.monedaPrincipal = [
            respuesta.configuracionTrabajo.valor_moneda_principal,
            respuesta.configuracionTrabajo.simbolo_moneda_principal,
          ];
          console.log("MostrarCostos: " + me.mostrarCostos);
          console.log("ProveedorEstado: " + me.mostrarProveedores);
          console.log("MostrarSaldosStock: " + me.mostrarSaldosStock);
        })
        .catch(function(error) {
          console.log(error);
        });
    },
    recuperarIdRol() {
      this.rolUsuario = window.userData.rol;
      console.log("ID_ROL: " + this.rolUsuario);
    },
  },
  mounted() {
    this.handleResize();
    window.addEventListener("resize", this.handleResize);
    this.recuperarIdRol();
    this.datosConfiguracion();
    this.obtenerConfiguracionTrabajo();
    this.listarArticulo(1, this.buscar, this.criterio);
    this.listarPrecio(); //aumenTe 6julio
  },
};
</script>
<style>
.card-error {
  margin-bottom: 10px;
  width: 100%;
  padding: 15px;
  border-radius: 15px;
  border: 2px solid #ab7078;
  background-color: #fec0ca;
}

.csv-headers-container {
  margin-top: 10px;
}

.csv-headers-list {
  list-style-type: none;
  padding: 0;
  display: flex;
  flex-wrap: wrap;
}

.csv-header {
  background-color: #3498db;
  color: white;
  padding: 5px 10px;
  margin: 5px;
  border-radius: 5px;
  cursor: pointer;
}

.csv-header label {
  display: flex;
  align-items: center;
}

.csv-header input {
  margin-right: 5px;
}

.selected-headers-container {
  margin-top: 10px;
}

.selected-headers-list {
  list-style-type: none;
  padding: 10px;
  display: flex;
  flex-wrap: wrap;
}

/* .selected-header {
        box-shadow: 10px 5px 5px black;
        color: black;

        -webkit-box-shadow: 3px -7px 38px -1px rgba(27, 209, 11, 0.69);
        -moz-box-shadow: 3px -7px 38px -1px rgba(27, 209, 11, 0.69);
        box-shadow: 3px -7px 38px -1px rgba(27, 209, 11, 0.69);
    } */

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

.table-responsive {
  overflow-x: auto;
}

.sticky-column {
  position: sticky;
  left: 0;
  z-index: 1;
  background-color: white;
}

.border-red {
  border-color: red !important;
}

.loader {
  display: block;
  position: relative;
  height: 12px;
  width: 100%;
  border: 1px solid #fff;
  border-radius: 10px;
  overflow: hidden;
}

.loader::after {
  content: "";
  width: 40%;
  height: 100%;
  background: #ff3d00;
  position: absolute;
  top: 0;
  left: 0;
  box-sizing: border-box;
  animation: animloader 2s linear infinite;
}

@keyframes animloader {
  0% {
    left: 0;
    transform: translateX(-100%);
  }

  100% {
    left: 100%;
    transform: translateX(0%);
  }
}

/**
 * Extracted from: SweetAlert
 * Modified by: Istiak Tridip
 */
.success-checkmark {
  width: 80px;
  height: 115px;
  margin: 0 auto;

  .check-icon {
    width: 80px;
    height: 80px;
    position: relative;
    border-radius: 50%;
    box-sizing: content-box;
    border: 4px solid #4caf50;

    &::before {
      top: 3px;
      left: -2px;
      width: 30px;
      transform-origin: 100% 50%;
      border-radius: 100px 0 0 100px;
    }

    &::after {
      top: 0;
      left: 30px;
      width: 60px;
      transform-origin: 0 50%;
      border-radius: 0 100px 100px 0;
      animation: rotate-circle 4.25s ease-in;
    }

    &::before,
    &::after {
      content: "";
      height: 100px;
      position: absolute;
      background: #ffffff;
      transform: rotate(-45deg);
    }

    .icon-line {
      height: 5px;
      background-color: #4caf50;
      display: block;
      border-radius: 2px;
      position: absolute;
      z-index: 10;

      &.line-tip {
        top: 46px;
        left: 14px;
        width: 25px;
        transform: rotate(45deg);
        animation: icon-line-tip 0.75s;
      }

      &.line-long {
        top: 38px;
        right: 8px;
        width: 47px;
        transform: rotate(-45deg);
        animation: icon-line-long 0.75s;
      }
    }

    .icon-circle {
      top: -4px;
      left: -4px;
      z-index: 10;
      width: 80px;
      height: 80px;
      border-radius: 50%;
      position: absolute;
      box-sizing: content-box;
      border: 4px solid rgba(76, 175, 80, 0.5);
    }

    .icon-fix {
      top: 8px;
      width: 5px;
      left: 26px;
      z-index: 1;
      height: 85px;
      position: absolute;
      transform: rotate(-45deg);
      background-color: #ffffff;
    }
  }
}

@keyframes rotate-circle {
  0% {
    transform: rotate(-45deg);
  }

  5% {
    transform: rotate(-45deg);
  }

  12% {
    transform: rotate(-405deg);
  }

  100% {
    transform: rotate(-405deg);
  }
}

@keyframes icon-line-tip {
  0% {
    width: 0;
    left: 1px;
    top: 19px;
  }

  54% {
    width: 0;
    left: 1px;
    top: 19px;
  }

  70% {
    width: 50px;
    left: -8px;
    top: 37px;
  }

  84% {
    width: 17px;
    left: 21px;
    top: 48px;
  }

  100% {
    width: 25px;
    left: 14px;
    top: 45px;
  }
}

@keyframes icon-line-long {
  0% {
    width: 0;
    right: 46px;
    top: 54px;
  }

  65% {
    width: 0;
    right: 46px;
    top: 54px;
  }

  84% {
    width: 55px;
    right: 0px;
    top: 35px;
  }

  100% {
    width: 47px;
    right: 8px;
    top: 38px;
  }
}
</style>

<style scoped>
.custom-loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}
.custom-loading-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.spinner-border-lg {
  width: 3rem;
  height: 3rem;
  border-width: 0.4em;
}
</style>

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

