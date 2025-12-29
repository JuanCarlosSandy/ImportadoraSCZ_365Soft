<div class="sidebar">
    
    <nav class="sidebar-nav">
        <ul class="nav">
          
            <li @click="menu=5" class="nav-item">
                <a class="nav-link active" href="#"><i class="fa fa-dashboard"></i> PRINCIPAL</a>
            </li>
            <li class="nav-title">
                Operaciones
            </li>


            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-briefcase"></i> EMPRESA </a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=13" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-building" style="font-size: 19px;"></i> Info Empresa</a>
                    </li>
                    <li @click="menu=14" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-sitemap" style="font-size: 19px;"></i> Mis Sucursales</a>
                    </li>
                    <!--<li @click="menu=41" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px;"></i> Puntos de
                            Venta</a>
                    </li>-->

                </ul>
            </li>
            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-usd"></i>
                    FINANZAS</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=16" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-money"></i> Apertura/Cierre Caja</a>
                    </li>
                    <!--
                    <li @click="menu=65" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px;"></i> Bancos</a>
                    </li>-->

                  <!--  <li @click="menu=15" class="nav-item">
    <a class="nav-link" href="#"><i class="fas fa-money-bill-alt"></i> Moneda</a>
</li>-->
                </ul>
            </li>
            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-shopping-cart" aria-hidden="true"></i>
                    VENTAS</a>
                <ul class="nav-dropdown-items">
                     <li @click="menu=0" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-shopping-cart"></i> Vender</a>
                    <li @click="menu=55" class="nav-item">
                    <li @click="menu=75" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-credit-card"></i> Cobros</a>
                    </li>
                    <!-- <li @click="menu=40" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px;"></i> Registro
                            Ventas</a>
                    </li> 
                    <li @click="menu=53" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-credit-card" style="font-size: 16px;"></i> Ventas a credito</a>
                    </li>-->
                    <li @click="menu=6" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-users" style="font-size: 16px;"></i> Mis Clientes</a>
                    </li>
                    <!--<li @click="menu=23" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-file-text-o" style="font-size: 16px;"></i> Cotizaciones</a>
                    </li>-->
                </ul>
            </li>
            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-shopping-bag" aria-hidden="true"></i>
                    COMPRAS</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=3" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px;"></i> Comprar</a>
                    </li>
                    <li @click="menu=4" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px;"></i> Mis Proveedores</a>
                    </li>
                    <!-- <li @click="menu=22" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px;"></i> Pedidos a
                            Prov.</a>
                    </li> 
                    <li @click="menu=70" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px"></i> Comprar Nueva Vista</a>
                    </li>
                    <li @click="menu=72" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px"></i> Compras a Credito</a>
                    </li>-->
                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-file-text"></i> ALMACEN</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=24" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-building" style="font-size: 19px;"></i> Mis Almacenes</a>
                    </li>
                    <li @click="menu=25" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-cubes" style="font-size: 19px;"></i> Mi Inventario</a>
                    </li>
                    <li @click="menu=73" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-line-chart" style="font-size: 19px;"></i> Ajuste de Inventario</a>
                    </li>
                    <!--<li @click="menu=30" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-line-chart" style="font-size: 19px;"></i> Traspasos</a>
                    </li>-->
                </ul>
            </li>
            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-tags"></i> PRODUCTOS</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=71" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px;"></i> Mis Productos</a>
                    </li>

                    <li @click="menu=19" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-tags" style="font-size: 19px;"></i> Categoria</a>
                    </li>
                </ul>
            </li>



            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-lock"></i> ACCESO</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=7" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-user" style="font-size: 19px;"></i> Mis Usuarios</a>
                    </li>
                    <li @click="menu=8" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-id-badge" style="font-size: 19px;"></i> Roles</a>
                    </li>
                </ul>
            </li>
            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-line-chart"></i> REPORTE INVENTARIO</a>
                <ul class="nav-dropdown-items">

                    <!--<li @click="menu=49" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px;"></i> Inventarios</a>
                    </li>
                    <li @click="menu=51" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-file-text-o" style="font-size: 19px;"></i> Kardex Fisico Valorado</a>
                    </li>
                    <li @click="menu=52" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-file-o" style="font-size: 19px;"></i> Kardex Fisico</a>
                    </li>-->
                    <li @click="menu=58" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-list-alt" style="font-size: 19px;"></i> Resumen de Inventario</a>
                    </li>
                    <!--<li @click="menu=60" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-exchange" style="font-size: 19px;"></i> Detallado Fisico de Movimientos</a>
                    </li>
                    <li @click="menu=63" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-archive" style="font-size: 19px;"></i> Inventario Fisico Valorado</a>
                    </li>
                    <li @click="menu=64" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-cubes" style="font-size: 19px;"></i> Inventario Fisico</a>
                    </li>-->
                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-line-chart"></i> REPORTE VENTAS</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=62" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-calendar-check-o" style="font-size: 19px;"></i> Ventas Diarias y Mensuales</a>
                    </li>
                </ul>
            </li>
            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-line-chart"></i> REPORTE CLIENTES</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=74" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-calendar-check-o" style="font-size: 19px;"></i> Clientes Deudas</a>
                    </li>
                </ul>
            </li>
            
            <!--<li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-line-chart"></i> REPORTE CLIENTES</a>
                <ul class="nav-dropdown-items">

                    <li @click="menu=10" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-users" style="font-size: 19px;"></i> Reporte Usuarios</a>
                    </li>
                    <li @click="menu=50" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-user-circle" style="font-size: 19px;"></i> Resumen de clientes</a>
                    </li>
                    
                    <li @click="menu=56" class="nav-item">
                                <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px;"></i> Kardex Clientes Detallado Global</a>
                            </li>

                            <li @click="menu=59" class="nav-item">
                                <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px;"></i> Kardex Clientes Resumen Global</a>
                            </li>

                            <li @click="menu=61" class="nav-item">
                                <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px;"></i> Detalle - Recibo Cliente Por Documento</a>
                            </li>

                            <li @click="menu=68" class="nav-item">
                                <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px;"></i> Resumen Ventas Y Cobranzas</a>
                            </li>

                        </ul>
                    </li>
                    
                </ul>

                <li class="nav-item nav-dropdown">
                    <a class="nav-link nav-dropdown-toggle" href="#"><i class="icon-info"></i>SIAT</a>
                    <ul class="nav-dropdown-items">
                        <li @click="menu=31" class="nav-item">
                            <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px;"></i>Sinc. Actividades</a>
                        </li>
                        <li @click="menu=34" class="nav-item">
                            <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px;"></i>Sinc. Servicios</a>
                        </li>
                        <li @click="menu=37" class="nav-item">
                            <a class="nav-link" href="#"><i class="icon-list" style="font-size: 11px;"></i>Sinc. Unidad Medida</a>
                        </li>
                    </ul>-->
    </nav>
   
</div>

