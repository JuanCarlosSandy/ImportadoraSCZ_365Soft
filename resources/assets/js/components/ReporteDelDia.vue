<template>
  <main class="main">
    <Panel>
      <template #header>
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="pi pi-user panel-icon" style="color: blue;"></i>
            <h4 class="panel-title" style="margin: 0;">MIS VENTAS DEL DÍA ({{ fechaSeleccionada }})</h4>
          </div>
        </div>
      </template>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
        
        <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
          
          <div style="display: flex; align-items: center; gap: 0.5rem; background-color: #f8f9fa; padding: 0.5rem; border-radius: 4px; border: 1px solid #dee2e6;">
            <i class="pi pi-home" style="font-size: 1.2rem; color: #28a745;"></i>
            <div>
              <div style="font-weight: bold; font-size: 0.8rem; color: #6c757d;">SUCURSAL</div>
              <div style="font-weight: bold;">{{ sucursalseleccionada.nombre || 'Cargando...' }}</div>
            </div>
          </div>

          <div style="display: flex; align-items: center; gap: 0.5rem; background-color: #f8f9fa; padding: 0.5rem; border-radius: 4px; border: 1px solid #dee2e6;">
            <i class="pi pi-user" style="font-size: 1.2rem; color: #17a2b8;"></i>
            <div>
              <div style="font-weight: bold; font-size: 0.8rem; color: #6c757d;">VENDEDOR</div>
              <div style="font-weight: bold;">{{ ejecutivoseleccionado.nombre || '...' }}</div>
            </div>
          </div>

          <div style="display: flex; align-items: center; gap: 0.5rem; background-color: #f8f9fa; padding: 0.5rem; border-radius: 4px; border: 1px solid #dee2e6;">
            <i class="pi pi-calendar" style="font-size: 1.2rem; color: #ffc107;"></i>
            <div>
              <div style="font-weight: bold; font-size: 0.8rem; color: #6c757d;">FECHA</div>
              <div style="font-weight: bold;">{{ fechaSeleccionada }}</div>
            </div>
          </div>
        </div>

        <div style="font-weight: bold; font-size: 1.3rem; color: #007bff;">
          Total: {{ totalVentas }} {{ monedaPrincipal[1] }}
        </div>

        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <Button
            icon="pi pi-file-excel"
            label="EXCEL"
            class="p-button-success"
            @click="exportarExcelDialog" />
          <Button
            icon="pi pi-file-pdf"
            label="PDF"
            class="p-button-danger"
            @click="descargarPDFDialog" />
        </div>
      </div>

      <template v-if="listado == 1">
        <div style="overflow-y: auto;">
          <div class="mb-2 d-flex" style="gap: 0.5rem;">
            <input
              type="text"
              v-model="busquedaVentas"
              class="form-control"
              placeholder="Buscar por cliente, recibo, etc..."
              style="flex:1; min-width:0;" />
            <Button
              icon="pi pi-times"
              class="p-button-secondary p-button-sm"
              @click="busquedaVentas = ''"
              title="Limpiar búsqueda" />
          </div>

          <DataTable
            :value="filteredVentas"
            :paginator="true"
            :rows="10"
            dataKey="id"
            responsiveLayout="scroll"
            class="p-datatable-gridlines p-datatable-sm">
            
            <Column header="Acciones" style="width: 80px; text-align:center">
              <template #body="slotProps">
                <Button
                  icon="pi pi-eye"
                  class="p-button-info btn-mini"
                  title="Ver Detalle"
                  @click="verVentaDesdeReporte(slotProps.data.id)"
                />
              </template>
            </Column>

            <Column header="Comprobante" field="Factura"></Column>
            
            <Column header="Hora">
              <template #body="slotProps">
                {{ slotProps.data.fecha_hora ? slotProps.data.fecha_hora.split(' ')[1].substring(0,5) : '' }}
              </template>
            </Column>
            
            <Column header="Cliente" field="nombre"></Column>
            
            <Column header="Total">
              <template #body="slotProps">
                <div style="text-align: right; font-weight: bold;">
                   {{ Number(slotProps.data.importe_BS).toFixed(2) }} Bs
                </div>
              </template>
            </Column>

            <Column header="Tipo">
              <template #body="slotProps">
                <span v-if="slotProps.data.idtipo_venta == 1" class="badge badge-primary">Contado</span>
                <span v-else class="badge badge-warning">Crédito</span>
              </template>
            </Column>
            
            <Column header="Estado">
              <template #body="slotProps">
                <span v-if="slotProps.data.estado == 0" class="badge badge-danger">Anulado</span>
                <span v-else class="badge badge-success">Registrado</span>
              </template>
            </Column>
          </DataTable>
        </div>
      </template>

      <template v-else-if="listado == 2">
  <div class="detalle-venta-pro">
    <div class="detalle-header-pro">
      <div class="detalle-section-pro">
        <h3 class="detalle-titulo-pro">Detalle de Comprobante</h3>
        <p class="detalle-subtitulo-pro">Resumen completo de la venta registrada</p>
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

    <div class="detalle-cliente-pro">
      <span class="label-pro">Cliente</span>
      <p class="valor-pro">{{ cliente }}</p>
    </div>

    <div class="detalle-tabla-pro">
      <DataTable :value="arrayDetalle" class="p-datatable-sm p-datatable-gridlines">
        <Column field="cantidad" header="Cant Vendida">
          <template #body="slotProps">
            <span :style="{
                  backgroundColor: slotProps.data.modo_venta === 'caja' ? '#0d6efd' : '#198754',
                  color: 'white',
                  padding: '4px 8px',
                  borderRadius: '4px',
                  fontWeight: 'bold'
                }">
              {{
              slotProps.data.cantidad + ' ' +
              (
              slotProps.data.modo_venta === 'caja'
              ? (slotProps.data.cantidad == 1 ? 'caja' : 'cajas')
              : (slotProps.data.cantidad == 1 ? 'unidad' : 'unidades')
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
            {{ (slotProps.data.precio * parseFloat(monedaVenta[0])).toFixed(2) }}
            {{ monedaVenta[1] }}
          </template>
        </Column>

        <Column field="subtotal" header="Subtotal">
          <template #body="slotProps">
            {{ (slotProps.data.subtotal * parseFloat(monedaVenta[0])).toFixed(2) }}
            {{ monedaVenta[1] }}
          </template>
        </Column>
      </DataTable>
    </div>

    <div v-if="idtipo_venta == 2" class="mt-4">
      <h4 class="mb-3">Cuotas del Crédito</h4>
      <DataTable :value="cuotas" class="p-datatable-sm p-datatable-gridlines">
        <Column field="numero_cuota" header="# Cuota"></Column>
        <Column field="fecha_pago" header="Fecha Pago">
          <template #body="slotProps">
            {{ slotProps.data.fecha_pago ? slotProps.data.fecha_pago.substring(0, 10) : '-' }}
          </template>
        </Column>
        <Column field="precio_cuota" header="Monto Cuota">
          <template #body="slotProps">
            {{ slotProps.data.precio_cuota }} {{ monedaVenta[1] }}
          </template>
        </Column>
        <Column field="saldo_restante" header="Saldo Restante">
          <template #body="slotProps">
            {{ slotProps.data.saldo_restante }} {{ monedaVenta[1] }}
          </template>
        </Column>
        <Column field="estado" header="Estado">
          <template #body="slotProps">
            <span v-if="slotProps.data.idtipo_pago === 5 && slotProps.data.descuento > 0" class="badge bg-info">
              Liquidado {{ slotProps.data.descuento }} {{ monedaVenta[1] }}
            </span>
            <span v-else-if="slotProps.data.estado === 'Pagado'" class="badge bg-success">
              Pagado
            </span>
            <span v-else class="badge bg-warning">
              {{ slotProps.data.estado }}
            </span>
          </template>
        </Column>
        <Column header="Tipo de Pago">
          <template #body="slotProps">
            <span v-if="slotProps.data.idtipo_pago === 1" class="badge bg-success">
              💵 Efectivo
            </span>
            <span v-else-if="slotProps.data.idtipo_pago === 5" class="badge bg-info">
              🔒 Liquidación
            </span>
            <span v-else-if="slotProps.data.idtipo_pago === 7" class="badge bg-primary">
              🏦 {{ slotProps.data.nombre_cuenta || 'Cuenta bancaria' }}
            </span>
            <span v-else class="badge bg-dark">
              No definido
            </span>
          </template>
        </Column>
      </DataTable>
    </div>

    <div class="detalle-resumen-pro">
      <div class="resumen-linea-pro">
        <span>SubTotal General</span>
        <strong>{{ (subtotalVista * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}</strong>
      </div>
      <div class="resumen-linea-pro">
        <span>Descuento Adicional</span>
        <strong>{{ (descuentoAdicionalvista * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}</strong>
      </div>
      <div class="resumen-linea-pro total-final-pro">
        <span>Total Neto</span>
        <strong>{{ (total * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}</strong>
      </div>
    </div>

    <div class="detalle-footer-pro">
      <Button @click="ocultarDetalle()" label="Cerrar" icon="pi pi-times" severity="danger"
        class="p-button-danger p-button-sm btn-mini" />
    </div>
  </div>
</template>
    </Panel>
    
    </main>
</template>

<script>
import Panel from "primevue/panel";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import InputText from "primevue/inputtext"; 
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  components: {
    Panel,
    DataTable,
    Column,
    Button,
    InputText
  },
  data() {
    return {

      tipoReporte: 'dia', 
      criterioEstado: 'Todos', 
 
      fechaSeleccionada: '',
      sucursalseleccionada: { id: 0, nombre: '' },
      ejecutivoseleccionado: { id: 0, nombre: 'Detectando...' },

      arrayReporte: [],
      totalVentas: 0,
      busquedaVentas: '',
      listado: 1, 
      
      
      arrayDetalle: [],
      cuotas: [],
      total: 0,
      monedaPrincipal: ['1', 'Bs'], 
      monedaVenta: [1, 'Bs'],
 
      modoVista: 'lectura',
      cliente: '', 
      num_comprobante: '',
      tipo_comprobante: '',
      idtipo_venta: 1, 
      

      subtotalVista: 0, 
      descuentoAdicionalvista: 0, 
      
  
  

      
      editarCuotas: false, 
      tiposPagoOptions: [], 
      bancosOptions: []     

    };
  },
  computed: {
    
    filteredVentas() {
      if (!this.busquedaVentas) return this.arrayReporte;
      const texto = this.busquedaVentas.toLowerCase();
      return this.arrayReporte.filter(item => {
        return Object.values(item).some(val =>
          String(val).toLowerCase().includes(texto)
        );
      });
    }
  },
  methods: {
    
    async cargarSucursalYReporte() {
        return axios.get('/usuario-autenticado')
            .then(response => {
                this.sucursalseleccionada = {
                    id: response.data.idsucursal,
                    nombre: response.data.sucursal_nombre
                };
                
                
                this.listaReporte(); 
            })
            .catch(error => {
                console.error("Error cargando sucursal", error);
            });
    },
    async inicializarVistaVendedor() {
        try {
            Swal.fire({
                title: 'Cargando sus ventas...',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false
            });

            
            const hoy = new Date();
            
            this.fechaSeleccionada = hoy.getFullYear() + '-' + 
                                     String(hoy.getMonth() + 1).padStart(2, '0') + '-' + 
                                     String(hoy.getDate()).padStart(2, '0');

            
            
            if (window.userData) {
                this.ejecutivoseleccionado = {
                    id: window.userData.id,
                    nombre: window.userData.nombre || window.userData.usuario 
                };
            }

            
            await this.datosConfiguracion();

            await this.cargarDatosUsuarioYReporte();

            
            await this.cargarSucursalYReporte();

            Swal.close();

        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'No se pudieron cargar los datos iniciales', 'error');
        }
    },

    
    cargarDatosUsuarioYReporte() {
        return axios.get('/usuario-autenticado')
            .then(response => {
                console.log("Datos del usuario:", response.data);

                
                this.sucursalseleccionada = {
                    id: response.data.idsucursal,
                    nombre: response.data.sucursal_nombre || 'Sucursal Principal'
                };

                
                
                this.ejecutivoseleccionado = {
                    id: response.data.id, 
                    nombre: response.data.nombre || response.data.usuario || 'Vendedor'
                };
                
                
                this.listaReporte(); 
            })
            .catch(error => {
                console.error("Error cargando usuario:", error);
                this.ejecutivoseleccionado.nombre = "Error al cargar";
            });
    },

    
    listaReporte() {
        let me = this;
        
        var url = "/resumen-ventas-documento?";
        
        url += "sucursal=" + this.sucursalseleccionada.id;
        url += "&ejecutivoCuentas=" + this.ejecutivoseleccionado.id; 
        url += "&estadoVenta=" + this.criterioEstado;
        url += "&moneda=" + this.monedaPrincipal[0];
        
        url += "&fechaInicio=" + this.fechaSeleccionada + "&fechaFin=" + this.fechaSeleccionada;

        console.log('Generando reporte automático:', url);

        axios.get(url)
            .then(function (response) {
                console.log("RESPUESTA REPORTE:", response.data);
                var respuesta = response.data;
                me.totalVentas = respuesta.total_BS;
                me.arrayReporte = respuesta.ventas;

                // VALIDAR QUE HAYA VENTAS ANTES DE ACCEDER A LA POSICIÓN [0]
                if(respuesta.ventas && respuesta.ventas.length > 0) {
                     me.ejecutivoseleccionado.nombre = respuesta.ventas[0].usuario;
                }
                
                // 🔴 ERROR ESTABA AQUÍ: usabas 'this' en lugar de 'me'
                console.log("Vendedor detectado:", me.ejecutivoseleccionado.nombre); // ✅ CORREGIDO
                
                me.arrayReporte.sort((a, b) => new Date(b.fecha_hora) - new Date(a.fecha_hora));
            })
            .catch(function (error) {
                console.error("ERROR REPORTE:", error);
            });
    },

    
    verVentaDesdeReporte(id) {
        this.listado = 2;
        this.verVenta(id);
    },
   ocultarDetalle() {
        this.listado = 1;
        this.arrayDetalle = [];
        this.cuotas = [];
    },
    getBankUrl(bancoNombre) {
        
        return '/img/bancos/default.png'; 
    },
    verVenta(id) {
        let me = this;
        me.listado = 2; 
        
        
        axios.get("/venta/obtenerCabecera?id=" + id).then(res => {
            let venta = res.data.venta[0];
            
            
            me.cliente = venta.nombre; 
            me.num_comprobante = venta.num_comprobante;
            me.tipo_comprobante = venta.tipo_comprobante;
            me.idtipo_venta = venta.idtipo_venta;
            
            
            me.total = venta.total;
            me.subtotalVista = venta.total + (venta.descuento || 0); 
            me.descuentoAdicionalvista = venta.descuento || 0;
        });

        
        axios.get("/venta/obtenerDetalles?id=" + id).then(res => {
            me.arrayDetalle = res.data.detalles;
        });
        
        
        
        axios.get("/credito/obtenerCuotas?id=" + id).then(res => {
             
             me.cuotas = res.data.cuotas || [];
        }).catch(err => {
             me.cuotas = []; 
        });
    },

    
    datosConfiguracion() {
        return axios.get("/configuracion").then((response) => {
            var conf = response.data.configuracionTrabajo;
            this.monedaPrincipal = [
                conf.valor_moneda_principal,
                conf.simbolo_moneda_principal,
            ];
        });
    },

    
    
    exportarExcelDialog() {
        Swal.fire({
            title: 'Exportar Excel',
            text: "¿Desea el reporte detallado o general?",
            showCancelButton: true,
            confirmButtonText: 'General',
            cancelButtonText: 'Detallado',
            confirmButtonColor: '#198754',
            cancelButtonColor: '#0d6efd',
        }).then((result) => {
            if (result.isConfirmed) this.descargarExcelGeneral();
            else if (result.dismiss === Swal.DismissReason.cancel) this.exportarExcelDetallado();
        });
    },

    descargarPDFDialog() {
        Swal.fire({
            title: 'Exportar PDF',
            text: "¿Desea el reporte detallado o general?",
            showCancelButton: true,
            confirmButtonText: 'General',
            cancelButtonText: 'Detallado',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#0d6efd',
        }).then((result) => {
            if (result.isConfirmed) this.descargarPDFGeneral();
            else if (result.dismiss === Swal.DismissReason.cancel) this.descargarVentasDetalladasPDF();
        });
    },

    
    construirUrlDescarga(endpoint) {
        let url = endpoint + "?";
        url += "sucursal=" + this.sucursalseleccionada.id;
        url += "&tipoReporte=dia"; 
        url += "&fechaSeleccionada=" + this.fechaSeleccionada;
        url += "&estadoVenta=" + this.criterioEstado;
        
        
        url += "&ejecutivoCuentas=" + this.ejecutivoseleccionado.id; 
        
        url += "&moneda=" + this.monedaPrincipal[0];
        return url;
    },

    async descargarArchivo(url, nombre) {
        window.open(url, '_blank');
        
        
    },

    descargarPDFGeneral() {
        let url = this.construirUrlDescarga("/descargar-reporte-general-pdf");
        this.descargarArchivo(url, 'reporte_dia.pdf');
    },
    descargarVentasDetalladasPDF() {
        let url = this.construirUrlDescarga("/descargar-ventas-detalladas-pdf");
        this.descargarArchivo(url, 'reporte_detallado.pdf');
    },
    descargarExcelGeneral() {
        let url = this.construirUrlDescarga("/descargar-ventas-general-excel");
        this.descargarArchivo(url, 'reporte.xlsx');
    },
    exportarExcelDetallado() {
        let url = this.construirUrlDescarga("/descargar-ventas-detalladas-excel");
        this.descargarArchivo(url, 'reporte_detallado.xlsx');
    }
  },
  mounted() {
    
    this.inicializarVistaVendedor();
  }
};
</script>

<style scoped>
/* Estilos simples para las tarjetas informativas */
.p-panel-header {
    background: #fff;
    border-bottom: 1px solid #dee2e6;
}
</style>
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

.responsive-dialog>>>.p-dialog {
  margin: 0.75rem;
  max-height: 90vh;
  overflow-y: auto;
}

.responsive-dialog>>>.p-dialog-content {
  overflow-x: auto;
  padding: 0.75rem 1rem;
}

.responsive-dialog>>>.p-dialog-header {
  padding: 0.75rem 1.5rem;
  font-size: 1.1rem;
}

.responsive-dialog>>>.p-dialog-footer {
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

.form-compact>>>.p-field {
  margin-bottom: 0.25rem !important;
}

>>>.p-fluid .p-field {
  margin-bottom: 0.25rem;
}

.responsive-dialog>>>.p-dialog-content {
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

.p-dialog-mask {
  z-index: 9990 !important;
}

.p-dialog {
  z-index: 9990 !important;
}

>>>.swal2-container {
  z-index: 99999 !important;
}

>>>.swal2-popup {
  z-index: 99999 !important;
}

@media (max-width: 1024px) {
  .responsive-dialog>>>.p-dialog {
    margin: 0.5rem;
    max-height: 95vh;
  }

  >>>.p-datatable {
    font-size: 0.85rem;
  }
}

@media (max-width: 768px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog>>>.p-dialog {
    margin: 0.25rem;
    max-height: 98vh;
  }

  .responsive-dialog>>>.p-dialog-content {
    padding: 0.5rem 0.75rem;
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.5rem 1rem;
    font-size: 1rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.4rem 1rem;
    justify-content: flex-end;
  }

  .toolbar-container {
    gap: 0.5rem;
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

  .required-icon {
    font-size: 0.8rem;
  }

  .optional-icon {
    font-size: 0.6rem;
  }

  >>>.p-inputtext,
  >>>.p-dropdown,
  >>>.p-inputnumber-input {
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

  .responsive-dialog>>>.p-dialog {
    margin: 0.1rem;
    max-height: 99vh;
  }

  .responsive-dialog>>>.p-dialog-content {
    padding: 0.4rem 0.5rem;
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.4rem 0.75rem;
    font-size: 0.95rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.3rem 0.75rem;
    justify-content: flex-end;
  }

  .responsive-dialog>>>.p-dialog-footer .p-button {
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

  .required-icon {
    font-size: 0.7rem;
  }

  .optional-icon {
    font-size: 0.55rem;
  }

  >>>.p-inputtext,
  >>>.p-dropdown,
  >>>.p-inputnumber-input {
    font-size: 0.85rem;
    padding: 0.4rem;
  }

  >>>.p-tag {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
  }

  .input-container {
    padding-bottom: 20px;
    margin-bottom: 4px;
  }
}

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

>>>.p-datatable .p-button {
  margin-right: 0.25rem;
}

@media (max-width: 768px) {
  >>>.p-datatable .p-button {
    margin-right: 0.15rem;
    margin-bottom: 0.15rem;
  }
}

>>>.p-fileupload .p-button.p-fileupload-choose {
  background-color: #22c55e !important;
  border-color: #22c55e !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

>>>.p-fileupload .p-button.p-fileupload-choose:enabled:hover {
  background-color: #16a34a !important;
  border-color: #16a34a !important;
}

>>>.p-fileupload .p-button.p-fileupload-choose:focus {
  box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.5) !important;
}

>>>.p-fileupload .p-button.p-fileupload-choose:enabled:active {
  background-color: #15803d !important;
  border-color: #15803d !important;
}

>>>.p-fileupload .p-button.p-fileupload-choose:disabled {
  background-color: #22c55e !important;
  border-color: #22c55e !important;
  opacity: 0.6;
}

>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose) {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):enabled:hover {
  background: #dc2626 !important;
  border-color: #dc2626 !important;
}

>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):focus {
  box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.5) !important;
}

>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):enabled:active {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
}

>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):disabled {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  opacity: 0.6;
}

>>>.p-fileupload .p-fileupload-files .p-button {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

>>>.p-fileupload .p-fileupload-files .p-button:enabled:hover {
  background: #dc2626 !important;
  border-color: #dc2626 !important;
}

>>>.p-fileupload .p-fileupload-files .p-button:focus {
  box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.5) !important;
}

>>>.p-fileupload .p-fileupload-files .p-button:enabled:active {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
}

>>>.p-fileupload .p-fileupload-files .p-button:disabled {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  opacity: 0.6;
}

>>>.p-fileupload .p-fileupload-files .p-button .p-button-icon {
  color: #ffffff !important;
}

>>>.p-fileupload-row>div:first-child {
  display: none !important;
}

>>>.p-dialog .p-dialog-content {
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
  font-family: 'Inter', 'Segoe UI', sans-serif;
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
  margin-bottom: 1.0rem;
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
</style>
