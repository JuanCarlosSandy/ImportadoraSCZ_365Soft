<template>
  <main class="main">

    <div class="loading-overlay" v-if="isLoading">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">LOADING...</div>
      </div>
    </div>

    <Panel>
      <!-- Encabezado del Panel -->
      <template #header>
        <div
          style="display: flex; align-items: center; justify-content: space-between; width: 100%;"
        >
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="pi pi-bars panel-icon" style="color: blue;"></i>
            <h4 class="panel-title" style="margin: 0;">
              REPORTE DE VENTAS DIARIAS Y MENSUALES
            </h4>
          </div>
        </div>
      </template>
      <!-- Sección de Filtros Aplicados, Total de Ventas y Botones -->
      <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;"
      >
        <div
          style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;"
        >
          <!-- Tarjeta de Sucursal -->
          <div
            style="display: flex; align-items: center; gap: 0.5rem; background-color: white; padding: 0.5rem; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
          >
            <i class="pi pi-home" style="font-size: 1.2rem; color: blue;"></i>
            <div>
              <div style="font-weight: bold; font-size: 0.9rem;">Sucursal</div>
              <div>{{ sucursalseleccionada.nombre }}</div>
            </div>
          </div>
          <!-- Tarjeta de Tipo de Reporte -->
          <div
            style="display: flex; align-items: center; gap: 0.5rem; background-color: white; padding: 0.5rem; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
          >
            <i
              :class="
                tipoReporte === 'dia'
                  ? 'pi pi-calendar'
                  : 'pi pi-calendar-times'
              "
              style="font-size: 1.2rem; color: blue;"
            ></i>
            <div>
              <div style="font-weight: bold; font-size: 0.9rem;">Tipo</div>
              <div>{{ tipoReporte === "dia" ? "Por Día" : "Por Mes" }}</div>
            </div>
          </div>
          <!-- Tarjeta de Fecha o Mes/Año -->
          <div
            style="display: flex; align-items: center; gap: 0.5rem; background-color: white; padding: 0.5rem; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
          >
            <i
              class="pi pi-calendar"
              style="font-size: 1.2rem; color: blue;"
            ></i>
            <div>
              <div style="font-weight: bold; font-size: 0.9rem;">
                {{ tipoReporte === "dia" ? "Fecha" : "Mes/Año" }}
              </div>
              <div>
                {{
                  tipoReporte === "dia" ? fechaSeleccionada : mesSeleccionado
                }}
              </div>
            </div>
          </div>
        </div>
        <div style="font-weight: bold; font-size: 1.1rem;">
          Total Ventas: {{ totalVentas }} {{ monedaPrincipal[1] }}
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <Button
            icon="pi pi-filter"
            :label="mostrarLabel ? 'Filtros' : ''"
            class="p-button-secondary p-button-sm"
            @click="modal = true"
          />
          <Button
            icon="pi pi-file-excel"
            label="EXCEL"
            class="p-button-success"
            @click="exportarExcelDialog"
          />
          <Button
            icon="pi pi-file-pdf"
            label="PDF"
            class="p-button-danger"
            @click="descargarPDFDialog"
          />
        </div>
      </div>
      <!-- Tabla de Ventas -->
      <template v-if="listado == 1">
        <div style="overflow-y: auto;">
          <div class="mb-2 d-flex" style="gap: 0.5rem;">
            <input
              type="text"
              v-model="busquedaVentas"
              class="form-control"
              placeholder="Buscar en reporte de ventas..."
              style="flex:1; min-width:0;"
            />
            <Button
              icon="pi pi-times"
              class="p-button-secondary p-button-sm"
              @click="busquedaVentas = ''"
              title="Reset"
            />
          </div>
          <DataTable
            :value="arrayReporte"
            :paginator="true"
            :rows="10"
            dataKey="id"
            responsiveLayout="scroll"
            class="p-datatable-gridlines p-datatable-sm"
          >
            <Column header="Acciones" style="width: 120px; text-align:center">
              <template #body="slotProps">
                <Button
                  icon="pi pi-eye"
                  class="p-button-sm p-button-primary btn-mini"
                  title="Ver Venta"
                  @click="verVentaDesdeReporte(slotProps.data.id)"
                />
              </template>
            </Column>

            <Column header="N° de Comprobante">
              <template #body="slotProps">
                {{ slotProps.data.Factura }}
              </template>
            </Column>

            <Column header="Fecha y Hora">
              <template #body="slotProps">
                {{ formatearFechaHora(slotProps.data.fecha_hora) }}
              </template>
            </Column>

            <Column header="Vendedor">
              <template #body="slotProps">
                {{ slotProps.data.usuario }}
              </template>
            </Column>

            <Column header="Cliente">
              <template #body="slotProps">
                {{ slotProps.data.nombre }}
              </template>
            </Column>

            <Column header="Total de Venta">
              <template #body="slotProps">
                {{ Number(slotProps.data.importe_BS).toFixed(2) }} Bs
              </template>
            </Column>

            <Column header="Tipo de Venta">
              <template #body="slotProps">
                <span
                  v-if="slotProps.data.idtipo_venta == 1"
                  class="badge badge-primary"
                >
                  Contado
                </span>
                <span
                  v-else-if="slotProps.data.idtipo_venta == 2"
                  class="badge badge-warning"
                >
                  Crédito
                </span>
                <span v-else class="badge badge-secondary">
                  -
                </span>
              </template>
            </Column>

            <Column header="Estado">
              <template #body="slotProps">
                <!-- Venta a Crédito -->
                <template v-if="slotProps.data.idtipo_venta == 2">
                  <span
                    v-if="slotProps.data.estado == 0"
                    class="badge badge-danger"
                  >
                    Anulado
                  </span>
                  <span
                    v-else-if="Number(slotProps.data.saldo_restante) > 0"
                    class="badge badge-warning"
                  >
                    Saldo Restante:
                    {{ Number(slotProps.data.saldo_restante).toFixed(2) }} Bs
                  </span>
                  <span v-else class="badge badge-success">
                    Pagado
                  </span>
                </template>
                <!-- Venta al Contado -->
                <template v-else>
                  <span
                    v-if="slotProps.data.estado == 1"
                    class="badge badge-success"
                  >
                    Registrado
                  </span>
                  <span
                    v-else-if="slotProps.data.estado == 0"
                    class="badge badge-danger"
                  >
                    Anulado
                  </span>
                  <span v-else class="badge badge-secondary">
                    Desconocido
                  </span>
                </template>
              </template>
            </Column>
          </DataTable>
        </div>
      </template>
      <template v-else-if="listado == 2">
        <div class="detalle-venta-pro">
          <!-- ENCABEZADO -->
          <div class="detalle-header-pro">
            <div class="detalle-section-pro">
              <h3 class="detalle-titulo-pro">Detalle de Comprobante</h3>
              <p class="detalle-subtitulo-pro">
                Resumen completo de la venta registrada
              </p>
            </div>
            <div class="detalle-meta-pro">
              <div>
                <span class="label-pro">Tipo Comprobante</span>
                <p class="valor-pro">{{ tipo_comprobante }}</p>
              </div>
              <div>
                <span class="label-pro">N° Comprobante</span>
                <p class="valor-pro">#{{ num_comprobante }}</p>
              </div>
            </div>
          </div>

          <!-- CLIENTE -->
          <div class="detalle-cliente-pro">
            <span class="label-pro">Cliente</span>
            <p class="valor-pro">{{ cliente }}</p>
          </div>

          <!-- TABLA DE ARTÍCULOS -->
          <div class="detalle-tabla-pro">
            <DataTable
              :value="arrayDetalle"
              class="p-datatable-sm p-datatable-gridlines"
            >
              <Column field="cantidad" header="Cant Vendida">
                <template #body="slotProps">
                  <span
                    :style="{
                      backgroundColor: obtenerColorModo(
                        slotProps.data.modo_venta
                      ),
                      color:
                        slotProps.data.modo_venta === 'docena'
                          ? 'black'
                          : 'white', // Texto oscuro para fondo amarillo
                      padding: '4px 8px',
                      borderRadius: '4px',
                      fontWeight: 'bold',
                      display: 'inline-block',
                    }"
                  >
                    {{ slotProps.data.cantidad }}
                    {{
                      formatearUnidad(
                        slotProps.data.modo_venta,
                        slotProps.data.cantidad
                      )
                    }}
                  </span>
                </template>
              </Column>
              <Column field="codigo" header="Codigo"></Column>
              <Column field="articulo" header="Producto"></Column>
              <Column field="unidad_envase" header="Cant x Caja">
                <template #body="slotProps">
                  <span v-if="slotProps.data.modo_venta === 'caja'">
                    {{ slotProps.data.unidad_envase }}
                  </span>

                  <span v-else>-</span>
                </template>
              </Column>

              <Column header="Precio Unit.">
                <template #body="slotProps">
                  {{
                    (
                      slotProps.data.precio * parseFloat(monedaVenta[0])
                    ).toFixed(2)
                  }}
                  {{ monedaVenta[1] }}
                </template>
              </Column>

              <!--<Column header="Subtotal sin Descuento">
                <template #body="slotProps">
                  {{ (slotProps.data.subtotal_sin_descuento * parseFloat(monedaVenta[0])).toFixed(2) }}
                  {{ monedaVenta[1] }}
                </template>
              </Column>

              <Column header="Descuento">
                <template #body="slotProps">
                  {{ slotProps.data.descuento }}% =
                  {{ (slotProps.data.descuento_monto * parseFloat(monedaVenta[0])).toFixed(2) }}
                  {{ monedaVenta[1] }}
                </template>
              </Column>-->

              <Column field="subtotal" header="Subtotal">
                <template #body="slotProps">
                  {{
                    (
                      slotProps.data.subtotal * parseFloat(monedaVenta[0])
                    ).toFixed(2)
                  }}
                  {{ monedaVenta[1] }}
                </template>
              </Column>
            </DataTable>
          </div>
          <div v-if="idtipo_venta == 2" class="mt-4">
            <h4 class="mb-3">Cuotas del Crédito</h4>
            <DataTable
              :value="cuotas"
              class="p-datatable-sm p-datatable-gridlines"
            >
              <Column field="numero_cuota" header="# Cuota"></Column>

              <Column field="fecha_pago" header="Fecha Pago">
                <template #body="slotProps">
                  <!-- MODO EDICIÓN -->
                  <div v-if="editarCuotas && slotProps.data.idtipo_pago !== 5">
                    <input
                      type="date"
                      class="form-control form-control-sm"
                      style="max-width: 160px"
                      v-model="slotProps.data.fecha_pago"
                    />
                  </div>

                  <!-- MODO LECTURA -->
                  <div v-else>
                    {{
                      slotProps.data.fecha_pago
                        ? slotProps.data.fecha_pago.substring(0, 10)
                        : "-"
                    }}
                  </div>
                </template>
              </Column>
              <Column field="precio_cuota" header="Monto Cuota">
                <template #body="slotProps">
                  <!-- MODO EDICIÓN -->
                  <div v-if="editarCuotas && slotProps.data.idtipo_pago !== 5">
                    <InputNumber
                      v-model="slotProps.data.precio_cuota"
                      mode="decimal"
                      :min="0"
                      :minFractionDigits="2"
                      :maxFractionDigits="2"
                      class="p-inputtext-sm w-8rem"
                      @input="recalcularSaldo(slotProps.data)"
                    />
                    <span class="ms-1">{{ monedaVenta[1] }}</span>
                  </div>

                  <!-- MODO LECTURA -->
                  <div v-else>
                    {{ slotProps.data.precio_cuota }} {{ monedaVenta[1] }}
                  </div>
                </template>
              </Column>

              <Column field="saldo_restante" header="Saldo Restante">
                <template #body="slotProps">
                  {{ slotProps.data.saldo_restante }} {{ monedaVenta[1] }}
                </template>
              </Column>

              <Column field="estado" header="Estado">
                <template #body="slotProps">
                  <span
                    v-if="
                      slotProps.data.idtipo_pago === 5 &&
                        slotProps.data.descuento > 0
                    "
                    class="badge bg-info"
                  >
                    Liquidado {{ slotProps.data.descuento }}
                    {{ monedaVenta[1] }}
                  </span>

                  <span
                    v-else-if="slotProps.data.estado === 'Pagado'"
                    class="badge bg-success"
                  >
                    Pagado
                  </span>

                  <span v-else class="badge bg-warning">
                    {{ slotProps.data.estado }}
                  </span>
                </template>
              </Column>
              <Column header="Tipo de Pago">
                <template #body="slotProps">
                  <!-- MODO EDICIÓN -->
                  <div v-if="editarCuotas" class="d-flex flex-column gap-1">
                    <!-- SELECT TIPO DE PAGO -->
                    <Dropdown
                      v-if="slotProps.data.idtipo_pago !== 5"
                      v-model="slotProps.data.idtipo_pago"
                      :options="tiposPagoOptions"
                      optionLabel="label"
                      optionValue="value"
                      placeholder="Seleccione"
                      class="p-inputtext-sm"
                    />

                    <!-- TEXTO FIJO CUANDO ES LIQUIDACIÓN -->
                    <span v-else class="badge bg-info p-2 text-center">
                      🔒 Liquidación
                    </span>
                    <Dropdown
                      v-if="slotProps.data.idtipo_pago === 7"
                      v-model="slotProps.data.bancoSeleccionado"
                      :options="bancosOptions"
                      dataKey="id"
                      placeholder="Seleccione un banco"
                      class="w-full custom-dropdown mt-1"
                      @change="onBancoSelectCuota(slotProps.data)"
                    >
                      <!-- OPTION -->
                      <template #option="slotPropsOpt">
                        <div class="banco-opcion">
                          <img
                            :src="getBankUrl(slotPropsOpt.option.nombre_banco)"
                            class="banco-logo"
                          />
                          <div class="banco-detalles">
                            <div class="cuenta">
                              {{ slotPropsOpt.option.nombre_cuenta }}
                            </div>
                            <div class="numero">
                              {{ slotPropsOpt.option.numero_cuenta }}
                            </div>
                            <div class="tipo">
                              {{ slotPropsOpt.option.tipo_cuenta }}
                            </div>
                          </div>
                        </div>
                      </template>

                      <!-- VALUE -->
                      <template #value="slotPropsVal">
                        <div v-if="slotPropsVal.value" class="banco-value">
                          <img
                            :src="getBankUrl(slotPropsVal.value.nombre_banco)"
                            class="banco-logo"
                          />
                          <span class="cuenta">{{
                            slotPropsVal.value.nombre_cuenta
                          }}</span>
                        </div>
                        <span v-else>-</span>
                      </template>
                    </Dropdown>
                  </div>

                  <!-- MODO LECTURA -->
                  <div v-else>
                    <span
                      v-if="slotProps.data.idtipo_pago === 1"
                      class="badge bg-success"
                    >
                      💵 Efectivo
                    </span>

                    <span
                      v-else-if="slotProps.data.idtipo_pago === 5"
                      class="badge bg-info"
                    >
                      🔒 Liquidación
                    </span>

                    <span
                      v-else-if="slotProps.data.idtipo_pago === 7"
                      class="badge bg-primary"
                    >
                      🏦 {{ slotProps.data.nombre_cuenta || "Cuenta bancaria" }}
                    </span>

                    <span v-else class="badge bg-dark">
                      No definido
                    </span>
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>

          <!-- RESUMEN DE TOTALES -->
          <div class="detalle-resumen-pro">
            <div class="resumen-linea-pro">
              <span>SubTotal General</span>
              <strong
                >{{ (subtotalVista * parseFloat(monedaVenta[0])).toFixed(2) }}
                {{ monedaVenta[1] }}</strong
              >
            </div>
            <div class="resumen-linea-pro">
              <span>Descuento Adicional</span>
              <strong
                >{{
                  (
                    descuentoAdicionalvista * parseFloat(monedaVenta[0])
                  ).toFixed(2)
                }}
                {{ monedaVenta[1] }}</strong
              >
            </div>
            <div class="resumen-linea-pro total-final-pro">
              <span>Total Neto</span>
              <strong
                >{{ (total * parseFloat(monedaVenta[0])).toFixed(2) }}
                {{ monedaVenta[1] }}</strong
              >
            </div>
          </div>

          <!-- BOTÓN -->
          <div class="detalle-footer-pro">
            <Button
              @click="ocultarDetalle()"
              label="Cerrar"
              icon="pi pi-times"
              severity="danger"
              class="p-button-danger p-button-sm btn-mini"
            />
          </div>
        </div>
      </template>
    </Panel>

    <!-- MODAL DE FILTROS -->
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
            <h4 class="modal-title">FILTRO DE REPORTES</h4>
            <button
              type="button"
              class="close"
              @click="modal = false"
              aria-label="Close"
            >
              <span aria-hidden="true">×</span>
            </button>
          </div>
          <form @submit.prevent="procesarReporte">
            <div class="modal-body">
              <div class="form-group row">
                <div class="col-md-6">
                  <label class="font-weight-bold"
                    >Sucursal <span class="text-danger">*</span></label
                  >
                  <div class="input-group">
                    <input
                      class="form-control"
                      type="text"
                      placeholder="Sucursal del usuario"
                      disabled
                      v-model="sucursalseleccionada.nombre"
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="font-weight-bold"
                    >Estado Venta <span class="text-danger">*</span></label
                  >
                  <div class="input-group">
                    <select
                      class="form-control col-md-12"
                      v-model="criterioEstado"
                    >
                      <option value="Todos">Todos</option>
                      <option value="Registrado">Registrado</option>
                      <option value="Anulado">Anulado</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="form-group row">
                <div class="col-md-6">
                  <label class="font-weight-bold"
                    >Tipo de Reporte <span class="text-danger">*</span></label
                  >
                  <div class="input-group">
                    <select
                      class="form-control col-md-12"
                      v-model="tipoReporte"
                    >
                      <option value="dia">Por Día</option>
                      <option value="mes">Por Mes</option>
                    </select>
                  </div>
                </div>

                <div class="col-md-6" v-if="tipoReporte === 'dia'">
                  <label class="font-weight-bold"
                    >Fecha: <span class="text-danger">*</span></label
                  >
                  <input
                    class="form-control"
                    type="date"
                    v-model="fechaSeleccionada"
                  />
                </div>

                <div class="col-md-6" v-if="tipoReporte === 'mes'">
                  <label class="font-weight-bold"
                    >Mes y Año: <span class="text-danger">*</span></label
                  >
                  <input
                    class="form-control"
                    type="month"
                    v-model="mesSeleccionado"
                  />
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-danger"
                @click="modal = false"
              >
                Cerrar
              </button>
              <button
                type="button"
                class="btn btn-primary"
                @click="procesarReporte"
              >
                Visualizar Reporte
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

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
                  </select>
                  <input
                    v-if="tituloModal2 == 'Ejecutivo'"
                    type="text"
                    v-model="buscarA"
                    @keyup="listarEjecutivo(1, buscarA, criterioA)"
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
                  <input
                    v-if="tituloModal2 == 'Sucursal'"
                    type="text"
                    v-model="buscarA"
                    @keyup="listarSucursal(1, buscarA, criterioA)"
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
                    <th>Nombre</th>
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
                        @click="seleccionar(arrayelemento)"
                        class="btn btn-success btn-sm"
                      >
                        <i class="icon-check"></i>
                      </button>
                    </td>
                    <td v-text="arrayelemento.nombre"></td>

                    <td
                      v-if="tituloModal2 == 'Cliente'"
                      v-text="arrayelemento.tipo_documento"
                    ></td>
                    <td
                      v-if="tituloModal2 == 'Cliente'"
                      v-text="arrayelemento.num_documento"
                    ></td>
                    <td
                      v-if="tituloModal2 == 'Cliente'"
                      v-text="arrayelemento.telefono"
                    ></td>

                    <td v-if="tituloModal2 == 'Sucursal'">
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
                    @click.prevent="cambiarPaginaLinea(page, buscar, criterio)"
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
                        buscar,
                        criterio
                      )
                    "
                    >Sig</a
                  >
                </li>
              </ul>
            </nav>
            <nav v-else-if="tituloModal2 == 'Cliente'">
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
      </div>
    </div>
  </main>
