/**
 * Created by A.J on 2017/10/22.
 */
$(document).ready(function(){
    if($("#coverpic").val() != ''){
        $('#coverImg').attr("src", $("#coverpic").val());
        $('#coverdel').removeClass('hidden');
    }
    var pic='';
    $('#upload').uploadify({
        auto:true,
        fileTypeExts:'*.jpg;*.png;*.gif;*.jpeg',
        multi:false,
        formData:{},
        fileSizeLimit:9999,
        buttonText:$('#buttonText').text(),
        showUploadedPercent:true,
        showUploadedSize:false,
        removeTimeout:3,
        uploader:'uploadCoverPic',
        onUploadComplete:function(file,data){
            pic = $("#webroot").text()+'data/uploads/'+data.replace('\\','/');
            $('#coverpic').val(pic);
            $('#coverImg').attr("src", pic);
            $('#coverdel').removeClass('hidden');
        }
    });
    $("#coverdel").click(function(){
        $.post("delbgpic", { pic: $('#coverpic').val() } );
        $('#coverpic').val('');
        $('#coverImg').attr("src", $("#webroot").text()+'public/common/images/default-thumbnail.png');
        $('#coverdel').addClass('hidden');
    });

    if($("#backgroundPic").val() != ''){
        $('#backgroundImg').attr("src", $("#backgroundPic").val());
        $('#backgrounddel').removeClass('hidden');
    }
    var pic_ico='';
    $('#bgPicUpload').uploadify({
        auto:true,
        fileTypeExts:'*.jpg;*.png;*.gif;*.jpeg',
        multi:false,
        formData:{},
        fileSizeLimit:9999,
        buttonText:$('#buttonText').text(),
        showUploadedPercent:true,
        showUploadedSize:false,
        removeTimeout:3,
        uploader:'uploadBgPic',
        onUploadComplete:function(file,data){
            pic_ico = $("#webroot").text()+'data/uploads/'+data.replace('\\','/');
            $('#backgroundPic').val(pic_ico);
            $('#backgroundImg').attr("src", pic_ico);
            $('#backgrounddel').removeClass('hidden');
        }
    });
    $("#backgrounddel").click(function(){
        $.post("delbgpic", { pic: $('#backgroundPic').val() } );
        $('#backgroundPic').val('');
        $('#backgroundImg').attr("src", $("#webroot").text()+'public/common/images/default-thumbnail.png');
        $('#backgrounddel').addClass('hidden');
    });
    $('#fengmianzi').colorpicker({
        format: "hex",
        align: 'left'
    });
});