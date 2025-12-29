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
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-usd"></i>
                    FINANZAS</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=16" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-money"></i> Apertura/Cierre Caja</a>
                    </li>
                </ul>
            </li>
            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-shopping-cart" aria-hidden="true"></i>
                    VENTAS</a>
                <ul class="nav-dropdown-items">
                     <li @click="menu=0" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-shopping-cart"></i> Vender</a>
                    <li @click="menu=55" class="nav-item">
                    <li @click="menu=6" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-users" style="font-size: 16px;"></i> Mis Clientes</a>
                    </li>
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
                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-file-text"></i> INVENTARIO</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=25" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-cubes" style="font-size: 19px;"></i> Mi Inventario</a>
                    </li>
                    <li @click="menu=30" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-line-chart" style="font-size: 19px;"></i> Traspasos</a>
                    </li>
                    <li @click="menu=73" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-line-chart" style="font-size: 19px;"></i> Ajuste de Inventario</a>
                    </li>
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

            <!--
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