</template>

<script>
import Panel from "primevue/panel";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import Swal from "sweetalert2";
import { esquemaArticulos } from "../constants/validations";
import "jspdf-autotable";
import VueBarcode from "vue-barcode";
import axios from "axios";

export default {
  components: {
    barcode: VueBarcode,
    Panel,
    DataTable,
    Column,
    Button,
    InputText,
    TabView,
    TabPanel,
  },
  data() {
    return {
       isLoading: false,
      // CONTROL DE VISTAS
      listado: 1, // 1 = listado, 2 = detalle
      modoVista: "lectura", // lectura | edicion

      // CABECERA DE VENTA
      cliente: "",
      tipo_comprobante: "",
      serie_comprobante: "",
      num_comprobante: "",
      impuesto: 0,
      total: 0,
      descuentoAdicionalvista: 0,

      // TIPO DE VENTA
      idtipo_venta: null,

      // DETALLES
      arrayDetalle: [],
      subtotalVista: 0,
      descuentoTotalDetalle: 0,

      // CUOTAS (CRÉDITO)
      cuotas: [],
      editarCuotas: false,

      // MONEDA (IMPORTANTE)
      monedaVenta: [1, "Bs"], // 👈 evita el error del [0]

      // REPORTE
      arrayReporte: [],
      totalVentas: 0,

      // MODALES
      modal: false,
      modal2: false,
      modal6: false,
      listado: 1,
      modoVista: "lectura",
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
      listado: 1,
      cliente: "",
      totalImpuesto: 0.0,

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
      criterioEstado: "Todos",
      buscarA: "",
      tituloModal2: "",
      clienteseleccionada: [],
      ejecutivoseleccionado: [],
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
      clienteseleccionadaVacio: false,
      ejecutivoseleccionadoVacio: false,
      industriaseleccionadaVacio: false,
      proveedorseleccionadaVacio: false,
      gruposeleccionadaVacio: false,
      medidaseleccionadaVacio: false,
      sucursalseleccionadaVacio: false,
      articuloseleccionadaVacio: false,

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
      modal: false,

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
      sucursalseleccionada: { id: 1, nombre: "" },

      //articulo
      articuloseleccionada: [],
      arrayReporte: [],
      total_saldofisico: 0,

      //fechas
      fechaInicio: "",
      fechaFin: "",

      arrayDetalle: [],
      arrayReporteDetallado: [],
      articulosVendidos: [], // <-- aquí se guardarán los datos del backend
      busquedaVentas: "",

      //añadidos para reporte de ventas diarias y mensuales

      tipoReporte: "dia",
      fechaSeleccionada: "",
      mesSeleccionado: "",
      totalVentas: 0,
      monedaPrincipal: ["1", "BOB"],
    };
  },

  computed: {
    sortedResultados: function() {
      return Object.values(this.arrayReporte).sort((a, b) => {
        return new Date(a.fecha_hora) - new Date(b.fecha_hora);
      });
    },
    filteredVentas() {
      if (!this.busquedaVentas) return this.arrayReporte;
      const texto = this.busquedaVentas.toLowerCase();
      return this.arrayReporte.filter((item) => {
        return Object.values(item).some((val) =>
          String(val)
            .toLowerCase()
            .includes(texto)
        );
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
    calcularTotal: function() {
      var resultado = 0.0;
      for (var i = 0; i < this.arrayDetalle.length; i++) {
        resultado +=
          this.arrayDetalle[i].precioseleccionado *
            this.arrayDetalle[i].cantidad -
          (this.arrayDetalle[i].precioseleccionado *
            this.arrayDetalle[i].cantidad *
            this.arrayDetalle[i].descuento) /
            100;
      }
      resultado -= this.descuentoAdicional;
      resultado -= this.descuentoGiftCard;
      return resultado;
    },

    calcularSubTotal: function() {
      var resultado = 0.0;
      for (var i = 0; i < this.arrayDetalle.length; i++) {
        resultado =
          resultado +
          (this.arrayDetalle[i].precioseleccionado *
            this.arrayDetalle[i].cantidad -
            (this.arrayDetalle[i].precioseleccionado *
              this.arrayDetalle[i].cantidad *
              this.arrayDetalle[i].descuento) /
              100);
      }
      return resultado;
    },
  },
  watch: {
    selectedDelimiter: "updateData",
    previewCsv: "parseCsv", // Llama a parseCsv cuando previewCsv cambie
  },
  methods: {
    // Función para determinar el color según el modo de venta
    obtenerColorModo(modo) {
      if (!modo) return "#6c757d"; // Gris si no hay modo
      switch (modo.toLowerCase()) {
        case "caja":
          return "#0d6efd"; // Azul
        case "docena":
          return "#ffc107"; // Amarillo/Dorado
        case "unidad":
          return "#198754"; // Verde
        default:
          return "#6c757d"; // Gris
      }
    },

    // FUNCIÓN PARA EL TEXTO (Corregida)
    formatearUnidad(modo, cantidad) {
      if (!modo) return "Unidad";

      const unidades = {
        caja: cantidad === 1 ? "Caja" : "Cajas",
        docena: cantidad === 1 ? "Docena" : "Docenas",
        unidad: cantidad === 1 ? "Unidad" : "Unidades",
      };

      return unidades[modo.toLowerCase()] || "Unidad";
    },
    verVenta(id) {
      this.idVentaSeleccionada = id;
      this.listado = 2;

      // 👇 NO tocar si ya viene desde edición normal
      if (!this.modoVista) {
        this.modoVista = "lectura";
      }

      let me = this;

      // CABECERA
      axios.get("/venta/obtenerCabecera?id=" + id).then(function(response) {
        const venta = response.data.venta[0];
        console.log("VENTA:", venta);
        me.cliente = venta.nombre;
        me.tipo_comprobante = venta.tipo_comprobante;
        me.serie_comprobante = venta.serie_comprobante;
        me.num_comprobante = venta.num_comprobante;
        me.impuesto = venta.impuesto;
        me.total = venta.total;
        me.descuentoAdicionalvista = parseFloat(venta.descuento_total) || 0;
        me.idtipo_venta = venta.idtipo_venta;

        // 👉 SOLO SI ES CRÉDITO
        if (venta.idtipo_venta == 2) {
          console.log("Cargando cuotas para venta crédito...");
          me.cargarCuotas(id);
        } else {
          me.cuotas = [];
        }
      });

      // DETALLES
      axios.get("/venta/obtenerDetalles?id=" + id).then(function(response) {
        me.arrayDetalle = response.data.detalles;

        me.descuentoTotalDetalle = me.arrayDetalle.reduce((acc, item) => {
          return acc + (parseFloat(item.descuento_monto) || 0);
        }, 0);

        me.descuentoAdicionalvista =
          (me.descuentoAdicionalvista || 0) - me.descuentoTotalDetalle;

        me.subtotalVista = me.arrayDetalle.reduce((acc, item) => {
          return acc + (parseFloat(item.subtotal) || 0);
        }, 0);
      });
    },
    cargarCuotas(id) {
      let me = this;

      axios
        .get("/venta/obtenerCuotas?id=" + id)
        .then(function(response) {
          me.cuotas = response.data.cuotas;
          console.log("Cuotas cargadas:", me.cuotas);
        })
        .catch(function(error) {
          console.log(error);
        });
    },

    verVentaDesdeReporte(id) {
      this.modoVista = "lectura"; // 👈 CLAVE
      this.verVenta(id);
    },
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
      this.datosFormulario.idCliente = this.clienteseleccionada.id;
      this.datosFormulario.idproveedor = this.proveedorseleccionada.id;
      this.datosFormulario.idindustria = this.industriaseleccionada.id;
      this.datosFormulario.idmedida = this.medidaseleccionada.id;
      this.datosFormulario.idgrupo = this.gruposeleccionada.id;
      this.datosFormulario.idSucursal = this.sucursalseleccionada.id;
      this.datosFormulario.idArticulo = this.articuloseleccionada.id;

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
        this.clienteseleccionada = false;
        if (selected.condicion == 1) {
          this.clienteseleccionada = selected;
          this.validarCampo("idmarca");
        } else if (selected.condicion == 0) {
          this.advertenciaInactiva("Marcas");
        }
      } else if (this.tituloModal2 == "Cliente") {
        this.clienteseleccionadaVacio = false;
        this.clienteseleccionada = selected;
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
      } else if (this.tituloModal2 == "Ejecutivo") {
        if (selected.condicion == 1) {
          this.ejecutivoseleccionado = selected;
        }
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

    abrirModal() {
      this.modal = true;
      document.body.classList.add("modal-open");
    },

    cerrarModal() {
      this.modal = false;
      document.body.classList.remove("modal-open");
    },

    abrirModal2(titulo) {
      if (titulo == "Estado Venta") {
        this.listarMarca(1, "", "nombre");
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.clienteseleccionada = false;
      } else if (titulo == "Cliente") {
        this.listarPersona(1, "", "nombre");
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.clienteseleccionada = false;
      } else if (titulo == "Ejecutivo") {
        this.listarEjecutivo(1, "", "nombre");
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.ejecutivoseleccionado = false;
      } else if (titulo == "Sucursal") {
        this.listarSucursal(1, "", "nombre");
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.gruposeleccionadaVacio = false;
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
          // Seleccionar automáticamente la primera sucursal si existe
          if (me.arrayBuscador && me.arrayBuscador.length > 0) {
            me.sucursalseleccionada = me.arrayBuscador[0];
          }
        })
        .catch(function(error) {
          console.log(error);
        });
    },

    listarEjecutivo(page, buscar, criterio) {
      let me = this;
      var url = "/user/selectUser/rol?filtro=" + 2;
      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.arrayBuscador = respuesta.usuarios;
          console.log("Ejecutivos", me.arrayBuscador);
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
            
       me.modal = false; 

      var url = "/resumen-ventas-documento?";

      url += "sucursal=" + this.sucursalseleccionada.id;

      if (this.ejecutivoseleccionado && this.ejecutivoseleccionado.id) {
        url += "&ejecutivoCuentas=" + this.ejecutivoseleccionado.id;
      }

      url += "&estadoVenta=" + this.criterioEstado;

      if (this.clienteseleccionada && this.clienteseleccionada.id) {
        url += "&idcliente=" + this.clienteseleccionada.id;
      }

      url += "&moneda=" + this.monedaPrincipal[0];

      let fechaInicio, fechaFin;

      if (this.tipoReporte === "dia") {
        fechaInicio = this.fechaSeleccionada;
        fechaFin = this.fechaSeleccionada;
      } else if (this.tipoReporte === "mes") {
        const [year, month] = this.mesSeleccionado.split("-");
        fechaInicio = `${year}-${month}-01`;
        const ultimoDia = new Date(year, month, 0).getDate();
        fechaFin = `${year}-${month}-${String(ultimoDia).padStart(2, "0")}`;
      }

      url += "&fechaInicio=" + fechaInicio + "&fechaFin=" + fechaFin;

      console.log("URL de consulta:", url);

      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.totalVentas = respuesta.total_BS;
          me.arrayReporte = respuesta.ventas;
          me.cantidadVentasRegistradasContado =
            respuesta.ventas_registradas_contado || 0;
          me.cantidadVentasRegistradasCredito =
            respuesta.ventas_registradas_credito || 0;
          me.cantidadVentasAnuladas = respuesta.ventas_anuladas || 0;

          console.log("Array reporte:", me.arrayReporte);

          if (me.arrayReporte.length === 0) {
            me.$swal.fire(
              "Sin resultados",
              "No se encontraron ventas para los filtros seleccionados",
              "info"
            );
          } else {
            let mensaje = `Se encontraron ${me.arrayReporte.length} ventas<br>`;
            mensaje += `Registradas Contado: ${me.cantidadVentasRegistradasContado}<br>`;
            mensaje += `Registradas Crédito: ${me.cantidadVentasRegistradasCredito}<br>`;
            mensaje += `Anuladas: ${me.cantidadVentasAnuladas}`;
            me.$swal.fire("Éxito", mensaje, "success");
          }
        })
        .catch(function(error) {
          console.error("ERROR:", error);
          me.$swal.fire("Error", "No se pudo generar el reporte", "error");
        }) .finally(function() {
                    setTimeout(() => {
                        me.isLoading = false;
                    }, 500);
          });
    },

    calcularTotalVentas() {
      this.totalVentas = this.arrayReporte.reduce((total, venta) => {
        return total + parseFloat(venta.importe_BS || 0);
      }, 0);
    },

    formatearFechaHora(fechaHora) {
      if (!fechaHora) return "";
      const fecha = new Date(fechaHora);
      const dia = String(fecha.getDate()).padStart(2, "0");
      const mes = String(fecha.getMonth() + 1).padStart(2, "0");
      const anio = fecha.getFullYear();
      const horas = String(fecha.getHours()).padStart(2, "0");
      const minutos = String(fecha.getMinutes()).padStart(2, "0");
      return `${dia}/${mes}/${anio} ${horas}:${minutos}`;
    },

    listaReporteDetallado() {
      let me = this;
      var url = "/resumen-ventas-documento-detallado?";

      // Agregar los parámetros obligatorios
      url +=
        "sucursal=" +
        this.sucursalseleccionada.id +
        "&ejecutivoCuentas=" +
        this.ejecutivoseleccionado.id +
        "&estadoVenta=" +
        this.criterioEstado +
        "&idcliente=" +
        this.clienteseleccionada.id +
        "&moneda=" +
        this.monedaPrincipal[0];

      // Agregar las fechas de inicio y fin
      url += "&fechaInicio=" + me.fechaInicio + "&fechaFin=" + me.fechaFin;

      axios
        .get(url)
        .then(function(response) {
          var respuesta = response.data;
          me.arrayReporteDetallado = respuesta.ventas;
          console.log("array reporte detallado", me.arrayReporteDetallado);
        })
        .catch(function(error) {
          console.log("ERRORES", error);
        });
    },

    // Nuevo método para llamar al backend y obtener los artículos vendidos
    obtenerArticulosVendidos() {
      let me = this;
      let url = "/reporte-articulos-vendidos?";
      url +=
        "sucursal=" +
        this.sucursalseleccionada.id +
        "&ejecutivoCuentas=" +
        this.ejecutivoseleccionado.id +
        "&estadoVenta=" +
        this.criterioEstado +
        "&idcliente=" +
        this.clienteseleccionada.id +
        "&moneda=" +
        this.monedaPrincipal[0];
      url += "&fechaInicio=" + me.fechaInicio + "&fechaFin=" + me.fechaFin;
      axios
        .get(url)
        .then(function(response) {
          me.articulosVendidos = response.data.articulos_vendidos;
          console.log("articulos vendidos", me.articulosVendidos);
        })
        .catch(function(error) {
          console.log("ERRORES articulos vendidos", error);
        });
    },

    async descargarArchivoReporte(url, nombreArchivo) {
      try {
        Swal.fire({
          title: "Generando reporte...",
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          },
        });

        const response = await axios.get(url, {
          responseType: "blob",
        });

        // Crear un enlace temporal para descargar el archivo
        const blob = new Blob([response.data]);
        const link = document.createElement("a");
        link.href = window.URL.createObjectURL(blob);
        link.download = nombreArchivo;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        Swal.close();
      } catch (error) {
        Swal.close();
        Swal.fire("Error al generar el reporte", "", "error");
      }
    },

    cargarSucursalUsuario() {
      console.log("Iniciando carga de sucursal del usuario...");
      axios
        .get("/usuario-autenticado")
        .then((response) => {
          console.log("Respuesta del servidor:", response.data);
          this.sucursalseleccionada = {
            id: response.data.idsucursal,
            nombre: response.data.sucursal_nombre,
          };
        })
        .catch((error) => {
          console.error(
            "Error completo al cargar sucursal del usuario:",
            error.response
          );
          this.$swal.fire(
            "Error",
            "No se pudo cargar la sucursal del usuario",
            "error"
          );
        });
    },

    validarFiltrosVisualizarReporte() {
      if (!this.sucursalseleccionada || !this.sucursalseleccionada.id) {
        this.$swal.fire(
          "Error",
          "No se ha cargado la sucursal del usuario",
          "warning"
        );
        return false;
      }

      if (this.tipoReporte === "dia" && !this.fechaSeleccionada) {
        this.$swal.fire("Error", "Debe seleccionar una fecha", "warning");
        return false;
      }

      if (this.tipoReporte === "mes" && !this.mesSeleccionado) {
        this.$swal.fire("Error", "Debe seleccionar un mes y año", "warning");
        return false;
      }

      return true;
    },

    procesarReporte() {
      if (this.validarFiltrosVisualizarReporte()) {
        this.listaReporte();
        this.modal = false;
      }
    },

    validarFiltrosExportacion() {
      if (!this.sucursalseleccionada || !this.sucursalseleccionada.id) {
        Swal.fire("Seleccione una sucursal", "", "warning");
        return false;
      }

      if (!this.tipoReporte) {
        Swal.fire("Seleccione un tipo de reporte", "", "warning");
        return false;
      }

      if (this.tipoReporte === "dia" && !this.fechaSeleccionada) {
        Swal.fire("Seleccione una fecha", "", "warning");
        return false;
      }

      if (this.tipoReporte === "mes" && !this.mesSeleccionado) {
        Swal.fire("Seleccione un mes", "", "warning");
        return false;
      }

      return true;
    },

    descargarPDFDialog() {
      Swal.fire({
        title: "¿Qué tipo de reporte quiere descargar?",
        showCancelButton: true,
        confirmButtonText: "GENERAL",
        cancelButtonText: "DETALLADO",
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#10B481",
        reverseButtons: true,
        allowOutsideClick: true,
      }).then((result) => {
        if (result.isConfirmed) {
          this.descargarPDFGeneral();
        } else if (result.dismiss === Swal.DismissReason.cancel) {
          this.descargarVentasDetalladasPDF();
        }
      });
    },
    exportarExcelDialog() {
      Swal.fire({
        title: "¿Qué tipo de reporte Excel quiere exportar?",
        showCancelButton: true,
        confirmButtonText: "GENERAL",
        cancelButtonText: "DETALLADO",
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#10B481",
        reverseButtons: true,
        allowOutsideClick: true,
      }).then((result) => {
        if (result.isConfirmed) {
          this.descargarExcelGeneral();
        } else if (result.dismiss === Swal.DismissReason.cancel) {
          this.exportarExcelDetallado();
        }
      });
    },

    async descargarPDFGeneral() {
      if (!this.validarFiltrosExportacion()) {
        Swal.fire(
          "No hay datos del filtro para generar reporte",
          "",
          "warning"
        );
        return;
      }

      let url = "/descargar-reporte-general-pdf?";
      url += "sucursal=" + this.sucursalseleccionada.id;
      url += "&tipoReporte=" + this.tipoReporte;

      if (this.tipoReporte === "dia") {
        url += "&fechaSeleccionada=" + this.fechaSeleccionada;
      } else if (this.tipoReporte === "mes") {
        url += "&mesSeleccionado=" + this.mesSeleccionado;
      }

      url += "&estadoVenta=" + this.criterioEstado;

      if (this.clienteseleccionada && this.clienteseleccionada.id) {
        url += "&idcliente=" + this.clienteseleccionada.id;
      }

      url += "&moneda=" + this.monedaPrincipal[0];

      await this.descargarArchivoReporte(url, "reporte_ventas_general.pdf");
    },

    async descargarVentasDetalladasPDF() {
      if (!this.validarFiltrosExportacion()) {
        Swal.fire(
          "No hay datos del filtro para generar reporte",
          "",
          "warning"
        );
        return;
      }

      let url = "/descargar-ventas-detalladas-pdf?";
      url += "sucursal=" + this.sucursalseleccionada.id;
      url += "&tipoReporte=" + this.tipoReporte;

      if (this.tipoReporte === "dia") {
        url += "&fechaSeleccionada=" + this.fechaSeleccionada;
      } else if (this.tipoReporte === "mes") {
        url += "&mesSeleccionado=" + this.mesSeleccionado;
      }

      url += "&estadoVenta=" + this.criterioEstado;

      if (this.clienteseleccionada && this.clienteseleccionada.id) {
        url += "&idcliente=" + this.clienteseleccionada.id;
      }

      url += "&moneda=" + this.monedaPrincipal[0];

      await this.descargarArchivoReporte(url, "reporte_ventas_detalladas.pdf");
    },

    async descargarVentasDetalladasPDF() {
      if (!this.validarFiltrosExportacion()) {
        Swal.fire(
          "No hay datos del filtro para generar reporte",
          "",
          "warning"
        );
        return;
      }

      let url = "/descargar-ventas-detalladas-pdf?";
      url += "sucursal=" + this.sucursalseleccionada.id;
      url += "&tipoReporte=" + this.tipoReporte;

      if (this.tipoReporte === "dia") {
        url += "&fechaSeleccionada=" + this.fechaSeleccionada;
      } else if (this.tipoReporte === "mes") {
        url += "&mesSeleccionado=" + this.mesSeleccionado;
      }

      url += "&estadoVenta=" + this.criterioEstado;

      if (this.clienteseleccionada && this.clienteseleccionada.id) {
        url += "&idcliente=" + this.clienteseleccionada.id;
      }

      url += "&moneda=" + this.monedaPrincipal[0];

      await this.descargarArchivoReporte(url, "reporte_ventas_detalladas.pdf");
    },

    async descargarVentasDetalladasPDF() {
      if (!this.validarFiltrosExportacion()) {
        Swal.fire(
          "No hay datos del filtro para generar reporte",
          "",
          "warning"
        );
        return;
      }

      let url = "/descargar-ventas-detalladas-pdf?";
      url += "sucursal=" + this.sucursalseleccionada.id;
      url += "&tipoReporte=" + this.tipoReporte;

      if (this.tipoReporte === "dia") {
        url += "&fechaSeleccionada=" + this.fechaSeleccionada;
      } else if (this.tipoReporte === "mes") {
        url += "&mesSeleccionado=" + this.mesSeleccionado;
      }

      url += "&estadoVenta=" + this.criterioEstado;

      if (this.clienteseleccionada && this.clienteseleccionada.id) {
        url += "&idcliente=" + this.clienteseleccionada.id;
      }

      url += "&moneda=" + this.monedaPrincipal[0];

      await this.descargarArchivoReporte(url, "reporte_ventas_detalladas.pdf");
    },

    async descargarExcelGeneral() {
      if (!this.validarFiltrosExportacion()) {
        Swal.fire(
          "No hay datos del filtro para generar reporte",
          "",
          "warning"
        );
        return;
      }

      let url = "/descargar-ventas-general-excel?";
      url += "sucursal=" + this.sucursalseleccionada.id;
      url += "&tipoReporte=" + this.tipoReporte;

      if (this.tipoReporte === "dia") {
        url += "&fechaSeleccionada=" + this.fechaSeleccionada;
      } else if (this.tipoReporte === "mes") {
        url += "&mesSeleccionado=" + this.mesSeleccionado;
      }

      url += "&estadoVenta=" + this.criterioEstado;

      if (this.clienteseleccionada && this.clienteseleccionada.id) {
        url += "&idcliente=" + this.clienteseleccionada.id;
      }

      url += "&moneda=" + this.monedaPrincipal[0];

      await this.descargarArchivoReporte(url, "reporte_ventas_general.xlsx");
    },

    async exportarExcelDetallado() {
      if (!this.validarFiltrosExportacion()) {
        Swal.fire(
          "No hay datos del filtro para generar reporte",
          "",
          "warning"
        );
        return;
      }

      let url = "/descargar-ventas-detalladas-excel?";
      url += "sucursal=" + this.sucursalseleccionada.id;
      url += "&tipoReporte=" + this.tipoReporte;

      if (this.tipoReporte === "dia") {
        url += "&fechaSeleccionada=" + this.fechaSeleccionada;
      } else if (this.tipoReporte === "mes") {
        url += "&mesSeleccionado=" + this.mesSeleccionado;
      }

      url += "&estadoVenta=" + this.criterioEstado;

      if (this.clienteseleccionada && this.clienteseleccionada.id) {
        url += "&idcliente=" + this.clienteseleccionada.id;
      }

      url += "&moneda=" + this.monedaPrincipal[0];

      await this.descargarArchivoReporte(url, "reporte_ventas_detalladas.xlsx");
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
      me.listarEjecutivo(page, buscar, criterio);
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
    ocultarDetalle() {
      this.listado = 1;
      this.codigo = null;
      this.arrayArticulo.length = 0;
      this.precioseleccionado = null;
      this.medida = null;
      this.nombreCliente = null;
      this.documento = null;
      this.email = null;
      this.idAlmacen = null;
      this.arrayProductos = [];
      this.arrayDetalle = [];
      this.precioBloqueado = false;
    },
    /*
    verVenta(id) {
      let me = this;
      me.listado = 2;

      //Obtener datos del ingreso
      var arrayVentaT = [];
      var url = "/venta/obtenerCabecera?id=" + id;

      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          arrayVentaT = respuesta.venta;
          console.log("VIENDO ", respuesta);

          me.cliente = arrayVentaT[0]["nombre"];
          me.tipo_comprobante = arrayVentaT[0]["tipo_comprobante"];
          me.serie_comprobante = arrayVentaT[0]["serie_comprobante"];
          me.num_comprobante = arrayVentaT[0]["num_comprobante"];
          me.impuesto = arrayVentaT[0]["impuesto"];
          me.total = arrayVentaT[0]["total"];
        })
        .catch(function (error) {
          console.log(error);
        });

      //obtener datos de los detalles
      var url = "/venta/obtenerDetalles?id=" + id;

      axios
        .get(url)
        .then(function (response) {
          //console.log(response);
          var respuesta = response.data;
          me.arrayDetalle = respuesta.detalles;
          console.log(array);
        })
        .catch(function (error) {
          console.log(error);
        });
    },
*/
    cerrarModal() {
      this.modal = 0;
      this.tituloModal = "";
      // Seleccionar la primera sucursal si existe al cerrar el modal
      if (this.arrayBuscador && this.arrayBuscador.length > 0) {
        this.sucursalseleccionada = this.arrayBuscador[0];
      }
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
      this.industriaseleccionadaVacio = false;
      this.proveedorseleccionadaVacio = false;
      this.gruposeleccionadaVacio = false;
      this.medidaseleccionadaVacio = false;
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
      this.lineaseleccionada.nombre = "";
      this.industriaseleccionada.nombre = "";
      this.proveedorseleccionada.nombre = "";
      this.gruposeleccionada.nombre_grupo = "";
      this.medidaseleccionada.descripcion_medida = "";
      this.lineaseleccionada.nombre = "";
      this.articuloseleccionada.nombre = "";
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
      this.ejecutivoseleccionado = false;
      this.clienteseleccionada = false;
      this.fechaInicio = "";
      this.fechaFin = "";
      // Al abrir el filtro, siempre listar sucursales y seleccionar la primera
      if (modelo === "articulo" && accion === "registrar") {
        this.listarSucursal(1, "", "nombre");
      }
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
              this.clienteseleccionada = {
                nombre: data["nombre"],
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
          console.log("Moneda principal; ", me.monedaPrincipal);
          console.log("Moneda; ", me.monedaPrincipal[0]);
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
    this.listarPrecio(); //aumenTe 6julio
    this.cargarSucursalUsuario();
  },
};
</script>
<style>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1040;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.5);
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1050;
  width: 100%;
  height: 100%;
  overflow-x: hidden;
  overflow-y: auto;
  outline: 0;
}

