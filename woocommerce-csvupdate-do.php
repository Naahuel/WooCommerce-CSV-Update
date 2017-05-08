<?php

if( isset($_GET['do-it']) ){

	//--------------------------------------------------------------
	// :: Actualizar options
	//--------------------------------------------------------------
	if( isset( $_POST['csv-delimiter'] ) )
		{ update_option( 'woocommerce-csvupdate-csv-delimiter', trim($_POST['csv-delimiter']) ); }
	if( isset( $_POST['column-sku'] ) )
		{ update_option( 'woocommerce-csvupdate-column-sku', intval($_POST['column-sku']) ); }
	if( isset( $_POST['column-price'] ) )
		{ update_option( 'woocommerce-csvupdate-column-price', intval($_POST['column-price']) ); }
	if( isset( $_POST['column-stock'] ) )
		{ update_option( 'woocommerce-csvupdate-column-stock', intval($_POST['column-stock']) ); }
	if( isset( $_POST['column-peso'] ) )
		{ update_option( 'woocommerce-csvupdate-column-peso', intval($_POST['column-peso']) ); }
	if( isset( $_POST['column-largo'] ) )
		{ update_option( 'woocommerce-csvupdate-column-largo', intval($_POST['column-largo']) ); }
	if( isset( $_POST['column-ancho'] ) )
		{ update_option( 'woocommerce-csvupdate-column-ancho', intval($_POST['column-ancho']) ); }
	if( isset( $_POST['column-alto'] ) )
		{ update_option( 'woocommerce-csvupdate-column-alto', intval($_POST['column-alto']) ); }
	if( isset( $_POST['column-title'] ) )
		{ update_option( 'woocommerce-csvupdate-column-title', intval($_POST['column-title']) ); }
	if( isset( $_POST['column-category'] ) )
		{ update_option( 'woocommerce-csvupdate-column-category', intval($_POST['column-category']) ); }
	if( isset( $_POST['column-subcategory'] ) )
		{ update_option( 'woocommerce-csvupdate-column-subcategory', intval($_POST['column-subcategory']) ); }
	if( isset( $_POST['sku-mask'] ) )
		{ update_option( 'woocommerce-csvupdate-sku-mask', $_POST['sku-mask'] ); }
	if( isset( $_POST['sku-mask-replace'] ) )
		{ update_option( 'woocommerce-csvupdate-sku-mask-replace', $_POST['sku-mask-replace'] ); }
	if( isset( $_POST['apply-discount'] ) )
		{ update_option( 'woocommerce-csvupdate-apply-discount', intval($_POST['apply-discount']) ); }
	else
		{ update_option( 'woocommerce-csvupdate-apply-discount', 0 ); }
	if( isset( $_POST['insert-new'] ) )
		{ update_option( 'woocommerce-csvupdate-insert-new', intval($_POST['insert-new']) ); }
	else
		{ update_option( 'woocommerce-csvupdate-insert-new', 0 ); }
	if( isset( $_POST['csv-delimiter-tab'] ) )
		{ update_option( 'woocommerce-csvupdate-csv-delimiter-tab', intval($_POST['csv-delimiter-tab']) ); }
	else
		{ update_option( 'woocommerce-csvupdate-csv-delimiter-tab', 0 ); }
	if( isset( $_POST['discount'] ) )
		{ update_option( 'woocommerce-csvupdate-discount', intval($_POST['discount']) ); }
	if( isset( $_POST['ignore-first-row'] ) )
		{ update_option( 'woocommerce-csvupdate-ignore-first-row', intval($_POST['ignore-first-row']) ); }
	else
		{ update_option( 'woocommerce-csvupdate-ignore-first-row', 0 ); }

	//--------------------------------------------------------------
	// :: Subir archivo
	//--------------------------------------------------------------
	$target_dir = WP_CONTENT_DIR .  '/uploads/csv/';
	$target_file = $target_dir . 'csv_import.csv';

	// Si el directorio no existe, crearlo
	if ( !file_exists($target_dir) ) {
		mkdir( $target_dir );
	}

	// Si el archivo ya existe, borrarlo
	if ( file_exists($target_file) ) {
		unlink( $target_file );
	}

	// Mover archivo subido
  if (@move_uploaded_file($_FILES["csv-file"]["tmp_name"], $target_file)) {
      $upload_error = false;
  } else {
      $upload_error = true;
  }

	// Count the number of lines
	$line_count = 0;
	$handle = fopen($target_file, "r");
	while(!feof($handle)){
	  $line = fgets($handle);
		if($line)
	  	$line_count++;
	}
	fclose($handle);

	//--------------------------------------------------------------
	// :: Procesar
	//--------------------------------------------------------------
	if( !$upload_error ){ ?>

		<style media="screen">
			#update-progress{
				display: block;
				height: 30px;
				background: #38d012;
				width: 0%;
			}
		</style>

		<div class="wrap">
			<div id="update-result">

			</div>
			<div id="update-progress">
			</div>
		</div>

		<script type="text/javascript">
			// Realizamos la importación por AJAX
			// Construir la data necesaria para pasar al archivo
			var url = '<?php echo plugin_dir_url(__FILE__); ?>/ajax-import-item.php';
			var line_count = <?php echo $line_count; ?>;
			var item_count = 0;
			var percent_done = 0;
			<?php
				// Archivo log
				$log_name = 'csv_import_log_' . date('Y-m-d_H-i-s') . '.txt';
				$_wp_upload = wp_upload_dir();
				$log_url = $_wp_upload['baseurl'] . '/csv/' . $log_name;
			?>
			var log_url = '<?php echo $log_url; ?>';
			var ajax_data = {
				target_dir: '<?php echo $target_dir; ?>',
				target_file: '<?php echo $target_file; ?>',
				log_file: '<?php echo $target_dir . $log_name; ?>',
				file_line: 0,
			};
			var result_data = {
				productos_encontrados: 0,
				productos_actualizados_precio: 0,
				productos_actualizados_stock: 0,
				productos_actualizados_peso: 0,
				productos_actualizados_largo: 0,
				productos_actualizados_ancho: 0,
				productos_actualizados_alto: 0,
				productos_actualizados_titulo: 0,
				productos_no_importados: 0,
				productos_nuevos: 0,
			};

			(function($){
				var $update_result = $('#update-result');
				var $update_progress = $('#update-progress');

				function update_item(){

					// increment item
					item_count++;
					percent_done = parseInt((item_count*100)/line_count);

					if( item_count <= line_count){

						// Update progress
						$update_progress.css('width', percent_done + "%");
						_resultado = 'Procesando ' + item_count + ' de ' + line_count + ' (' + percent_done + '%)';
						_resultado += '<br><strong>Productos encontrados: </strong>' + result_data.productos_encontrados;
						_resultado += '<br><strong>Productos nuevos: </strong>' + result_data.productos_nuevos;
						_resultado += '<br><strong>Productos ignorados: </strong>' + result_data.productos_no_importados;
						_resultado += '<br><strong>Productos con nuevo precio: </strong>' + result_data.productos_actualizados_precio;
						_resultado += '<br><strong>Productos con nuevo stock: </strong>' + result_data.productos_actualizados_stock;
						_resultado += '<br><strong>Productos con nuevo peso: </strong>' + result_data.productos_actualizados_peso;
						_resultado += '<br><strong>Productos con nuevo largo: </strong>' + result_data.productos_actualizados_largo;
						_resultado += '<br><strong>Productos con nuevo ancho: </strong>' + result_data.productos_actualizados_ancho;
						_resultado += '<br><strong>Productos con nuevo alto: </strong>' + result_data.productos_actualizados_alto;
						$update_result.html(_resultado);

						// Enviar al script que hace la actualización
						ajax_data.file_line++;
						$.ajax({
		          url: url,
		          type: 'post',
		          data: ajax_data,
		          dataType: 'json'
		        }).fail(function(data){

							// En caso de fallar seguimos intentando
							update_item();

		        }).done(function(data){
							// Agrego el resultado
							result_data.productos_encontrados += parseInt( data.productos_encontrados ),
							result_data.productos_actualizados_precio += parseInt( data.productos_actualizados_precio ),
							result_data.productos_actualizados_stock += parseInt( data.productos_actualizados_stock ),
							result_data.productos_actualizados_peso += parseInt( data.productos_actualizados_peso ),
							result_data.productos_actualizados_largo += parseInt( data.productos_actualizados_largo ),
							result_data.productos_actualizados_ancho += parseInt( data.productos_actualizados_ancho ),
							result_data.productos_actualizados_alto += parseInt( data.productos_actualizados_alto ),
							result_data.productos_actualizados_titulo += parseInt( data.productos_actualizados_titulo ),
							result_data.productos_no_importados += parseInt( data.productos_no_importados ),
							result_data.productos_nuevos += parseInt( data.productos_nuevos ),

							// Recursión
							update_item();

		        });
					} else {

						// Finalizado!
						$update_progress.css('width', "100%");
						_resultado = '<strong>FINALIZADO</strong>';
						_resultado += '<br><strong>Productos encontrados (ya existentes): </strong>' + result_data.productos_encontrados;
						_resultado += '<br><strong>Productos nuevos: </strong>' + result_data.productos_nuevos;
						_resultado += '<br><strong>Productos ignorados: </strong>' + result_data.productos_no_importados;
						_resultado += '<br><strong>Productos con nuevo precio: </strong>' + result_data.productos_actualizados_precio;
						_resultado += '<br><strong>Productos con nuevo stock: </strong>' + result_data.productos_actualizados_stock;
						_resultado += '<br><strong>Productos con nuevo peso: </strong>' + result_data.productos_actualizados_peso;
						_resultado += '<br><strong>Productos con nuevo largo: </strong>' + result_data.productos_actualizados_largo;
						_resultado += '<br><strong>Productos con nuevo ancho: </strong>' + result_data.productos_actualizados_ancho;
						_resultado += '<br><strong>Productos con nuevo alto: </strong>' + result_data.productos_actualizados_alto;
						$update_result.html(_resultado);
						$update_result.append('<br><a class="button button button-primary" href="'+log_url+'" target="_blank">Ver registro</a><br>');
					}
				}

				// Llamamos la primera instancia
				update_item();

			})(jQuery);
		</script>

	<?php } // if !$upload_error
	else { ?>

		<div class="wrap">
			<div class="error notice is-dismissible"><p><?php _e('There was an error uploading the file.', 'woocommerce-csvupdate'); ?></p></div>
			<p><a href="<?php menu_page_url('woocommerce-csvupdate/woocommerce-csvupdate-do.php'); ?>">&larr; Volver</a></p>
		</div>

	<?php } // if !$upload_error

} //isset($_GET['do-it'])
else {

?>
<div class="wrap">
	<style media="screen">
		.woocommerce-csvupdate-label{
			min-width: 200px;
			display: inline-block;
		}
		.woocommerce-csvupdate-input{
			max-width: 50px;
		}
		.woocommerce-csvupdate-input-wide{
			width: 200px;
			max-width: none;
		}
	</style>
	<?php if( isset( $exito ) && $exito ): ?>
		<div class="updated notice is-dismissible"><p>
			<?php echo sprintf( __('<strong>%s</strong> products were found. <strong>%s</strong> were inserted. <strong>%s</strong> were ignored.', 'woocommerce-csvupdate' ), $productos_encontrados, $productos_nuevos, $productos_no_importados ); ?>
		</p></div>
		<div class="updated notice is-dismissible"><p>
			<?php echo sprintf( __('<strong>%s</strong> products with new price.', 'woocommerce-csvupdate' ), $productos_actualizados_precio); ?>
		</p></div>
		<div class="updated notice is-dismissible"><p>
			<?php echo sprintf( __('<strong>%s</strong> products with new stock.', 'woocommerce-csvupdate' ), $productos_actualizados_stock); ?>
		</p></div>
	<?php else: ?>
		<div class="error notice is-dismissible"><p><?php _e('<strong>WARNING</strong> - <em>Always</em> backup your database before uploading a file!', 'woocommerce-csvupdate'); ?></p></div>
		<div class="error notice is-dismissible"><p><?php _e('<strong>WARNING</strong> - Check that the column numbers correspond with the ones in the CSV file!', 'woocommerce-csvupdate'); ?></p></div>
	<?php endif; ?>

<h1><?php _e('CSV Update', 'woocommerce-csvupdate'); ?></h1>

<form id="form-csv-update" action="<?php menu_page_url('woocommerce-csvupdate/woocommerce-csvupdate-do.php'); ?>&amp;do-it=go" method="post" enctype="multipart/form-data">
	<p>
		<label class="woocommerce-csvupdate-label" for="csv-delimiter"><?php _e('CSV Delimiter', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input" type="text" name="csv-delimiter" id="csv-delimiter" value="<?php echo get_option( 'woocommerce-csvupdate-csv-delimiter', ';' ); ?>">
		<label for="csv-delimiter-tab"><?php _e("TAB", "woocommerce-csvupdate"); ?> <input class="woocommerce-csvupdate-input" type="checkbox" name="csv-delimiter-tab" id="csv-delimiter-tab" value="1" <?php if( get_option( 'woocommerce-csvupdate-csv-delimiter-tab', '0' ) == '1' ){ echo 'checked="checked"'; }; ?>></label>
	</p>
	<p>
		<label class="woocommerce-csvupdate-label" for="column-sku"><?php _e('SKU Column', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input" type="text" name="column-sku" id="column-sku" value="<?php echo get_option( 'woocommerce-csvupdate-column-sku', '1' ); ?>">
	</p>
	<p>
		<label class="woocommerce-csvupdate-label" for="column-price"><?php _e('Price Column', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input" type="text" name="column-price" id="column-price" value="<?php echo get_option( 'woocommerce-csvupdate-column-price', '9' ); ?>">
	</p>
	<p>
		<label class="woocommerce-csvupdate-label" for="column-stock"><?php _e('Stock Column', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input" type="text" name="column-stock" id="column-stock" value="<?php echo get_option( 'woocommerce-csvupdate-column-stock', '11' ); ?>">
	</p>
	<p>
		<label class="woocommerce-csvupdate-label" for="column-peso"><?php _e('Weight Column', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input" type="text" name="column-peso" id="column-peso" value="<?php echo get_option( 'woocommerce-csvupdate-column-peso', '99' ); ?>">
	</p>
	<p>
		<label class="woocommerce-csvupdate-label" for="column-largo"><?php _e('Length Column', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input" type="text" name="column-largo" id="column-largo" value="<?php echo get_option( 'woocommerce-csvupdate-column-largo', '99' ); ?>">
	</p>
	<p>
		<label class="woocommerce-csvupdate-label" for="column-ancho"><?php _e('Width Column', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input" type="text" name="column-ancho" id="column-ancho" value="<?php echo get_option( 'woocommerce-csvupdate-column-ancho', '99' ); ?>">
	</p>
	<p>
		<label class="woocommerce-csvupdate-label" for="column-alto"><?php _e('Height Column', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input" type="text" name="column-alto" id="column-alto" value="<?php echo get_option( 'woocommerce-csvupdate-column-alto', '99' ); ?>">
	</p>
	<p>
		<label class="woocommerce-csvupdate-label" for="column-title"><?php _e('Title Column <br><small>Only used when inserting new products</small>', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input" type="text" name="column-title" id="column-title" value="<?php echo get_option( 'woocommerce-csvupdate-column-title', '3' ); ?>">
	</p>
	<p>
		<label class="woocommerce-csvupdate-label" for="column-category"><?php _e('Category Column <br><small>Only used when inserting new products</small>', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input" type="text" name="column-category" id="column-category" value="<?php echo get_option( 'woocommerce-csvupdate-column-category', '6' ); ?>">
	</p>
	<p>
		<label class="woocommerce-csvupdate-label" for="column-subcategory"><?php _e('Sub-category Column <br><small>Only used when inserting new products</small>', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input" type="text" name="column-subcategory" id="column-subcategory" value="<?php echo get_option( 'woocommerce-csvupdate-column-subcategory', '7' ); ?>">
	</p>
	<p>
		<label class="woocommerce-csvupdate-label" for="apply-discount"><?php _e('Apply discount in % to all products?', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input" type="checkbox" name="apply-discount" id="apply-discount" value="1" <?php if( get_option( 'woocommerce-csvupdate-apply-discount', '0' ) == '1' ){ echo 'checked="checked"'; }; ?>>
		<input class="woocommerce-csvupdate-input" type="number" name="discount" id="discount" value="<?php echo get_option( 'woocommerce-csvupdate-discount', '0' ); ?>"> %
	</p>
	<p>
		<label class="woocommerce-csvupdate-label" for="insert-new"><?php _e('Add nonexistent products?', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input" type="checkbox" name="insert-new" id="insert-new" value="1" <?php if( get_option( 'woocommerce-csvupdate-insert-new', '0' ) == '1' ){ echo 'checked="checked"'; }; ?>>
	</p>
	<p>
		<label class="woocommerce-csvupdate-label" for="insert-new"><?php _e('Ignore first row?', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input" type="checkbox" name="ignore-first-row" id="ignore-first-row" value="1" <?php if( get_option( 'woocommerce-csvupdate-ignore-first-row', '1' ) == '1' ){ echo 'checked="checked"'; }; ?>>
	</p>
	<hr>
	<p>
		<label class="woocommerce-csvupdate-label" for="csv-file"><?php _e('CSV File', 'woocommerce-csvupdate'); ?></label>
		<input type="file" name="csv-file" id="csv-file" value="">
	</p>
	<hr>
	<p>
		<label class="woocommerce-csvupdate-label" for="sku-mask"><?php _e('SKU Mask <br><small>RegExp for SKU code</small>', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input woocommerce-csvupdate-input-wide" placeholder="[Pattern]" type="text" name="sku-mask" id="sku-mask" value="<?php echo get_option( 'woocommerce-csvupdate-sku-mask', '' ); ?>">
		<input class="woocommerce-csvupdate-input woocommerce-csvupdate-input-wide" placeholder="[Replace]" type="text" name="sku-mask-replace" id="sku-mask-replace" value="<?php echo get_option( 'woocommerce-csvupdate-sku-mask-replace', '' ); ?>">
	</p>
	<hr>
	<p><?php _e('<strong>WARNING</strong> - This might take a long time!', 'woocommerce-csvupdate'); ?></p>
	<p>
		<button id="button-go" class="button button button-primary">
			<?php _e('Run', 'woocommerce-csvupdate'); ?>
		</button>
	</p>
</form>

<?php if( isset( $_log ) ): ?>
	<h2><?php _e('Log', 'woocommerce-csvupdate'); ?>:</h2>
<textarea rows="8" cols="40" class="widefat" style="height:500px"><?php echo $_log; ?></textarea>
<?php endif; ?>

<script type="text/javascript">
	(function($){
		$('#form-csv-update').on('submit', function(){
			$('#button-go').attr('disabled', true);
		})
	})(jQuery);
</script>

</div> <!--.wrap-->
<?php } //isset($_GET['do-it'])
