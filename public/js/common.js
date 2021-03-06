/**************************************************************************/
/*  Extraxcel 共通JavaScript                                              */
/*========================================================================*/
/*  変更履歴  変更日      変更概要                                        */
/*------------------------------------------------------------------------*/
/*  REV-000   2015-10-06  新規                               by.JunOhtake */
/**************************************************************************/

/********************************************
  ファイルアップロード
*********************************************/
var uploading_filecount = 0;
$('#filedroparea').on("click", function(e){
	$(e.target).find("input:file").click();
});
$('#fileupload').fileupload({ dataType: 'json',
                              dropZone: $('#filedroparea'),
                              sequentialUploads : true,
                              formData: { _token : $('input[name="_token"]').val() }
                           })
	.on('fileuploadadd', function (e, data) {
		var $filelist = $("ul.filelist");
		$.each(data.files, function(idx, fl){
			var total = $('span#filecounter').text();
			uploading_filecount++;
			total++;
			$('span#filecounter').text(total);
			uploadStart($filelist, fl);
		});
	})
	.on('fileuploadprogress', function (e, data) {
		var val = parseInt(data.loaded / data.total * 100, 10);
		data.files[0].bar.css('width',  val + '%')
	})
	.on('fileuploadalways', function(e, data) {
		uploadComplete(e, data);
		uploading_filecount--;
		if (uploading_filecount < 1) {
			setTimeout(function(){
				location.reload(true);
			},1000);
		}
	});

/********************************************
  ファイル抹消
*********************************************/
$('ul.filelist button.close').on("click", function(e){
	var $li = $(e.target).parents("li");
	var idx = $li.data('fileidx');
	var msg = '「' + $li.find('p').text() + '」のデータを抹消します\n\nよろしいですか？';
	
	if (!confirm(msg)) return;
	
	var token = $('input[name="_token"]').val();
	var param = { _method : "DELETE",
                _token  : token,
  				      idx     : idx
  				}
	$.post( 'file/remove', param, function(data){
		$("table.preview tbody#file-"+idx).remove();
		$li.remove();
	}, 'json');
	
});

/********************************************
  スキーマアップロード
*********************************************/
$('#schemaimport').on("click", function(e){
	$(e.target).find("input:file").click();
});
$('#schemaupload').fileupload({ dataType: 'json',
	                              dropZone: $('#schemaupload'),
                              	sequentialUploads : true,
                              	formData: { _token : $('input[name="_token"]').val() }
                           	  })
	.on('fileuploadalways', function(e, data) {
		location.reload(true);
	});

/********************************************
  スキーマ並べ替え
*********************************************/
$('button#sort-mode-toggle').on("click", function(e){
	$("div#screen").show();
	$("div#sort-mode-button").show();
	$("div#schema-controller-button").hide();
	$("div#schema-readby").hide();
	$("ul.schemalist").sortable('enable');
});
$("ul.schemalist").sortable({
	disabled : true,
	axis     : 'y',
	cursor:    'move',
	opacity:   0.7,
	placeholder: 'ui-state-highlight'
});
$('button#sort-ok').on("click", function(e){
	var sodr = [];
	$('ul#schemalist_single p.name').each(function(idx,elm){
		sodr.push(elm.innerText);
	})
	var modr = [];
	$('ul#schemalist_multi p.name').each(function(idx,elm){
		modr.push(elm.innerText);
	})
	
	var token = $('input[name="_token"]').val();
	var param = { _token     : token,
  				      single_odr : sodr,
  				      multi_odr  : modr
  				    }
	$.post( 'schema/sort', param, function(data){
		location.reload(true);
	}, 'json');
	
});
$('button#sort-cancel').on("click", function(e){
	location.reload();
});

/********************************************
  データ取得方法の変更
*********************************************/
$('a#readby-change').on("click", function(e){
	var token = $('input[name="_token"]').val();
	var param = { _token     : token  }
	$.post( 'schema/readby', param, function(data){
		location.reload(true);
	}, 'json');
	
});

/********************************************
  Previewシートセレクタ
*********************************************/
$('select#sheetidx').on("change", function(e){
	var url = '.';
	var $opt = $('select#sheetidx option:selected');
	
	url+= ($opt.attr('value') == 'single') ? '?st=s' : '?st=m';
	url+= '&idx=' + $opt.data('idx');
	location.href = url;
});




///////////////////////////////////////////
//  アップロード開始処理
function uploadStart($parent, fl) {
	var $finfo = $("<li>");
	var $bar   = $("<div class='progress-bar' role='progressbar' aria-valuenow='0' aria-valuemin='0' aria-valuemax='100' style='width: 0%;'>");
	var no = $parent.data('cnt') + 1;
	$parent.data('cnt', no);
	
	$finfo.append($("<span>").text('No.'+no))
	      .append("<span class='size'>")
	      .append($("<p>").text(fl.name))
	      .append($("<div class='progress' style='height:3px;'>").append($bar));
	$finfo.addClass('uploading');
	fl.bar = $bar;
	fl.li  = $finfo;
	$parent.append($finfo);
}

///////////////////////////////////////////
//  アップロード完了処理
function uploadComplete(e, data) {
	var $li = data.files[0].li;
	data.files[0].bar.parents(".progress").delay(500).fadeOut(500);
	$li.removeClass('uploading');
	
	if (data.result.error) {
		$li.addClass('error');
		$li.find('span.result').text(data.result.error);
	} else {
		$li.find('span.size').text(data.result.file.size_si);
	}
}