.modal.show {
  display: block !important;
}

body.modal-open {
  overflow: hidden;
}

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
  padding-bottom: 20px;
  margin-bottom: 8px;
}

.input-container .p-inputtext {
  width: 100%;
  margin-bottom: 0;
}

.error-message {
  position: absolute;
  bottom: 2px;
  left: 0;
  font-size: 0.75rem;
  margin-top: 0;
}

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

.responsive-dialog >>> .p-dialog {
  margin: 0.75rem;
  max-height: 90vh;
  overflow-y: auto;
}

.responsive-dialog >>> .p-dialog-content {
  overflow-x: auto;
  padding: 0.75rem 1rem;
}

.responsive-dialog >>> .p-dialog-header {
  padding: 0.75rem 1.5rem;
  font-size: 1.1rem;
}

.responsive-dialog >>> .p-dialog-footer {
  padding: 0.5rem 1.5rem;
  gap: 0.5rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

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

.form-compact >>> .p-field {
  margin-bottom: 0.25rem !important;
}

>>> .p-fluid .p-field {
  margin-bottom: 0.25rem;
}

.responsive-dialog >>> .p-dialog-content {
  padding: 0.75rem 1rem !important;
}

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

>>> .swal2-container {
  z-index: 99999 !important;
}

>>> .swal2-popup {
  z-index: 99999 !important;
}

@media (max-width: 1024px) {
  .responsive-dialog >>> .p-dialog {
    margin: 0.5rem;
    max-height: 95vh;
  }

  >>> .p-datatable {
    font-size: 0.85rem;
  }
}

@media (max-width: 768px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog >>> .p-dialog {
    margin: 0.25rem;
    max-height: 98vh;
  }

  .responsive-dialog >>> .p-dialog-content {
    padding: 0.5rem 0.75rem;
  }

  .responsive-dialog >>> .p-dialog-header {
    padding: 0.5rem 1rem;
    font-size: 1rem;
  }

  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.4rem 1rem;
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

  >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }

  .toolbar >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  .search-bar .p-inputtext-sm {
    padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
    font-size: 0.85rem !important;
  }

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

  .input-container {
    padding-bottom: 20px;
    margin-bottom: 6px;
  }
}

