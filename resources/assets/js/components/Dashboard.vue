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

      <!-- Componentes de precios actualizados -->
      <div class="row mb-4">
        <div class="col-12">
          <newprecioarti :fechaInicio="fechaInicio" :fechaFin="fechaFin" :moneda="monedaPrincipal" />
        </div>
      </div>

      <!-- Cuadros resumen: Ventas, Gastos, Ganancia -->
      <div class="row justify-content-between mb-5">
        <!-- Ventas siempre visible -->
        <square-item :icono="'fa fa-usd'" :titulo="'Ventas'" :moneda="monedaPrincipal[1]"
          :cantidad="sumaVentas.toFixed(2)" :fondoDegradado="'linear-gradient(35deg, #028bd2, #6dd3dd)'" />

        <!-- Gastos solo para rol 4 -->
        <square-item v-if="idrol == 4" :icono="'fa fa-shopping-cart'" :titulo="'Gastos'" :moneda="monedaPrincipal[1]"
          :cantidad="sumaCompras.toFixed(2)" :fondoDegradado="'linear-gradient(35deg, #f67318, #f9ca38)'" />

        <!-- Ganancias solo para rol 4 -->
        <square-item v-if="idrol == 4" :icono="'fa fa-angle-double-up'" :titulo="'Ganancias'"
          :moneda="monedaPrincipal[1]" :cantidad="(sumaVentas - sumaCompras).toFixed(2)"
          :fondoDegradado="'linear-gradient(35deg, #3b9c3f, #41d445)'" />
      </div>
      <hr class="my-4" />

      <!-- Gráficas de ventas y compras -->
      <div class="row mb-5">
        <div class="col-md-6" v-if="idrol == 4">
          <div class="card card-chart shadow-sm">
            <div class="card-header bg-light">
              <h5 class="mb-0">Compras</h5>
            </div>
            <div class="card-body">
              <canvas id="ingresos"></canvas>
            </div>
            <div class="card-footer small text-muted">
              Compras de los últimos meses.
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

      <!-- Componentes Top -->
      <div class="row mb-5">
        <div class="col-md-6 mb-4">
          <TopArticulos :fechaInicio="fechaInicio" :fechaFin="fechaFin" />
        </div>
        <div class="col-md-6 mb-4">
          <TopClientes :fechaInicio="fechaInicio" :fechaFin="fechaFin" :moneda="monedaPrincipal" />
        </div>
        <div class="col-md-12">
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

      charIngreso: null,
      ingresos: [],

      sumaCompras: 0,
      charVenta: null,
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

        this.ingresos = respuesta.ingresos.map((item) => {
          item.total *= parseFloat(this.monedaPrincipal[0]);
          return item;
        });

        this.ventas = respuesta.ventas.map((item) => {
          item.total *= parseFloat(this.monedaPrincipal[0]);
          return item;
        });

        await Promise.all([this.loadIngresos(), this.loadVentas()]);
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
          arrayTotal.push(item.total);
        });

        const nombresMeses = [
          "Enero",
          "Febrero",
          "Marzo",
          "Abril",
          "Mayo",
          "Junio",
          "Julio",
          "Agosto",
          "Septiembre",
          "Octubre",
          "Noviembre",
          "Diciembre",
        ];
        const meses = arrayMes.map((numero) => nombresMeses[numero - 1]);

        if (tipo == "compras") {
          this.sumaCompras = arrayTotal.reduce(
            (total, valor) => total + parseFloat(valor),
            0
          );
        } else {
          this.sumaVentas = arrayTotal.reduce(
            (total, valor) => total + parseFloat(valor),
            0
          );
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
                borderColor: "rgba(54, 162, 235, 0.2)",
                borderWidth: 1,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              yAxes: [
                {
                  ticks: {
                    beginAtZero: true,
                  },
                },
              ],
            },
          },
        });
      } catch (error) {
        console.error(`Error al cargar gráfico de ${tipo}:`, error);
        throw error;
      }
    },
    async loadIngresos() {
      try {
        this.isLoading = true; // Activar loading
        if (this.charIngreso) {
          this.charIngreso.destroy();
        }
        this.charIngreso = this.loadChart(
          "compras",
          this.ingresos,
          document.getElementById("ingresos").getContext("2d"),
          "#fec71f"
        );
      } catch (error) {
        console.error("Error al cargar gráfico de ingresos:", error);
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    async loadVentas() {
      try {
        this.isLoading = true; // Activar loading
        if (this.charVenta) {
          this.charVenta.destroy();
        }
        this.charVenta = this.loadChart(
          "ventas",
          this.ventas,
          document.getElementById("ventas").getContext("2d"),
          "rgb(54, 162, 235)"
        );
      } catch (error) {
        console.error("Error al cargar gráfico de ventas:", error);
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
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
