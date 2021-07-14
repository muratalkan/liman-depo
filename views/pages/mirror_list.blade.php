@include('modals.mirrorList-modals')

<button type="button" class="btn btn btn-outline-primary mb-3 mt-2" data-toggle="modal" data-target="#addMirror">
    <i class="fas fa-plus mr-1"></i> {{__('Aynalama Ekle')}}
</button>

<div id="mirrorTable">
    <br>
    <div class="overlay">
        <div class="spinner-border" role="status">
            <span class="sr-only">{{__('Loading')}}...</span>
        </div>
    </div>
</div>


<script>
    var MIRRORNAME = "";
    var STORAGEPATH = "";
    var mirroring  = false;

    resetModalForm('#addMirrorAddress');
    setLinkInputOnChange();

    $('#editMirror').on('hidden.bs.modal', function (e) {
        resetModal('#editMirror');
    })
    $('#editAddressComponent').on('shown.bs.modal', function (e) {
        setLinkInput();
    })
    $('#addMirrorAddress').on('shown.bs.modal', function (e) {
        resetModal('#addMirrorAddress');
    })

    setAttr_Required('#addMirror', 'description', false);
    setAttr_Required('#editMirror', 'description', false);


    var mirrorTimeout;
    function getMirrors(msg='', refresh=false){
        if(refresh == false){
            showSwal('{{__("Yükleniyor...")}}','info');
        }
        request(API('get_mirrors'), new FormData(), function (response) {
            $('#mirrorTable').html(response).find('table').DataTable(dataTablePresets('normal'));
            if(refresh == false){
                $('#addMirror').modal('hide');
                $('#editMirror').modal('hide');
                Swal.close();
            }
            setMirrorStatus();
            mirrorTimeout && clearTimeout(mirrorTimeout);
            mirrorTimeout = setTimeout(function () {
                if($(`a[href=\"#mirrorList\"]`).hasClass("active")){
                    getMirrors('', true);
                }
            }, 30000);
        }, function(response){
            const error = JSON.parse(response).message;
            showSwal(error,'error',2000);
        })
    }

    function startMirror(row){
        let mirrorName = row.querySelector("#name").innerHTML;
        Swal.fire({
            title: `${mirrorName}`,
            text: "{{ __('Bu işlem uzun sürecektir. Aynalamayı başlatmak istediğinize emin misiniz?') }}",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: "#28a745",
            cancelButtonText: "{{ __('İptal') }}",
            confirmButtonText: "{{ __('Başlat') }}",
            showLoaderOnConfirm: true,
              preConfirm: () => {
               return new Promise((resolve) => {         
                    let formData = new FormData();
                        formData.append("mirrorName",mirrorName);
                    request(API("start_mirror") ,formData,function(response){
                        const message = JSON.parse(response).message;
                        mirroring  = true;
                        Swal.fire({title:"{{ __('Başarılı') }}", text: message, type: "success", showConfirmButton: false});
                        setTimeout(function() { getMirrors(); }, 1000);
                    }, function(response){
                        const error = JSON.parse(response).message;
                        Swal.fire("{{ __('Hata!') }}",error, "error");
                    }); 
                })
              },
              allowOutsideClick: false
        });
        
    }

    function stopMirror(row){
        let mirrorName = row.querySelector("#name").innerHTML;
        Swal.fire({
            title: `${mirrorName}`,
            text: "{{ __('Bu işlemi durdurmak istediğinize emin misiniz?') }}",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonText: "{{ __('İptal') }}",
            confirmButtonText: "{{ __('Durdur') }}",
            showLoaderOnConfirm: true,
              preConfirm: () => {
               return new Promise((resolve) => {         
                    let formData = new FormData();
                        formData.append("mirrorName",mirrorName);
                    request(API("stop_mirror") ,formData,function(response){
                        const message = JSON.parse(response).message;
                        mirroring = false;
                        Swal.fire({title:"{{ __('Başarılı') }}", text: message, type: "success", showConfirmButton: false});
                        setTimeout(function() { getMirrors(); }, 1000);
                    }, function(response){
                        const error = JSON.parse(response).message;
                        Swal.fire("{{ __('Hata!') }}",error, "error");
                    });   
                })
              },
              allowOutsideClick: false
        });
    }

    function deleteMirror(row){
        let mirrorName = row.querySelector("#name").innerHTML;
        Swal.fire({
            title: `${mirrorName}`,
            text: "{{ __('Aynalamayı silmek istediğinize emin misiniz?') }}",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085D6',
            cancelButtonColor: '#d33',
            cancelButtonText: "{{ __('İptal') }}",
            confirmButtonText: "{{ __('Sil') }}",
            showLoaderOnConfirm: true,
              preConfirm: () => {
               return new Promise((resolve) => {            
                    let formData = new FormData();
                    let storagePath = row.querySelector("#storagePath").innerHTML;
                        formData.append("name",mirrorName);
                        formData.append("storagePath",storagePath);
                    request(API("delete_mirror") ,formData,function(response){
                        const message = JSON.parse(response).message;
                        Swal.fire({title:"{{ __('Başarılı') }}", text: message, type: "success", showConfirmButton: false});
                        setTimeout(function() { getMirrors(); }, 1000);
                    }, function(response){
                        const error = JSON.parse(response).message;
                        Swal.fire("{{ __('Hata!') }}",error, "error");
                    });
                })
              },
              allowOutsideClick: false
        });
    }

    function moveMirror(row){
        let mirrorName = row.querySelector('#name').innerHTML;
        let oldPath = row.querySelector('#oldStoragePath').innerHTML;
        Swal.fire({
            title: `<h5><span class='badge badge-primary badge-pill'>'${oldPath}</span>/<span class='badge badge-warning badge-pill'>${mirrorName}'</span></h5>`,
            input: 'text',
            inputPlaceholder: "{{__('Yeni aynalama dizini')}} (e.g. /home/myMirror)",
            text: "{{ __('Bu işlem dosya taşıma işlemi değildir. Sadece aynalamanın mevcut konumunu güncelleyecektir. Bu yüzden aynalama dizinini güncellemeden önce aynalamanın belirtilen dizinde olduğundan emin olun.') }}",
            showCancelButton: true,
            confirmButtonText: "{{__('Güncelle')}}", cancelButtonText: "{{__('İptal')}}",
            inputValidator: (path) => {
                if(!path){
                    return "{{__('Geçerli bir dizin giriniz')}}!";
                }
            },
        }).then((result) => {
            if (result.value) {
                result.value = (result.value[0] !== '/') ? '/'+result.value : result.value;
                moveMirror_Confirm(row, result.value);
            }
        });
    }

    function moveMirror_Confirm(row, newPath){
        let mirrorName = row.querySelector('#name').innerHTML;
        let oldPath = row.querySelector('#oldStoragePath').innerHTML;
        Swal.fire({
            html: `
                <div class='row'>
                    <div class='col-md-12 center-block text-center'>
                        <h5><span class='badge badge-danger badge-pill'>'${oldPath}/${mirrorName}'</span></h5>
                    </div>
                </div>
                <div class='row'>
                    <div class='col-md-12 center-block text-center'>
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                </div>
                <div class='row'>
                    <div class='col-md-12 center-block text-center'>
                        <h5><span class='badge badge-success'>'${newPath}/${mirrorName}'</span></h5>
                    </div>
                </div>
                <div class='row'>
                    <div class='col-md-12 center-block text-center'>
                        <p>{{__('Aynalamanın eski ve yeni konumu yukarıda sırasıyla belirtilmiştir. Eğer aynalamayı halen taşımadıysanız aşağıdaki komutu kullanarak taşıyabilirsiniz.')}}</p>
                        <blockquote class='quote-secondary'>sudo mv '${oldPath}/${mirrorName}' '${newPath}'</blockquote>
                        <small>*{{__('Taşıma işlemi aynalama boyutuna göre zaman alabilir')}}</small>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonColor: "#28a745",
            confirmButtonText: "{{__('Onayla')}}", cancelButtonText: "{{__('İptal')}}",
            showLoaderOnConfirm: true,
            preConfirm: () => {
               return new Promise((resolve) => {            
                    let formData = new FormData();
                        formData.append("mirrorName", mirrorName);
                        formData.append("oldPath", oldPath);
                        formData.append("newPath", newPath);
                    request("{{API('move_mirror')}}", formData, function(response) {
                        const message = JSON.parse(response).message;
                        Swal.fire({title:"{{ __('Başarılı') }}", text: message, type: "success", showConfirmButton: false});
                        setTimeout(function() { getMirrors(); }, 1000);
                    },function(response) {
                        const error = JSON.parse(response).message;
                        Swal.fire("{{ __('Hata!') }}", error, "error");
                    });
                })
              },
              allowOutsideClick: false
        });
    }

    function getDiskInfo(row){
        showSwal('{{__("Yükleniyor...")}}','info');
        const storagePath = row.querySelector('#storagePath').innerHTML;
        const mirrorName = row.querySelector('#name').innerHTML;
        let formData = new FormData();
            formData.append("storagePath", storagePath);
            formData.append("mirrorName", mirrorName);
            getDiskAlert(`${storagePath}/${mirrorName}`, formData, 'get_disk_info')
    }

    var modalTimeout;
    function getLinksAndPaths(row, refresh=false){
        if(refresh == false){
            $('.overlay').show();
            showSwal('{{__("Yükleniyor...")}}','info');
        }
        let mirrorName = (row == null) ? MIRRORNAME : row.querySelector("#name").innerHTML;
        let storagePath = (row == null) ? STORAGEPATH : row.querySelector("#storagePath").innerHTML;
        let formData = new FormData();
            formData.append("mirrorName",mirrorName);
            formData.append("storagePath", storagePath);
        request(API('get_links_and_paths'), formData, function (response) {
            $('#linkPathTable').html(response).find('table').DataTable(dataTablePresets('normal'));
            setFolderAndLinkStatus();
            if(refresh == false){
                MIRRORNAME = mirrorName;
                STORAGEPATH = storagePath;
                $('#createLinkModal').modal('hide');
                $('#linksAndPathsComponent').modal("show");
                $('.overlay').hide();
                Swal.close();
            }
            modalTimeout && clearTimeout(modalTimeout);
            modalTimeout = setTimeout(function () {
                if($('#linksAndPathsComponent').hasClass('show') && mirroring  == true){
                    getLinksAndPaths(row, true);
                }
            }, 10000);
        }, function(response){
            const error = JSON.parse(response).message;
            $('.overlay').hide();
            showSwal(error,'error',2000);
        })
    }

    function createSymbolicLink(row){
        let mirrorName = row.querySelector('#name').innerHTML;
        let storagePath =  row.querySelector('#storagePath').innerHTML;
        let extUrl = row.querySelector('#extUrl').innerHTML;
		let	extRepoName = row.querySelector('#extRepoName').innerHTML;
        let downloadPath = row.querySelector('#downloadPath').innerHTML;
        let oldLink = row.querySelector('#oldLinkName').innerHTML;
        Swal.fire({
            title: "{{__('Yeni Sembolik Link Oluştur')}}",
            input: 'text',
            text : `${downloadPath}`,
            inputPlaceholder: "{{__('Yeni sembolik link adını giriniz')}} (e.g. debian)",
            showCancelButton: true,
            confirmButtonText: "{{__('Oluştur')}}", cancelButtonText: "{{__('İptal')}}",
            inputValidator: (value) => {
                if (!value || value[0] == "/") {
                    return "{{__('Geçerli bir sembolik link adı giriniz')}}!";
                }
            },
            showLoaderOnConfirm: true,
              preConfirm: (value) => {
                return new Promise((resolve) => {
                    let formData = new FormData();
                        formData.append("mirrorName", mirrorName);
                        formData.append("storagePath", storagePath);
                        formData.append("extUrl", extUrl);
                        formData.append("extRepoName", extRepoName);
                        formData.append("linkName", value);
                        formData.append("downloadPath", downloadPath);
                        formData.append("oldLinkName", oldLink);
                    request("{{API('create_mirror_link')}}", formData, function(response) {
                        const message = JSON.parse(response).message;
                        Swal.fire({title:"{{ __('Başarılı') }}", text: message, type: "success", showConfirmButton: false});
                        setTimeout(function() { getLinksAndPaths(row); }, 1000);
                    }, function(response) {
                        const error = JSON.parse(response).message;
                        Swal.fire("{{ __('Hata!') }}", error, "error");
                    });
                })
              },
              allowOutsideClick: false
        });
    }

    function openLinkAddress(row){
        const linkAddr = row.querySelector('#link').innerHTML;
        window.open(linkAddr, '_blank');
    }

    function getSizeMirror(){
        showSwal('{{__("Hesaplanıyor...")}}', 'info');
        let formData = new FormData();
            formData.append("mirrorName", MIRRORNAME)
        request(API('get_size_mirror') ,formData,function(response){
            $('#taskModal').find('.modal-body').html(JSON.parse(response).message);
            $('#taskModal').modal("show"); 
            Swal.close();
        }, function(response){
            const error = JSON.parse(response).message;
            showSwal(error, 'error', 2000);
        });
    }

    function getSourcesList(){
        showSwal('{{__("Yükleniyor...")}}', 'info');
        let formData = new FormData();
            formData.append("mirrorName", MIRRORNAME)
            formData.append("storagePath", STORAGEPATH);
        request(API('get_sources_list') ,formData,function(response){
            const output = JSON.parse(response).message;
            $("#sourcesListModal").find('.list-group').html('');
            output.forEach(function(item){
                $("#sourcesListModal").find('.list-group').append(`
                    <li class="list-group-item">${item.sourceName}</li>
                `);
            });
            $("#sourcesListModal").modal('show');
            Swal.close();
        }, function(response){
            const error = JSON.parse(response).message;
            showSwal(error, 'error', 2000);
        });
    }
    

    function getAddress(row, mirrorName2, storagePath2){
        showSwal('{{__("Yükleniyor...")}}','info');
        let mirrorName = (row == null) ? mirrorName2 : row.querySelector("#name").innerHTML;
        let storagePath = (row == null) ? storagePath2 : row.querySelector("#storagePath").innerHTML;
        let formData = new FormData();
            formData.append("mirrorName", mirrorName);
            formData.append("storagePath", storagePath);
        request(API('get_address'), formData, function (response) {
            MIRRORNAME = mirrorName;
            STORAGEPATH = storagePath;
            $('#addressTable').html(response).find('table').DataTable(dataTablePresets('normal'));
            $('#addressComponent').modal("show");
            setAddressStatus();
            Swal.close();
        }, function(response){
            const error = JSON.parse(response).message;
            showSwal(error,'error',2000);
        })
    }

    function addMirrorAddress(){
        showSwal('{{__("Ekleniyor...")}}','info');
        let activeState = $('#addMirrorAddress').find('select[name=activeState]').val();
        let address = $('#addMirrorAddress').find('input[name=address]').val();
        let link = $('#addMirrorAddress').find('input[name=link]').val();
        let formData = new FormData();
            formData.append("activeState",activeState);
            formData.append("address",address);
            formData.append("link",link);
            formData.append("mirrorName", MIRRORNAME);
            formData.append("storagePath", STORAGEPATH);
        request(API('add_mirror_address'), formData, function (response) {
            const output = JSON.parse(response).message;
            Swal.close();
            $('#addMirrorAddress').modal("hide");
            getAddress(null, MIRRORNAME, STORAGEPATH);
            showSwal(output,'info',2000);
        }, function(response){
            const error = JSON.parse(response).message;
            showSwal(error,'error',2000);
        });
    }

    function editMirrorAddress(){
        showSwal('{{__("Güncelleniyor...")}}','info');
        let address = $('#editAddressComponent').find('input[name=address]').val();
        let oldAddress = $('#editAddressComponent').find('input[name=oldAddress]').val();
        let link = $('#editAddressComponent').find('input[name=link]').val();
        let oldlink = $('#editAddressComponent').find('input[name=oldLink]').val();
        let activeState = $('#editAddressComponent').find('select[name=activeState]').val();
        let oldActiveState = $('#editAddressComponent').find('input[name=oldActiveState]').val();
        let formData = new FormData();
            formData.append('mirrorName', MIRRORNAME);
            formData.append('storagePath', STORAGEPATH);
            formData.append('activeState', activeState);
            formData.append('oldActiveState', oldActiveState);
            formData.append('address', address);
            formData.append('oldAddress', oldAddress);
            formData.append('link', link);
            formData.append('oldLink', oldlink);
        request(API("edit_mirror_address"), formData, function(response) {
            const output = JSON.parse(response).message;
            getAddress(null, MIRRORNAME, STORAGEPATH);
            showSwal(output,'success',2000);
            $('#editAddressComponent').modal("hide");
        }, function(response){
            const error = JSON.parse(response).message;
            showSwal(error,'error',2000);
        });
    }

    function deleteAddress(row){
        let address = row.querySelector("#address").innerHTML;
        Swal.fire({
            title: `<h5>${address}</h5>`,
            text: "{{ __('Depo adresini silmek istediğinize emin misiniz?') }}",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085D6',
            cancelButtonColor: '#d33',
            cancelButtonText: "{{ __('İptal') }}",
            confirmButtonText: "{{ __('Sil') }}"
        }).then((result) => {
            if (result.value) {
                showSwal('{{__("Siliniyor...")}}','info');
                let link = row.querySelector("#link").innerHTML;
                let storagePath = row.querySelector("#storagePath").innerHTML;
                let mirrorName = row.querySelector("#mirrorName").innerHTML;
                let activeState = row.querySelector("#activeState").innerHTML;
                let formData = new FormData();
                    formData.append('mirrorName', mirrorName);
                    formData.append('address', address);
                    formData.append('link', link);
                    formData.append('storagePath', storagePath);
                    formData.append('activeState', activeState);
                request(API("delete_mirror_address"), formData, function(response) {
                    const output = JSON.parse(response).message;
                    getAddress(null, mirrorName, storagePath);
                    getMirrors();
                    showSwal(output,'success',2000);
                }, function(response){
                    const error = JSON.parse(response).message;
                    showSwal( error,'error',2000);
                });
            }
        });
    }

    function addCron(){
        showSwal('{{__("Ekleniyor...")}}','info');
        let cronOption = $('#addCronComponent').find('select[name=cronOption]').val()
        let formData = new FormData();
        let cronTime = "";
        if(cronOption === "interface"){
            let cronMinutes = $('#addCronComponent').find('select[name=cronMinutes]').val()
            let cronHours = $('#addCronComponent').find('select[name=cronHours]').val()
            let cronDays = $('#addCronComponent').find('select[name=cronDays]').val()
            let cronMonths = $('#addCronComponent').find('select[name=cronMonths]').val()
            let cronWeekDays = $('#addCronComponent').find('select[name=cronWeekDays]').val()
            cronTime = cronMinutes+" "+cronHours+" "+cronDays+" "+cronMonths+" "+cronWeekDays
        }else{
            let cronTime = $('#addCronComponent').find('input[name=custom]').val();
        }
        formData.append('time', cronTime);
        formData.append('mirrorName', MIRRORNAME);
        request(API("add_cron"), formData, function(res) {
            showSwal('{{__("Eklendi..")}}','success',2000);
            getMirrors();
            $('#addCronComponent').modal("hide")
        }, function(response){
            let error = JSON.parse(response);
            showSwal( error.message,'error',2000);
        });
    }

    function openAddCron(row){
        MIRRORNAME = row.querySelector("#name").innerHTML;
        let cron = row.querySelector("#cron").innerHTML;
        if(cron === "-"){
            $('#addCronComponent').modal("show");
        }else{
            $('#editCronComponent').find('input[name=time]').val(cron);
            $('#editCronComponent').modal("show");
        }
    }

    $('#addCronComponent').find('input[name=cronClock]').val("10:45")

    $('[name=cronOption]').change(function(){
        let val = $(this).val();
        if(val === "interface"){
            $('#addCronComponent').find('#cronJob').show();
            $('#addCronComponent').find('.text_input').hide();
        }else {
            $('#addCronComponent').find('#cronJob').hide();
            $('#addCronComponent').find('.text_input').show();
        }
    });

    function editCron(){
        showSwal('{{__("Güncelleniyor...")}}','info');
        let formData = new FormData();
        let time = $('#editCronComponent').find('input[name=time]').val()
        formData.append('time', time);
        formData.append('mirrorName', MIRRORNAME);
        request(API("edit_cron"), formData, function(res) {
            getMirrors();
            showSwal('{{__("Güncellendi")}}','success',2000);
            $('#editCronComponent').modal("hide")
        }, function(response){
            let error = JSON.parse(response);
            showSwal( error.message,'error',2000);
        });
    }

    function removeCron(){
        showSwal('{{__("Kaldırılıyor...")}}', 'info');
        let formData = new FormData();
        formData.append('mirrorName', MIRRORNAME);
        request(API("remove_cron"), formData, function(res) {
            getMirrors();
            showSwal('{{__("Kaldırıldı")}}', 'success', 2000);
            $('#editCronComponent').modal("hide")
        }, function(response){
            let error = JSON.parse(response);
            showSwal( error.message,'error',2000);
        });
    }

    function setMirrorStatus(){
        const table = $('#mirrorTable');
        table.find("td[id='status']").each(function(){
            if($(this).text() === '1'){ 
                $(this).html(`
                            <a>{{__('İndiriliyor')}}</a>
                            <span class="spinner-border text-success spinner-border-sm" role="status" aria-hidden="true"></span>
                        `);
            }else{//no download
                $(this).html("{{__('İndirme Yok')}}");
            }
        });
        setMirrorButtons();
    }
    
    function setMirrorButtons(){
        const table = $('#mirrorTable');
        table.find('th').eq(5).addClass("text-center"); table.find('th').eq(6).addClass("text-center");
        table.find("td[id='status']").addClass("text-center"); table.find("td[id='operation']").addClass("text-center");
        table.find("td[id='operation']").each(function(){
            if($(this).text() === '1'){ 
                $(this).html(`<button class="btn btn-xs btn-danger" onclick='stopMirror(this.parentNode.parentNode)'><i class="fa fa-stop"></i></button>`);
            }else{//no download
                $(this).html(`<button class="btn btn-xs btn-success" onclick='startMirror(this.parentNode.parentNode);'><i class="fa fa-play"></i></button>`);
            }
        });
    }

    function setLinkInputOnChange(){
        $('.modal').find('select[name="activeState"]').on('change', function(){
            if($(this).val() == 'true'){
                $('.modal').find("input[name='link']").parent().show();
            }else{
                $('.modal').find("input[name='link']").parent().hide();
            }
        });
    }

    function setLinkInput(){
        const selectedVal = $('.modal').find("input[name='oldActiveState']").val();
        if(selectedVal === 'true'){
            $('.modal').find("input[name='link']").parent().show();
        }else{
            $('.modal').find("input[name='link']").parent().hide();
        }
    }

    function setFolderAndLinkStatus(){
        const table = $('#linkPathTable');
        table.find("td[id='checkLink']").each(function(){
            if($(this).text() === '1'){ 
                $(this).parent().find("td[id='link']").css("color", "blue");
            }else{//does not exist
                $(this).parent().find("td[id='link']").css("color", "red");
            }
        });
        table.find("td[id='checkDownload']").each(function(){
            if($(this).text() === '1'){ 
                $(this).parent().find("td[id='downloadPath']").css("color", "green");
            }else{//does not exist
                $(this).parent().find("td[id='downloadPath']").css("color", "red");
            }
        });
    }

    function setAddressStatus(){
        const table = $('#addressComponent');
        table.find("td[id='activeState']").each(function(){
            if($(this).text() === 'true'){ 
                $(this).parent().find("td[id='activeStateTxt']").html(`<small class="badge badge-primary">{{__('Aktif')}}</small>`);
            }else{//inactive address
                $(this).parent().find("td[id='activeStateTxt']").html(`<small class="badge badge-secondary">{{__('İnaktif')}}</small>`);
            }
        });
    }

    function resetModal(modal){
        switch(modal) {
            case '#editMirror':
                    $(modal).find("[name='set_nthreads']").parent().hide();
                    $(modal).find("[name='set_tilde']").parent().hide();
                break;
            case '#addMirrorAddress':
                    $(modal).find('select').val('true');
                    $(modal).find("input[name='link']").parent().show();
                break;
            default:
        }
    }

    function editMirrorConfig(){
        resetModal('#editMirror');
        $('#editMirror').find('#editMirrorConfig').change(function() {
            if($(this).prop('checked')) {
                $('#editMirror').find("[name='set_nthreads']").parent().show();
                $('#editMirror').find("[name='set_tilde']").parent().show();
            }else {
                $('#editMirror').find("[name='set_nthreads']").parent().hide();
                $('#editMirror').find("[name='set_tilde']").parent().hide();
            }
        });
    }
    editMirrorConfig();


</script>
