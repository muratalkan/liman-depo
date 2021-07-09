<div class="alert alert-warning" role="alert">
  {{__("Eklentiyi kullanabilmek için 'apt-mirror', 'reprepro' ve 'apache2' paketlerini sunucuya yüklemeniz gerekmektedir. Aşağıda yer alan 'Paketleri Yükle' butonunu kullanarak yükleyebilirsiniz.")}}.
</div>

<button id="installButton" class="btn btn-secondary" onclick="startInstallation()">{{__("Paketleri Yükle")}}</button>

@component('modal-component',[
    "id" => "taskModal",
    "title" => "Görev İşleniyor",
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