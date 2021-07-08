<script>

    $('.modal').on('hidden.bs.modal', function(e){
        if (typeof $(this).find('form')[0] !== 'undefined'){
            $(this).find(".alert").fadeOut();
            $(this).find('form')[0].reset();
        }
    });
    
    function resetModalForm(modal){
        $(modal).on('hidden.bs.modal', function(e){
            $(this).find('input').val("");
        });
    }

    function setAttr_Required(modal, nameField, bool){
        $(modal).find(`input[name='${nameField}']`).attr("required", bool);
    }

    function setAttr_Readonly(modal, nameField, bool){
        $(modal).find(`input[name='${nameField}']`).prop("readonly", bool);
    }

    function setInputValue(modal, input, value){
        $(modal).find('input[name="'+input+'"]').val(value)
    }

    function getDiskAlert(title, formData, route){
        showSwal('{{__("Yükleniyor...")}}','info');
        request(`{{API('${route}')}}`, formData, function(response) {
            const data = JSON.parse(response).message;
            color = data.DirectoryStatus === '1' ? 'success' : 'danger';
            Swal.close();
            Swal.fire({
                title: `<h5><span class='badge badge-${color} badge-pill'>'${title}'</span></h5>`,
                width: '550px',
                html:
                    `
                    <li class='list-group-item d-flex justify-content-between align-items-center'><strong>{{__("Bağlanılan Yer")}}:</strong><span class='badge badge-pill'>${data.MountedOn}</span> </li>
                    <li class='list-group-item d-flex justify-content-between align-items-center'><strong>{{__("Dosya Sistemi")}}:</strong><span class='badge badge-pill'>${data.Filesystem}</span> </li>
                    <li class='list-group-item d-flex justify-content-between align-items-center'><strong>{{__("Tür")}}:</strong><span class='badge badge-pill'>${data.Type}</span> </li>
                    <li class='list-group-item d-flex justify-content-between align-items-center'><strong>{{__("Toplam Boyut")}}:</strong><span class='badge badge-secondary badge-pill'>${data.Size}</span> </li>
                    <li class='list-group-item d-flex justify-content-between align-items-center'><strong>{{__("Kullanılan")}}:</strong><span class='badge badge-danger badge-pill'>${data.Used} (${data.UsedPercentage})</span> </li>
                    <li class='list-group-item d-flex justify-content-between align-items-center'><strong>{{__("Dosya Boyutu")}}:</strong><span class='badge badge-warning badge-pill'>${data.InstallSize}</span> </li>
                    <li class='list-group-item d-flex justify-content-between align-items-center'><strong>{{__("Boş")}}:</strong><span class='badge badge-primary badge-pill'>${data.Available}</span> </li>
                    `,
                showCancelButton: true,
                showConfirmButton: true,
                confirmButtonText: "{{__('Yenile')}}", cancelButtonText: "{{__('Kapat')}}"
            }).then((result) => {
                if (result.value) {
                    getDiskAlert(title, formData, route);
                }
        });
        },function(response) {
            const error = JSON.parse(response).message;
            Swal.fire("{{ __('Error!') }}", error, "error");
        });
    }
    
</script>