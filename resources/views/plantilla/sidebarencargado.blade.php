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
                        <a class="nav-link" href="#"><i class="fa fa-building"></i> Info Empresa</a>
                    </li>
                    <li @click="menu=14" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-sitemap"></i> Mis Sucursales</a>
                    </li>
                   <!--<li @click="menu=41" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list"></i> Puntos de
                            Venta</a>
                    </li>-->

                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-usd"></i>
                    FINANZAS</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=16" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-money"></i> Mi Cartera</a>
                    </li>
                    <!--<li @click="menu=65" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-university"></i> Bancos</a>
                    </li>-->
                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-shopping-cart" aria-hidden="true"></i>
                    VENTAS</a>
                <ul class="nav-dropdown-items">
                     <li @click="menu=0" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-cart-plus"></i> Vender</a>
                    </li>
                    <!--
                    <li @click="menu=75" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-credit-card"></i> Deudores/Acreedores</a>
                    </li>
                    <li @click="menu=74" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-calendar-check-o"></i> Reporte Deudores</a>
                    </li>
-->
                    <li @click="menu=6" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-users"></i> Mis Clientes</a>
                    </li>
                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-shopping-bag" aria-hidden="true"></i>
                    COMPRAS</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=3" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-shopping-basket"></i> Comprar</a>
                    </li>
                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-file-text"></i> ALMACEN</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=24" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-building"></i> Mis Almacenes</a>
                    </li>
                    <li @click="menu=25" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-cubes"></i> Mi Inventario</a>
                    </li>
                    <li @click="menu=73" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-cog"></i> Ajuste de Inventario</a>
                    </li>
                    <li @click="menu=30" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-exchange"></i> Traspasos</a>
                    </li>
                    <li @click="menu=101" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-line-chart"></i> Control de Inventario</a>
                    </li>
                </ul>
            </li>
            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-tags"></i> PRODUCTOS</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=71" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-product-hunt"></i> Mis Productos</a>
                    </li>

                    <li @click="menu=19" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-th-list"></i> Categoria</a>
                    </li>
                    
                    <li @click="menu=4" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-globe"></i> Mis Proveedores</a>
                    </li>
                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-h-square"></i> OFERTAR/COMBOS</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=76" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list"></i> Mis Ofertas</a>
                    </li>
                    <!--<li @click="menu=75" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list"></i> Categoria Servicios</a>
                    </li>-->
                </ul>
            </li>


            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-lock"></i> ACCESO</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=7" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-user"></i> Mis Usuarios</a>
                    </li>
                    <li @click="menu=8" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-id-badge"></i> Roles</a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-book"></i> REPORTE INVENTARIO</a>
                <ul class="nav-dropdown-items">
                    <!--<li @click="menu=100" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-list-alt"></i> Kardex Fisico de Inventario</a>
                    </li>-->
                    <li @click="menu=92" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list"></i> Productos Bajo Stock</a>
                    </li>
                    <li @click="menu=63" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list"></i> Inventario Fisico Valorado</a>
                    </li>
                    <li @click="menu=64" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list"></i> Inventario Fisico</a>
                    </li>
                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-line-chart"></i> REPORTE VENTAS</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=62" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list"></i> Ventas Diarias y Mensuales</a>
                    </li>
                    <li @click="menu=102" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list"></i> Productos Vendidos</a>
                    </li>
                </ul>
            </li>    
            
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
                    </ul>
            </li>
    </nav>
   
</div>

