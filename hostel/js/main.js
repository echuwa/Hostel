 $(document).ready(function () {
	 
 	$(".ts-sidebar-menu li a").each(function () {
 		if ($(this).next().length > 0) {
 			$(this).addClass("parent");
 		};
 	})
 	var menux = $('.ts-sidebar-menu li a.parent');
 	$('<div class="more"><i class="fa fa-angle-down"></i></div>').insertBefore(menux);
 	$('.more').click(function () {
 		$(this).parent('li').toggleClass('open');
 	});
	$('.parent').click(function (e) {
		e.preventDefault();
 		$(this).parent('li').toggleClass('open');
 	});
 	$('.menu-btn').click(function () {
 		$('nav.ts-sidebar').toggleClass('menu-open');
 	});
	 
	 
	 // Initialize DataTable only if element exists and DataTables is loaded
	 if ($('#zctb').length > 0) {
	 	if (typeof $.fn.DataTable !== 'undefined') {
	 		try {
	 			$('#zctb').DataTable();
	 		} catch(e) {
	 			console.error('DataTable initialization error:', e);
	 		}
	 	} else {
	 		console.warn('DataTable library not loaded');
	 	}
	 }
	 
	 
	 // Initialize file input only if element exists and plugin is loaded
	 if ($("#input-43").length > 0) {
	 	if (typeof $.fn.fileinput !== 'undefined') {
	 		try {
	 			$("#input-43").fileinput({
	 				showPreview: false,
	 				allowedFileExtensions: ["zip", "rar", "gz", "tgz"],
	 				elErrorContainer: "#errorBlock43"
	 			});
	 		} catch(e) {
	 			console.error('FileInput initialization error:', e);
	 		}
	 	} else {
	 		console.warn('FileInput library not loaded');
	 	}
	 }

 });