@media (max-width: 480px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog >>> .p-dialog {
    margin: 0.1rem;
    max-height: 99vh;
  }

  .responsive-dialog >>> .p-dialog-content {
    padding: 0.4rem 0.5rem;
  }

  .responsive-dialog >>> .p-dialog-header {
    padding: 0.4rem 0.75rem;
    font-size: 0.95rem;
  }

  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.3rem 0.75rem;
    justify-content: flex-end;
  }

  .responsive-dialog >>> .p-dialog-footer .p-button {
    width: auto;
    margin-bottom: 0.25rem;
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

  .toolbar >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

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

  .input-container {
    padding-bottom: 20px;
    margin-bottom: 4px;
  }
}

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

>>> .p-fileupload .p-button.p-fileupload-choose:enabled:hover {
  background-color: #16a34a !important;
  border-color: #16a34a !important;
}

>>> .p-fileupload .p-button.p-fileupload-choose:focus {
  box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.5) !important;
}

>>> .p-fileupload .p-button.p-fileupload-choose:enabled:active {
  background-color: #15803d !important;
  border-color: #15803d !important;
}

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

>>> .p-fileupload
  .p-fileupload-buttonbar
  .p-button.p-component:not(.p-fileupload-choose):enabled:hover {
  background: #dc2626 !important;
  border-color: #dc2626 !important;
}

