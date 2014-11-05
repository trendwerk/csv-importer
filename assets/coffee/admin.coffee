$ = jQuery

$ ->
	$( '.tp-csv-importer-remove-file' ).click ->
		return confirm( TP_CSV_Importer_Labels[ 'remove_notice' ] );

	return;
