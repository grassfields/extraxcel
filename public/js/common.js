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
								uploadStart($filelist, fl);
							});
	})
	.on('fileuploadprogress', function (e, data) {
		var val = parseInt(data.loaded / data.total * 100, 10);
		data.files[0].bar.css('width',  val + '%')
	})
	.on('fileuploaddone', function (e, data) {
		data.files[0].bar.parents(".progress").delay(2000).fadeOut(1000);
		data.files[0].li.removeClass('uploading');
	})




///////////////////////////////////////////
//  アップロード開始処理
function uploadStart($parent, fl) {
	var $finfo = $("<li>");
	var $bar   = $("<div class='progress-bar' role='progressbar' aria-valuenow='0' aria-valuemin='0' aria-valuemax='100' style='width: 0%;'>");
	var no = $parent.data('cnt') + 1;
	$parent.data('cnt', no);
	
	$finfo.append($("<span>").text(no))
	      .append($("<p>").text(fl.name))
	      .append($("<div class='progress' style='height:3px;'>").append($bar));
	$finfo.addClass('uploading');
	fl.bar = $bar;
	fl.li  = $finfo;
	$parent.prepend($finfo);
}


