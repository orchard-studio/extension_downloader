/*
 * @author Deux Huit Huit
 * LICENCE: MIT http://deuxhuithuit.mit-license.org;
 */
(function ($) {
	
	"use strict";
	
	var SYM_URL = Symphony.Context.get('symphony')+ '/' ;
	var BASE_URL = SYM_URL + 'extension/extension_downloader/';
	var DOWNLOAD_URL = BASE_URL + 'download/';
	var SEARCH_URL = BASE_URL + 'search/';
	var EXTENSIONS_URL = SYM_URL + 'system/extensions/';
	var EXPORT_URL = BASE_URL + 'export/';
	var UPLOAD_FILE = BASE_URL + 'upload/';
	var REMOVE_URL = BASE_URL + 'remove/';
	var COMPATIBLE_ONLY = true;
	
	var win = $(window);
	
	var context;
	var wrap;
	var input;
	var results;
	
	var searchTimer = 0;
	
	var queryStringParser = (function () {
		var
		a = /\+/g,  // Regex for replacing addition symbol with a space
		r = /([^&=]+)=?([^&]*)/gi,
		d = function (s) { return decodeURIComponent(s.replace(a, ' ')); },
		
		_parse = function (qs) {
			var 
			u = {},
			e,
			q;
			
			//if we dont have the parameter qs, use the window location search value
			if (qs !== '' && !qs) {
				qs = window.location.search;
			}
			
			//remove the first caracter (?)
			q = qs.substring(1);

			while ((e = r.exec(q))) {
				u[d(e[1])] = d(e[2]);
			}
			
			return u;
		};
		
		return {
			parse : _parse
		};
	})();
	
	var error = function (data) {
		alert(data.error || 'Unknown error');
	};
	
	var httpError = function (e) {
		alert('HTTP error');
	};
	
	var search = function () {
		var data = {
			q: input.val(),
			compatible: !COMPATIBLE_ONLY
		};
		
		if (!data.q) {
			results.empty();
			return;	
		}
		
		wrap.addClass('loading');
		
		$.post(SEARCH_URL, data, function (data) {
			var temp = $();
			var createSpan = function (clas, text) {
				return $('<span />').attr('class', 'ed_' + clas).text(text);	
			};
			if (data.success && data.results) {
				results.empty();
				if (!!data.results.length) {
					$.each(data.results, function (i, r) {
						var a = $('<a />')
							.attr('href','#')
							.attr('data-handle', r.handle);
						var name = createSpan('name', r.name);
						var version = createSpan('version', r.version);
						var status = createSpan('status', r.status + (r.compatible ? '' : ' (n/a)'));
						var dev = createSpan('dev', r.by);
						
						a.append(name).append(version).append(status).append(dev);
						
						temp = temp.add(a);
					});
					results.append(temp);
				}
			} else if (!data.empty) {
				error(data);
			}
		}).fail(httpError).always(function (e) {
			wrap.removeClass('loading');
		});
	};
	
	var download = function (force) {
		var data = {
			q: input.val(),
			force: force
		};
		
		if (!data.q) {
			return;
		}
		
		wrap.addClass('loading');
		input.attr('disabled', 'disabled').blur();
		
		$.post(DOWNLOAD_URL, data, function (data) {			
			if (data.success) {
				alert('Download completed! Page will refresh.');
				document.location = EXTENSIONS_URL + '?download_handle=' + data.handle;
			} else if (data.exists) {
				if (confirm('Extension ' + data.handle + ' already exists. Overwrite?')) {
					download(true); // force download
				}
			} else {
				error(data);
			}
		}).fail(httpError).always(function (e) {
			wrap.removeClass('loading');
			input.removeAttr('disabled');
			input.focus();
		});
	};
	var keyup = function (e) {
		clearTimeout(searchTimer);
		if (e.which === 13) {
			download();
		} else {
			searchTimer = setTimeout(search, 200);	
		}
	};	
	var resultClick = function (e) {
		var t = $(this);
		var handle = t.attr('data-handle');
		if (confirm('Download ' + handle + '?')) {
			input.val(handle);
			setTimeout(download, 0);	
		}
		e.preventDefault();
		return false;
	};
	
	var injectUI = function () {
		context = $('#context');
		wrap = $('<div />').attr('id', 'extension_downloader');
		var link = $('<a />')
				.attr('href', 'http://symphonyextensions.com/')
					.attr('target', '_blank').text('(Browse available extensions)');
		var title = $('<h3 />').text('Download extension').append(link);
		input = $('<input />')
				.attr('type', 'text')
				.attr('placeholder',
				'zipball url, github-user/repo, extension_handle or keywords');
		results = $('<div />').attr('id', 'extension_downloader_results');
		wrap.append(title).append(input).append(results);
		context.append(wrap);
		input.keyup(keyup);
		results.on('click', 'a', resultClick);
	};

	var selectExtension = function () {
		var qs = queryStringParser.parse();
		if (!!qs.download_handle) {
			var tr = $('#contents table td input[name="items[' + qs.download_handle + ']"]').closest('tr');
			tr.click();
			win.scrollTop(tr.position().top);
		}
	};
	var selected = function(){
		$('tr.inactive.extension-can-install').each(function(){
			$(this).addClass('selected');
		});
	}
	var getUrlVars = function () {
		var vars = {};
		var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
			vars[key] = value;
		});
		return vars;
	}	 
	var remove = function(){
			var arr = [];
			var name;
			
			$('.selected').each(function(){
				var id = $(this).find('td:nth-child(4)').find('a').attr('href');
				var label = $(this).find('td:nth-child(1)').find('label');
				label.remove();
				var value = $(this).find('td:nth-child(1)').find('input').attr('id');
				var test = value.toLowerCase().replace(/extension-/g, '');
				if($(this).hasClass('inactive')){
					arr.push(test);
				}else{
					var pieces = id.split("/");
					pieces = test;//pieces[4];
					alert('Extension ' + pieces+ ' cannot be removed');
				}
			});	
			var t = arr.join(',');
			var text = {
				remove : t
			};
			if(arr ==''){
				alert('Please select extensions to remove') 
			}else{
				
				$.post(REMOVE_URL, text, function (data) {			
					
						alert('Extensions Removed');
						document.location = EXTENSIONS_URL;
					
					});
				
			}
	}
	var init = function () {
		injectUI();
		win.load(selectExtension);
		
		
		// uses a string replace function to grab GET request and highlight recently downloaded extensions
		var a = getUrlVars()["a"];
		a == '1'? selected() : '';		
		
		// create hidden iframe for export of extensions to force download xml file
		$('body').append("<iframe id='exportextensions' style='display:none'></iframe>");
		
		
		// listen to the the filebrowser event once selected in filebrowser perform following event
		$('#xml_browser').on('change',function(event){
			event.preventDefault();
			$('#upload').submit();	
		});	

		// adds listen event to button for filebrowser without using default input type file
		$('#import_extensions').on('click', function(event) {
			event.preventDefault();
			$('#xml_browser').click();
		});
		$('.selected_branch').change(function(){
			var selected = $(this).find('option:selected');
			$('.selected_branch').find('option').removeAttr('selected');
			selected.attr('selected','selected');
		});
		// export selected extensions URLs once button clicked  as formatted xml file
		$('#export_extensions').on('click',function(event){
			event.preventDefault();				
			var arr = [];
			$('.selected').each(function(){			
				var id = $(this).find('td:nth-child(4)').find('a').attr('href');
				arr.push(id);
			});			
			var text = arr.join(',');
			arr =='' ? alert('Please select extensions to export') : $('#exportextensions').attr('src',EXPORT_URL +'?a='+text);
		});
		$('#remove_extensions').click(function(event){
			event.preventDefault();
			
			remove();
		});
		// listen to submit of form and post to content.upload.php file
		// add appropriate actions to ui for loading, and alert and reload upon success of data retrieved
		$('#upload').on('submit', function(event) {
			event.preventDefault();		
			var control = $("#import_extensions");			
			if (confirm('If ANY of the extensions already exist they will be Overwritten?')) {
				$('#extension_downloader').addClass('loading');
				
				$('#import_extensions').attr('disabled', 'disabled').blur();
				var branch = $('.selected_branch option:selected').val();
				var oData = new FormData(document.forms.namedItem("upload"));
				var oReq = new XMLHttpRequest();		 
				oReq.open("POST",UPLOAD_FILE, true);
				oReq.onload = function(oEvent) {
					console.log(this.response);
					var json = JSON.stringify(eval("(" + this.response + ")"));
					var js = $.parseJSON(json);
					
					$('#extension_downloader').removeClass('loading');
					$('#import_extensions').removeAttr('disabled');							
					control.replaceWith( control = control.clone( true ) );
					if(js.error){
						alert(js.error + ' and failed to download');
					}
					alert('Download completed! Page will refresh.');
					document.location = EXTENSIONS_URL + '?a=1';					
				};
			  oReq.send(oData,branch);
			  
		  }else{
			control.replaceWith( control = control.clone( true ) );
		  }	
		});		
	};
	$(init);
	
})(jQuery);
