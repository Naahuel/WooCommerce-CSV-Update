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
	if( isset( $_POST['apply-discount'] ) )
		{ update_option( 'woocommerce-csvupdate-apply-discount', intval($_POST['apply-discount']) ); }
	else
		{ update_option( 'woocommerce-csvupdate-apply-discount', 0 ); }
	if( isset( $_POST['discount'] ) )
		{ update_option( 'woocommerce-csvupdate-discount', intval($_POST['discount']) ); }

	//--------------------------------------------------------------
	// :: Obtener options
	//--------------------------------------------------------------
	$csv_delimiter = get_option( 'woocommerce-csvupdate-csv-delimiter', trim($_POST['csv-delimiter']) );
	$column_sku    = intval( get_option( 'woocommerce-csvupdate-column-sku',    intval($_POST['column-sku']) ) ) - 1;
	$column_price  = intval( get_option( 'woocommerce-csvupdate-column-price',  intval($_POST['column-price']) ) ) - 1;
	$column_stock  = intval( get_option( 'woocommerce-csvupdate-column-stock',  intval($_POST['column-stock']) ) ) - 1;
	$apply_discount  = intval( get_option( 'woocommerce-csvupdate-apply-discount',  intval($_POST['apply-discount']) ) );
	$discount  			= floatval( get_option( 'woocommerce-csvupdate-discount',  floatval($_POST['discount']) ) );

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
  if (move_uploaded_file($_FILES["csv-file"]["tmp_name"], $target_file)) {
      $upload_error = false;
  } else {
      $upload_error = true;
  }

	//--------------------------------------------------------------
	// :: Procesar
	//--------------------------------------------------------------
	if( !$upload_error ){

		// Se subió el archivo, algo hicimos
		$exito = true;

		// Iniciar LOG
		$_log = '';

		// Leo el archivo a un array
		$hw_file = file( $target_file );
		$num_linea = 0;
		$importados = 0;
		$no_importados = 0;

		// Recorro linea a línea y convierto a array el CSV
		foreach ($hw_file as $linea) {

			// Obtengo la línea del csv reemplazando comas por puntos en números
			$csv_linea = str_replace(',', '.', str_getcsv( $linea, $csv_delimiter ));
			$num_linea++;

			if( $num_linea == 1 ){
				// Ignorar primera línea
				continue;
			}

			// Nuevos valores para el productow
			$sku 							= trim($csv_linea[ $column_sku ]);
			$precio 					= str_replace(',','.',trim($csv_linea[ $column_price ]));
			$precio_descuento = $precio;
			$stock  					= trim($csv_linea[ $column_stock ]);

			// Aplica descuento?
			if( $apply_discount ){
				$__precio = floatval( trim($csv_linea[ $column_price ]) );
				$precio_descuento = ( (100-$discount)/100 )*$__precio;
				$precio_descuento = str_replace(',','.', $precio_descuento);
			}

			// Busco el producto por SKU
			if ($sku) {

				$product_id = wc_get_product_id_by_sku( $sku );

				if( $product_id ){
					// El producto existe
					$importados++;
					// Obtener producto
					$_product = wc_get_product( $product_id );
					// Log info
					$_log .= "-----------------------------------------------------------";
					$_log .= "-----------------------------------------------------------------------------\n";
					$_log .= $_product->post->post_title . "\n";
					$_log .= __('Price', 'woocommerce-csvupdate') . ": $" . $_product->get_price() . " ---> $" . $precio . " | ";
					$_log .= __('Sale Price', 'woocommerce-csvupdate') . ": $" . $_product->get_sale_price() . " ---> $" . $precio_descuento . " | ";
					$_log .= __('Stock', 'woocommerce-csvupdate') . ": " . $_product->get_stock_quantity() . " ---> " . $stock . "\n";
					$_log .= "-----------------------------------------------------------";
					$_log .= "-----------------------------------------------------------------------------\n";
					// Actualizar precio
					// Aplica descuento?
					if( $apply_discount ){
						wcsvu_change_price_by_type( $product_id, $precio_descuento ,'price' );
						wcsvu_change_price_by_type( $product_id, $precio ,'regular_price' );
						wcsvu_change_price_by_type( $product_id, $precio_descuento ,'sale_price' );
					} else {
						wcsvu_change_product_price( $product_id, $precio );
					}
					// Actualizar stock
					wc_update_product_stock( $product_id, $stock );

				} else {
					// El producto no existe
					$no_importados++;
				}

				// DEBUG: cortar en 50
				// if( $num_linea == 2 ){
				// 	// break;
				// }

			} // if $sku

		} // hwdfile as linea

	} // if !$upload_error

} //isset($_GET['do-it'])

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
	</style>

	<?php if( isset( $upload_error ) && $upload_error ): ?>
		<div id="message" class="error notice is-dismissible"><p><?php _e('There was an error uploading the file.', 'woocommerce-csvupdate'); ?></p></div>
	<?php endif; ?>

	<?php if( isset( $exito ) && $exito ): ?>
		<div id="message" class="updated notice is-dismissible"><p>
			<?php echo sprintf( __('<strong>%s</strong> products were updated. <strong>%s</strong> were ignored.', 'woocommerce-csvupdate' ), $importados, $no_importados ); ?></p></div>
	<?php endif; ?>

<h1><?php _e('CSV Update', 'woocommerce-csvupdate'); ?></h1>

<form action="<?php menu_page_url('woocommerce-csvupdate/woocommerce-csvupdate-do.php'); ?>&amp;do-it=go" method="post" enctype="multipart/form-data">
	<p>
		<label class="woocommerce-csvupdate-label" for="csv-delimiter"><?php _e('CSV Delimiter', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input" type="text" name="csv-delimiter" id="csv-delimiter" value="<?php echo get_option( 'woocommerce-csvupdate-csv-delimiter', ';' ); ?>">
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
		<label class="woocommerce-csvupdate-label" for="apply-discount"><?php _e('Apply discount in % to all products?', 'woocommerce-csvupdate'); ?></label>
		<input class="woocommerce-csvupdate-input" type="checkbox" name="apply-discount" id="apply-discount" value="1" <?php if( get_option( 'woocommerce-csvupdate-apply-discount', '0' ) == '1' ){ echo 'checked="checked"'; }; ?>>
		<input class="woocommerce-csvupdate-input" type="number" name="discount" id="discount" value="<?php echo get_option( 'woocommerce-csvupdate-discount', '0' ); ?>"> %
	</p>
	<hr>
	<p>
		<label class="woocommerce-csvupdate-label" for="csv-file"><?php _e('CSV File', 'woocommerce-csvupdate'); ?></label>
		<input type="file" name="csv-file" id="csv-file" value="">
	</p>
	<hr>
	<p>
		<button class="button button button-primary">
			<?php _e('Run', 'woocommerce-csvupdate'); ?>
		</button>
	</p>
</form>

<?php if( isset( $_log ) ): ?>
	<h2><?php _e('Log', 'woocommerce-csvupdate'); ?>:</h2>
<textarea rows="8" cols="40" class="widefat" style="height:500px"><?php echo $_log; ?></textarea>
<?php endif; ?>

</div> <!--.wrap-->
