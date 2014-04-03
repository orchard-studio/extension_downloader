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
			//alert(DOWNLOAD_URL);
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

	var init = function () {
		injectUI();
		win.load(selectExtension);
		selected();
		
		document.getElementById('xml_browser').addEventListener('change', handleFileSelect, false);

		$('body').append("<iframe id='exportextensions' style='display:none'></iframe>");

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
		
		
		$('#import_extensions').on('click', function(e) {
		   e.preventDefault();
		  // Use the native click() of the file input.
		  $('#xml_browser').click();

		});
	};

	function handleFileSelect(evt) {

		var files = evt.target.files; 
		if (!files.length) {
		  alert('Please select a file!');
		  return;
		}	
		var file = files[0];		
		var start = 0;
		var stop = file.size - 1;
		var reader = new FileReader();
		reader.onloadend = function(evt) {
		  if (evt.target.readyState == FileReader.DONE) {			
			var text = evt.target.result;
			var xmlDoc = $.parseXML( text ),xml = $(xmlDoc),exts = xml.find( "bundle" );						
			var link = [];
			var arr = [];
			$.each(exts.find('extension'), function(i, el){
					var ext = $(el);
					link.push(ext.attr('commit'));
					var name = ext.attr('commit');
					name = name.split('/');
					//alert(name);
					arr.push(' • \t' + name[4]);
					//$('#list').after(ext.attr('href')+ '<br/>');					
			});
			link = link.join('!');
			arr = arr.join('\n');
			
			if (confirm('These are the Extensions you want to Install \n \n ' + arr + ' \n \n If they exist they will be Overwritten, Do you want to Overwrite?')) {	
						wrap.addClass('loading');
				$('#xml_browser').attr('disabled', 'disabled').blur();	
				$.post(UPLOAD_FILE,	{a : link},function(data){						
						
					if(data.success){
						
						alert('Download completed! Page will refresh.');
						
							document.location = EXTENSIONS_URL;
							selected();
						
					} else if (data.exists) {
						if (confirm('Extension ' + data.handle + ' already exists. Overwrite?')) {	
							
						}
					}
				}).fail(function(e){
					alert('Import Complete');					
				}).always(function (e) {
					wrap.removeClass('loading');
					$('#xml_browser').removeAttr('disabled');
					$('#xml_browser').focus();
				});
			}
		  }
		};
		
		var blob = file.slice(start, stop + 1);
		reader.readAsBinaryString(blob);
		
		
	  }
	 
 
	var selected = function(){
		$('tr.inactive.extension-can-install').each(function(){
			$(this).addClass('selected');
		});
	}

	$(init);
	
})(jQuery);
