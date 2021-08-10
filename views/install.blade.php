<div class="alert alert-info" role="alert">
  <i class="fas fa-info-circle mr-2"></i>{{__("Eklentiyi kullanabilmek için 'apt-mirror', 'apache2' ve 'reprepro' paketlerini sunucuya yüklemeniz gerekmektedir. Aşağıda yer alan 'Paketleri Yükle' butonunu kullanarak yükleyebilirsiniz.")}}
</div>

<button id="installButton" class="btn btn-secondary" onclick="startInstallation()">{{__("Paketleri Yükle")}}</button>

@component('modal-component',[
    "id" => "taskModal",
    "title" => "Package Installer",
])@endcomponent

<script>
    $('#taskModal').on('hidden.bs.modal', function (e) {
      $('#taskModal').find('.modal-body').html("");
    })

    function onTaskSuccess(){
      showSwal('{{__("İsteğiniz başarıyla tamamlandı")}}', 'success', 2000);
      setTimeout(function(){
          $('#taskModal').modal("hide"); 
      }, 2000);
      window.location.href = 'index';
    }

    function onTaskFail(){
      showSwal('{{__("İsteğiniz yerine getirilirken bir hata oluştu!")}}', 'error', 2000);
    }

    function startInstallation(){
      showSwal('{{__("Yükleniyor...")}}','info',2000);
      request(API('install_package'), new FormData(), function (response) {
        $("#installButton").attr("disabled","true");
        $('#taskModal').find('.modal-body').html(JSON.parse(response).message);
        $('#taskModal').modal("show"); 
      }, function(response){
          let error = JSON.parse(response);
          showSwal(error.message,'error',2000);
      })
    }
</script>