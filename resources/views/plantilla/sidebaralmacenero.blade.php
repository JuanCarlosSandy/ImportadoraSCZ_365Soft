<div class="sidebar">
    
    <nav class="sidebar-nav">
        <ul class="nav">

            <li class="nav-title">
                Operaciones
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-shopping-bag" aria-hidden="true"></i>
                    COMPRAS</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=0" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-shopping-basket"></i> Comprar</a>
                    </li>
                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-file-text"></i> ALMACEN</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=1" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-building"></i> Mis Almacenes</a>
                    </li>
                    <li @click="menu=2" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-cubes"></i> Mi Inventario</a>
                    </li>
                    <li @click="menu=3" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-cog"></i> Ajuste de Inventario</a>
                    </li>
                    <li @click="menu=4" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-exchange"></i> Traspasos</a>
                    </li>
                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-tags"></i> PRODUCTOS</a>
                <ul class="nav-dropdown-items">
                    <li @click="menu=5" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-product-hunt"></i> Mis Productos</a>
                    </li>

                    <li @click="menu=6" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-th-list"></i> Categoria</a>
                    </li>
                    
                    <li @click="menu=7" class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-globe"></i> Mis Proveedores</a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-book"></i> REPORTE INVENTARIO</a>
                <ul class="nav-dropdown-items">

                    <li @click="menu=8" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list"></i> Productos Bajo Stock</a>
                    </li>

                    <li @click="menu=9" class="nav-item">
                        <a class="nav-link" href="#"><i class="icon-list"></i> Inventario Fisico</a>
                    </li>
                </ul>
            </li>

    </nav>
   
</div>

