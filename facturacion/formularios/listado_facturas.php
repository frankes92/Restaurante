<?php 
require_once("../apifacturacion/ado/clsEmisor.php");
require_once("../apifacturacion/ado/clsCompartido.php");

$objEmisor = new clsEmisor();
$listado = $objEmisor->consultarListaEmisores();

$objCompartido = new clsCompartido();
$monedas = $objCompartido->listarMonedas();

$comprobantes = $objCompartido->listarComprobantes();

$documentos = $objCompartido->listarTipoDocumento();

?>
<form id="frmVenta" name="frmVenta" submit="return false">
<div class="col-12">
<div class="row">
	<div class="col-3">
		<div class="form-group">
			<label>Facturar Por</label>
			<select class="form-control" id="idemisor" name="idemisor">
				<?php while($fila = $listado->fetch(PDO::FETCH_NAMED)){ ?>
					<option value="<?php echo $fila['id'];?>"><?php echo $fila['razon_social'];?></option>
				<?php } ?>
				<input type="hidden" name="accion" id="accion" value="GUARDAR_VENTA">
			</select>
		</div>
		<div class="form-group">
			<label>Fecha</label>
			<input class="form-control" type="date" name="fecha_emision" id="fecha_emision" value="<?php echo date('Y-m-d');?>" />
		</div>
		<div class="form-group">
			<label>Moneda</label>
			<select class="form-control" type="date" name="moneda" id="moneda">
				<?php while($fila = $monedas->fetch(PDO::FETCH_NAMED)){ ?>
					<option value="<?php echo $fila['codigo'];?>"><?php echo $fila['descripcion'];?></option>
				<?php } ?>
			</select>
		</div>
	</div>
	<div class="col-3">
		<div class="form-group">
			<label>Tipo Comp.</label>
			<select class="form-control" name="tipocomp" id="tipocomp" onchange="ConsultarSerie()">
				<?php while($fila = $comprobantes->fetch(PDO::FETCH_NAMED)){ ?>
					<option value="<?php echo $fila['codigo'];?>"><?php echo $fila['descripcion'];?></option>
				<?php } ?>
			</select>
		</div>	
		<div class="form-group">
			<label>Serie</label>
			<select class="form-control" type="date" name="idserie" id="idserie" onchange="ConsultarCorrelativo()">
				
			</select>
		</div>
		<div class="form-group">
			<label>Correlativo</label>
			<input class="form-control" type="number" name="correlativo" id="correlativo" />
		</div>				
	</div>
	<div class="col-3">
		<div class="form-group">
			<label>Tipo Doc.</label>
			<select class="form-control" name="tipodoc" id="tipodoc">
				<?php while($fila = $documentos->fetch(PDO::FETCH_NAMED)){ ?>
					<option value="<?php echo $fila['codigo'];?>"><?php echo $fila['descripcion'];?></option>
				<?php } ?>
			</select>
		</div>
		<div class="form-group">
			<label>Nro. Doc</label>
			<div class="input-group">
				<input class="form-control" type="text" name="nrodoc" id="nrodoc" />
				<div class="input-group-addon">
					<button type="button" class="btn btn-default" onclick="ObtenerDatosEmpresa()"><li class="fa fa-search"></li></button>	
				</div>
			</div>
		</div>
		<div class="form-group">
			<label>Nombre/Raz. Social</label>
			<input class="form-control" type="text" name="razon_social" id="razon_social" />
		</div>
		<div class="form-group">
			<label>Dirección</label>
			<input class="form-control" type="text" name="direccion" id="direccion" />
		</div>									
	</div>
</div>
<div class="row">
	<div class="col-6">
		<div class="input-group">
				<input class="form-control" type="text" name="producto" id="producto" placeholder="producto..." />
				<div class="input-group-addon">
					<button type="button" class="btn btn-default" onclick="BuscarProducto()"><li class="fa fa-search"></li></button>	
				</div>
			</div>
		<div class="col-12">
			<table class="table table-bordered table-hover">
				<thead>
					<th>Cod</th>
					<th>Nombre</th>
					<th>+</th>
				</thead>
				<tbody id="div_productos">
					
				</tbody>
			</table>
		</div>
	</div>
	<div class="col-6">
		<div class="col-12" id="div_carrito">
		</div>
		<div>
			<button type="button" class="btn btn-primary" onclick="GuardarVenta()">Guardar</button> 
			<button type="button" class="btn btn-danger" onclick="CancelarVenta();">Cancelar</button>
		</div>
	</div>
