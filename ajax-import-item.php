<?php
	$parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
	require_once( $parse_uri[0] . 'wp-load.php' );

	//--------------------------------------------------------------
	// :: Obtener options
	// Los valores si no se encuentran no deberían actuar (los @$_POST)
	//--------------------------------------------------------------
	$csv_delimiter =		get_option( 'woocommerce-csvupdate-csv-delimiter', trim(@$_POST['csv-delimiter']) );
	$column_sku   			= intval( get_option( 'woocommerce-csvupdate-column-sku',    intval(@$_POST['column-sku']) ) ) - 1;
	$column_price 			= intval( get_option( 'woocommerce-csvupdate-column-price',  intval(@$_POST['column-price']) ) ) - 1;
	$column_stock  			= intval( get_option( 'woocommerce-csvupdate-column-stock',  intval(@$_POST['column-stock']) ) ) - 1;
	$column_peso  			= intval( get_option( 'woocommerce-csvupdate-column-peso',  intval(@$_POST['column-peso']) ) ) - 1;
	$column_largo  			= intval( get_option( 'woocommerce-csvupdate-column-largo',  intval(@$_POST['column-largo']) ) ) - 1;
	$column_ancho  			= intval( get_option( 'woocommerce-csvupdate-column-ancho',  intval(@$_POST['column-ancho']) ) ) - 1;
	$column_alto  			= intval( get_option( 'woocommerce-csvupdate-column-alto',  intval(@$_POST['column-alto']) ) ) - 1;
	$column_title  			= intval( get_option( 'woocommerce-csvupdate-column-title',  intval(@$_POST['column-stock']) ) ) - 1;
	$column_category  	= intval( get_option( 'woocommerce-csvupdate-column-category',  intval(@$_POST['column-stock']) ) ) - 1;
	$column_subcategory	= intval( get_option( 'woocommerce-csvupdate-column-subcategory',  intval(@$_POST['column-stock']) ) ) - 1;
	$apply_discount   	= intval( get_option( 'woocommerce-csvupdate-apply-discount',  intval(@$_POST['apply-discount']) ) );
	$force_price_update	= intval( get_option( 'woocommerce-csvupdate-force-price-update',  intval(@$_POST['force-price-update']) ) );
	$insert_new		 			= intval( get_option( 'woocommerce-csvupdate-insert-new',  intval(@$_POST['insert-new']) ) );
	$ignore_first_row	  = intval( get_option( 'woocommerce-csvupdate-ignore-first-row',  intval(@$_POST['ignore-first-row']) ) );
	$csv_delimiter_tab	= intval( get_option( 'woocommerce-csvupdate-csv-delimiter-tab',  intval(@$_POST['csv-delimiter-tab']) ) );
	$discount  		 			= floatval( get_option( 'woocommerce-csvupdate-discount',  floatval(@$_POST['discount']) ) );
	$sku_mask  		 			= get_option( 'woocommerce-csvupdate-sku-mask',  @$_POST['sku-mask'] );
	$sku_mask_replace		= get_option( 'woocommerce-csvupdate-sku-mask-replace',  @$_POST['sku-mask-replace'] );

	// Iniciar LOG
	$_log = '';

	// Leo el archivo a un array
	$hw_file = file( $_POST['target_file'] );
	$productos_encontrados = 0;
	$productos_actualizados_precio = 0;
	$productos_actualizados_stock = 0;
	$productos_actualizados_peso = 0;
	$productos_actualizados_largo = 0;
	$productos_actualizados_ancho = 0;
	$productos_actualizados_alto = 0;
	$productos_actualizados_titulo = 0;
	$productos_no_importados = 0;
	$productos_nuevos = 0;

	// Funcion para finalizar
	function finalizar(){
		global $productos_encontrados;
		global $productos_actualizados_precio;
		global $productos_actualizados_stock;
		global $productos_actualizados_peso;
		global $productos_actualizados_largo;
		global $productos_actualizados_ancho;
		global $productos_actualizados_alto;
		global $productos_actualizados_titulo;
		global $productos_no_importados;
		global $productos_nuevos;
		global $_log;
		// Mostrar resultado
		echo json_encode(array(
			'productos_encontrados' => $productos_encontrados,
			'productos_actualizados_precio' => $productos_actualizados_precio,
			'productos_actualizados_stock' => $productos_actualizados_stock,
			'productos_actualizados_peso' => $productos_actualizados_peso,
			'productos_actualizados_largo' => $productos_actualizados_largo,
			'productos_actualizados_ancho' => $productos_actualizados_ancho,
			'productos_actualizados_alto' => $productos_actualizados_alto,
			'productos_actualizados_titulo' => $productos_actualizados_titulo,
			'productos_no_importados' => $productos_no_importados,
			'productos_nuevos' => $productos_nuevos,
		));

		// Agregar al log
		if( $_log ){
			file_put_contents( $_POST['log_file'] , $_log.PHP_EOL , FILE_APPEND | LOCK_EX);
		}
		// Terminar
		die;
	}

	// Recorro linea a línea y convierto a array el CSV
	// foreach ($hw_file as $linea) {
	$linea = $hw_file[intval($_POST['file_line'])-1];

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

		if( $ignore_first_row && intval($_POST['file_line']) == 1 ){
			// Ignorar primera línea
			finalizar();
		}

		// Nuevos valores para el productow
		$sku 							= isset($csv_linea[ $column_sku ])? @$csv_linea[ $column_sku ] : FALSE;
		$precio 					= isset($csv_linea[ $column_price ])? @floatval(str_replace(',','',trim($csv_linea[ $column_price ]))) : FALSE;
		// $precio 					= str_replace( '.', ',', $precio );
		$precio_descuento = @$precio;
		$stock  					= isset($csv_linea[ $column_stock ])? @intval(str_replace(',','',trim($csv_linea[ $column_stock ]))) : FALSE;
		$peso  						= isset($csv_linea[ $column_peso ])? @floatval(str_replace(',','',trim($csv_linea[ $column_peso ]))) : FALSE;
		$largo  					= isset($csv_linea[ $column_largo ])? @floatval(str_replace(',','',trim($csv_linea[ $column_largo ]))) : FALSE;
		$ancho  					= isset($csv_linea[ $column_ancho ])? @floatval(str_replace(',','',trim($csv_linea[ $column_ancho ]))) : FALSE;
		$alto  						= isset($csv_linea[ $column_alto ])? @floatval(str_replace(',','',trim($csv_linea[ $column_alto ]))) : FALSE;
		// $stock	 					= str_replace( '.', ',', $stock );
		$title  					= isset($csv_linea[ $column_title ])? @trim($csv_linea[ $column_title ]) : FALSE;
		$category 				= isset($csv_linea[ $column_category ])? @trim($csv_linea[ $column_category ]) : FALSE;
		$subcategory			= isset($csv_linea[ $column_subcategory ])? @trim($csv_linea[ $column_subcategory ]) : FALSE;

		// Aplica descuento?
		if( $apply_discount ){
			$precio_descuento = ( (100-$discount)/100 )*$precio;
		}

		// Necesito aplicarle una máscara al SKU
		if( $sku_mask ){
			// Aplicar máscara al SKU
			$sku = preg_replace( "/". $sku_mask ."/", $sku_mask_replace, $sku );

		}

		// Busco el producto por SKU
		if ($sku !== FALSE) {
			// Obtengo el ID del producto
			$product_id = wc_get_product_id_by_sku( $sku );

			if( $product_id ){
				// Obtener producto
				$_product = wc_get_product( $product_id );

				// El producto existe
				$productos_encontrados++;

				$hice_cambios_en_este_producto = array();

				// Actualizar precio ?
				if( $force_price_update || ( ($precio !== FALSE) && floatval($precio) && ( floatval($_product->get_regular_price()) != floatval($precio) ) ) ){
					$productos_actualizados_precio++;
					$hice_cambios_en_este_producto[] = 'precio';
					$hice_cambios_en_este_producto['precio_viejo'] = $_product->get_price();
					if( $apply_discount ){
						wcsvu_change_price_by_type( $product_id, $precio_descuento ,'price' );
						wcsvu_change_price_by_type( $product_id, $precio ,'regular_price' );
						wcsvu_change_price_by_type( $product_id, $precio_descuento ,'sale_price' );
					} else {
						wcsvu_change_price_by_type( $product_id, $precio ,'price' );
						wcsvu_change_price_by_type( $product_id, $precio ,'regular_price' );
						wcsvu_change_price_by_type( $product_id, '' ,'sale_price' );
					}
				}

				// Actualizar stock ?
				if( ($stock !== FALSE) && intval($_product->get_stock_quantity()) != intval($stock) ){
					$productos_actualizados_stock++;
					$hice_cambios_en_este_producto[] = 'stock';
					$hice_cambios_en_este_producto['stock_viejo'] = $_product->get_stock_quantity();
					update_post_meta( $product_id, '_manage_stock', 'yes');
					wc_update_product_stock( $product_id, intval($stock) );
					if( intval($stock) > 0 ){
						update_post_meta( $product_id, '_stock_status', 'instock');
					} else {
						update_post_meta( $product_id, '_stock_status', 'outofstock');
					}
				}

				// Actualizar weight ?
				if( ($peso !== FALSE) && floatval($peso) && ( floatval($_product->get_weight()) != floatval($peso) ) ){
					$productos_actualizados_peso++;
					$hice_cambios_en_este_producto[] = 'peso';
					$hice_cambios_en_este_producto['peso_viejo'] = $_product->get_weight();
					$_product->set_weight($peso);
				}
				// Actualizar largo ?
				if( ($largo !== FALSE) && floatval($largo) && ( floatval($_product->get_length()) != floatval($largo) ) ){
					$productos_actualizados_largo++;
					$hice_cambios_en_este_producto[] = 'largo';
					$hice_cambios_en_este_producto['largo_viejo'] = $_product->get_length();
					$_product->set_length($largo);
				}
				// Actualizar ancho ?
				if( ($ancho !== FALSE) && floatval($ancho) && ( floatval($_product->get_width()) != floatval($ancho) ) ){
					$productos_actualizados_ancho++;
					$hice_cambios_en_este_producto[] = 'ancho';
					$hice_cambios_en_este_producto['ancho_viejo'] = $_product->get_width();
					$_product->set_width($ancho);
				}
				// Actualizar alto ?
				if( ($alto !== FALSE) && floatval($alto) && ( floatval($_product->get_height()) != floatval($alto) ) ){
					$productos_actualizados_alto++;
					$hice_cambios_en_este_producto[] = 'alto';
					$hice_cambios_en_este_producto['alto_viejo'] = $_product->get_height();
					$_product->set_height($alto);
				}

				// Guardar cambios
				$_product->save();

				// Limpiar transitiens
				wc_delete_product_transients( $product_id );


				// Log info
				if( !empty( $hice_cambios_en_este_producto ) ){
					$_log .= "ACTUALIZADO -----------------------------------------------------------";
					$_log .= "-----------------------------------------------------------------------------\n";
					$_log .= "(".$sku.")" . "\n";
					if( in_array('titulo', $hice_cambios_en_este_producto) ){
						$_log .= __('Title', 'woocommerce-csvupdate') . ": " . $hice_cambios_en_este_producto['titulo_viejo'] . " ---> " . $title . "\n";
					}
					if( in_array('precio', $hice_cambios_en_este_producto) ){
						$_log .= __('Price', 'woocommerce-csvupdate') . ": $" . $hice_cambios_en_este_producto['precio_viejo'] . " ---> $" . $precio . "\n";
					}
					if( in_array('stock', $hice_cambios_en_este_producto) ){
						$_log .= __('Stock', 'woocommerce-csvupdate') . ": " . $hice_cambios_en_este_producto['stock_viejo'] . " ---> " . $stock . "\n";
					}
					if( in_array('peso', $hice_cambios_en_este_producto) ){
						$_log .= __('Weight', 'woocommerce-csvupdate') . ": " . $hice_cambios_en_este_producto['peso_viejo'] . " ---> " . $peso . "\n";
					}
					if( in_array('largo', $hice_cambios_en_este_producto) ){
						$_log .= __('Length', 'woocommerce-csvupdate') . ": " . $hice_cambios_en_este_producto['largo_viejo'] . " ---> " . $largo . "\n";
					}
					if( in_array('ancho', $hice_cambios_en_este_producto) ){
						$_log .= __('Width', 'woocommerce-csvupdate') . ": " . $hice_cambios_en_este_producto['ancho_viejo'] . " ---> " . $ancho . "\n";
					}
					if( in_array('alto', $hice_cambios_en_este_producto) ){
						$_log .= __('Height', 'woocommerce-csvupdate') . ": " . $hice_cambios_en_este_producto['alto_viejo'] . " ---> " . $alto . "\n";
					}
					$_log .= "-----------------------------------------------------------";
					$_log .= "-----------------------------------------------------------------------------\n";
				}


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
						update_post_meta( $newproduct_id, '_weight', $peso);
						update_post_meta( $newproduct_id, '_length', $largo);
						update_post_meta( $newproduct_id, '_width', $ancho);
						update_post_meta( $newproduct_id, '_height', $alto);

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
						$_log .= __('Stock', 'woocommerce-csvupdate') . ": " . $stock . "\n";
						$_log .= __('Weight', 'woocommerce-csvupdate') . ": " . $peso . "\n";
						$_log .= __('Length', 'woocommerce-csvupdate') . ": " . $largo . "\n";
						$_log .= __('Width', 'woocommerce-csvupdate') . ": " . $ancho . "\n";
						$_log .= __('Height', 'woocommerce-csvupdate') . ": " . $alto . "\n";
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

	// } // hwdfile as linea

	finalizar();