>>> .p-fileupload
  .p-fileupload-buttonbar
  .p-button.p-component:not(.p-fileupload-choose):focus {
  box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.5) !important;
}

>>> .p-fileupload
  .p-fileupload-buttonbar
  .p-button.p-component:not(.p-fileupload-choose):enabled:active {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
}

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

>>> .p-fileupload .p-fileupload-files .p-button:enabled:hover {
  background: #dc2626 !important;
  border-color: #dc2626 !important;
}

>>> .p-fileupload .p-fileupload-files .p-button:focus {
  box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.5) !important;
}

>>> .p-fileupload .p-fileupload-files .p-button:enabled:active {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
}

>>> .p-fileupload .p-fileupload-files .p-button:disabled {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  opacity: 0.6;
}

>>> .p-fileupload .p-fileupload-files .p-button .p-button-icon {
  color: #ffffff !important;
}

>>> .p-fileupload-row > div:first-child {
  display: none !important;
}

>>> .p-dialog .p-dialog-content {
  padding: 0 1.5rem 1.5rem 1.5rem;
}

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
/* ===== CONTENEDOR GENERAL ===== */
.detalle-venta-pro {
  background: #ffffff;
  border-radius: 0.5rem;
  padding: 0.75rem;
  max-width: 1500px;
  margin: 0 auto;
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
  animation: fadeIn 0.4s ease;
  font-family: "Inter", "Segoe UI", sans-serif;
}

