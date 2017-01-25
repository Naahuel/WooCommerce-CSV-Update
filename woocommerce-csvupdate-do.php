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
	if( isset( $_POST['column-title'] ) )
		{ update_option( 'woocommerce-csvupdate-column-title', intval($_POST['column-title']) ); }
	if( isset( $_POST['column-category'] ) )
		{ update_option( 'woocommerce-csvupdate-column-category', intval($_POST['column-category']) ); }
	if( isset( $_POST['column-subcategory'] ) )
		{ update_option( 'woocommerce-csvupdate-column-subcategory', intval($_POST['column-subcategory']) ); }
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

	//--------------------------------------------------------------
	// :: Obtener options
	//--------------------------------------------------------------
	$csv_delimiter = get_option( 'woocommerce-csvupdate-csv-delimiter', trim($_POST['csv-delimiter']) );
	$column_sku   			= intval( get_option( 'woocommerce-csvupdate-column-sku',    intval($_POST['column-sku']) ) ) - 1;
	$column_price 			= intval( get_option( 'woocommerce-csvupdate-column-price',  intval($_POST['column-price']) ) ) - 1;
	$column_stock  			= intval( get_option( 'woocommerce-csvupdate-column-stock',  intval($_POST['column-stock']) ) ) - 1;
	$column_title  			= intval( get_option( 'woocommerce-csvupdate-column-title',  intval($_POST['column-stock']) ) ) - 1;
	$column_category  	= intval( get_option( 'woocommerce-csvupdate-column-category',  intval($_POST['column-stock']) ) ) - 1;
	$column_subcategory	= intval( get_option( 'woocommerce-csvupdate-column-subcategory',  intval($_POST['column-stock']) ) ) - 1;
	$apply_discount   	= intval( get_option( 'woocommerce-csvupdate-apply-discount',  intval($_POST['apply-discount']) ) );
	$insert_new		 			= intval( get_option( 'woocommerce-csvupdate-insert-new',  intval($_POST['insert-new']) ) );
	$csv_delimiter_tab	= intval( get_option( 'woocommerce-csvupdate-insert-new',  intval($_POST['csv-delimiter-tab']) ) );
	$discount  		 			= floatval( get_option( 'woocommerce-csvupdate-discount',  floatval($_POST['discount']) ) );

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
		$productos_actualizados = 0;
		$productos_no_importados = 0;
		$productos_nuevos = 0;

		// Recorro linea a línea y convierto a array el CSV
		foreach ($hw_file as $linea) {

			// Obtengo la línea del csv reemplazando comas por puntos en números
			$csv_delimiter = $csv_delimiter_tab ? "\t" : $csv_delimiter;
			$__detect_encod = mb_detect_encoding($linea, mb_detect_order(), true);
			if( $__detect_encod ){
				// Se detectó algún encoding. No importa cual, convertir a UTF-8
				$linea = iconv($__detect_encod, "UTF-8", $linea);
			} else {
				// NO se pudo detectar un encoding. Intentar convertir de todas formas
				$linea = utf8_encode( $linea );
			}
			$csv_linea = str_getcsv( $linea, $csv_delimiter );
			$num_linea++;

			if( $num_linea == 1 ){
				// Ignorar primera línea
				continue;
			}

			// Nuevos valores para el productow
			$sku 							= @trim($csv_linea[ $column_sku ]);
			$precio 					= @floatval(str_replace(',','',trim($csv_linea[ $column_price ])));
			// $precio 					= str_replace( '.', ',', $precio );
			$precio_descuento = @$precio;
			$stock  					= @floatval(str_replace(',','',trim($csv_linea[ $column_stock ])));
			// $stock	 					= str_replace( '.', ',', $stock );
			$title  					= @trim($csv_linea[ $column_title ]);
			$category 				= @trim($csv_linea[ $column_category ]);
			$subcategory			= @trim($csv_linea[ $column_subcategory ]);

			// Aplica descuento?
			if( $apply_discount ){
				$precio_descuento = ( (100-$discount)/100 )*$precio;
			}

			// Busco el producto por SKU
			if ($sku) {
				// Obtengo el ID del producto
				$product_id = wc_get_product_id_by_sku( $sku );

				if( $product_id ){
					// El producto existe
					//-------------------------------------------------
					// ACTUALIZAR
					//-------------------------------------------------
					$productos_actualizados++;
					// Obtener producto
					$_product = wc_get_product( $product_id );

					// Log info
					$_log .= "ACTUALIZADO -----------------------------------------------------------";
					$_log .= "-----------------------------------------------------------------------------\n";
					$_log .= "(".$sku.") :: " . $_product->post->post_title . "\n";
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
					update_post_meta( $product_id, '_manage_stock', 'yes');

					// Actualizar título
					$product_new_title = array(
				      'ID'           => $product_id,
				      'post_title'   => $title,
				  );
				  wp_update_post( $product_new_title );

				} else {
					// El producto no existe
					if( ($stock > 0) && $precio && $insert_new ){
						// El producto nuevo tiene stock y se pidió agregar nuevos
						//-------------------------------------------------
						// INSERTAR
						//-------------------------------------------------

						// Obtengo la categoría
						$cat_id = false;
						$cat_obj = get_term_by('name', $category, 'product_cat');
						if( $cat_obj ){
							// La categoría ya existe
							$cat_id = $cat_obj->term_id;
						} else {
							// La categoría no existe
							if( $category ){
								// Crear categoria
								$cat_obj = wp_insert_term( $category, 'product_cat' );
								if( is_array($cat_obj) ){
									$cat_id = $cat_obj['term_id'];
								} else{
									// Ocurrió un error. Tal vez ya existe?
									// HACER ALGO
								}
							}
						}
						// Obtengo la SUB categoría
						$subcat_id = false;
						// $subcat_obj = get_term_by('name', $subcategory, 'product_cat');

						if( $subcategory ){
							// USO wp_insert_term para buscar. Si devuelve error: EXISTE; SI NO, LA CREA
							$subcat_obj = wp_insert_term( $subcategory, 'product_cat', array( 'parent' => $cat_id ) );

							if( is_wp_error($subcat_obj) ){
								// Ya existía
								$subcat_id = $subcat_obj->error_data['term_exists'];
							} else {
								// Es nueva
								$subcat_id = $subcat_obj['term_id'];
							}
						}

						// Creo el producto
						$post = array(
					    'post_author' => get_current_user_id(),
					    'post_content' => '',
					    'post_status' => "publish",
					    'post_title' => $title,
					    'post_parent' => '',
					    'post_type' => "product",
						);

						//Create post
						$newproduct_id = wp_insert_post( $post, true );

						if( is_wp_error($newproduct_id) ){
							$_log .= "ERROR!!!! -----------------------------------------------------------";
							$_log .= "-----------------------------------------------------------------------------\n";
							$_log .= "(".$sku.") :: NO SE PUDO GUARDAR EN LA BASE DE DATOS";
							$_log .= "-----------------------------------------------------------";
							$_log .= "-----------------------------------------------------------------------------\n";
							// El producto nuevo no se pudo insertar
							$productos_no_importados++;
						} else {
							// Tipo de producto (SIMPLE)
							wp_set_object_terms( $newproduct_id, 'simple', 'product_type');

							// Terms
							if( $cat_id )
								wp_set_object_terms( $newproduct_id, intval($cat_id), 'product_cat' );
							if( $subcat_id )
								wp_set_object_terms( $newproduct_id, intval($subcat_id), 'product_cat', true );

							// Meta
							update_post_meta( $newproduct_id, '_visibility', 'visible' );
							update_post_meta( $newproduct_id, '_stock_status', 'instock');
							update_post_meta( $newproduct_id, '_manage_stock', 'yes');
							update_post_meta( $newproduct_id, 'total_sales', '0');
							update_post_meta( $newproduct_id, '_downloadable', 'no');
							update_post_meta( $newproduct_id, '_virtual', 'no');
							update_post_meta( $newproduct_id, '_sku', $sku);

							// Actualizar precio
							// Aplica descuento?
							if( $apply_discount ){
								wcsvu_change_price_by_type( $newproduct_id, $precio_descuento ,'price' );
								wcsvu_change_price_by_type( $newproduct_id, $precio ,'regular_price' );
								wcsvu_change_price_by_type( $newproduct_id, $precio_descuento ,'sale_price' );
							} else {
								wcsvu_change_product_price( $newproduct_id, $precio );
							}
							// Actualizar stock
							wc_update_product_stock( $newproduct_id, $stock );


							// Log info
							$_log .= "NUEVO -----------------------------------------------------------";
							$_log .= "-----------------------------------------------------------------------------\n";
							$_log .= "(".$sku.") :: " . $title . "\n";
							$_log .= __('Price', 'woocommerce-csvupdate') . ": $" . $precio . " | ";
							$_log .= __('Sale Price', 'woocommerce-csvupdate') . ": $" . $precio_descuento . " | ";
							$_log .= __('Stock', 'woocommerce-csvupdate') . ": " . $stock . "\n";
							$_log .= __('Category', 'woocommerce-csvupdate') . ": " . $category . "\n";
							$_log .= __('Sub-Category', 'woocommerce-csvupdate') . ": " . $subcategory . "\n";
							$_log .= "-----------------------------------------------------------";
							$_log .= "-----------------------------------------------------------------------------\n";

							// Incrementar contador
							$productos_nuevos++;
						}
					} else {
						// El producto nuevo no tiene stock
						$productos_no_importados++;
					}
				}

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
			<?php echo sprintf( __('<strong>%s</strong> products were updated. <strong>%s</strong> were inserted. <strong>%s</strong> were ignored.', 'woocommerce-csvupdate' ), $productos_actualizados, $productos_nuevos, $productos_no_importados ); ?></p></div>
	<?php else: ?>
		<div id="message" class="error notice is-dismissible"><p><?php _e('<strong>CUIDADO</strong> - Realice <em>siempre</em> un backup de la base de datos antes de importar un archivo!', 'woocommerce-csvupdate'); ?></p></div>
		<div id="message" class="error notice is-dismissible"><p><?php _e('<strong>CUIDADO</strong> - Verifique que las columnas sean correctas antes de importar un archivo nuevo!', 'woocommerce-csvupdate'); ?></p></div>
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
	<hr>
	<p>
		<label class="woocommerce-csvupdate-label" for="csv-file"><?php _e('CSV File', 'woocommerce-csvupdate'); ?></label>
		<input type="file" name="csv-file" id="csv-file" value="">
	</p>
	<hr>
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
