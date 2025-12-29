@extends('principal')
@section('contenido')

@if(Auth::check())
@if (Auth::user()->idrol == 1)
<template v-if="menu==0">
    <venta></venta>
</template>

<template v-if="menu==3">
    <ingreso></ingreso>
</template>

<template v-if="menu==4">
    <proveedor></proveedor>
</template>

<template v-if="menu==5">
    <dashboard></dashboard>
</template>

<template v-if="menu==6">
    <cliente></cliente>
</template>

<template v-if="menu==7">
    <user></user>
</template>

<template v-if="menu==8">
    <rol></rol>
</template>

<template v-if="menu==9">
    <consultaingreso></consultaingreso>
</template>

<template v-if="menu==10">
    <consultaventa></consultaventa>
</template>

<template v-if="menu==11">
    <h1>Ayuda</h1>
</template>

<template v-if="menu==12">
    <h1>Acerca de</h1>
</template>

<template v-if="menu==13">
    <empresa></empresa>
</template>

<template v-if="menu==14">
    <sucursal></sucursal>
</template>

<template v-if="menu==15">
    <moneda></moneda>
</template>

<template v-if="menu==16">
    <caja></caja>
</template>

<template v-if="menu==17">
    <editarperfil></editarperfil>
</template>

<template v-if="menu==21">
    <configuracion></configuracion>
</template>


<template v-if="menu==22">
    <cotizacioncompras></cotizacioncompras>
</template>

<template v-if="menu==23">
    <cotizacionventas></cotizacionventas>
</template>
<template v-if="menu==24">
    <almacenes></almacenes>
</template>
<template v-if="menu==25">
    <inventarios></inventarios>
</template>
<template v-if="menu==26">
    <grupos></grupos>
</template>
<!--<template v-if="menu==27">
    <medidas></medidas>
</template>-->
<template v-if="menu==28">
    <monitoreoproductos></monitoreoproductos>
</template>
<template v-if="menu==29">
    <factura></factura>
</template>
<template v-if="menu==30">
    <traspasoproducto></traspasoproducto>
</template>
<template v-if="menu==31">
    <sincronizacionactividades></sincronizacionactividades>
</template>
<template v-if="menu==32">
    <sincronizarparametricatiposfactura></sincronizarparametricatiposfactura>
</template>
<template v-if="menu==33">
    <sincronizarlistaleyendasfactura></sincronizarlistaleyendasfactura>
</template>
<template v-if="menu==34">
    <sincronizarproductosservicios></sincronizarproductosservicios>
</template>
<template v-if="menu==35">
    <sincronizarmotivoanulacion></sincronizarmotivoanulacion>
</template>
<template v-if="menu==36">
    <sincronizareventossignificativos></sincronizareventossignificativos>
</template>
<template v-if="menu==37">
    <sincronizarunidadmedida></sincronizarunidadmedida>
</template>
<template v-if="menu==38">
    <eventossignificativos></eventossignificativos>
</template>
<template v-if="menu==39">
    <facturafueralinea></facturafueralinea>
</template>
<template v-if="menu==40">
    <rolventa></rolventa>
</template>

<template v-if="menu==41">
    <puntoventa></puntoventa>
</template>
<template v-if="menu==42">
    <ofertas></ofertas>
</template>

<template v-if="menu==43">
    <kits></kits>
</template>
<template v-if="menu==45">
    <reporteventas></reporteventas>
</template>
<template v-if="menu==48">
    <ventasinstitucionales></ventasinstitucionales>
</template>
<template v-if="menu==49">
    <reporteinventario></reporteinventario>
</template>
<template v-if="menu==50">
    <resumenclientes></resumenclientes>
</template>
<template v-if="menu==51">
    <reportekardexfisicovalorado></reportekardexfisicovalorado>
</template>
<template v-if="menu==52">
    <reportekardexfisico></reportekardexfisico>
</template>

<template v-if="menu==53">
    <ventacredito></ventacredito>
</template>

<template v-if="menu==55">
    <resumenventaxdocumento></resumenventaxdocumento>
</template>

<template v-if="menu==56">
    <kardexclientesdetalladoglobal></kardexclientesdetalladoglobal>
</template>

<template v-if="menu==57">
    <resumenventaxproducto></resumenventaxproducto>
</template>

<template v-if="menu==58">
    <resumenfisico></resumenfisico>
</template>
<template v-if="menu==59">
    <kardexclientesresumenglobal></kardexclientesresumenglobal>
</template>

<template v-if="menu==60">
    <reportefisicomovimientos></reportefisicomovimientos>
</template>

<template v-if="menu==62">
    <reporteventasdetallado></reporteventasdetallado>