/* ===== ENCABEZADO ===== */
.detalle-header-pro {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  border-bottom: 2px solid #f1f5f9;
  padding-bottom: 0.1rem;
  margin-bottom: 1rem;
}
.detalle-titulo-pro {
  font-size: 1.2rem;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.detalle-subtitulo-pro {
  font-size: 0.7rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.detalle-meta-pro {
  display: flex;
  gap: 2rem;
}

.label-pro {
  font-size: 0.7rem;
  text-transform: uppercase;
  color: #6b7280;
  letter-spacing: 0.04em;
}

.valor-pro {
  font-size: 0.8rem;
  font-weight: 600;
  color: #111827;
  margin-top: 0.15rem;
}

/* ===== CLIENTE ===== */
.detalle-cliente-pro {
  background: #f9fafb;
  border-radius: 0.2rem;
  padding: 0.05rem 1rem;
  margin-bottom: 1rem;
  border: 1px solid #e5e7eb;
}

/* ===== TABLA ===== */
.detalle-tabla-pro .p-datatable {
  border-radius: 0.2rem;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.p-datatable tbody tr:hover {
  background-color: #f9fafb;
}

/* ===== RESUMEN ===== */
.detalle-resumen-pro {
  margin-top: 1.2rem;
  border-top: 1px solid #e5e7eb;
  padding-top: 0.7rem;
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  text-align: right;
}

.resumen-linea-pro {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  font-size: 0.8rem;
  color: #374151;
}

.total-final-pro {
  font-size: 0.8rem;
  font-weight: 700;
  color: #111827;
}

/* ===== FOOTER ===== */
.detalle-footer-pro {
  margin-top: 2rem;
  text-align: right;
}
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
  z-index: 99999;
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
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
