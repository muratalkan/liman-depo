
@include('modals.internalRepo-modals')

<button type="button" class="btn btn btn-outline-primary mb-3 mt-2 mr-1" data-toggle="modal" data-target="#internalRepoAddModal">
    <i class="fas fa-plus mr-1"></i> {{__('Yerel Depo Ekle')}}
</button>
<button type="button" class="btn btn btn-outline-primary mb-3 mt-2" id="ufwRuleBtn" onclick="getUploadedFiles()">
    <i class="fas fa-folder mr-1"></i> {{__('Dosyalar')}}
</button>


<div id="internalRepoTable">
    <br>
    <div class="overlay">
        <div class="spinner-border" role="status">
            <span class="sr-only">{{__('Loading')}}...</span>
        </div>
    </div>
</div>


<script>
    PATH = "";
    NAME = "";
    CODENAME = "";
    LINK = ""

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

    function getInternalRepoPackages(line){
        showSwal('{{__("Yükleniyor...")}}','info');
        let internalRepoPath = line.querySelector("#path").innerHTML;
        let internalRepoName = line.querySelector("#name").innerHTML;
        let internalRepoLink = line.querySelector("#link").innerHTML;
        let internalRepoCodeName = line.querySelector("#codename").innerHTML;
        let formData = new FormData();
            formData.append("internalRepoPath",internalRepoPath)
            formData.append("internalRepoName",internalRepoName)
        request(API('get_internal_repo_packages'), formData, function (res) {
            let json = JSON.parse(res);
            $('#internalRepoPackagesTable').html(json.message.table).find('table').DataTable(dataTablePresets('normal'));
            PATH = internalRepoPath;
            NAME = internalRepoName;
            CODENAME = internalRepoCodeName;
            LINK = internalRepoLink;
            $('#internalRepoPackagesComponent').find("#repoName").html(internalRepoName)
            $('#internalRepoPackagesComponent').find("#repoPath").html('<b>{{__("Depo Dizini")}}</b>: '+ internalRepoPath)
            $('#internalRepoPackagesComponent').find("#repoSourceList").html('<b>{{__("Source List")}}</b>: '+ json.message.sourceList)
            $('#internalRepoPackagesComponent').modal("show");
            Swal.close();
        }, function(res){
            let error = JSON.parse(res);
            showSwal(error.message,'error',2000);
        });
    }

    function deleteInternalRepo(line){
        Swal.fire({
            title: "{{ __('Onay') }}",
            text: "{{ __('Yerel depoyu silmek istediğinize emin misiniz?') }}",
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

    function gpgKeyExport(row){
        showSwal('{{__("GPG anahtarı dışa aktarılıyor...")}}','info');
        let form = new FormData();
            form.append("repoName",NAME);
            form.append("path",PATH);
            form.append("link",LINK);
        request(API('gpg_key_export'), form, function (response) {
            const output = JSON.parse(response).message;
            Swal.fire({
                icon: 'success',
                title: "{{__('GPG Anahtarı')}}",
                showCancelButton: true, showConfirmButton: false,
                cancelButtonText: "{{ __('Kapat') }}",
                html: `{{__('Aşağıdaki komutu kullanarak GPG anahtarını istemcinize ekleyebilirsiniz.')}} 
                <blockquote class='quote-secondary'>${output}</blockquote>
                <small>*{{__('Depoyu istemciye eklemek için GPG anahtarı gereklidir')}}</small>
                `,
            })
        }, function(response){
            const error = JSON.parse(response).message;
            showSwal(error,'error',2000);

        });
    }

    function uploadPackage(upload){
        let data = new FormData();
        showSwal('{{__("Ekleniyor...")}}','info');
        data.append('name', upload.info.name);
        data.append('path', upload.info.file_path);
        data.append('fileSize', upload.info.size);
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
            showSwal(error.message,'error', 2000);
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

    function reloadPackagesTable(internalRepoPath){
        let formData = new FormData();
        formData.append("internalRepoPath",internalRepoPath)
        request(API('get_internal_repo_packages'), formData, function (res) {
            let json = JSON.parse(res);
            $('#internalRepoPackagesTable').html(json.message.table).find('table').DataTable(dataTablePresets('normal'));
        }, function(res){
            let error = JSON.parse(res);
            showSwal(error.message,'error',2000);
        });
    }

    function openLocalRepoURL(){
        window.open(LINK, '_blank').focus();
    }

    function getRepoDiskInfo(row){
        showSwal('{{__("Yükleniyor...")}}','info');
        const localRepoName = row.querySelector('#name').innerHTML;
        const storagePath = row.querySelector('#path').innerHTML;
        let formData = new FormData();
            formData.append('repoName', localRepoName);
            formData.append('storagePath', storagePath);
        getDiskAlert(`${storagePath}`, formData, 'get_repoDisk_info')
    }

    var filesLink;
    function getUploadedFiles(){
        showSwal('{{__("Yükleniyor...")}}','info');
        request(API('get_uploaded_files'), new FormData(), function (response) {
            const output = JSON.parse(response).message;
            $('#uploadedFilesTable').html(output.filesTable).find('table').DataTable(dataTablePresets('normal'));
            $('#uploadedFilesModal').modal("show");
            filesLink = output.filesLink;
            Swal.close();
        }, function(response){
            const error = JSON.parse(response).message;
            showSwal(error, 'error', 2000);
        });
    }

    function uploadFile(upload){
        Swal.close();
        showSwal('{{__("Dosya yükleniyor...")}}', 'info');
        let data = new FormData();
            data.append('fileName', upload.info.name);
            data.append('filePath', upload.info.file_path);
            data.append('fileSize', upload.info.size);
        request(API("upload_file"), data, function(response){
            const error = JSON.parse(response).message;
            try {
                showSwal("Dosya yüklendi",'success',2000);
                $('#fileUploadForm').trigger("reset");
                $(".progress").hide();
                $('.progress-bar').css('width', 0+'%').attr('aria-valuenow', 0);
                $('#uploadFileModal').modal('hide');
                getUploadedFiles();
            }catch(error) {
                showSwal('{{__("Dosya sunucuya yüklenirken hata oluştu!")}}', 'error', 2000);
            }
        }, function(response){
            const error = JSON.parse(response).message;
            showSwal(error, 'error', 2000);
        });
    }

    function deleteFile(row){
        const fileName = row.querySelector("#fileName").innerHTML;
        Swal.fire({
            title: `<h6>${fileName}</h6>`,
            text: "{{ __('Dosyayı silmek istediğinize emin misiniz?') }}",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085D6', cancelButtonColor: '#d33',
            cancelButtonText: "{{ __('İptal') }}", confirmButtonText: "{{ __('Sil') }}"
        }).then((result) => {
            if (result.value) {
                showSwal('{{__("Siliniyor...")}}','info', 5000);
                let data = new FormData();
                    data.append('fileName', fileName);
                request(API("remove_file"), data, function(response){
                    const output = JSON.parse(response).message;
                    showSwal(output, 'success', 2000);
                    setTimeout(function() { getUploadedFiles(); }, 1000);
                },function(response){
                    const error = JSON.parse(response).message;
                    showSwal(error, 'error', 2000);
                });
            }
        });
    }

    function openFilesURL(){
        window.open(filesLink, '_blank').focus();
    }

    function getFilesDiskInfo(){
        showSwal('{{__("Yükleniyor...")}}','info');
        getDiskAlert(`Files2Share`, new FormData(), 'get_filesDisk_info')
    }

</script>