@include('modals.internalRepo-modals')

@include('modal-button',[
    "class"     =>  "btn btn-primary mb-3",
    "target_id" =>  "internalRepoAddModal",
    "text"      =>  "Yerel Depo Ekle",
    "icon" => "fas fa-plus mr-2"
])

<div id="internalRepoTable">
    <br>
    <div class="overlay">
        <div class="spinner-border" role="status">
            <span class="sr-only">{{__('Loading')}}...</span>
        </div>
    </div>
</div>

<script>
    $('.modal').on('hidden.bs.modal', function(e){
        if (typeof $(this).find('form')[0] !== 'undefined'){
            $(this).find(".alert").fadeOut();
            $(this).find('form')[0].reset();
        }
    });
</script>

<script>
    PATH = "";
    NAME = "";
    CODENAME = "";
    LINK = ""

    function deleteInternalRepo(line){
        Swal.fire({
            title: "{{ __('Onay') }}",
            text: "{{ __('Silmek istediğinize emin misiniz?') }}",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085D6',
            cancelButtonColor: '#d33',
            cancelButtonText: "{{ __('İptal') }}",
            confirmButtonText: "{{ __('Evet') }}"
        }).then((result) => {
            if (result.value) {
                showSwal('{{__("Siliniyor...")}}','info',2000);
                var name = line.querySelector('#name').innerHTML;
                var path = line.querySelector('#path').innerHTML;
                let form = new FormData();
                form.append("name",name);
                form.append("path",path);
                request(API('delete_internal_repo'), form, function (res) {
                    showSwal("Silindi",'success',2000);
                    getInternalRepo();
                    Swal.close();
                }, function(res){
                    let error = JSON.parse(res);
                    showSwal(error.message,'error',2000);
                });
            }
        });
        
    }

    function gpgKeyExport(){
        showSwal('{{__("Export Alınıyor...")}}','info');
        let form = new FormData();
        form.append("repoName",NAME);
        form.append("path",PATH);
        form.append("link",LINK);
        request(API('gpg_key_export'), form, function (res) {
            let json = JSON.parse(res);
            Swal.fire({
                icon: 'success',
                title: 'Export Gpg Key',
                html: "Gpg keyi aşağıdaki komutu uygulayarak client bilgisayarlara ekleyebilirsiniz <blockquote class='quote-secondary'> wget -qO - "+ json.message +" | sudo apt-key add - </blockquote> ",
            })
        }, function(res){
            let error = JSON.parse(res);
            showSwal(error.message,'error',2000);

        });
    }

    function uploadPackage(upload){
        let data = new FormData();
        showSwal('{{__("Ekleniyor...")}}','info');
        data.append('name', upload.info.name);
        data.append('path', upload.info.file_path);
        data.append('repoName',NAME);
        data.append('repoPath',PATH);
        data.append('codeName',CODENAME);
        request(API("add_internal_repo_package"), data, function(response){
            try {
                showSwal("Eklendi",'success',2000);
                $('#fileUploadForm').trigger("reset");
                $(".progress").hide();
                $('.progress-bar').css('width', 0+'%').attr('aria-valuenow', 0);   
                reloadPackagesTable(PATH);
                Swal.close();
            } catch(e) {
                showSwal('{{__("Dosya karşı sunucuya gönderilirken hata oluştu!")}}','error',2000);
            }
        }, function(response){
            let error = JSON.parse(response);
            showSwal(error.message,'error');
        });
    }

    function deletePackage(line) {
        var packageName = line.querySelector('#name').innerHTML;
        showSwal('{{__("Siliniyor...")}}','info',2000);
        let form = new FormData();
        form.append("packageName",packageName);
        form.append("path",PATH);
        form.append('codeName',CODENAME);
        request(API('delete_internal_repo_package'), form, function (res) {
            showSwal("Silindi",'success',2000);
            reloadPackagesTable(PATH);
            Swal.close();
        }, function(res){
            let error = JSON.parse(res);
            showSwal(error.message,'error',2000);

        });
    }

    function getInternalRepo(){
        showSwal('{{__("Yükleniyor...")}}','info');
        request(API('get_internal_repo'), new FormData(), function (res) {
            $('#internalRepoTable').html(res).find('table').DataTable(dataTablePresets('normal'));
            $('#internalRepoAddModal').modal('hide');
            Swal.close();
        }, function(res){
            let error = JSON.parse(res);
            showSwal(error.message,'error',2000);
        });
    }

    function reloadPackagesTable(internalRepoPath){
        let formData = new FormData();
        formData.append("internalRepoPath",internalRepoPath)
        request(API('get_internal_repo_packages'), formData, function (res) {
            $('#internalRepoPackagesTable').html(res).find('table').DataTable(dataTablePresets('normal'));
        }, function(res){
            let error = JSON.parse(res);
            showSwal(error.message,'error',2000);
        });
    }

    function getInternalRepoPackages(line){
        showSwal('{{__("Yükleniyor...")}}','info');
        let internalRepoPath = line.querySelector("#path").innerHTML;
        let internalRepoName = line.querySelector("#name").innerHTML;
        let internalRepoLink = line.querySelector("#link").innerHTML;
        let internalRepoCodeName = line.querySelector("#codename").innerHTML;
        let formData = new FormData();
        formData.append("internalRepoPath",internalRepoPath)
        request(API('get_internal_repo_packages'), formData, function (res) {
            $('#internalRepoPackagesTable').html(res).find('table').DataTable(dataTablePresets('normal'));
            PATH = internalRepoPath;
            NAME = internalRepoName;
            CODENAME = internalRepoCodeName;
            LINK = internalRepoLink;
            $('#internalRepoPackagesComponent').find("#repoName").html(internalRepoName)
            $('#internalRepoPackagesComponent').find("#repoPath").html(internalRepoPath)
            $('#internalRepoPackagesComponent').find("#repoLink").html(`<a href='${internalRepoLink}' target="_blank">${internalRepoLink}</a>`)
            $('#internalRepoPackagesComponent').modal("show");
            Swal.close();
        }, function(res){
            let error = JSON.parse(res);
            showSwal(error.message,'error',2000);
        });
    }

</script>