</template>

<template v-if="menu==63">
    <reporteinventariofisicovalorado></reporteinventariofisicovalorado>
</template>

<template v-if="menu==64">
    <reporteinventariofisico></reporteinventariofisico>
</template>


<template v-if="menu==65">
    <bancos></bancos>
</template>

<template v-if="menu==66">
    <transferencias></transferencias>
</template>

<template v-if="menu==67">
    <mayoreo></mayoreo>
</template>

<template v-if="menu==68">
    <resumenventasycobranz></resumenventasycobranz>a
</template>

<template v-if="menu==61">
    <reciboclientexdocumento></reciboclientexdocumento>
</template>

<template v-if="menu==70">
    <nuevacompra></nuevacompra>
</template>

<template v-if="menu==72">
    <comprascredito></comprascredito>
</template>

<template v-if="menu==27">
    <medidasnewview></medidasnewview>
</template>
<template v-if="menu==20">
    <industrianewview></industrianewview>
</template>
<template v-if="menu==19">
    <lineanewview></lineanewview>
</template>
<template v-if="menu==18">
    <marcanewview></marcanewview>
</template>
<template v-if="menu==71">
    <articulonewview></articulonewview>
</template>
<template v-if="menu==73">
    <ajusteinventarios></ajusteinventarios>
</template>
<template v-if="menu==74">
    <reportedeudores></reportedeudores>
</template>
@elseif (Auth::user()->idrol == 2)
<template v-if="menu==0">
    <venta></venta>
</template>

<template v-if="menu==3">
    <ingreso></ingreso>
</template>

<template v-if="menu==4">
    <proveedor></proveedor>
</template>

<template v-if="menu==5">
    <dashboard></dashboard>
</template>

<template v-if="menu==6">
    <cliente></cliente>
</template>

<template v-if="menu==7">
    <user></user>
</template>

<template v-if="menu==8">
    <rol></rol>
</template>

<template v-if="menu==9">
    <consultaingreso></consultaingreso>
</template>

<template v-if="menu==10">
    <consultaventa></consultaventa>
</template>

<template v-if="menu==11">
    <h1>Ayuda</h1>
</template>

<template v-if="menu==12">
    <h1>Acerca de</h1>
</template>

<template v-if="menu==13">
    <empresa></empresa>
</template>

<template v-if="menu==14">
    <sucursal></sucursal>
</template>

<template v-if="menu==15">
    <moneda></moneda>
</template>

<template v-if="menu==16">
    <caja></caja>
</template>

<template v-if="menu==17">
    <editarperfil></editarperfil>
</template>

<template v-if="menu==21">
    <configuracion></configuracion>
</template>


<template v-if="menu==22">
    <cotizacioncompras></cotizacioncompras>
</template>

<template v-if="menu==23">
    <cotizacionventas></cotizacionventas>
</template>
<template v-if="menu==24">
    <almacenes></almacenes>
</template>
<template v-if="menu==25">
    <inventarios></inventarios>
</template>
<template v-if="menu==26">
    <grupos></grupos>
</template>
<!--<template v-if="menu==27">
    <medidas></medidas>
</template>-->
<template v-if="menu==28">
    <monitoreoproductos></monitoreoproductos>
</template>
<template v-if="menu==29">
    <factura></factura>
</template>
<template v-if="menu==30">
    <traspasoproducto></traspasoproducto>
</template>
<template v-if="menu==31">
    <sincronizacionactividades></sincronizacionactividades>
</template>
<template v-if="menu==32">
    <sincronizarparametricatiposfactura></sincronizarparametricatiposfactura>
</template>
<template v-if="menu==33">
    <sincronizarlistaleyendasfactura></sincronizarlistaleyendasfactura>
</template>
<template v-if="menu==34">
    <sincronizarproductosservicios></sincronizarproductosservicios>
</template>
<template v-if="menu==35">
    <sincronizarmotivoanulacion></sincronizarmotivoanulacion>
</template>
<template v-if="menu==36">
    <sincronizareventossignificativos></sincronizareventossignificativos>
</template>
<template v-if="menu==37">
    <sincronizarunidadmedida></sincronizarunidadmedida>
</template>
<template v-if="menu==38">
    <eventossignificativos></eventossignificativos>
</template>
<template v-if="menu==39">
    <facturafueralinea></facturafueralinea>
</template>
<template v-if="menu==40">
    <rolventa></rolventa>
</template>

<template v-if="menu==41">
    <puntoventa></puntoventa>
</template>
<template v-if="menu==42">
    <ofertas></ofertas>
</template>

<template v-if="menu==43">
    <kits></kits>
