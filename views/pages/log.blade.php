<div class="row">
    <div class="col-md-12 p-2" >
        <div class="card table-card" id="mirrorLogs">
            @include('components.date-switch', [
                'id' => 'mirror_log_dates'
            ])
            <div class="overlay">
                <div class="spinner-border" role="status">
                    <span class="sr-only">{{__('Loading')}}...</span>
                </div>
            </div>
            <div class="card-body">
                <div id="mirrorLogs-table"> </div>
            </div>
        </div>
    </div>
</div>


<script>

    function getLogDates(){
        showSwal('{{__("Yükleniyor...")}}','info');
        request(API("get_log_dates"), new FormData(), function(response) {
            const dates = JSON.parse(response).message;
            initLogDates('mirror_log_dates', dates, () => {
                getLog();
            });
            Swal.close();
        }, function(response){
            const error = JSON.parse(response).message;
            showSwal(error,'error',2000);
        });
    }

    function getLog(){
        showSwal('{{__("Yükleniyor...")}}','info');
        let form = new FormData();
            getCurrentDate && form.append('date', getCurrentDate('mirror_log_dates'));
        request(API('get_log'), form, function (response) {
            $('#mirrorLogs-table').html(response).find('table').DataTable(dataTablePresets('normal'));
            $('#mirrorLogs').find('.overlay').hide();
            Swal.close();
        }, function(response){
            const error = JSON.parse(response).message;
            showSwal(error,'error',2000);
        })
    }

</script>