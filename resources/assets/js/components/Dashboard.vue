<template>
  <main class="main">
    <div class="loading-overlay" v-if="isLoading">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">LOADING...</div>
      </div>
    </div>
    <div class="container-fluid">
      <!-- Selección de periodo -->
      <div class="row d-flex mt-3 mb-4 justify-content-end align-items-center border-bottom pb-3">
        <label class="col-auto fw-bold text-primary">Periodo</label>
        <div class="col-md-2 col-auto">
          <select class="form-select" v-model="tipoPeriodo">
            <option selected value="Mes">Este mes</option>
            <option value="Año">Este año</option>
            <option value="Personalizado">Personalizado</option>
          </select>
        </div>

        <template v-if="tipoPeriodo == 'Personalizado'">
          <div class="col-auto">
            <label class="form-label mb-1" for="fechaInicio">Fecha de Inicio:</label>
            <input type="date" class="form-control" id="fechaInicio" @change="fetchData()" v-model="fechaInicio" />
          </div>
          <div class="col-auto">
            <label class="form-label mb-1" for="fechaFin">Fecha de Fin:</label>
            <input type="date" class="form-control" id="fechaFin" @change="fetchData()" v-model="fechaFin" />
          </div>
        </template>
      </div>

      <!-- Cuadros resumen: Ventas, Gastos, Ganancia -->
      <div class="row justify-content-between mb-5">
        <!-- Ventas siempre visible -->
        <square-item :icono="'fa fa-usd'" :titulo="'Ventas'" :moneda="monedaPrincipal[1]"
          :cantidad="sumaVentas.toFixed(2)" :fondoDegradado="'linear-gradient(35deg, #028bd2, #6dd3dd)'" />

         <!-- Costos (antes gastos) -->
        <square-item
          v-show="idrol == 4"
          :icono="'fa fa-shopping-cart'"
          :titulo="'Costos'"
          :moneda="monedaPrincipal[1]"
          :cantidad="sumaCostos.toFixed(2)"
          :fondoDegradado="'linear-gradient(35deg, #f67318, #f9ca38)'"
        />

        <!-- Utilidad -->
        <square-item
          v-show="idrol == 4"
          :icono="'fa fa-line-chart'"
          :titulo="'Utilidad'"
          :moneda="monedaPrincipal[1]"
          :cantidad="sumaUtilidad.toFixed(2)"
          :fondoDegradado="'linear-gradient(35deg, #3b9c3f, #41d445)'"
        />
      </div>
      <hr class="my-4" />

      <!-- Gráficas de ventas y compras -->
      <div class="row mb-5">
        <div class="col-md-6" v-show="idrol == 4">
          <div class="card card-chart shadow-sm">
            <div class="card-header bg-light">
              <h5 class="mb-0">Costos</h5>
            </div>
            <div class="card-body">
              <canvas id="costos"></canvas>
            </div>
            <div class="card-footer small text-muted">
              Costos de los últimos meses.
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card card-chart shadow-sm">
            <div class="card-header bg-light">
              <h5 class="mb-0">Ventas</h5>
            </div>
            <div class="card-body">
              <canvas id="ventas"></canvas>
            </div>
            <div class="card-footer small text-muted">
              Ventas de los últimos meses.
            </div>
          </div>
        </div>
      </div>

      <!-- Componentes de precios actualizados -->
      <div class="row mb-4">
        <div class="col-12">
          <newprecioarti :fechaInicio="fechaInicio" :fechaFin="fechaFin" :moneda="monedaPrincipal" />
        </div>
      </div>

      <!-- Componentes Top -->
      <div class="row mb-5">
        <div class="col-md-6 mb-4">
          <TopArticulos :fechaInicio="fechaInicio" :fechaFin="fechaFin" />
        </div>
        <div class="col-md-6 mb-4">
          <TopClientes :fechaInicio="fechaInicio" :fechaFin="fechaFin" :moneda="monedaPrincipal" />
        </div>
        <div class="col-md-12" v-if="idrol != 2">
          <TopVendedores :fechaInicio="fechaInicio" :fechaFin="fechaFin" :moneda="monedaPrincipal" />
        </div>
      </div>

      <!-- Productos críticos -->
      <div class="row mb-5">
        <div class="col-12 mb-4">
          <productosbajostock />
        </div>
      </div>
    </div>
  </main>