</template>
<template v-if="menu==45">
    <reporteventas></reporteventas>
</template>
<template v-if="menu==48">
    <ventasinstitucionales></ventasinstitucionales>
</template>
<template v-if="menu==49">
    <reporteinventario></reporteinventario>
</template>
<template v-if="menu==50">
    <resumenclientes></resumenclientes>
</template>
<template v-if="menu==51">
    <reportekardexfisicovalorado></reportekardexfisicovalorado>
</template>
<template v-if="menu==52">
    <reportekardexfisico></reportekardexfisico>
</template>

<template v-if="menu==53">
    <ventacredito></ventacredito>
</template>

<template v-if="menu==55">
    <resumenventaxdocumento></resumenventaxdocumento>
</template>

<template v-if="menu==56">
    <kardexclientesdetalladoglobal></kardexclientesdetalladoglobal>
</template>

<template v-if="menu==57">
    <resumenventaxproducto></resumenventaxproducto>
</template>

<template v-if="menu==58">
    <resumenfisico></resumenfisico>
</template>
<template v-if="menu==59">
    <kardexclientesresumenglobal></kardexclientesresumenglobal>
</template>

<template v-if="menu==60">
    <reportefisicomovimientos></reportefisicomovimientos>
</template>

<template v-if="menu==62">
    <reporteventasdetallado></reporteventasdetallado>
</template>

<template v-if="menu==63">
    <reporteinventariofisicovalorado></reporteinventariofisicovalorado>
</template>

<template v-if="menu==64">
    <reporteinventariofisico></reporteinventariofisico>
</template>


<template v-if="menu==65">
    <bancos></bancos>
</template>

<template v-if="menu==66">
    <transferencias></transferencias>
</template>

<template v-if="menu==67">
    <mayoreo></mayoreo>
</template>

<template v-if="menu==68">
    <resumenventasycobranz></resumenventasycobranz>a
</template>

<template v-if="menu==61">
    <reciboclientexdocumento></reciboclientexdocumento>
</template>

<template v-if="menu==70">
    <nuevacompra></nuevacompra>
</template>

<template v-if="menu==72">
    <comprascredito></comprascredito>
</template>

<template v-if="menu==27">
    <medidasnewview></medidasnewview>
</template>
<template v-if="menu==20">
    <industrianewview></industrianewview>
</template>
<template v-if="menu==19">
    <lineanewview></lineanewview>
</template>
<template v-if="menu==18">
    <marcanewview></marcanewview>
</template>
<template v-if="menu==71">
    <articulonewview></articulonewview>
</template>
<template v-if="menu==73">
    <ajusteinventarios></ajusteinventarios>
</template>
@elseif (Auth::user()->idrol == 3)
<template v-if="menu==0">
    <dashboard></dashboard>
</template>

<template v-if="menu==3">
    <ingreso></ingreso>
</template>

<template v-if="menu==4">
    <proveedor></proveedor>
</template>
<template v-if="menu==9">
    <consultaingreso></consultaingreso>
</template>
<template v-if="menu==11">
    <h1>Ayuda</h1>
</template>

<template v-if="menu==12">
    <h1>Acerca de</h1>
</template>

<template v-if="menu==17">
    <editarperfil></editarperfil>
</template>

@elseif (Auth::user()->idrol == 4)
<template v-if="menu==0">
    <venta></venta>
</template>

<template v-if="menu==3">
    <ingreso></ingreso>
</template>

<template v-if="menu==4">
    <proveedor></proveedor>
</template>

<template v-if="menu==5">
    <dashboard></dashboard>
</template>

<template v-if="menu==6">
    <cliente></cliente>
</template>

<template v-if="menu==7">
    <user></user>
</template>

<template v-if="menu==8">
    <rol></rol>
</template>

<template v-if="menu==9">
    <consultaingreso></consultaingreso>
</template>

<template v-if="menu==10">
    <consultaventa></consultaventa>
</template>

<template v-if="menu==11">
    <h1>Ayuda</h1>
</template>

<template v-if="menu==12">
    <h1>Acerca de</h1>
</template>

<template v-if="menu==13">
    <empresa></empresa>
</template>

<template v-if="menu==14">
    <sucursal></sucursal>
</template>

<template v-if="menu==15">
    <moneda></moneda>
</template>

<template v-if="menu==16">
    <caja></caja>
</template>

<template v-if="menu==17">
    <editarperfil></editarperfil>
</template>

<template v-if="menu==21">
    <configuracion></configuracion>
</template>

<template v-if="menu==22">
    <cotizacioncompras></cotizacioncompras>
</template>

<template v-if="menu==23">
    <cotizacionventas></cotizacionventas>