</div>
</div>
</form>
<script>
	
  function ConsultarSerie(){
      $.ajax({
          method: "POST",
          url: 'apifacturacion/controlador/controlador.php',
          data: {
          	  "accion": "LISTAR_SERIES",
              "tipocomp": $("#tipocomp").val()
            }
      })
      .done(function( text ) {
            json = JSON.parse(text);            
            series = json.series;
            options = '';
            for(i=0;i<series.length;i++){
            	options = options + '<option value="'+series[i].id+'">'+series[i].serie+'</option>';
            }
            $("#idserie").html(options);
            ConsultarCorrelativo();
      });
  }


  ConsultarSerie();


  function ConsultarCorrelativo(){
      $.ajax({
          method: "POST",
          url: 'apifacturacion/controlador/controlador.php',
          data: {
          	  "accion": "OBTENER_CORRELATIVO",
              "idserie": $("#idserie").val()
            }
      })
      .done(function( text ) {
            $("#correlativo").val(text);
      });
  }

  function ObtenerDatosEmpresa(){
  		tipodoc = $("#tipodoc").val();
  		if(tipodoc==1){
  			ObtenerDatosDni();
  		}else if(tipodoc==6){
  			ObtenerDatosRuc();
  		}
  }


  function ObtenerDatosDni(){
  	 urlx = 'https://dni.optimizeperu.com/api/persons/'+$("#nrodoc").val()+'?format=json';
      $.ajax({
          method: "GET",
          url: urlx
      })
      .done(function( json ) {
            $("#razon_social").val(json.name+' '+json.first_name+' '+json.last_name);
      });  		
  }

  function ObtenerDatosRuc(){
  	 urlx = 'https://dni.optimizeperu.com/api/company/'+$("#nrodoc").val()+'?format=json';
      //urlx = 'https://api.sunat.cloud/ruc/'+$("#nrodoc").val();

      $.ajax({
          method: "GET",
          url: urlx
      })
      .done(function( json ) {
            $("#razon_social").val(json.razon_social);
            $("#direccion").val(json.domicilio_fiscal);
      });  	
  }

  function BuscarProducto(){
      $.ajax({
          method: "POST",
          url: 'apifacturacion/controlador/controlador.php',
          data: {
          	  "accion": "BUSCAR_PRODUCTO",
              "filtro": $("#producto").val()
            }
      })
      .done(function( text ) {
            json = JSON.parse(text);            
            productos = json.productos;
            listado = '';
            for(i=0;i<productos.length;i++){
            	listado = listado + '<tr><td>'+productos[i].codigo+'</td><td>'+productos[i].nombre+'</td><td><button type="button" class="btn btn-primary" onclick="AgregarCarrito('+productos[i].codigo+')">Agregar</button></td></tr>';
            }
            $("#div_productos").html(listado);
      });
  }

  function AgregarCarrito(codigo){
      $.ajax({
          method: "POST",
          url: 'apifacturacion/controlador/controlador.php',
          data: {
          	  "accion": "ADD_PRODUCTO",
              "codigo": codigo
            }
      })
      .done(function( html ) {
            $("#div_carrito").html(html);
      });  		
  }

  function CancelarVenta(codigo){
      $.ajax({
          method: "POST",
          url: 'apifacturacion/controlador/controlador.php',
          data: {
          	  "accion": "CANCELAR_CARRITO"
            }
      })
      .done(function( html ) {
            $("#div_carrito").html(html);
      });  		
  }

  function GuardarVenta(){
  	var datax = $("#frmVenta").serializeArray();

	$.ajax({
      method: "POST",
      url: 'apifacturacion/controlador/controlador.php',
      data: datax
  	})
  	.done(function( html ) {
        $("#div_carrito").html(html);
  	}); 

  }

</script>