</template>

<script>
import axios from "axios";

export default {
  data() {
    // Calcular fechas por defecto del mes actual
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);

    // Formatear fechas directamente aquí
    const formatDateLocal = (date) => {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");
      return `${year}-${month}-${day}`;
    };

    return {
      idrol: null,
      isLoading: false,
      monedaPrincipal: [],
      tipoPeriodo: "Mes",
      sumaVentas: 0,
      sumaCostos: 0,
      sumaUtilidad: 0,
      data: [],

      charIngreso: null,
      ingresos: [],

      sumaCompras: 0,
      charVenta: null,
      charCosto: null,
      charUtilidad: null,

      ventas: [],

      fechaInicio: formatDateLocal(firstDayOfMonth),
      fechaFin: formatDateLocal(lastDayOfMonth),
    };
  },
  watch: {
    async tipoPeriodo(newValue) {
      try {
        this.isLoading = true; // Activar loading
        this.obtenerDiaFechaActual();
        await this.fetchData();
      } catch (error) {
        console.error("Error al cambiar periodo:", error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "Error al actualizar el periodo",
          life: 3000,
        });
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },
  },

  methods: {
    // Método para validar y asegurar fechas válidas
    validarFechas() {
      const today = new Date();
      
      // Si fechaInicio es nula, vacía o inválida
      if (!this.fechaInicio || this.fechaInicio === '' || isNaN(new Date(this.fechaInicio).getTime())) {
        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        this.fechaInicio = this.formatDate(firstDayOfMonth);
        console.log('📅 fechaInicio asignada por defecto:', this.fechaInicio);
      }
      
      // Si fechaFin es nula, vacía o inválida
      if (!this.fechaFin || this.fechaFin === '' || isNaN(new Date(this.fechaFin).getTime())) {
        this.fechaFin = this.formatDate(today);
        console.log('📅 fechaFin asignada por defecto:', this.fechaFin);
      }
      
      // Validar que fechaInicio no sea mayor que fechaFin
      if (new Date(this.fechaInicio) > new Date(this.fechaFin)) {
        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        this.fechaInicio = this.formatDate(firstDayOfMonth);
        this.fechaFin = this.formatDate(today);
        console.log('📅 Fechas corregidas: inicio no puede ser mayor que fin');
      }
    },

    async datosConfiguracion() {
      try {
        this.isLoading = true; // Activar loading
        const response = await axios.get("/configuracion");
        const respuesta = response.data;
        this.monedaPrincipal = [
          respuesta.configuracionTrabajo.valor_moneda_principal,
          respuesta.configuracionTrabajo.simbolo_moneda_principal,
        ];
      } catch (error) {
        console.error("Error al cargar configuración:", error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "Error al cargar la configuración",
          life: 3000,
        });
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    formatDate(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");
      return `${year}-${month}-${day}`;
    },
    obtenerDiaFechaActual() {
      const fechaActual = new Date();
      if (this.tipoPeriodo === "Mes") {
        this.fechaInicio = new Date(
          fechaActual.getFullYear(),
          fechaActual.getMonth(),
          1
        )
          .toISOString()
          .split("T")[0];
        this.fechaFin = new Date(
          fechaActual.getFullYear(),
          fechaActual.getMonth() + 1,
          0
        )
          .toISOString()
          .split("T")[0];
      } else if (this.tipoPeriodo === "Año") {
        this.fechaInicio = new Date(fechaActual.getFullYear(), 0, 1)
          .toISOString()
          .split("T")[0];
        this.fechaFin = new Date(fechaActual.getFullYear(), 11, 31)
          .toISOString()
          .split("T")[0];
      }
      // Validar fechas después de asignarlas
      this.validarFechas();
    },
    async fetchData() {
      try {
        this.isLoading = true; // Activar loading
        
        // Validar fechas antes de hacer la petición
        this.validarFechas();
        
        const response = await axios.get("/dashboard", {
          params: {
            fecha_inicio: this.fechaInicio,
            fecha_fin: this.fechaFin,
          },
        });

        const respuesta = response.data;
        this.idrol = respuesta.idrol;

        this.data = (respuesta.data || []).map((item) => {
          return {
            ...item,
            total_ventas: item.total_ventas * parseFloat(this.monedaPrincipal[0]),
            total_costo: item.total_costo * parseFloat(this.monedaPrincipal[0]),
            utilidad: item.utilidad * parseFloat(this.monedaPrincipal[0]),
          };
        });
        this.sumaVentas = this.data.reduce(
          (total, item) => total + parseFloat(item.total_ventas || 0),
          0
        );

        this.sumaCostos = this.data.reduce(
          (total, item) => total + parseFloat(item.total_costo || 0),
          0
        );

        this.sumaUtilidad = this.data.reduce(
          (total, item) => total + parseFloat(item.utilidad || 0),
          0
        );
        await this.$nextTick();
        await Promise.all([
          this.loadVentas(),
          this.loadCostos(),
          this.loadUtilidad()
        ]);
      } catch (error) {
        console.error("Error al obtener datos:", error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "Error al cargar los datos del dashboard",
          life: 3000,
        });
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },

    loadChart(tipo, data, chartElement, color) {
      try {
        const arrayMes = [];
        const arrayTotal = [];

        data.forEach((item) => {
          arrayMes.push(item.mes);

          if (tipo === "ventas") {
            arrayTotal.push(item.total_ventas);
          } else if (tipo === "costos") {
            arrayTotal.push(item.total_costo);
          } else if (tipo === "utilidad") {
            arrayTotal.push(item.utilidad);
          }
        });

        const nombresMeses = [
          "Enero","Febrero","Marzo","Abril","Mayo","Junio",
          "Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"
        ];

        const meses = arrayMes.map((numero) => nombresMeses[numero - 1]);

        // 🔥 SUMATORIAS
        if (tipo === "ventas") {
          this.sumaVentas = arrayTotal.reduce((t, v) => t + parseFloat(v), 0);
        } else if (tipo === "costos") {
          this.sumaCostos = arrayTotal.reduce((t, v) => t + parseFloat(v), 0);
        } else if (tipo === "utilidad") {
          this.sumaUtilidad = arrayTotal.reduce((t, v) => t + parseFloat(v), 0);
        }

        return new Chart(chartElement, {
          type: "bar",
          data: {
            labels: meses,
            datasets: [
              {
                label: "Total de " + tipo,
                data: arrayTotal,
                backgroundColor: color,
                borderWidth: 1,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              yAxes: [{ ticks: { beginAtZero: true } }],
            },
          },
        });

      } catch (error) {
        console.error(`Error en gráfico ${tipo}:`, error);
        throw error;
      }
    },
    async loadCostos() {
      const canvas = document.getElementById("costos");

      if (!canvas) {
        console.warn("Canvas costos no existe aún");
        return;
      }

      if (this.charCosto) this.charCosto.destroy();

      this.charCosto = this.loadChart(
        "costos",
        this.data,
        canvas.getContext("2d"),
        "#f39c12"
      );
    },
    async loadVentas() {
      const canvas = document.getElementById("ventas");

      if (!canvas) return;

      if (this.charVenta) this.charVenta.destroy();

      this.charVenta = this.loadChart(
        "ventas",
        this.data,
        canvas.getContext("2d"),
        "rgb(54, 162, 235)"
      );
    },
    async loadUtilidad() {
      console.log("Cargando utilidad...");
      const canvas = document.getElementById("utilidad");

      if (!canvas) return;

      if (this.charUtilidad) this.charUtilidad.destroy();

      this.charUtilidad = this.loadChart(
        "utilidad",
        this.data,
        canvas.getContext("2d"),
        "#2ecc71"
      );
    }
  },
  async mounted() {
    try {
      this.isLoading = true; // Activar loading
      
      // Validar fechas al iniciar el dashboard
      this.validarFechas();
      console.log('📊 Dashboard iniciado con fechas:', this.fechaInicio, '-', this.fechaFin);
      
      await Promise.all([this.datosConfiguracion(), this.fetchData()]);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
      this.$toast.add({
        severity: "error",
        summary: "Error",
        detail: "Error al cargar los datos iniciales",
        life: 3000,
      });
    } finally {
      this.isLoading = false; // Desactivar loading
    }
  },
};
</script>
<style scoped>
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