</template>
<template v-if="menu==24">
    <almacenes></almacenes>
</template>

<template v-if="menu==26">
    <grupos></grupos>
</template>
<!--<template v-if="menu==27">
    <medidas></medidas>
</template>-->
<template v-if="menu==28">
    <monitoreoproductos></monitoreoproductos>
</template>
<template v-if="menu==29">
    <factura></factura>
</template>
<template v-if="menu==25">
    <inventarios></inventarios>
</template>
<template v-if="menu==30">
    <traspasoproducto></traspasoproducto>
</template>
<template v-if="menu==31">
    <sincronizacionactividades></sincronizacionactividades>
</template>
<template v-if="menu==32">
    <sincronizarparametricatiposfactura></sincronizarparametricatiposfactura>
</template>
<template v-if="menu==33">
    <sincronizarlistaleyendasfactura></sincronizarlistaleyendasfactura>
</template>
<template v-if="menu==34">
    <sincronizarproductosservicios></sincronizarproductosservicios>
</template>
<template v-if="menu==35">
    <sincronizarmotivoanulacion></sincronizarmotivoanulacion>
</template>
<template v-if="menu==36">
    <sincronizareventossignificativos></sincronizareventossignificativos>
</template>
<template v-if="menu==37">
    <sincronizarunidadmedida></sincronizarunidadmedida>
</template>
<template v-if="menu==38">
    <eventossignificativos></eventossignificativos>
</template>
<template v-if="menu==39">
    <facturafueralinea></facturafueralinea>
</template>
<template v-if="menu==40">
    <rolventa></rolventa>
</template>

<template v-if="menu==41">
    <puntoventa></puntoventa>
</template>
<template v-if="menu==42">
    <ofertas></ofertas>
</template>

<template v-if="menu==43">
    <kits></kits>
</template>
<template v-if="menu==45">
    <reporteventas></reporteventas>
</template>
<template v-if="menu==48">
    <ventasinstitucionales></ventasinstitucionales>
</template>
<template v-if="menu==49">
    <reporteinventario></reporteinventario>
</template>
<template v-if="menu==50">
    <resumenclientes></resumenclientes>
</template>
<template v-if="menu==51">
    <reportekardexfisicovalorado></reportekardexfisicovalorado>
</template>
<template v-if="menu==52">
    <reportekardexfisico></reportekardexfisico>
</template>

<template v-if="menu==53">
    <ventacredito></ventacredito>
</template>

<template v-if="menu==55">
    <resumenventaxdocumento></resumenventaxdocumento>
</template>

<template v-if="menu==56">
    <kardexclientesdetalladoglobal></kardexclientesdetalladoglobal>
</template>

<template v-if="menu==57">
    <resumenventaxproducto></resumenventaxproducto>
</template>

<template v-if="menu==58">
    <resumenfisico></resumenfisico>
</template>
<template v-if="menu==59">
    <kardexclientesresumenglobal></kardexclientesresumenglobal>
</template>

<template v-if="menu==60">
    <reportefisicomovimientos></reportefisicomovimientos>
</template>

<template v-if="menu==62">
    <reporteventasdetallado></reporteventasdetallado>
</template>

<template v-if="menu==63">
    <reporteinventariofisicovalorado></reporteinventariofisicovalorado>
</template>

<template v-if="menu==64">
    <reporteinventariofisico></reporteinventariofisico>
</template>


<template v-if="menu==65">
    <bancos></bancos>
</template>

<template v-if="menu==66">
    <transferencias></transferencias>
</template>

<template v-if="menu==67">
    <mayoreo></mayoreo>
</template>

<template v-if="menu==68">
    <resumenventasycobranz></resumenventasycobranz>a
</template>

<template v-if="menu==61">
    <reciboclientexdocumento></reciboclientexdocumento>
</template>

<template v-if="menu==70">
    <nuevacompra></nuevacompra>
</template>

<template v-if="menu==72">
    <comprascredito></comprascredito>
</template>

<template v-if="menu==27">
    <medidasnewview></medidasnewview>
</template>
<template v-if="menu==20">
    <industrianewview></industrianewview>
</template>
<template v-if="menu==19">
    <lineanewview></lineanewview>
</template>
<template v-if="menu==18">
    <marcanewview></marcanewview>
</template>
<template v-if="menu==71">
    <articulonewview></articulonewview>
</template>
<template v-if="menu==73">
    <ajusteinventarios></ajusteinventarios>
</template>
<template v-if="menu==74">
    <reportedeudores></reportedeudores>
</template>
<template v-if="menu==75">
    <cobros></cobros>
</template>

@else

@endif

@endif


@endsection