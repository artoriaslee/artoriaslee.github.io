/**
 * Created by A.J on 2017/12/9.
 */
$(document).ready(function(){
    $('#upload').uploadify({
        auto:true,
        fileTypeExts:'*.zip',
        multi:false,
        formData:{},
        fileSizeLimit:9999999,
        buttonText:$('#buttonText').text(),
        showUploadedPercent:true,//是否实时显示上传的百分比，如20%
        showUploadedSize:false,
        removeTimeout:3,
        uploader:'sysupgrade',
        onUploadStart:function(){
            $('#shengjizhuangtai').text($('#zhengzai').text()).removeClass("hidden");
        },
        onUploadComplete:function(file,data){
            if(data == 'ok'){
                $('#shengjizhuangtai').text($('#chenggong').text());
            }
            else if(data == 'fail'){
                $('#shengjizhuangtai').text($('#shibai').text());
            }
        }
    });
});